export type TabKey  = 'partners' | 'suppliers'
export type Region  = 'Luzon' | 'Visayas' | 'Mindanao'
export type Status  = 'Active' | 'Inactive'

export interface Partner {
  id:            string
  name:          string
  industry:      string
  region:        Region
  status:        Status
  contracts:     number
  totalValue:    string
  contactPerson: string
  email:         string
  phone:         string
  address:       string
  linkedContracts?: LinkedContract[]
}

export interface AddPartnerForm {
  name:          string
  industry:      string
  region:        Region | ''
  status:        Status
  contactPerson: string
  email:         string
  phone:         string
  address:       string
}

export type EngagementStatus = 'active' | 'expiring' | 'expired'

export interface LinkedContract {
  associationId:    string
  contractId:       string
  description:      string
  businessPartner:  string
  startDate:        string
  endDate:          string
  engagementStatus: EngagementStatus
  attachedBy:       string  // display name
}

export const engagementBadge: Record<EngagementStatus, string> = {
  active:   'bg-emerald-50 text-emerald-700 border-emerald-200',
  expiring: 'bg-amber-50 text-amber-700 border-amber-200',
  expired:  'bg-black/5 text-black/40 border-black/10',
}
