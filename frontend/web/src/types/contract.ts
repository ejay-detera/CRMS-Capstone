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
  createdBy:       string
}

export const statusBadge: Record<ContractStatus, string> = {
  'Notarized PDF': 'bg-[#252578]/8 text-[#252578] border-[#252578]/20',
  'Client Review': 'bg-[#2E85D8]/8 text-[#2E85D8] border-[#2E85D8]/20',
  'SBSI Review':   'bg-black/5 text-black/50 border-black/10',
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
