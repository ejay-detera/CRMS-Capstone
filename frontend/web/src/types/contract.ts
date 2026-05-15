export type ContractStatus = 'Notarized PDF' | 'Client Review' | 'SBSI Review'
export type ContractRegion = 'Luzon' | 'Visayas' | 'Mindanao'
export type FilterTab      = 'all' | 'active' | 'expiring' | 'expired'

export interface Contract {
  id:              string
  businessPartner: string
  category:        string
  itemCode:        string
  description:     string
  serialNo:        string
  region:          ContractRegion
  startDate:       string
  endDate:         string
  status:          ContractStatus
  contractLink:    string
}

export const statusBadge: Record<ContractStatus, string> = {
  'Notarized PDF': 'bg-black/5 text-black/60 border-black/12',
  'Client Review': 'bg-emerald-50 text-emerald-700 border-emerald-200',
  'SBSI Review':   'bg-red-50 text-red-600 border-red-200',
}

export function remainingDays(endDate: string): number {
  const end   = new Date(endDate)
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  end.setHours(0, 0, 0, 0)
  return Math.floor((end.getTime() - today.getTime()) / 86_400_000)
}

export function fmtDate(iso: string): string {
  return new Date(iso).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
}
