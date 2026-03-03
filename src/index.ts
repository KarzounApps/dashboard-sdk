/**
 * @octobots/dashboard-sdk
 *
 * Lightweight SDK for building Octobots dashboard panel apps inside iframes.
 * Provides a typed postMessage bridge for executing miniapp actions,
 * receiving conversation/customer context, and triggering host UI actions.
 *
 * @example
 * ```ts
 * import { createDashboard } from '@octobots/dashboard-sdk';
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
  OctobotsDashboard,
  DashboardOptions,
  Unsubscribe,
  PostMessageEnvelope,
  // Conversation
  ConversationData,
  ConversationMessage,
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
