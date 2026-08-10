<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>طلبات سلة — كرزون</title>
  <meta name="theme-color" content="#1f93ff">
  <meta name="description" content="لوحة طلبات سلة — تطبيق خارجي بدون SDK">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --primary: #1f93ff;
      --primary-dark: #1a7de8;
      --surface: #f8fafc;
      --success: #10b981;
      --warning: #f59e0b;
      --error: #ef4444;
      --text: #1f2937;
      --text-muted: #6b7280;
      --border: #e5e7eb;
      --radius: 8px;
      --radius-lg: 12px;
      --shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
      --transition: 0.2s ease;
    }

    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: 'Cairo', system-ui, sans-serif;
      background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
      color: var(--text);
      line-height: 1.6;
      min-height: 100vh;
    }

    .container { max-width: 1200px; margin: 0 auto; padding: 16px 20px 32px; }

    .panel {
      background: #fff;
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      box-shadow: var(--shadow);
      padding: 16px;
      margin-bottom: 14px;
    }

    .panel-title {
      font-size: 16px;
      font-weight: 700;
      margin-bottom: 14px;
      color: var(--text);
    }

    .search-form {
      display: grid;
      grid-template-columns: 1fr auto auto;
      gap: 12px;
      align-items: end;
    }

    .search-actions {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
    }

    .form-input, .form-select {
      width: 100%;
      padding: 11px 14px;
      border: 2px solid var(--border);
      border-radius: var(--radius);
      font: inherit;
      font-size: 14px;
      background: #fff;
      transition: border-color var(--transition), box-shadow var(--transition);
    }

    .form-input:focus, .form-select:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgb(31 147 255 / 0.12);
    }

    .btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      padding: 11px 20px;
      border: none;
      border-radius: var(--radius);
      font: inherit;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      transition: transform var(--transition), box-shadow var(--transition), background var(--transition);
      white-space: nowrap;
    }

    .btn:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }

    .btn-primary {
      background: linear-gradient(135deg, var(--primary), var(--primary-dark));
      color: #fff;
    }

    .btn-primary:hover:not(:disabled) { transform: translateY(-1px); }

    .btn-secondary {
      background: #fff;
      color: var(--text);
      border: 2px solid var(--border);
    }

    .btn-secondary:hover:not(:disabled) {
      border-color: var(--primary);
      background: var(--surface);
    }

    .banner {
      display: none;
      align-items: center;
      gap: 10px;
      padding: 12px 16px;
      border-radius: var(--radius);
      margin-bottom: 14px;
      font-weight: 600;
      font-size: 14px;
    }

    .banner.is-visible { display: flex; }
    .banner--loading { background: #fef3c7; color: #92400e; border: 1px solid #fcd34d; }
    .banner--success { background: #d1fae5; color: #065f46; border: 1px solid #34d399; }
    .banner--error { background: #fee2e2; color: #991b1b; border: 1px solid #f87171; }
    .banner--info { background: #e0f2fe; color: #0369a1; border: 1px solid #7dd3fc; }

    .orders { display: flex; flex-direction: column; gap: 10px; }

    .order-card {
      background: #fff;
      border: 1px solid var(--border);
      border-radius: var(--radius);
      box-shadow: var(--shadow-sm, 0 1px 2px rgb(0 0 0 / 0.05));
      overflow: hidden;
    }

    .order-card details[open] {
      box-shadow: var(--shadow);
      border-color: rgb(31 147 255 / 0.35);
    }

    .order-details-wrap > summary {
      list-style: none;
      cursor: pointer;
      user-select: none;
    }

    .order-details-wrap > summary::-webkit-details-marker { display: none; }

    .order-summary {
      display: grid;
      grid-template-columns: 1fr auto auto;
      gap: 8px 12px;
      align-items: center;
      padding: 12px 14px;
      background: #fff;
      transition: background var(--transition);
    }

    .order-summary:hover { background: var(--surface); }

    .order-summary-top {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      gap: 8px;
      grid-column: 1 / -1;
    }

    .order-number {
      font-size: 15px;
      font-weight: 700;
      color: var(--primary);
    }

    .copy-chip {
      border: none;
      background: transparent;
      font: inherit;
      color: inherit;
      padding: 0;
      margin: 0;
      cursor: pointer;
      text-decoration: underline dotted;
      text-underline-offset: 3px;
      border-radius: 2px;
    }

    .copy-chip:hover { color: var(--primary); }

    .copy-chip.order-number {
      font-size: 15px;
      font-weight: 700;
      color: var(--primary);
    }

    .copy-chip.order-phone { font-size: 12px; }

    .order-code {
      font-size: 12px;
      color: var(--text-muted);
      font-family: monospace;
    }

    .copy-chip.order-code {
      font-family: monospace;
      font-size: 12px;
      color: var(--text-muted);
    }

    .order-summary-customer {
      font-size: 14px;
      font-weight: 600;
      color: var(--text);
    }

    .order-summary-bottom {
      grid-column: 1 / 2;
      display: flex;
      flex-wrap: wrap;
      gap: 6px 12px;
      font-size: 12px;
      color: var(--text-muted);
    }

    .order-summary-total {
      font-size: 15px;
      font-weight: 700;
      color: var(--text);
      white-space: nowrap;
    }

    .order-status {
      background: var(--surface);
      color: var(--primary);
      border: 1px solid rgb(31 147 255 / 0.25);
      padding: 4px 10px;
      border-radius: 999px;
      font-size: 12px;
      font-weight: 600;
      white-space: nowrap;
    }

    .order-chevron {
      width: 28px;
      height: 28px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 6px;
      background: var(--surface);
      color: var(--text-muted);
      transition: transform var(--transition), background var(--transition);
      flex-shrink: 0;
    }

    .order-details-wrap[open] .order-chevron {
      transform: rotate(180deg);
      background: rgb(31 147 255 / 0.1);
      color: var(--primary);
    }

    .order-body {
      padding: 0 14px 14px;
      border-top: 1px solid var(--border);
    }

    .order-body-inner { padding-top: 12px; }

    /* ── Support-first layout ── */
    .order-workspace { display: flex; flex-direction: column; gap: 12px; }

    .alerts-strip {
      display: flex;
      flex-direction: column;
      gap: 8px;
    }

    .alert-banner {
      padding: 10px 12px;
      border-radius: var(--radius);
      font-size: 13px;
      font-weight: 600;
      border: 1px solid;
    }

    .alert-banner--note {
      background: #fffbeb;
      border-color: #fcd34d;
      color: #92400e;
    }

    .alert-banner--gift {
      background: #fdf2f8;
      border-color: #f9a8d4;
      color: #9d174d;
    }

    .quick-bar {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      gap: 10px 14px;
      padding: 10px 12px;
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius);
    }

    .quick-bar-identity {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      gap: 8px 12px;
      flex: 1;
      min-width: 200px;
    }

    .quick-bar-name {
      font-size: 15px;
      font-weight: 700;
      color: var(--text);
    }

    .quick-bar-contact {
      display: flex;
      flex-wrap: wrap;
      gap: 6px 10px;
      font-size: 12px;
      color: var(--text-muted);
    }

    .quick-bar-actions {
      display: flex;
      flex-wrap: wrap;
      gap: 6px;
    }

    .btn-sm {
      padding: 6px 12px;
      font-size: 12px;
      font-weight: 600;
    }

    .section-block {
      background: #fff;
      border: 1px solid var(--border);
      border-radius: var(--radius);
      overflow: hidden;
    }

    .section-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
      padding: 10px 12px;
      background: var(--surface);
      border-bottom: 1px solid var(--border);
      font-size: 13px;
      font-weight: 700;
      color: var(--text);
    }

    .section-head-meta {
      font-size: 12px;
      font-weight: 600;
      color: var(--text-muted);
    }

    .product-table-wrap {
      overflow-x: auto;
      -webkit-overflow-scrolling: touch;
    }

    .products-table-rich {
      width: 100%;
      min-width: 720px;
      border-collapse: collapse;
      font-size: 12px;
    }

    .products-table-rich th,
    .products-table-rich td {
      padding: 10px 8px;
      border-bottom: 1px solid var(--border);
      vertical-align: top;
      text-align: right;
    }

    .products-table-rich th {
      background: #fff;
      font-weight: 700;
      font-size: 11px;
      color: var(--text-muted);
      white-space: nowrap;
    }

    .products-table-rich tbody tr:last-child td { border-bottom: none; }

    .product-cell-rich { display: flex; gap: 10px; min-width: 200px; }

    .product-thumb-rich {
      width: 56px;
      height: 56px;
      border-radius: 6px;
      object-fit: cover;
      border: 1px solid var(--border);
      flex-shrink: 0;
      background: var(--surface);
    }

    .product-name-rich {
      font-size: 13px;
      font-weight: 700;
      line-height: 1.35;
      margin-bottom: 3px;
    }

    .product-parent-rich {
      font-size: 11px;
      color: var(--text-muted);
      margin-bottom: 3px;
    }

    .product-meta-rich {
      font-size: 11px;
      color: var(--text-muted);
      font-family: monospace;
      line-height: 1.45;
    }

    .product-desc-rich {
      font-size: 11px;
      color: var(--text-muted);
      margin-top: 5px;
      line-height: 1.4;
    }

    .product-badges {
      display: flex;
      flex-wrap: wrap;
      gap: 4px;
      margin-top: 5px;
    }

    .product-badge {
      display: inline-block;
      padding: 1px 6px;
      border-radius: 999px;
      font-size: 10px;
      font-weight: 600;
      border: 1px solid var(--border);
      background: #fff;
      color: var(--text-muted);
    }

    .product-badge--discount {
      border-color: #86efac;
      color: #166534;
      background: #f0fdf4;
    }

    .product-row-detail td {
      padding: 0 8px 10px;
      background: var(--surface);
      border-bottom: 1px solid var(--border);
    }

    .product-detail-line {
      font-size: 11px;
      color: var(--text-muted);
      padding: 6px 10px;
      border-radius: 6px;
      background: #fff;
      border: 1px solid var(--border);
    }

    .product-detail-line strong { color: var(--text); }

    .product-total-foot {
      padding: 10px 12px;
      font-size: 12px;
      font-weight: 700;
      text-align: left;
      background: var(--surface);
      border-top: 1px solid var(--border);
    }

    .quick-bar-badge {
      display: inline-block;
      padding: 2px 8px;
      border-radius: 999px;
      font-size: 10px;
      font-weight: 600;
      background: #fff;
      border: 1px solid var(--border);
      color: var(--text-muted);
    }

    .alert-banner--warn {
      background: #fef2f2;
      border-color: #fca5a5;
      color: #991b1b;
    }

    .ops-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 10px;
    }

    .ops-card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 10px 12px;
    }

    .ops-card--wide { grid-column: 1 / -1; }

    .ops-card h4 {
      font-size: 12px;
      font-weight: 700;
      color: var(--primary);
      margin-bottom: 8px;
      text-transform: none;
    }

    .ops-card .info-row {
      padding: 4px 0;
      font-size: 12px;
    }

    .tracking-highlight {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 8px;
      padding: 8px 10px;
      margin-bottom: 8px;
      background: #fff;
      border: 1px dashed rgb(31 147 255 / 0.45);
      border-radius: var(--radius);
    }

    .tracking-highlight-label {
      font-size: 11px;
      color: var(--text-muted);
      font-weight: 600;
    }

    .tracking-highlight-value {
      font-family: monospace;
      font-size: 13px;
      font-weight: 700;
      color: var(--text);
    }

    .inline-tags {
      display: flex;
      flex-wrap: wrap;
      gap: 4px;
      margin-top: 6px;
    }

    .inline-tag {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      padding: 2px 8px;
      background: #fff;
      border: 1px solid var(--border);
      border-radius: 999px;
      font-size: 11px;
      color: var(--text);
    }

    .inline-tag strong { font-weight: 700; }

    .invoice-compact .info-row { padding: 3px 0; font-size: 11px; }
    .invoice-compact .invoice-total { font-size: 12px !important; }

    .action-row { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px; }

    .meta-fold {
      border: 1px solid var(--border);
      border-radius: var(--radius);
      background: #fff;
      overflow: hidden;
    }

    .meta-fold > summary {
      list-style: none;
      cursor: pointer;
      user-select: none;
      padding: 10px 12px;
      font-size: 12px;
      font-weight: 700;
      color: var(--text-muted);
      background: var(--surface);
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 8px;
    }

    .meta-fold > summary::-webkit-details-marker { display: none; }

    .meta-fold[open] > summary {
      border-bottom: 1px solid var(--border);
      color: var(--primary);
    }

    .meta-fold-body { padding: 10px 12px; }

    .meta-fold-body--nested {
      padding: 8px;
      display: flex;
      flex-direction: column;
      gap: 6px;
    }

    .meta-subfold {
      border: 1px solid var(--border);
      border-radius: 6px;
      overflow: hidden;
      background: #fff;
    }

    .meta-subfold > summary {
      list-style: none;
      cursor: pointer;
      user-select: none;
      padding: 8px 10px;
      font-size: 11px;
      font-weight: 700;
      color: var(--text);
      background: var(--surface);
    }

    .meta-subfold > summary::-webkit-details-marker { display: none; }

    .meta-subfold[open] > summary {
      color: var(--primary);
      border-bottom: 1px solid var(--border);
    }

    .meta-subfold-body { padding: 8px 10px; }
    .meta-subfold-body .info-row { font-size: 12px; padding: 4px 0; }

    .meta-fold-body .info-row { font-size: 12px; padding: 5px 0; }

    .history-timeline {
      list-style: none;
      margin: 0;
      padding: 0;
      display: flex;
      flex-direction: column;
      gap: 0;
    }

    .history-timeline-item {
      display: grid;
      grid-template-columns: 72px 1fr;
      gap: 8px 10px;
      padding: 8px 0;
      border-bottom: 1px solid var(--border);
      font-size: 12px;
    }

    .history-timeline-item:last-child { border-bottom: none; }

    .history-timeline-time {
      font-size: 11px;
      color: var(--text-muted);
      line-height: 1.4;
    }

    .history-timeline-status {
      font-weight: 700;
      color: var(--text);
      margin-bottom: 2px;
    }

    .history-timeline-detail {
      color: var(--text-muted);
      font-size: 11px;
    }

    .address-block {
      font-size: 13px;
      font-weight: 600;
      line-height: 1.5;
      margin-bottom: 6px;
    }

    .address-sub {
      font-size: 12px;
      color: var(--text-muted);
      font-weight: 500;
    }

    .empty-state {
      text-align: center;
      padding: 48px 20px;
      color: var(--text-muted);
    }

    .empty-state svg { width: 64px; height: 64px; opacity: 0.45; margin-bottom: 12px; }

    .spinner {
      width: 18px;
      height: 18px;
      border: 2px solid rgb(0 0 0 / 0.08);
      border-top-color: currentColor;
      border-radius: 50%;
      animation: spin 0.8s linear infinite;
      flex-shrink: 0;
    }

    @keyframes spin { to { transform: rotate(360deg); } }

    @media (max-width: 720px) {
      .search-form { grid-template-columns: 1fr; }
      .search-actions { width: 100%; }
      .search-actions .btn { flex: 1; }
      .ops-grid { grid-template-columns: 1fr; }
      .products-table-rich { min-width: 640px; }
      .order-summary {
        grid-template-columns: 1fr auto;
      }
      .order-summary-total { grid-column: 1; }
      .order-chevron { grid-row: 1 / 3; grid-column: 2; }
    }

    .info-row {
      display: flex;
      justify-content: space-between;
      gap: 12px;
      padding: 7px 0;
      border-bottom: 1px solid var(--border);
      font-size: 14px;
    }

    .info-row:last-child { border-bottom: none; }
    .info-label { color: var(--text-muted); }
    .info-value { font-weight: 600; text-align: left; }

    .invoice-total {
      border-top: 2px solid var(--primary) !important;
      padding-top: 8px !important;
      margin-top: 4px;
      font-weight: 700;
    }
  </style>
</head>
<body>
  <div class="container">
    <section class="panel" aria-label="البحث عن الطلبات">
      <h2 class="panel-title">البحث عن الطلبات</h2>
      <form id="searchForm" class="search-form">
        <input id="searchValue" class="form-input" type="text" placeholder="رقم الطلب، الجوال، أو أي مصطلح بحث" autocomplete="off">
        <div class="search-actions">
          <button id="searchBtn" type="submit" class="btn btn-primary" disabled>بحث</button>
          <button id="latestBtn" type="button" class="btn btn-secondary">آخر الطلبات في المتجر</button>
        </div>
      </form>
    </section>
    <div id="banner" class="banner" role="status" aria-live="polite"></div>
    <div id="ordersRoot" class="orders">
      <div class="empty-state">
        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg>
        <h3>لا توجد طلبات للعرض</h3>
        <p>ابحث برقم الطلب أو الجوال، أو اعرض آخر الطلبات في المتجر</p>
      </div>
    </div>
  </div>
  <script>

(function () {
  'use strict';

  var SALLA_API = 'https://api.salla.dev/admin/v2';
  var PAGE_SIZE = 15;
  var ACCESS_TOKEN = @json($data['access_token'] ?? '');

  var HOST_MESSAGE_TYPES = {
    CONVERSATION_INIT: 'conversation_init',
    CONVERSATION_UPDATE: 'conversation_update',
  };

  var state = {
    lastAutoSearchTerm: null,
    searchGeneration: 0,
    isSearching: false,
  };

  var dom = {};

  function init() {
    cacheDom();
    bindEvents();
    window.addEventListener('message', onHostMessage);
    notifyHostReady();
    renderEmptyState();
    updateSearchControls();
  }

  function cacheDom() {
    dom.searchForm = document.getElementById('searchForm');
    dom.searchValue = document.getElementById('searchValue');
    dom.searchBtn = document.getElementById('searchBtn');
    dom.latestBtn = document.getElementById('latestBtn');
    dom.banner = document.getElementById('banner');
    dom.ordersRoot = document.getElementById('ordersRoot');
  }

  function bindEvents() {
    dom.searchForm.addEventListener('submit', onSearchSubmit);
    dom.latestBtn.addEventListener('click', onLatestOrdersClick);
    dom.searchValue.addEventListener('input', updateSearchControls);
    dom.ordersRoot.addEventListener('click', onOrdersClick);
  }

  function updateSearchControls() {
    var hasTerm = !!normalizeSearchTerm(dom.searchValue.value);
    dom.searchBtn.disabled = state.isSearching || !hasTerm;
    dom.latestBtn.disabled = state.isSearching;
  }

  function notifyHostReady() {
    postToHost({ type: 'app_ready', timestamp: Date.now() });
  }

  function postToHost(payload) {
    if (window.parent && window.parent !== window) {
      window.parent.postMessage(payload, '*');
    }
  }

  function onHostMessage(event) {
    var msg = event.data;
    if (typeof msg === 'string') {
      try { msg = JSON.parse(msg); } catch (_) { return; }
    }
    if (!msg || typeof msg !== 'object') return;

    if (msg.type === HOST_MESSAGE_TYPES.CONVERSATION_INIT || msg.type === HOST_MESSAGE_TYPES.CONVERSATION_UPDATE) {
      handleConversationPayload(msg.data);
      return;
    }

    // Legacy Karzoun / miniapp payload
    if (msg.data && msg.data.contact && msg.data.contact.phone_number) {
      var legacyPhone = normalizeSearchTerm(msg.data.contact.phone_number);
      if (!legacyPhone || legacyPhone === state.lastAutoSearchTerm) return;
      state.lastAutoSearchTerm = legacyPhone;
      dom.searchValue.value = legacyPhone;
      runSearch({ searchTerm: legacyPhone, auto: true });
    }
  }

  function handleConversationPayload(data) {
    var conv = data && data.conversation;
    if (!conv) return;
    var customer = conv.customer || {};
    var phone = normalizeSearchTerm(customer.primaryPhone || '');
    if (!phone || phone === state.lastAutoSearchTerm) return;
    state.lastAutoSearchTerm = phone;
    dom.searchValue.value = phone;
    runSearch({ searchTerm: phone, auto: true });
  }

  function onSearchSubmit(event) {
    event.preventDefault();
    var term = normalizeSearchTerm(dom.searchValue.value);
    if (!term) {
      showBanner('يرجى إدخال رقم الطلب أو الجوال', 'error');
      updateSearchControls();
      return;
    }
    dom.searchValue.value = term;
    runSearch({ searchTerm: term, auto: false });
  }

  function onLatestOrdersClick() {
    dom.searchValue.value = '';
    state.lastAutoSearchTerm = null;
    updateSearchControls();
    runSearch({ mode: 'latest' });
  }

  function setSearching(active) {
    state.isSearching = active;
    updateSearchControls();
  }

  async function runSearch(opts) {
    if (!ACCESS_TOKEN) {
      showBanner('رمز مصادقة Salla غير متوفر.', 'error');
      return;
    }
    if (state.isSearching) return;

    var isLatest = opts.mode === 'latest';
    var searchTerm = isLatest ? '' : normalizeSearchTerm(opts.searchTerm !== undefined ? opts.searchTerm : dom.searchValue.value);
    if (!isLatest && !searchTerm) return;

    var generation = ++state.searchGeneration;
    setSearching(true);
    showBanner(isLatest ? 'جاري جلب آخر الطلبات...' : (opts.auto ? 'جاري البحث التلقائي...' : 'جاري البحث...'), 'loading');

    try {
      var orders = await fetchOrders(searchTerm);
      if (generation !== state.searchGeneration) return;
      if (!orders.length) {
        renderEmptyState();
        showBanner('لم يتم العثور على طلبات', 'error');
        return;
      }
      renderOrders(orders);
      showBanner('تم العثور على ' + orders.length + ' طلب', 'success');
    } catch (err) {
      if (generation !== state.searchGeneration) return;
      console.error('[salla-orders]', err);
      renderEmptyState();
      showBanner(err.message || 'حدث خطأ أثناء البحث', 'error');
    } finally {
      if (generation === state.searchGeneration) setSearching(false);
    }
  }

  function buildListQuery(searchTerm) {
    var q = 'sort_by=created_at-desc';
    if (!searchTerm) return q;
    if (/^\d{9,15}$/.test(searchTerm)) {
      return q + '&keyword=' + encodeURIComponent('+' + searchTerm);
    }
    return q + '&reference_id=' + encodeURIComponent(searchTerm);
  }

  async function sallaFetch(path) {
    var res = await fetch(SALLA_API + path, {
      headers: { Authorization: 'Bearer ' + ACCESS_TOKEN },
    });
    if (!res.ok) {
      var detail = '';
      try {
        var body = await res.json();
        detail = body.error && body.error.message || body.message || '';
      } catch (_) {}
      throw new Error('Salla API ' + res.status + (detail ? ': ' + detail : ''));
    }
    return res.json();
  }

  async function fetchOrders(searchTerm) {
    var data = await sallaFetch('/orders?' + buildListQuery(searchTerm));
    var list = (data.data || []).slice(0, PAGE_SIZE);
    if (!list.length) return [];
    var bundles = await Promise.all(list.map(function (o) { return fetchOrderBundle(o.id); }));
    return bundles.filter(Boolean);
  }

  async function fetchOrderBundle(orderId) {
    try {
      var results = await Promise.all([
        sallaFetch('/orders/' + orderId),
        sallaFetch('/orders/' + orderId + '/histories').catch(function () { return { data: [] }; }),
      ]);
      var order = results[0].data;
      if (!order) return null;
      order._histories = results[1].data || [];
      return order;
    } catch (err) {
      console.warn('[salla-orders] failed order', orderId, err);
      return null;
    }
  }

  var bannerTimer = null;
  function showBanner(message, kind) {
    dom.banner.className = 'banner is-visible banner--' + kind;
    dom.banner.innerHTML = (kind === 'loading' ? '<span class="spinner"></span>' : '') + '<span>' + esc(message) + '</span>';
    clearTimeout(bannerTimer);
    if (kind === 'success') bannerTimer = setTimeout(hideBanner, 5000);
  }
  function hideBanner() { dom.banner.classList.remove('is-visible'); }

  function renderEmptyState() {
    dom.ordersRoot.innerHTML = '<div class="empty-state"><svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg><h3>لا توجد طلبات للعرض</h3><p>ابحث برقم الطلب أو الجوال، أو اعرض آخر الطلبات في المتجر</p></div>';
  }

  function renderOrders(orders) {
    dom.ordersRoot.innerHTML = '';
    orders.forEach(function (order) {
      dom.ordersRoot.appendChild(buildOrderCard(order, orders.length === 1));
    });
  }

  function buildOrderCard(order, openByDefault) {
    var card = document.createElement('article');
    card.className = 'order-card';
    var customer = order.customer || {};
    var items = order.items || [];
    var amounts = order.amounts || {};
    var status = order.status || {};
    var urls = order.urls || {};
    var features = order.features || {};
    var paid = !order.is_pending_payment;
    var phone = customerPhone(customer);
    var refId = String(order.reference_id || order.id);
    var firstItem = items[0];
    var summaryProduct = firstItem ? truncate(firstItem.name || '', 36) : '';
    var totalStr = money(amounts.total);

    card.innerHTML = '<details class="order-details-wrap"' + (openByDefault ? ' open' : '') + '>' +
      '<summary class="order-summary">' +
        '<div class="order-summary-top">' +
          copyChip(refId, '#' + refId, 'order-number') +
          '<span class="order-summary-customer">' + esc(customer.full_name || customer.first_name || '—') + '</span>' +
          '<span class="order-status">' + esc(status.name || '—') + '</span>' +
        '</div>' +
        '<div class="order-summary-bottom">' +
          (summaryProduct ? '<span>' + esc(summaryProduct) + '</span>' : '') +
          (items.length > 1 ? '<span>+' + (items.length - 1) + ' أخرى</span>' : '') +
          (phone ? copyChip(phone, '☎ ' + phone, 'order-phone') : '') +
          '<span style="color:' + (paid ? 'var(--success)' : 'var(--warning)') + '">' + esc(paid ? (order.payment_method || 'مدفوع') : 'بانتظار الدفع') + '</span>' +
          '<span>' + esc(formatSallaDate(order.date)) + '</span>' +
          (features.digitalable ? '<span>رقمي</span>' : '') +
        '</div>' +
        '<span class="order-summary-total">' + esc(totalStr) + '</span>' +
        '<span class="order-chevron" aria-hidden="true">▼</span>' +
      '</summary>' +
      '<div class="order-body"><div class="order-body-inner"><div class="order-workspace">' +
        buildAlertsStrip(order) +
        buildQuickBar(customer, order, urls) +
        buildProductsSection(items, order) +
        buildOpsSection(order) +
        buildMetaFolds(order, customer, order._histories || []) +
      '</div></div></div></details>';
    return card;
  }

  function buildAlertsStrip(order) {
    var alerts = [];
    if (order.features && order.features.has_suspicious_alert) {
      alerts.push('<div class="alert-banner alert-banner--warn"><strong>تنبيه:</strong> تنبيه مشبوه على الطلب</div>');
    }
    if (order.is_pending_payment) {
      alerts.push('<div class="alert-banner alert-banner--note"><strong>بانتظار الدفع</strong>' + (order.pending_payment_ends_at ? ' · متبقي ' + order.pending_payment_ends_at + ' ث' : '') + '</div>');
    }
    if (order.notes) alerts.push('<div class="alert-banner alert-banner--note"><strong>ملاحظة:</strong> ' + esc(order.notes) + '</div>');
    if (order.address_incomplete) alerts.push('<div class="alert-banner alert-banner--warn"><strong>العنوان غير مكتمل</strong></div>');
    return alerts.length ? '<div class="alerts-strip">' + alerts.join('') + '</div>' : '';
  }

  function buildQuickBar(customer, order, urls) {
    var phone = customerPhone(customer);
    var email = customer.email || '';
    var customerId = customer.id ? String(customer.id) : '';
    var actions = '';
    if (urls.digital_content) actions += '<button type="button" class="btn btn-primary btn-sm" data-action="open" data-value="' + escAttr(urls.digital_content) + '">المحتوى الرقمي</button>';
    if (urls.admin) actions += '<button type="button" class="btn btn-secondary btn-sm" data-action="open" data-value="' + escAttr(urls.admin) + '">لوحة سلة</button>';
    if (urls.customer) actions += '<button type="button" class="btn btn-secondary btn-sm" data-action="open" data-value="' + escAttr(urls.customer) + '">صفحة العميل</button>';
    if (customer.urls && customer.urls.admin) actions += '<button type="button" class="btn btn-secondary btn-sm" data-action="open" data-value="' + escAttr(customer.urls.admin) + '">ملف العميل</button>';
    if (urls.rating) actions += '<button type="button" class="btn btn-secondary btn-sm" data-action="copy" data-value="' + escAttr(urls.rating) + '" data-copy-label="رابط التقييم">التقييم</button>';

    var shipping = order.shipping;
    var tracking = shipping && shipping.shipment && shipping.shipment.id;
    if (tracking) actions += '<button type="button" class="btn btn-secondary btn-sm" data-action="copy" data-value="' + escAttr(String(tracking)) + '" data-copy-label="رقم التتبع">نسخ التتبع</button>';
    if (shipping && shipping.shipment && shipping.shipment.tracking_link) {
      actions += '<button type="button" class="btn btn-secondary btn-sm" data-action="open" data-value="' + escAttr(shipping.shipment.tracking_link) + '">تتبع</button>';
    }

    return '<div class="quick-bar"><div class="quick-bar-identity">' +
      '<span class="quick-bar-name">' + esc(customer.full_name || '—') + '</span>' +
      (customerId ? '<span class="quick-bar-badge">عميل ' + copyChip(customerId, '#' + customerId, 'order-phone') + '</span>' : '') +
      (customer.gender ? '<span class="quick-bar-badge">' + esc(customer.gender) + '</span>' : '') +
      '<div class="quick-bar-contact">' +
        (phone ? copyChip(phone, '☎ ' + phone, 'order-phone') : '') +
        (email ? copyChip(email, '✉ ' + email, 'order-phone') : '') +
      '</div></div><div class="quick-bar-actions">' + actions + '</div></div>';
  }

  function buildProductsSection(items, order) {
    if (!items.length) return '';
    var rows = items.map(buildProductTableRows).join('');
    var foot = money((order.amounts && order.amounts.sub_total) || null);
    return '<section class="section-block"><div class="section-head"><span>المنتجات</span><span class="section-head-meta">' + items.length + ' صنف · ' + esc(foot) + '</span></div>' +
      '<div class="product-table-wrap"><table class="products-table-rich"><thead><tr>' +
      '<th>المنتج</th><th>الكمية</th><th>الأصلي</th><th>بدون ضريبة</th><th>الخصم</th><th>الضريبة</th><th>الإجمالي</th>' +
      '</tr></thead><tbody>' + rows + '</tbody></table></div></section>';
  }

  function buildProductTableRows(item) {
    var am = item.amounts || {};
    var thumb = item.product_thumbnail || (item.product && item.product.thumbnail) || '';
    var img = thumb ? '<img class="product-thumb-rich" src="' + escAttr(thumb) + '" alt="">' : '<div class="product-thumb-rich" style="display:flex;align-items:center;justify-content:center;color:var(--text-muted)">📦</div>';
    var optionsText = formatItemOptions(item.options);
    var badges = '';
    if (item.product_type) badges += '<span class="product-badge">' + esc(item.product_type) + '</span>';
    if (am.total_discount && am.total_discount.amount > 0) badges += '<span class="product-badge product-badge--discount">خصم</span>';
    var detailParts = [];
    if (optionsText) detailParts.push('<strong>الخيارات:</strong> ' + esc(optionsText));
    if (item.notes) detailParts.push('<strong>ملاحظة:</strong> ' + esc(item.notes));
    if (item.id) detailParts.push('<strong>معرّف السطر:</strong> ' + copyChip(String(item.id), String(item.id), 'order-phone'));
    if (item.product && item.product.url) detailParts.push('<strong>رابط المنتج:</strong> ' + copyChip(item.product.url, 'فتح', 'order-phone'));
    var detailRow = detailParts.length ? '<tr class="product-row-detail"><td colspan="7"><div class="product-detail-line">' + detailParts.join(' · ') + '</div></td></tr>' : '';
    var taxStr = am.tax ? money(am.tax.amount) + (am.tax.percent ? ' (' + am.tax.percent + '%)' : '') : '—';
    return '<tr><td><div class="product-cell-rich">' + img + '<div><div class="product-name-rich">' + esc(item.name) + '</div>' +
      '<div class="product-meta-rich">SKU: ' + esc(item.sku || '—') + (item.weight_label && item.weight_label !== '-' ? ' · ' + esc(item.weight_label) : '') + '</div>' +
      (badges ? '<div class="product-badges">' + badges + '</div>' : '') + '</div></div></td>' +
      '<td style="text-align:center;font-weight:700;color:var(--primary)">' + esc(String(item.quantity)) + '</td>' +
      '<td style="text-align:center;white-space:nowrap">' + esc(money(am.original_price)) + '</td>' +
      '<td style="text-align:center;white-space:nowrap">' + esc(money(am.price_without_tax)) + '</td>' +
      '<td style="text-align:center;white-space:nowrap">' + esc(money(am.total_discount)) + '</td>' +
      '<td style="text-align:center;white-space:nowrap">' + esc(taxStr) + '</td>' +
      '<td style="text-align:center;font-weight:700;white-space:nowrap">' + esc(money(am.total)) + '</td></tr>' + detailRow;
  }

  function buildOpsSection(order) {
    return '<div class="ops-grid">' + buildShippingOps(order) + buildPaymentOps(order) + buildCustomerLocationOps(order) + '</div>';
  }

  function buildShippingOps(order) {
    var shipping = order.shipping;
    var features = order.features || {};
    var urls = order.urls || {};
    var tracking = shipping && shipping.shipment && shipping.shipment.id;
    var trackingBlock = tracking ? '<div class="tracking-highlight"><span class="tracking-highlight-label">رقم التتبع</span><span class="tracking-highlight-value">' + copyChip(String(tracking), String(tracking), 'order-phone') + '</span></div>' : '';
    var actions = '<div class="action-row">';
    if (shipping && shipping.shipment && shipping.shipment.tracking_link) actions += '<button type="button" class="btn btn-secondary btn-sm" data-action="open" data-value="' + escAttr(shipping.shipment.tracking_link) + '">تتبع الشحنة</button>';
    if (shipping && shipping.shipment && shipping.shipment.label && shipping.shipment.label.url) actions += '<button type="button" class="btn btn-secondary btn-sm" data-action="open" data-value="' + escAttr(shipping.shipment.label.url) + '">البوليصة</button>';
    if (urls.digital_content) actions += '<button type="button" class="btn btn-secondary btn-sm" data-action="open" data-value="' + escAttr(urls.digital_content) + '">المحتوى الرقمي</button>';
    actions += '</div>';
    var company = shipping && (shipping.company || shipping.name) || (order.shipping_status === 'not_shippable' ? 'غير قابل للشحن' : '—');
    return '<div class="ops-card"><h4>الشحن والتوصيل</h4>' + trackingBlock +
      infoRow('حالة الشحن', order.shipping_status || '—') +
      infoRow('الشركة', company) +
      infoRow('قابل للشحن', yesNo(features.shippable)) +
      infoRow('رقمي', yesNo(features.digitalable)) +
      (order.total_weight != null ? infoRow('الوزن', String(order.total_weight)) : '') +
      actions + '</div>';
  }

  function buildPaymentOps(order) {
    var am = order.amounts || {};
    var paid = !order.is_pending_payment;
    var pa = order.payment_actions || {};
    var refund = pa.refund_action || {};
    var remaining = pa.remaining_action || {};
    var discounts = (am.discounts || []).map(function (d) {
      return '<span class="inline-tag"><strong>' + esc(d.title || d.code || 'خصم') + '</strong>' + esc(money(d.amount || d.discount)) + '</span>';
    }).join('');
    var invoice = '';
    if (am.sub_total) invoice += infoRow('الإجمالي الفرعي', money(am.sub_total));
    if (am.shipping_cost) invoice += infoRow('الشحن', money(am.shipping_cost));
    if (am.cash_on_delivery && am.cash_on_delivery.amount) invoice += infoRow('الدفع عند الاستلام', money(am.cash_on_delivery));
    if (am.tax) invoice += infoRow('الضريبة' + (am.tax.percent ? ' (' + am.tax.percent + '%)' : ''), money(am.tax.amount));
    if (am.total) invoice += '<div class="invoice-compact" style="margin-top:8px;border-top:1px solid var(--border);padding-top:4px">' + infoRow('الإجمالي', money(am.total)) + '</div>';
    return '<div class="ops-card"><h4>الدفع والفاتورة</h4>' +
      infoRow('الطريقة', order.payment_method || '—') +
      '<div class="info-row"><span class="info-label">الحالة</span><span class="info-value" style="color:' + (paid ? 'var(--success)' : 'var(--warning)') + '">' + esc(paid ? 'مدفوع' : 'بانتظار الدفع') + '</span></div>' +
      infoRow('العملة', order.currency || '—') +
      (refund.paid_amount ? infoRow('المبلغ المدفوع', money(refund.paid_amount)) : '') +
      (remaining.remaining_amount && remaining.remaining_amount.amount > 0 ? infoRow('المتبقي', money(remaining.remaining_amount)) : '') +
      (discounts ? '<div class="inline-tags">' + discounts + '</div>' : '') +
      invoice + '</div>';
  }

  function buildCustomerLocationOps(order) {
    var c = order.customer || {};
    if (!c.city && !c.country) return '';
    return '<div class="ops-card ops-card--wide"><h4>موقع العميل</h4>' +
      '<div class="address-block">' + esc([c.city, c.location].filter(Boolean).join(' · ') || '—') + '</div>' +
      '<div class="address-sub">' + esc(c.country || '') + (c.country_code ? ' · ' + esc(c.country_code) : '') + '</div></div>';
  }

  function buildMetaFolds(order, customer, histories) {
    var folds = [];
    if (histories && histories.length) folds.push(buildHistoryFold(histories));
    folds.push(buildExtraMetaFold(order, customer));
    return folds.join('');
  }

  function buildHistoryFold(histories) {
    var items = histories.slice(0, 15).map(function (h) {
      return '<li class="history-timeline-item"><span class="history-timeline-time">' + esc(formatSallaDate(h.created_at)) + '</span><div>' +
        '<div class="history-timeline-status">' + esc(h.status || '—') + '</div>' +
        (h.note ? '<div class="history-timeline-detail">' + esc(h.note) + '</div>' : '') + '</div></li>';
    }).join('');
    return '<details class="meta-fold"><summary>سجل الطلب <span>' + histories.length + ' حدث</span></summary><div class="meta-fold-body"><ul class="history-timeline">' + items + '</ul></div></details>';
  }

  function buildExtraMetaFold(order, customer) {
    var sectionsHtml = '';
    var currentTitle = null;
    var currentRows = '';
    function beginSection(title) { if (currentTitle) flushSection(); currentTitle = title; currentRows = ''; }
    function flushSection() {
      if (!currentTitle || !currentRows) { currentTitle = null; currentRows = ''; return; }
      sectionsHtml += '<details class="meta-subfold"><summary>' + esc(currentTitle) + '</summary><div class="meta-subfold-body">' + currentRows + '</div></details>';
      currentTitle = null; currentRows = '';
    }
    function addRow(l, v) { if (v == null || v === '' || v === '—') return; currentRows += infoRow(l, v); }
    function addCopy(l, v) { if (v == null || v === '') return; currentRows += infoRowCopy(l, String(v)); }
    function addLink(l, u) { if (!u) return; currentRows += infoRowCopy(l, u); }
    function addBool(l, v) { if (v == null) return; currentRows += infoRow(l, yesNo(v)); }

    var urls = order.urls || {};
    var status = order.status || {};
    var features = order.features || {};
    var src = order.source_details || {};
    var campaign = order.campaign || {};
    var store = order.store || {};
    var cod = order.cod || {};
    var ex = order.exchange_rate || {};
    var pa = order.payment_actions || {};
    var remaining = pa.remaining_action || {};

    beginSection('معرّفات الطلب');
    addCopy('رقم الطلب', order.reference_id);
    addCopy('معرّف سلة', order.id);
    addCopy('checkout_id', order.checkout_id);
    if (order.external_order_id) addCopy('external_order_id', order.external_order_id);
    addLink('رابط العميل', urls.customer);
    addLink('رابط الإدارة', urls.admin);
    addLink('المحتوى الرقمي', urls.digital_content);
    addLink('رابط التقييم', urls.rating);
    addRow('حالة الطلب', (status.name || '—') + (status.slug ? ' (' + status.slug + ')' : ''));

    beginSection('التواريخ والمصدر');
    addRow('تاريخ الإنشاء', formatSallaDate(order.date));
    addRow('آخر تحديث', formatSallaDate(order.updated_at));
    addRow('المصدر', order.source || '—');
    addRow('الجهاز', src.device || '—');
    addRow('نوع المصدر', (src.type || '—') + (src.value ? ' · ' + src.value : ''));

    if (customer) {
      beginSection('حساب العميل');
      addCopy('معرّف العميل', customer.id);
      addRow('الاسم الكامل', customer.full_name);
      addRow('المدينة', customer.city);
      addRow('الدولة', customer.country);
      addRow('اللغة', customer.lang);
      addLink('ملف العميل (إدارة)', customer.urls && customer.urls.admin);
      addLink('ملف العميل (متجر)', customer.urls && customer.urls.customer);
      addRow('تاريخ التسجيل', formatSallaDate(customer.created_at));
    }

    beginSection('خصائص الطلب');
    addBool('مسودة', order.draft);
    addBool('مقروء', order.read);
    addBool('عرض سعر', order.is_price_quote);
    addBool('يمكن الإلغاء', order.can_cancel);
    addBool('يمكن إعادة الطلب', order.can_reorder);
    addBool('بانتظار الدفع', order.is_pending_payment);
    addBool('عنوان غير مكتمل', order.address_incomplete);
    addBool('وزن زائد', order.is_overweighted);
    addBool('جلسة عميل نشطة', order.has_customer_session);
    addBool('شحن', features.shippable);
    addBool('رقمي', features.digitalable);
    addBool('دولي', features.is_international);
    addBool('تنبيه مشبوه', features.has_suspicious_alert);
    addBool('محفظة', features.wallet);

    if (campaign.source || campaign.medium) {
      beginSection('الحملة والتتبع');
      addRow('المصدر', campaign.source);
      addRow('الوسيط', campaign.medium);
      addRow('الحملة', campaign.campaign);
      addRow('utm_source', src.utm_source);
      addRow('utm_medium', src.utm_medium);
      addRow('utm_campaign', src.utm_campaign);
      addRow('utm_content', src.utm_content);
      addRow('IP', src.ip);
    }

    beginSection('تفاصيل الدفع');
    addRow('طريقة الدفع', order.payment_method);
    if (ex.rate) addRow('سعر الصرف', ex.base_currency + ' → ' + ex.exchange_currency + ' (' + ex.rate + ')');
    if (remaining.checkout_url) addLink('رابط إتمام الدفع', remaining.checkout_url);
    if (order.gateway) addRow('بوابة الدفع', order.gateway);
    if (order.receipt_image) addLink('صورة الإيصال', order.receipt_image);
    if (cod.message) addRow('COD', cod.message);
    addBool('COD متاح', cod.is_available);

    if (order.shipping || order.shipments) {
      beginSection('تفاصيل الشحن');
      addRow('حالة الشحن', order.shipping_status);
      if (order.shipping) {
        addRow('شركة الشحن', order.shipping.company);
        if (order.shipping.shipment) {
          addCopy('رقم الشحنة', order.shipping.shipment.id);
          addLink('رابط التتبع', order.shipping.shipment.tracking_link);
        }
      }
    }

    if (store.name || store.username) {
      beginSection('المتجر');
      addRow('الاسم', (store.name && (store.name.ar || store.name.en)) || store.username);
      addRow('المعرف', store.username);
      addRow('الخطة', store.plan);
      addRow('البريد', store.user_email);
    }

    beginSection('مرجعيات');
    if (order.bank) addRow('البنك', typeof order.bank === 'object' ? JSON.stringify(order.bank) : order.bank);
    if (order.tags && order.tags.length) addRow('الوسوم', order.tags.join(' · '));

    flushSection();
    return '<details class="meta-fold"><summary>تفاصيل إضافية</summary><div class="meta-fold-body meta-fold-body--nested">' + (sectionsHtml || infoRow('—', 'لا توجد تفاصيل')) + '</div></details>';
  }

  function infoRow(label, value) {
    return '<div class="info-row"><span class="info-label">' + esc(label) + '</span><span class="info-value">' + esc(String(value)) + '</span></div>';
  }
  function infoRowCopy(label, value) {
    if (!value || value === '—') return infoRow(label, '—');
    return '<div class="info-row"><span class="info-label">' + esc(label) + '</span><span class="info-value">' + copyChip(String(value), String(value), 'order-phone') + '</span></div>';
  }

  function onOrdersClick(event) {
    var btn = event.target.closest('[data-action]');
    if (!btn) return;
    event.preventDefault();
    event.stopPropagation();
    var action = btn.getAttribute('data-action');
    var value = btn.getAttribute('data-value') || '';
    var copyLabel = btn.getAttribute('data-copy-label') || '';
    if (action === 'copy') copyText(value, copyLabel);
    else if (action === 'open' && value) window.open(value, '_blank', 'noopener,noreferrer');
  }

  function copyText(text, label) {
    if (!text) return;
    var done = function () { showBanner(label ? ('تم نسخ ' + label) : 'تم النسخ بنجاح', 'success'); };
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(text).then(done).catch(function () { fallbackCopy(text); done(); });
    } else { fallbackCopy(text); done(); }
  }
  function fallbackCopy(text) {
    var area = document.createElement('textarea');
    area.value = text; area.style.position = 'fixed'; area.style.opacity = '0';
    document.body.appendChild(area); area.select(); document.execCommand('copy'); document.body.removeChild(area);
  }

  function copyChip(value, display, extraClass) {
    if (!value) return esc(display || '—');
    return '<button type="button" class="copy-chip' + (extraClass ? ' ' + extraClass : '') + '" data-action="copy" data-value="' + escAttr(String(value)) + '" data-copy-label="' + escAttr(String(display || value)) + '" title="نسخ">' + esc(display || value) + '</button>';
  }

  function formatItemOptions(options) {
    if (!options || !options.length) return '';
    return options.map(function (opt) {
      var val = opt.value;
      if (val && typeof val === 'object') {
        return (opt.name || '') + ': ' + (val.name || val.option_value || '') + (val.price && val.price.amount ? ' (+' + money(val.price) + ')' : '');
      }
      return (opt.name || '') + ': ' + String(val);
    }).join(' · ');
  }

  function customerPhone(c) {
    if (!c) return '';
    var code = String(c.mobile_code || '').replace(/\s/g, '');
    var mobile = String(c.mobile || '').replace(/\s/g, '');
    if (!mobile) return '';
    if (code && code.indexOf('+') !== 0) code = '+' + code.replace(/^\+/, '');
    return normalizeSearchTerm(code + mobile);
  }

  function money(obj) {
    if (!obj) return '—';
    if (typeof obj === 'object' && obj.amount != null) return obj.amount + ' ' + (obj.currency || '');
    return String(obj);
  }

  function yesNo(value) {
    if (value === true || value === 1 || value === '1') return 'نعم';
    if (value === false || value === 0 || value === '0') return 'لا';
    return value == null ? '—' : String(value);
  }

  function normalizeSearchTerm(term) {
    return String(term || '').trim().replace(/\+/g, '');
  }

  function formatSallaDate(d) {
    if (!d) return '—';
    var raw = d.date || d;
    return formatDate(raw);
  }

  function formatDate(value) {
    if (!value) return '—';
    try {
      return new Date(value).toLocaleString('ar-SA', {
        calendar: 'gregory',
        numberingSystem: 'latn',
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
      });
    } catch (_) { return String(value); }
  }

  function esc(value) {
    return String(value).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
  }
  function escAttr(value) { return esc(value).replace(/`/g, '&#96;'); }
  function truncate(text, max) {
    if (!text || text.length <= max) return text || '';
    return text.slice(0, max) + '…';
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();

  </script>
</body>
</html>
