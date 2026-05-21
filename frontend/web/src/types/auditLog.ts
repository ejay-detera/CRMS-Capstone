export type ActionType =
  | 'Contract Created'  | 'Contract Updated' | 'Contract Approved'
  | 'Contract Deleted'  | 'User Created'     | 'User Updated'
  | 'User Deleted'      | 'Partner Added'    | 'Partner Updated'
  | 'Role Updated'      | 'Settings Changed' | 'Login'
  | 'created'           | 'updated'          | 'deleted'
  | 'user_created'      | 'LOGIN_SUCCESS'    | 'LOGIN_FAILED'
  | 'LOGOUT'

export interface LogEntry {
  id: string
  source: 'crms' | 'auth'
  user_id: number
  user_name: string
  action: ActionType
  entity_type: string
  description: string
  performed_at: string
  old_data?: any
  new_data?: any
}

export const actionOptions: Array<ActionType | 'All'> = [
  'All',
  'created',
  'updated',
  'deleted',
  'user_created',
  'LOGIN_SUCCESS',
  'LOGIN_FAILED',
  'LOGOUT',
  'Contract Created',
  'Contract Updated',
  'Contract Approved',
  'Contract Deleted',
  'User Created',
  'User Updated',
  'User Deleted',
  'Partner Added',
  'Partner Updated',
  'Role Updated',
  'Settings Changed',
  'Login',
]

export const actionBadge: Record<ActionType, string> = {
  'Contract Created':  'bg-emerald-50 text-emerald-700 border-emerald-200',
  'Contract Updated':  'bg-[#2E85D8]/8 text-[#2E85D8] border-[#2E85D8]/20',
  'Contract Approved': 'bg-[#252578]/8 text-[#252578] border-[#252578]/20',
  'Contract Deleted':  'bg-red-50 text-red-600 border-red-200',
  'User Created':      'bg-emerald-50 text-emerald-700 border-emerald-200',
  'User Updated':      'bg-[#2E85D8]/8 text-[#2E85D8] border-[#2E85D8]/20',
  'User Deleted':      'bg-red-50 text-red-600 border-red-200',
  'Partner Added':     'bg-emerald-50 text-emerald-700 border-emerald-200',
  'Partner Updated':   'bg-[#2E85D8]/8 text-[#2E85D8] border-[#2E85D8]/20',
  'Role Updated':      'bg-amber-50 text-amber-700 border-amber-200',
  'Settings Changed':  'bg-amber-50 text-amber-700 border-amber-200',
  'Login':             'bg-black/4 text-black/45 border-black/10',
  'created':           'bg-emerald-50 text-emerald-700 border-emerald-200',
  'updated':           'bg-[#2E85D8]/8 text-[#2E85D8] border-[#2E85D8]/20',
  'deleted':           'bg-red-50 text-red-600 border-red-200',
  'user_created':      'bg-indigo-50 text-indigo-700 border-indigo-200',
  'LOGIN_SUCCESS':     'bg-teal-50 text-teal-700 border-teal-200',
  'LOGIN_FAILED':      'bg-rose-50 text-rose-700 border-rose-200',
  'LOGOUT':            'bg-slate-50 text-slate-700 border-slate-200',
}

