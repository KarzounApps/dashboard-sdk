# Dashboard SDK examples

Run from the `dashboard-sdk` package root:

```bash
npx vite --open /examples/zid-orders-panel/
# or
npx vite --open /examples/shopify-orders-panel/
```

Open with a **trailing slash** (or `/index.html`). Without it Vite returns 404.

Examples import the SDK from `../../src/index.ts` for local development. In production HTML, use:

```js
import { createDashboard } from '@karzoun/dashboard-sdk';
// or ESM CDN after npm publish:
// import { createDashboard } from 'https://unpkg.com/@karzoun/dashboard-sdk/dist/index.js';
```

| Folder | Uses SDK? | Notes |
|--------|-----------|--------|
| `shopify-orders-panel/` | Yes | English LTR sample |
| `zid-orders-panel/` | Yes | Arabic RTL · Zid orders UI via MiniApp actions (`get_list_of_orders`, `get_get_order_by_id`) |
| `none_sdk_dashboard_app/` | No | **Deprecated** — historical UI reference only; use `zid-orders-panel` |

Point a Dashboard App **Iframe URL** at the Vite URL (e.g. `http://localhost:5173/examples/shopify-orders-panel/`) while testing.
