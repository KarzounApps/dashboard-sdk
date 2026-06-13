/**
 * @karzoun/dashboard-sdk — Type definitions
 *
 * All public types for the Karzoun Dashboard SDK.
 * These mirror the postMessage protocol between the host (Karzoun inbox)
 * and the iframe (dashboard panel app).
 * @module
 */

// ─── Conversation ───────────────────────────────────────────────────────────

/** Customer embedded on the conversation object from the host. */
export interface ConversationCustomer {
  _id: string;
  firstName?: string;
  lastName?: string;
  primaryEmail?: string;
  primaryPhone?: string;
  avatar?: string;
}

/** Minimal conversation object sent from the host. */
export interface ConversationData {
  conversation: {
    _id: string;
    status: string;
    content: string;
    createdAt: string;
    updatedAt: string;
    assignedUserId?: string;
    participatedUserIds?: string[];
    customerId?: string;
    integrationId?: string;
    tagIds?: string[];
    customer?: ConversationCustomer;
    integration?: { _id: string; kind?: string; name?: string };
  };
  messages: ConversationMessage[];
  metadata: {
    totalMessages: number;
    lastMessageAt?: string;
  };
}

/** A single conversation message. */
export interface ConversationMessage {
  _id: string;
  type?: string;
  text?: string;
  media?: unknown;
  sender?: unknown;
  visibility?: string;
  delivery?: unknown;
  createdAt: string;
}

// ─── Customer Context ───────────────────────────────────────────────────────

/** Resolved customer data from the current conversation. */
export interface CustomerContext {
  customerId: string;
  customer: {
    firstName?: string;
    lastName?: string;
    primaryEmail?: string;
    primaryPhone?: string;
    /** External platform IDs (e.g. shopify, salla, zid) */
    externalCodes: Record<string, string>;
  };
  /** Pre-populated form values that can auto-fill action forms. */
  suggestedFormValues: Record<string, string>;
}

// ─── Actions / Capabilities ─────────────────────────────────────────────────

/** Validation rules for a form field. */
export interface FormFieldRules {
  required?: boolean;
  min?: number;
  max?: number;
  pattern?: string;
}

/** A single form field definition for an action. */
export interface FormField {
  label: string;
  type: string;
  source?: string;
  rules?: FormFieldRules;
  defaultValue?: string;
  items?: FormField[];
  subForm?: Record<string, FormField>;
}

/** A miniapp action definition exposed to the iframe. */
export interface ActionDefinition {
  name: string;
  title: string;
  description?: string;
  /** Form schema — keys are field names, values are field definitions. */
  form: Record<string, FormField>;
  /** Whether this action requires user input before execution. */
  requiresInput?: boolean;
}

/** Capabilities payload sent from the host after connection. */
export interface AppCapabilities {
  miniAppNs: string;
  miniAppName: string;
  actions: ActionDefinition[];
  isAuthenticated: boolean;
}

// ─── Action Results ────────────────────────────────────────────────────────

/** Machine-readable action error codes. */
export type ActionErrorCode =
  | "AUTH_EXPIRED"
  | "RATE_LIMITED"
  | "ACTION_NOT_FOUND"
  | "VALIDATION_ERROR"
  | "EXTERNAL_API_ERROR"
  | "INTERNAL_ERROR"
  | "NOT_INSTALLED"
  | "NOT_CONFIGURED";

/** Error detail returned when an action fails. */
export interface ActionError {
  code: ActionErrorCode;
  message: string;
  retryable?: boolean;
  details?: Record<string, unknown>;
}

/** Result of an action execution. */
export interface ActionResult {
  requestId: string;
  actionName: string;
  success: boolean;
  data?: unknown;
  error?: ActionError;
}

// ─── Host Actions ───────────────────────────────────────────────────────────

/** Supported host UI actions that the iframe can trigger. */
export type HostActionType =
  | "show_toast"
  | "navigate_conversation"
  | "add_tag"
  | "add_note"
  | "open_customer";

/** Toast notification variants. */
export type ToastVariant = "success" | "error" | "warning" | "info";

/** Result of a host action request. */
export interface HostActionResult {
  requestId: string;
  success: boolean;
  action: HostActionType;
}

// ─── Source Data (dynamic dropdowns) ────────────────────────────────────────

/** A single option item for dynamic dropdowns. */
export interface SourceItem {
  value: string;
  label: string;
}

// ─── SDK Options ────────────────────────────────────────────────────────────

/** Options for `createDashboard()`. */
export interface DashboardOptions {
  /**
   * Called when the SDK is fully initialized (conversation + capabilities received).
   * This is the safe point to start rendering and executing actions.
   */
  onReady?: () => void;
  /**
   * Called when the host signals a connection error or the iframe is disconnected.
   */
  onError?: (message: string) => void;
  /**
   * Heartbeat interval in milliseconds. Defaults to 10000 (10s).
   */
  heartbeatInterval?: number;
  /**
   * Timeout for action execution in milliseconds. Defaults to 30000 (30s).
   */
  actionTimeout?: number;
  /**
   * Enable debug logging to console. Defaults to false.
   */
  debug?: boolean;
}

/** Function to unsubscribe from an event listener. */
export type Unsubscribe = () => void;

// ─── PostMessage Protocol (internal) ────────────────────────────────────────

/**
 * Envelope for all postMessage payloads between host and iframe.
 * @internal
 */
export interface PostMessageEnvelope {
  type: string;
  data?: unknown;
  requestId?: string;
  timestamp: number;
}

/**
 * The public interface of the Karzoun Dashboard SDK.
 * Returned by `createDashboard()`.
 */
export interface KarzounDashboard {
  // ─── Lifecycle ─────────────────────────────────

  /** Initialize the SDK and signal readiness to the host. */
  init(options?: Pick<DashboardOptions, "onReady">): void;

  /** Whether the SDK is connected and has received initial data. */
  readonly isConnected: boolean;

  /** Whether capabilities (miniapp actions) have been received. */
  readonly isReady: boolean;

  /** Signal readiness to the host (sends `app_ready`). */
  ready(): void;

  /** Signal an error to the host. */
  error(message: string): void;

  /** Destroy the SDK instance and clean up all listeners. */
  destroy(): void;

  // ─── Context ───────────────────────────────────

  /** Get the current conversation data (null if not yet received). */
  getConversation(): ConversationData | null;

  /** Get the current customer context (null if not yet received). */
  getCustomerContext(): CustomerContext | null;

  /** Subscribe to conversation changes. Returns an unsubscribe function. */
  onConversationUpdate(callback: (data: ConversationData) => void): Unsubscribe;

  /** Subscribe to customer context changes. Returns an unsubscribe function. */
  onCustomerContextUpdate(callback: (data: CustomerContext) => void): Unsubscribe;

  // ─── Actions ───────────────────────────────────

  /** Get available miniapp actions (empty array if no capabilities received). */
  getActions(): ActionDefinition[];

  /** Get the full capabilities object (null if not yet received). */
  getCapabilities(): AppCapabilities | null;

  /**
   * Execute a miniapp action through the host.
   * Returns a promise that resolves with the action result.
   * The promise always resolves (never rejects) — check `result.success`.
   */
  executeAction(
    actionName: string,
    formInput?: Record<string, unknown>
  ): Promise<ActionResult>;

  /**
   * Request dynamic source data for dropdown fields.
   * @param sourceKey - The source identifier from the form field's `source` property.
   */
  getSourceData(sourceKey: string): Promise<SourceItem[]>;

  // ─── Host Actions ─────────────────────────────

  /** Show a toast notification in the host UI. Fire-and-forget. */
  showToast(message: string, variant?: ToastVariant): void;

  /** Add an internal note to the current conversation. */
  addNote(content: string): Promise<HostActionResult>;

  /** Add a tag to the current conversation. */
  addTag(tagName: string): Promise<HostActionResult>;

  /** Navigate to the customer profile in the host. Fire-and-forget. */
  openCustomer(): void;

  // ─── Events ────────────────────────────────────

  /** Subscribe to capabilities updates. */
  onCapabilitiesUpdate(callback: (data: AppCapabilities) => void): Unsubscribe;

  /**
   * Subscribe to any raw postMessage from the host.
   * Useful for handling custom message types.
   */
  onMessage(callback: (envelope: PostMessageEnvelope) => void): Unsubscribe;
}
