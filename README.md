# @octobots/dashboard-sdk

Lightweight SDK for building **Octobots dashboard panel apps** inside iframes. Provides a typed postMessage bridge for executing miniapp actions, receiving conversation/customer context, and triggering host UI actions — all without exposing credentials to the iframe.

**3.8 KB** minified · Zero dependencies · TypeScript-first · ESM + CJS

---

## Table of Contents

- [Overview](#overview)
- [Prerequisites](#prerequisites)
- [Getting Started](#getting-started)
  - [Step 1: Install a MiniApp](#step-1-install-a-miniapp)
  - [Step 2: Create a Dashboard App](#step-2-create-a-dashboard-app)
  - [Step 3: Build Your Iframe App](#step-3-build-your-iframe-app)
- [How It Works](#how-it-works)
  - [Architecture](#architecture)
  - [How Actions Work](#how-actions-work)
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
  - [Shopify Orders Panel](#shopify-orders-panel)
  - [Zid Orders Panel (Arabic/RTL)](#zid-orders-panel-arabicrtl)
  - [Using with React](#using-with-react)
  - [Vanilla JS (Script Tag)](#vanilla-js-script-tag)
- [Security Model](#security-model)
- [Troubleshooting](#troubleshooting)
- [Development](#development)
- [Changelog](#changelog)

---

## Overview

**Dashboard Apps** are custom panels that appear in the Octobots Inbox conversation sidebar. They let agents interact with external platforms (Shopify, Zid, Salla, custom APIs) without leaving the conversation — viewing orders, issuing refunds, checking shipping, and more.

This SDK is the **client-side library** your iframe app uses to communicate with the Octobots host. It handles:

- **Receiving conversation & customer context** — know who the agent is talking to
- **Executing authenticated actions** — call external APIs via stored credentials (never exposed to your code)
- **Triggering host UI actions** — show toasts, add notes, add tags, navigate to customer profiles
- **Subscribing to updates** — react to conversation switches, new messages, and context changes

---

## Prerequisites

Before you start building a dashboard app, you need:

1. **An Octobots account** with admin access to Settings
2. **A MiniApp installed and configured** with valid credentials (OAuth2 or API keys) — this is the backend that defines the actions your iframe can call
3. **A hosted web page** (your iframe app) — can be any static host: Vercel, Netlify, Cloudflare Pages, your own server, or even `localhost` during development

---

## Getting Started

### Step 1: Install a MiniApp

Go to **Settings → MiniApps** in your Octobots dashboard. Find and install the miniapp for your external platform (e.g., Shopify, Zid, Salla). Follow the setup wizard to connect your store credentials.

Once installed, the miniapp defines **actions** — named API operations like `listOrders`, `getOrderDetails`, `createRefund`, etc. These are what your iframe will call through the SDK.

### Step 2: Create a Dashboard App

Go to **Settings → Dashboard Apps** and create a new app:

| Field | Description |
|-------|-------------|
| **Name** | Display name shown in the inbox sidebar tab |
| **Iframe URL** | Full URL to your hosted iframe app (e.g., `https://my-app.vercel.app`) |
| **Linked MiniApp** | Select the miniapp whose actions this dashboard should use |
| **Allowed Actions** | (Optional) Restrict which actions this dashboard can call. Leave empty to allow all. |

Once created, the dashboard app tab appears in the Inbox conversation panel for all agents.

### Step 3: Build Your Iframe App

Install the SDK, initialize it, and start calling actions. See [Quick Start](#quick-start) below.

> **Tip:** During development, set the iframe URL to `http://localhost:5173` (or whichever port your dev server uses). The host bridge works with any origin.

---

## How It Works

### Architecture

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
3. Executes it against the external API (Shopify, Salla, Zid, etc.)
4. Returns the filtered response to your iframe

**Credentials never reach your iframe code.**

### How Actions Work

Every MiniApp defines **actions** — named API operations with a request template and an optional form schema.

```
Action: "listOrders"
├── Request Template:
│   GET https://api.example.com/orders?email={{email}}&limit={{limit}}
│   Headers:
│     Authorization: Bearer [[access_token]]    ← credential (server-side)
│     X-Api-Key: [[api_key]]                    ← credential (server-side)
│
├── Form Schema:
│   email:  { label: "Email", type: "text", rules: { required: true } }
│   limit:  { label: "Limit", type: "number" }
│
└── Response Mapping:
    orders → orders    (only mapped fields are returned)
```

**Placeholder types:**
- `{{key}}` — **user-supplied values** from your `formInput` parameter
- `[[key]]` — **stored credentials** injected server-side (never visible to your code)

When you call `dashboard.executeAction('listOrders', { email: 'a@b.com', limit: '10' })`:

1. Your iframe sends the action name + form values via postMessage
2. The host bridge validates the action is allowed and form fields are valid
3. The backend replaces `{{email}}` with `a@b.com` and `[[access_token]]` with the stored credential
4. The HTTP request is sent to the external API
5. The response is filtered through the mapping schema
6. Your iframe receives the result

If the OAuth token is expired, the backend **automatically attempts a token refresh** and retries once before returning an `AUTH_EXPIRED` error.

---

## Installation

### NPM / Yarn

```bash
npm install @octobots/dashboard-sdk
# or
yarn add @octobots/dashboard-sdk
```

### CDN (Script Tag)

For non-bundled environments, load the ESM build with a module script:

```html
<script type="module">
  import { createDashboard } from 'https://unpkg.com/@octobots/dashboard-sdk/dist/index.js';

  const dashboard = createDashboard();
  dashboard.init({ onReady: () => console.log('Ready!') });
</script>
```

> **Note:** The SDK ships as ESM + CJS. For a `<script>` without `type="module"`, use a bundler or include an [import map](https://developer.mozilla.org/en-US/docs/Web/HTML/Element/script/type/importmap).

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

Full working examples are in the [`examples/`](./examples) directory:

| Example | Description | Language |
|---------|-------------|----------|
| [Shopify Orders Panel](./examples/shopify-orders-panel/) | English LTR · Order list, refunds, customer context | Vanilla JS (ESM) |
| [Zid Orders Panel](./examples/zid-orders-panel/) | Arabic RTL · Order search, invoice, shipping, products | Vanilla JS (ESM) |

To run an example locally:

```bash
npx vite
# Open: http://localhost:5173/examples/shopify-orders-panel/index.html
# Or:   http://localhost:5173/examples/zid-orders-panel/index.html
```

### Shopify Orders Panel

A complete example showing how to build an orders panel:

```typescript
import { createDashboard } from '@octobots/dashboard-sdk';

const dashboard = createDas