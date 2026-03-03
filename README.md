# @octobots/dashboard-sdk

Lightweight SDK for building **Octobots dashboard panel apps** inside iframes. Provides a typed postMessage bridge for executing miniapp actions, receiving conversation/customer context, and triggering host UI actions — all without exposing credentials to the iframe.

**3.8 KB** minified · Zero dependencies · TypeScript-first · ESM + CJS

---

## Table of Contents

- [How It Works](#how-it-works)
- [Installation](#installation)
- [Quick Start](#quick-start)
- [API Reference](#api-reference)
  - [createDashboard()](#createdashboardoptions)
  - [Lifecycle](#lifecycle)
  - [Context](#context)
  - [Actions](#actions)
  - [Host Actions](#host-actions)
  - [Events](#events)
- [PostMessage Protocol](#postmessage-protocol)
- [TypeScript Types](#typescript-types)
- [Examples](#examples)
- [Security Model](#security-model)
- [Development](#development)

---

## How It Works

```
┌───────────────────────────────────────────────────────────────┐
│  Octobots Inbox — Conversation Panel                         │
│  ┌─────────────────────────────────────────────────────────┐  │
│  │  YOUR IFRAME APP                                        │  │
│  │  ┌──────────────────────────────────────────────────┐   │  │
│  │  │  const dashboard = createDashboard()             │   │  │
│  │  │  dashboard.init({ onReady: ... })                │   │  │
│  │  │                                                  │   │  │
│  │  │  // Get customer from conversation               │   │  │
│  │  │  const ctx = dashboard.getCustomerContext()       │   │  │
│  │  │                                                  │   │  │
│  │  │  // Execute authenticated action                 │   │  │
│  │  │  const orders = await dashboard.executeAction(   │   │  │
│  │  │    'listOrders', { email: ctx.customer.email }   │   │  │
│  │  │  )                                               │   │  │
│  │  └───────────────┬──────────────────────────────────┘   │  │
│  │                  │ postMessage                           │  │
│  │                  ▼                                       │  │
│  │  HOST BRIDGE — validates, rate-limits, proxies           │  │
│  └──────────────────┬──────────────────────────────────────┘  │
│                     │ GraphQL                                  │
│                     ▼                                          │
│  MINIAPPS BACKEND — injects credentials → calls external API  │
└───────────────────────────────────────────────────────────────┘
```

Your iframe sends **action names + user input**. The Octobots backend:
1. Looks up the stored OAuth/API key credentials for the miniapp
2. Builds the HTTP request (injecting credentials server-side)
3. Executes it against the external API (Shopify, Salla, etc.)
4. Returns the response to your iframe

**Credentials never reach your iframe code.**

---

## Installation

### NPM / Yarn

```bash
npm install @octobots/dashboard-sdk
# or
yarn add @octobots/dashboard-sdk
```

### CDN (Script Tag)

```html
<script src="https://unpkg.com/@octobots/dashboard-sdk/dist/index.cjs"></script>
<script>
  // Available as window.OctobotsDashboard
  const dashboard = OctobotsDashboard.createDashboard();
</script>
```

---

## Quick Start

```typescript
import { createDashboard } from '@octobots/dashboard-sdk';

const dashboard = createDashboard();

dashboard.init({
  onReady: async () => {
    // 1. Get customer context from the conversation
    const ctx = dashboard.getCustomerContext();
    if (!ctx?.customer.primaryEmail) {
      document.body.innerHTML = '<p>No customer email available</p>';
      return;
    }

    // 2. Execute an authenticated miniapp action
    const result = await dashboard.executeAction('listOrders', {
      email: ctx.customer.primaryEmail,
      limit: '10',
    });

    if (result.success) {
      renderOrders(result.data);
    } else {
      renderError(result.error?.message ?? 'Failed to load orders');
    }
  },
});

// 3. React to conversation changes
dashboard.onConversationUpdate(async () => {
  const ctx = dashboard.getCustomerContext();
  if (ctx?.customer.primaryEmail) {
    const result = await dashboard.executeAction('listOrders', {
      email: ctx.customer.primaryEmail,
    });
    if (result.success) renderOrders(result.data);
  }
});
```

---

## API Reference

### `createDashboard(options?)`

Factory function that creates an SDK instance.

```typescript
const dashboard = createDashboard({
  heartbeatInterval: 10000,  // ms (default: 10000)
  actionTimeout: 30000,      // ms (default: 30000)
  debug: false,              // Enable console.debug logs
  onError: (message) => {},  // Global error handler
});
```

**Parameters:**

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `heartbeatInterval` | `number` | `10000` | Heartbeat ping interval in ms |
| `actionTimeout` | `number` | `30000` | Timeout for action/host-action promises |
| `debug` | `boolean` | `false` | Log all postMessage traffic to console |
| `onError` | `(msg: string) => void` | — | Called on fatal errors |

**Returns:** `OctobotsDashboard`

---

### Lifecycle

#### `dashboard.init(options?)`

Initialize the SDK and signal readiness to the host. This sends `app_ready` and triggers data flow.

```typescript
dashboard.init({
  onReady: () => {
    // Safe to call getConversation(), getCustomerContext(), executeAction()
    console.log('SDK ready!');
  },
});
```

The `onReady` callback fires when the host sends conversation data. If capabilities (miniapp actions) are available, they will also be loaded by this point.

#### `dashboard.isConnected` (readonly)

`true` after the host acknowledges the connection.

#### `dashboard.isReady` (readonly)

`true` after conversation data has been received.

#### `dashboard.ready()`

Re-send `app_ready` to the host (e.g., after recovering from an error).

#### `dashboard.error(message)`

Signal an error to the host. Shows a disconnected state indicator.

#### `dashboard.destroy()`

Clean up all event listeners, timers, and pending promises. Call this when your app unmounts.

---

### Context

#### `dashboard.getConversation()`

Returns the current conversation data, or `null` if not yet received.

```typescript
const conv = dashboard.getConversation();
console.log(conv?.conversation._id);
console.log(conv?.messages.length);
```

**Returns:** `ConversationData | null`

#### `dashboard.getCustomerContext()`

Returns the resolved customer context from the current conversation.

```typescript
const ctx = dashboard.getCustomerContext();
console.log(ctx?.customer.primaryEmail);
console.log(ctx?.customer.externalCodes.shopify);
console.log(ctx?.suggestedFormValues);
```

**Returns:** `CustomerContext | null`

```typescript
interface CustomerContext {
  customerId: string;
  customer: {
    firstName?: string;
    lastName?: string;
    primaryEmail?: string;
    primaryPhone?: string;
    externalCodes: Record<string, string>; // e.g. { shopify: "123", salla: "456" }
  };
  suggestedFormValues: Record<string, string>; // Pre-filled values for action forms
}
```

---

### Actions

#### `dashboard.getActions()`

Returns the list of available miniapp actions.

```typescript
const actions = dashboard.getActions();
actions.forEach((a) => {
  console.log(a.name, a.title, a.form);
});
```

**Returns:** `ActionDefinition[]`

#### `dashboard.getCapabilities()`

Returns the full capabilities object including authentication status.

```typescript
const caps = dashboard.getCapabilities();
if (caps?.isAuthenticated) {
  console.log(`Connected to ${caps.miniAppName}`);
}
```

**Returns:** `AppCapabilities | null`

#### `dashboard.executeAction(actionName, formInput?)`

Execute a miniapp action through the authenticated backend proxy.

```typescript
const result = await dashboard.executeAction('listOrders', {
  email: 'ahmad@example.com',
  limit: '10',
});

if (result.success) {
  console.log(result.data); // Response from external API
} else {
  console.error(result.error?.code, result.error?.message);
}
```

**Parameters:**

| Param | Type | Description |
|-------|------|-------------|
| `actionName` | `string` | Action name from `getActions()` |
| `formInput` | `Record<string, unknown>` | User-supplied form values (replaces `{{placeholders}}` in the request) |

**Returns:** `Promise<ActionResult>`

The promise **always resolves** (never rejects). Check `result.success` for the outcome.

```typescript
interface ActionResult {
  requestId: string;
  actionName: string;
  success: boolean;
  data?: unknown;        // Response data (on success)
  error?: {
    code: ActionErrorCode;
    message: string;
    retryable?: boolean;
  };
}
```

**Error Codes:**

| Code | Meaning |
|------|---------|
| `AUTH_EXPIRED` | OAuth token expired — user needs to reconnect |
| `RATE_LIMITED` | Too many requests — slow down |
| `ACTION_NOT_FOUND` | Action name doesn't match any defined action |
| `VALIDATION_ERROR` | Missing required form fields |
| `EXTERNAL_API_ERROR` | The external API returned an error |
| `INTERNAL_ERROR` | Unexpected server error or timeout |
| `NOT_INSTALLED` | MiniApp not installed |
| `NOT_CONFIGURED` | MiniApp installed but credentials missing |

#### `dashboard.getSourceData(sourceKey)`

Fetch dynamic dropdown data (e.g., order statuses, product categories).

```typescript
const statuses = await dashboard.getSourceData('orderStatuses');
// [{ value: "pending", label: "Pending" }, ...]
```

**Returns:** `Promise<SourceItem[]>`

---

### Host Actions

Trigger UI actions in the Octobots host application from your iframe.

#### `dashboard.showToast(message, variant?)`

Show a toast notification. Fire-and-forget (no return value).

```typescript
dashboard.showToast('Order refunded successfully', 'success');
dashboard.showToast('Something went wrong', 'error');
```

| Param | Type | Default |
|-------|------|---------|
| `message` | `string` | — |
| `variant` | `'success' \| 'error' \| 'warning' \| 'info'` | `'info'` |

#### `dashboard.addNote(content)`

Add an internal note to the current conversation.

```typescript
await dashboard.addNote('Refund of 50 SAR issued for order #1234');
```

**Returns:** `Promise<HostActionResult>`

#### `dashboard.addTag(tagName)`

Add a tag to the current conversation.

```typescript
await dashboard.addTag('refund-issued');
```

**Returns:** `Promise<HostActionResult>`

#### `dashboard.openCustomer()`

Navigate to the customer profile in the host UI. Fire-and-forget.

```typescript
dashboard.openCustomer();
```

---

### Events

#### `dashboard.onConversationUpdate(callback)`

Subscribe to conversation changes (new messages, status changes).

```typescript
const unsub = dashboard.onConversationUpdate((data) => {
  console.log('New message count:', data.messages.length);
});

// Later: unsub();
```

**Returns:** `() => void` (unsubscribe function)

#### `dashboard.onCustomerContextUpdate(callback)`

Subscribe to customer context changes.

```typescript
dashboard.onCustomerContextUpdate((ctx) => {
  console.log('Customer:', ctx.customer.primaryEmail);
});
```

#### `dashboard.onCapabilitiesUpdate(callback)`

Subscribe to capabilities/config updates.

```typescript
dashboard.onCapabilitiesUpdate((caps) => {
  console.log('Actions available:', caps.actions.length);
});
```

#### `dashboard.onMessage(callback)`

Subscribe to all raw postMessage envelopes from the host. Useful for custom message types.

```typescript
dashboard.onMessage((envelope) => {
  console.log(envelope.type, envelope.data);
});
```

---

## PostMessage Protocol

The SDK abstracts these messages, but here's the full protocol for reference:

### Host → Iframe

| Type | Payload | When |
|------|---------|------|
| `conversation_init` | `ConversationData` | After connection established |
| `conversation_update` | `ConversationData` | On new messages / status change |
| `conversation_data` | `ConversationData` | Response to `request_conversation_data` |
| `customer_context` | `{ customerContext: CustomerContext }` | After init or context change |
| `customer_context_update` | `{ customerContext: CustomerContext }` | On customer data change |
| `app_capabilities` / `config_result` | `AppCapabilities` | After `app_ready`, when miniapp linked |
| `action_result` | `ActionResult` | Successful action response |
| `action_error` | `ActionResult` (with error) | Failed action response |
| `host_action_result` | `HostActionResult` | Response to host action request |
| `sources_result` | `{ items: SourceItem[] }` | Response to source data request |
| `heartbeat_ack` | `{ timestamp }` | Response to heartbeat |

### Iframe → Host

| Type | Payload | Purpose |
|------|---------|---------|
| `app_ready` | — | Signal iframe is ready for data |
| `app_error` | `{ message }` | Signal error state |
| `heartbeat` | — | Connection keepalive |
| `request_conversation_data` | — | Request current conversation |
| `execute_action` | `{ actionName, formInput }` | Execute a miniapp action |
| `request_sources` | `{ sourceKey }` | Request dropdown data |
| `host_action` | `{ action, params }` | Trigger host UI action |

All messages include a `timestamp` field and optionally a `requestId` for correlation.

---

## TypeScript Types

All types are exported from the package:

```typescript
import type {
  OctobotsDashboard,
  DashboardOptions,
  ConversationData,
  ConversationMessage,
  CustomerContext,
  ActionDefinition,
  ActionResult,
  ActionError,
  ActionErrorCode,
  AppCapabilities,
  FormField,
  FormFieldRules,
  HostActionType,
  HostActionResult,
  ToastVariant,
  SourceItem,
  Unsubscribe,
  PostMessageEnvelope,
} from '@octobots/dashboard-sdk';
```

---

## Examples

### Shopify Orders Panel

A complete example showing how to build an orders panel:

```typescript
import { createDashboard } from '@octobots/dashboard-sdk';

const dashboard = createDashboard({ debug: true });

dashboard.init({
  onReady: async () => {
    const customer = dashboard.getCustomerContext();
    if (!customer?.customer.primaryEmail) {
      showEmptyState('No customer email found');
      return;
    }

    await loadOrders(customer.customer.primaryEmail);
  },
});

// Reload when conversation switches
dashboard.onConversationUpdate(async () => {
  const customer = dashboard.getCustomerContext();
  if (customer?.customer.primaryEmail) {
    await loadOrders(customer.customer.primaryEmail);
  }
});

async function loadOrders(email: string) {
  showLoading();

  const result = await dashboard.executeAction('listOrders', {
    email,
    limit: '10',
  });

  if (result.success) {
    renderOrderTable(result.data as { orders: Order[] });
  } else {
    showError(result.error?.message ?? 'Failed to load orders');
  }
}

async function handleRefund(orderId: string, amount: number) {
  const result = await dashboard.executeAction('createRefund', {
    orderId,
    amount: String(amount),
    reason: 'Customer requested',
  });

  if (result.success) {
    dashboard.showToast('Refund created successfully', 'success');
    await dashboard.addNote(
      `Refund of ${amount} SAR issued for order #${orderId}`
    );
    // Reload orders
    const ctx = dashboard.getCustomerContext();
    if (ctx?.customer.primaryEmail) {
      await loadOrders(ctx.customer.primaryEmail);
    }
  } else {
    dashboard.showToast(result.error?.message ?? 'Refund failed', 'error');
  }
}
```

### Using with React

```tsx
import { useEffect, useRef, useState } from 'react';
import { createDashboard, type CustomerContext, type ActionResult } from '@octobots/dashboard-sdk';

function OrdersPanel() {
  const dashRef = useRef(createDashboard());
  const [customer, setCustomer] = useState<CustomerContext | null>(null);
  const [orders, setOrders] = useState<unknown[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const dash = dashRef.current;

    dash.init({
      onReady: async () => {
        const ctx = dash.getCustomerContext();
        setCustomer(ctx);
        if (ctx?.customer.primaryEmail) {
          setLoading(true);
          const result = await dash.executeAction('listOrders', {
            email: ctx.customer.primaryEmail,
          });
          if (result.success) setOrders((result.data as any)?.orders ?? []);
          setLoading(false);
        }
      },
    });

    dash.onCustomerContextUpdate(setCustomer);

    return () => dash.destroy();
  }, []);

  if (loading) return <div>Loading...</div>;
  if (!customer) return <div>No customer data</div>;

  return (
    <div>
      <h2>Orders for {customer.customer.primaryEmail}</h2>
      <ul>
        {orders.map((order: any) => (
          <li key={order.id}>
            #{order.id} — {order.total} {order.currency}
          </li>
        ))}
      </ul>
    </div>
  );
}
```

### Vanilla JS (Script Tag)

```html
<!DOCTYPE html>
<html>
<head>
  <title>My Dashboard App</title>
  <script src="https://unpkg.com/@octobots/dashboard-sdk/dist/index.cjs"></script>
</head>
<body>
  <div id="app">Loading...</div>
  <script>
    var dashboard = OctobotsDashboard.createDashboard();

    dashboard.init({
      onReady: function () {
        var ctx = dashboard.getCustomerContext();
        document.getElementById('app').textContent =
          'Customer: ' + (ctx?.customer?.primaryEmail || 'Unknown');
      },
    });
  </script>
</body>
</html>
```

---

## Security Model

The SDK is designed with a **zero-trust iframe** model:

1. **Credentials never reach the iframe.** The iframe sends action names + user-supplied form values. The backend injects stored OAuth/API key credentials server-side.

2. **Origin validation.** The host validates `event.origin` on every incoming postMessage against the dashboard app's registered URL.

3. **Action allowlisting.** Admins configure which actions each dashboard app can access. The host enforces this before proxying.

4. **Rate limiting.** The host applies a sliding-window rate limit (30 req/min default) per iframe session.

5. **Server-side validation.** Required form fields are validated on the server before execution.

6. **Sandboxed iframe.** The host renders iframes with `sandbox="allow-scripts allow-same-origin allow-forms"` — no top-navigation, no popups to parent.

---

## Development

```bash
# Install dependencies
npm install

# Build (ESM + CJS + types)
npm run build

# Watch mode
npm run dev

# Typecheck
npm run typecheck
```

### Build Output

```
dist/
├── index.js       # ESM (3.8 KB)
├── index.cjs      # CommonJS (3.8 KB)
├── index.d.ts     # TypeScript declarations
├── index.d.cts    # CTS declarations
└── *.map          # Source maps
```

---

## License

MIT © [KarzounApps](https://github.com/KarzounApps)
