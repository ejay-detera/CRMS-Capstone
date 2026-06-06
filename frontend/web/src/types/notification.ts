export type NotifType = 'contract' | 'user' | 'partner' | 'system' | 'reminder'
export type TabKey    = 'all' | 'archive' | 'favorite'

export interface Notification {
  id:           string
  type:         NotifType
  message:      string
  timestamp:    string
  isRead:       boolean
  isFavorite:   boolean
  isArchived:   boolean
  contractId?:  number | null
  notifType?:   string
}

export const typeColor: Record<NotifType, string> = {
  contract: 'text-[#2E85D8] bg-[#2E85D8]/8',
  user:     'text-[#252578] bg-[#252578]/8',
  partner:  'text-[#2F2F73] bg-[#2F2F73]/8',
  system:   'text-amber-600 bg-amber-50',
  reminder: 'text-emerald-600 bg-emerald-50',
}
