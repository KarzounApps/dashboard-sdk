# Dashboard SDK examples

Run from the `dashboard-sdk` package root:

```bash
npx vite --open /examples/zid-orders-panel/
# or
npx vite --open /examples/salla-orders-panel/
# or
npx vite --open /examples/shopify-orders-panel/
```

Open with a **trailing slash** (or `/index.html`). Without it Vite returns 404.

Examples import the SDK from `../../src/index.ts` for local development. In production HTML, use:

```js
import { createDashboard } from '@karzounchat/dashboard-sdk';
// or ESM CDN after npm publish:
// import { createDashboard } from 'https://unpkg.com/@karzounchat/dashboard-sdk/dist/index.js';
```

| Folder | Uses SDK? | Notes |
|--------|-----------|--------|
| `shopify-orders-panel/` | Yes | English LTR sample |
| `zid-orders-panel/` | Yes | Arabic RTL · Zid orders via MiniApp actions |
| `salla-orders-panel/` | Yes | Arabic RTL · Salla orders via MiniApp actions |
| `none_sdk_dashboard_app/` | No | **Deprecated** — historical UI reference only |

Point a Dashboard App **Iframe URL** at the Vite URL (e.g. `http://localhost:5173/examples/shopify-orders-panel/`) while testing.
