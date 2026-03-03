/**
 * @octobots/dashboard-sdk — Core Implementation
 *
 * `createDashboard()` factory that returns a fully-typed SDK instance
 * for building Octobots dashboard panel apps inside iframes.
 *
 * Features:
 * - Bidirectional postMessage bridge with the host (Octobots inbox)
 * - Promise-based action execution with request/response correlation
 * - Context subscriptions (conversation, customer, capabilities)
 * - Host action helpers (toast, note, tag, navigate)
 * - Automatic heartbeat for connection monitoring
 * - Configurable timeouts and debug logging
 *
 * @module
 */

import type {
  ActionDefinition,
  ActionResult,
  AppCapabilities,
  ConversationData,
  CustomerContext,
  DashboardOptions,
  HostActionResult,
  HostActionType,
  OctobotsDashboard,
  PostMessageEnvelope,
  SourceItem,
  ToastVariant,
  Unsubscribe,
} from "./types";

// ─── Internal Types ─────────────────────────────────────────────────────────

/** Pending promise entry for request/response correlation. */
interface PendingRequest<T = unknown> {
  resolve: (value: T) => void;
  reject: (reason: unknown) => void;
  timeout: ReturnType<typeof setTimeout>;
}

/** Internal event names emitted by the SDK. */
type InternalEvent =
  | "conversationUpdate"
  | "customerContextUpdate"
  | "capabilitiesUpdate"
  | "message"
  | "ready"
  | "error";

// ─── Constants ──────────────────────────────────────────────────────────────

const DEFAULT_HEARTBEAT_INTERVAL = 10_000;
const DEFAULT_ACTION_TIMEOUT = 30_000;
const READY_POLL_INTERVAL = 50;
const READY_POLL_MAX_WAIT = 15_000;

// ─── Helpers ────────────────────────────────────────────────────────────────

/**
 * Generates a unique request ID for correlating postMessage round-trips.
 * @returns A prefixed random ID string.
 */
function generateRequestId(): string {
  return `req_${Date.now()}_${Math.random().toString(36).slice(2, 9)}`;
}

// ─── Factory ────────────────────────────────────────────────────────────────

/**
 * Create an Octobots Dashboard SDK instance.
 *
 * Call this from within an iframe that is embedded as a dashboard app
 * in the Octobots inbox conversation panel.
 *
 * @param options - Optional configuration (heartbeat interval, timeouts, debug).
 * @returns An `OctobotsDashboard` API object.
 *
 * @example
 * ```ts
 * import { createDashboard } from '@octobots/dashboard-sdk';
 *
 * const dashboard = createDashboard({ debug: true });
 *
 * dashboard.init({
 *   onReady: async () => {
 *     const customer = dashboard.getCustomerContext();
 *     const orders = await dashboard.executeAction('listOrders', {
 *       email: customer?.customer.primaryEmail,
 *     });
 *     console.log(orders);
 *   },
 * });
 * ```
 */
export function createDashboard(
  options: DashboardOptions = {}
): OctobotsDashboard {
  const {
    heartbeatInterval = DEFAULT_HEARTBEAT_INTERVAL,
    actionTimeout = DEFAULT_ACTION_TIMEOUT,
    debug = false,
    onError,
  } = options;

  // ─── State ──────────────────────────────────────────────────────────

  let connected = false;
  let ready = false;
  let destroyed = false;
  let conversation: ConversationData | null = null;
  let customerContext: CustomerContext | null = null;
  let capabilities: AppCapabilities | null = null;

  /** Pending action/host-action/source requests keyed by requestId. */
  const pendingRequests = new Map<string, PendingRequest>();
  /** Event listeners by event name. */
  const listeners = new Map<InternalEvent, Set<(data: unknown) => void>>();

  // ─── Logging ────────────────────────────────────────────────────────

  /**
   * Conditional debug logger.
   * @param args - Arguments forwarded to `console.debug`.
   */
  function log(...args: unknown[]): void {
    if (debug) {
      console.debug("[octobots-sdk]", ...args);
    }
  }

  // ─── Internal Event Emitter ─────────────────────────────────────────

  /**
   * Emit an internal event to all registered listeners.
   * @param event - The event name.
   * @param data - The event payload.
   */
  function emit(event: InternalEvent, data: unknown): void {
    const set = listeners.get(event);
    if (!set) return;
    for (const cb of set) {
      try {
        cb(data);
      } catch (err) {
        console.error(`[octobots-sdk] Error in ${event} listener:`, err);
      }
    }
  }

  /**
   * Subscribe to an internal event.
   * @param event - The event name.
   * @param callback - The listener function.
   * @returns An unsubscribe function.
   */
  function on(
    event: InternalEvent,
    callback: (data: unknown) => void
  ): Unsubscribe {
    if (!listeners.has(event)) {
      listeners.set(event, new Set());
    }
    listeners.get(event)!.add(callback);
    return () => {
      listeners.get(event)?.delete(callback);
    };
  }

  // ─── PostMessage Sending ────────────────────────────────────────────

  /**
   * Send a typed postMessage to the host (parent window).
   * @param type - The message type.
   * @param data - Optional payload.
   * @param requestId - Optional request ID for correlation.
   */
  function sendMessage(
    type: string,
    data?: unknown,
    requestId?: string
  ): void {
    if (destroyed) return;
    const envelope: PostMessageEnvelope = {
      type,
      data,
      ...(requestId && { requestId }),
      timestamp: Date.now(),
    };
    log("→", type, requestId ?? "", data);
    // Use "*" since the iframe doesn't know the host origin.
    // Security is enforced on the host side via origin validation.
    window.parent.postMessage(envelope, "*");
  }

  // ─── Pending Request Helpers ────────────────────────────────────────

  /**
   * Create a promise correlated to a requestId. The promise resolves/rejects
   * when a matching response message arrives or the timeout expires.
   * @param requestId - The unique request identifier.
   * @param timeoutMs - Timeout in milliseconds.
   * @param fallback - Fallback value on timeout.
   * @returns A promise that resolves with the response data.
   */
  function createPendingPromise<T>(
    requestId: string,
    timeoutMs: number,
    fallback: T
  ): Promise<T> {
    return new Promise<T>((resolve, reject) => {
      const timeout = setTimeout(() => {
        pendingRequests.delete(requestId);
        log("⏱ timeout:", requestId);
        resolve(fallback);
      }, timeoutMs);

      pendingRequests.set(requestId, {
        resolve: resolve as (value: unknown) => void,
        reject,
        timeout,
      });
    });
  }

  /**
   * Resolve a pending request with data.
   * @param requestId - The request ID to resolve.
   * @param data - The response data.
   */
  function resolvePending(requestId: string, data: unknown): void {
    const pending = pendingRequests.get(requestId);
    if (!pending) return;
    clearTimeout(pending.timeout);
    pendingRequests.delete(requestId);
    pending.resolve(data);
  }

  // ─── Inbound Message Handler ────────────────────────────────────────

  /**
   * Handle all inbound postMessage events from the host.
   * @param event - The raw MessageEvent.
   */
  function handleMessage(event: MessageEvent): void {
    if (destroyed) return;

    const msg = event.data as PostMessageEnvelope | undefined;
    if (!msg || typeof msg.type !== "string") return;

    log("←", msg.type, msg.requestId ?? "", msg.data);

    // Emit raw message to global listeners
    emit("message", msg);

    switch (msg.type) {
      // ── Conversation lifecycle ───────────────────────────────
      case "conversation_init":
      case "conversation_update":
      case "conversation_data":
        conversation = msg.data as ConversationData;
        connected = true;
        emit("conversationUpdate", conversation);
        checkReady();
        break;

      // ── Customer context ─────────────────────────────────────
      case "customer_context":
      case "customer_context_update":
      case "customer_context_result":
        if (msg.data) {
          // Handle both { customerContext: {...} } and direct {...} shapes
          const raw = msg.data as Record<string, unknown>;
          customerContext = (raw.customerContext ?? raw) as CustomerContext;
          emit("customerContextUpdate", customerContext);
        }
        break;

      // ── Capabilities (miniapp config) ───────────────────────
      case "app_capabilities":
      case "config_result": {
        const raw = msg.data as Record<string, unknown>;
        // config_result wraps: { config: {...}, customerContext: {...} }
        if (raw.config) {
          capabilities = raw.config as AppCapabilities;
          if (raw.customerContext) {
            customerContext = raw.customerContext as CustomerContext;
            emit("customerContextUpdate", customerContext);
          }
        } else {
          capabilities = raw as unknown as AppCapabilities;
        }
        emit("capabilitiesUpdate", capabilities);
        checkReady();
        break;
      }

      // ── Action result / error ───────────────────────────────
      case "action_result":
      case "action_error": {
        const data = msg.data as Record<string, unknown>;
        const requestId =
          msg.requestId ?? (data?.requestId as string | undefined);
        if (requestId) {
          resolvePending(requestId, data);
        }
        break;
      }

      // ── Host action result ──────────────────────────────────
      case "host_action_result": {
        const data = msg.data as Record<string, unknown>;
        const requestId =
          msg.requestId ?? (data?.requestId as string | undefined);
        if (requestId) {
          resolvePending(requestId, data);
        }
        break;
      }

      // ── Source data result ──────────────────────────────────
      case "sources_result": {
        const data = msg.data as Record<string, unknown>;
        const requestId =
          msg.requestId ?? (data?.requestId as string | undefined);
        if (requestId) {
          resolvePending(requestId, data);
        }
        break;
      }

      // ── Heartbeat ack ───────────────────────────────────────
      case "heartbeat_ack":
        connected = true;
        break;
    }
  }

  // ─── Readiness Check ────────────────────────────────────────────────

  /**
   * Check whether the SDK has received enough data to fire the `ready` event.
   * We consider ready when we have conversation data.
   * Capabilities are optional (non-miniapp dashboard apps won't have them).
   */
  function checkReady(): void {
    if (ready) return;
    if (conversation) {
      ready = true;
      emit("ready", undefined);
    }
  }

  // ─── Setup ──────────────────────────────────────────────────────────

  // Start listening for messages immediately
  window.addEventListener("message", handleMessage);

  // Heartbeat interval
  const heartbeatTimer = setInterval(() => {
    if (!destroyed) {
      sendMessage("heartbeat");
    }
  }, heartbeatInterval);

  // ─── Public API ─────────────────────────────────────────────────────

  const sdk: OctobotsDashboard = {
    // ── Lifecycle ───────────────────────────────────────────────

    init(initOpts) {
      sendMessage("app_ready");
      log("init — sent app_ready");

      if (initOpts?.onReady) {
        if (ready) {
          initOpts.onReady();
        } else {
          // Wait until ready
          const startTime = Date.now();
          const poll = setInterval(() => {
            if (ready || destroyed) {
              clearInterval(poll);
              if (ready && initOpts.onReady) {
                initOpts.onReady();
              }
            } else if (Date.now() - startTime > READY_POLL_MAX_WAIT) {
              // Timeout — fire onReady anyway with whatever we have
              clearInterval(poll);
              log("⚠ ready timeout — firing onReady with partial data");
              if (initOpts.onReady) initOpts.onReady();
            }
          }, READY_POLL_INTERVAL);
        }
      }
    },

    get isConnected() {
      return connected;
    },

    get isReady() {
      return ready;
    },

    ready() {
      sendMessage("app_ready");
    },

    error(message: string) {
      sendMessage("app_error", { message });
      if (onError) onError(message);
    },

    destroy() {
      if (destroyed) return;
      destroyed = true;
      window.removeEventListener("message", handleMessage);
      clearInterval(heartbeatTimer);

      // Reject all pending requests
      for (const [id, pending] of pendingRequests) {
        clearTimeout(pending.timeout);
        pending.reject(new Error("SDK destroyed"));
        pendingRequests.delete(id);
      }

      // Clear all listeners
      listeners.clear();
      log("destroyed");
    },

    // ── Context ─────────────────────────────────────────────────

    getConversation() {
      return conversation;
    },

    getCustomerContext() {
      return customerContext;
    },

    onConversationUpdate(callback) {
      return on(
        "conversationUpdate",
        callback as (data: unknown) => void
      );
    },

    onCustomerContextUpdate(callback) {
      return on(
        "customerContextUpdate",
        callback as (data: unknown) => void
      );
    },

    // ── Actions ─────────────────────────────────────────────────

    getActions(): ActionDefinition[] {
      return capabilities?.actions ?? [];
    },

    getCapabilities(): AppCapabilities | null {
      return capabilities;
    },

    async executeAction(
      actionName: string,
      formInput: Record<string, unknown> = {}
    ): Promise<ActionResult> {
      const requestId = generateRequestId();

      const fallback: ActionResult = {
        requestId,
        actionName,
        success: false,
        error: {
          code: "INTERNAL_ERROR",
          message: "Request timed out",
          retryable: true,
        },
      };

      const promise = createPendingPromise<ActionResult>(
        requestId,
        actionTimeout,
        fallback
      );

      sendMessage("execute_action", { actionName, formInput }, requestId);
      return promise;
    },

    async getSourceData(sourceKey: string): Promise<SourceItem[]> {
      const requestId = generateRequestId();

      const promise = createPendingPromise<Record<string, unknown>>(
        requestId,
        actionTimeout,
        { items: [] }
      );

      sendMessage("request_sources", { sourceKey }, requestId);
      const result = await promise;
      return (result.items as SourceItem[]) ?? [];
    },

    // ── Host Actions ────────────────────────────────────────────

    showToast(message: string, variant: ToastVariant = "info") {
      sendMessage("host_action", {
        requestId: generateRequestId(),
        action: "show_toast" as HostActionType,
        params: { message, variant },
      });
    },

    async addNote(content: string): Promise<HostActionResult> {
      const requestId = generateRequestId();

      const fallback: HostActionResult = {
        requestId,
        success: false,
        action: "add_note",
      };

      const promise = createPendingPromise<HostActionResult>(
        requestId,
        actionTimeout,
        fallback
      );

      sendMessage(
        "host_action",
        {
          action: "add_note" as HostActionType,
          params: { content },
        },
        requestId
      );

      return promise;
    },

    async addTag(tagName: string): Promise<HostActionResult> {
      const requestId = generateRequestId();

      const fallback: HostActionResult = {
        requestId,
        success: false,
        action: "add_tag",
      };

      const promise = createPendingPromise<HostActionResult>(
        requestId,
        actionTimeout,
        fallback
      );

      sendMessage(
        "host_action",
        {
          action: "add_tag" as HostActionType,
          params: { tagName },
        },
        requestId
      );

      return promise;
    },

    openCustomer() {
      sendMessage("host_action", {
        requestId: generateRequestId(),
        action: "open_customer" as HostActionType,
        params: {},
      });
    },

    // ── Events ──────────────────────────────────────────────────

    onCapabilitiesUpdate(callback) {
      return on(
        "capabilitiesUpdate",
        callback as (data: unknown) => void
      );
    },

    onMessage(callback) {
      return on("message", callback as (data: unknown) => void);
    },
  };

  return sdk;
}
