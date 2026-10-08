// Visual Contract Workflow Tracker (Phase 6) — presentational types.
// Shaped to map cleanly onto contract-management's
// GET /contracts/{id}/approval-progress response (Phase 5's
// ApprovalEngineController::formatRun/formatTask) once wired to live data.
// Until then, ContractDetail/index.vue uses local ref<WorkflowProgress>
// mock data (no API calls, no DB seeding) per the project's dummy-data
// convention.

export type NodeStatus = 'completed' | 'active' | 'pending' | 'rejected' | 'canceled' | 'on_hold'
export type GroupMode = 'sequential' | 'parallel'
export type TrackerStatus = 'loading' | 'ready' | 'error' | 'empty'

export interface NodeDelegation {
  delegateName: string
  onBehalfOfRole: string
}

export interface WorkflowNode {
  id: string
  title: string
  status: NodeStatus
  actedAt: string | null // ISO-8601, or null for not-yet-acted steps
  subText: string | null
  delegation: NodeDelegation | null
  // Decision #14: role display state. roleDeleted takes precedence over
  // roleRenamedFrom if somehow both are set (shouldn't happen in practice —
  // a role is either currently resolvable with history, or gone).
  roleDeleted: boolean
  roleRenamedFrom: string | null // previous name, shown on hover when set
}

export interface WorkflowGroup {
  id: string
  mode: GroupMode
  nodes: WorkflowNode[]
}

export interface WorkflowRun {
  runNumber: number
  groups: WorkflowGroup[]
}

export interface WorkflowProgress {
  contractId: string
  currentRun: number
  runs: WorkflowRun[] // at least one when status === 'ready'; latest last
}

// ── Derived display helpers (pure functions, no component state) ───────

/**
 * Group status for connector styling (spec 5.3): rejected if any node is
 * rejected; else active if any node is active; else completed if all
 * nodes are completed; else pending. canceled nodes don't independently
 * drive group status — a group is only ever 'canceled' overall if every
 * node in it is canceled (the rejected sibling already makes the group
 * read as 'rejected' per the rule above).
 */
export function groupStatus(group: WorkflowGroup): NodeStatus {
  if (group.nodes.some(n => n.status === 'rejected')) return 'rejected'
  if (group.nodes.some(n => n.status === 'active' || n.status === 'on_hold')) return 'active'
  if (group.nodes.every(n => n.status === 'completed')) return 'completed'
  if (group.nodes.every(n => n.status === 'canceled')) return 'canceled'
  return 'pending'
}
