/**
 * @karzoun/dashboard-sdk
 *
 * Lightweight SDK for building Karzoun dashboard panel apps inside iframes.
 * Provides a typed postMessage bridge for executing miniapp actions,
 * receiving conversation/customer context, and triggering host UI actions.
 *
 * @example
 * ```ts
 * import { createDashboard } from '@karzoun/dashboard-sdk';
 *
 * const dashboard = createDashboard();
 * dashboard.init({
 *   onReady: () => {
 *     const ctx = dashboard.getCustomerContext();
 *     console.log('Customer:', ctx?.customer.primaryEmail);
 *   },
 * });
 * ```
 *
 * @packageDocumentation
 */

export { createDashboard } from "./dashboard";
export type {
  // Core
  KarzounDashboard,
  DashboardOptions,
  Unsubscribe,
  PostMessageEnvelope,
  // Conversation
  ConversationData,
  ConversationMessage,
  ConversationCustomer,
  // Customer
  CustomerContext,
  // Actions
  ActionDefinition,
  ActionResult,
  ActionError,
  ActionErrorCode,
  AppCapabilities,
  FormField,
  FormFieldRules,
  // Host Actions
  HostActionType,
  HostActionResult,
  ToastVariant,
  // Sources
  SourceItem,
} from "./types";
