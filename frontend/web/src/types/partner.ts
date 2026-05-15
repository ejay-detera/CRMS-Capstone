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
