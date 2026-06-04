export type TabKey  = 'partners' | 'suppliers'
export type Region  = 'Luzon' | 'Visayas' | 'Mindanao'
export type Status  = 'Active' | 'Inactive' | 'Suspended'

export interface Partner {
  id:            number
  name:          string
  industry:      string
  region:        Region | null
  status:        Status
  contactPerson: string
  email:         string
  phone:         string
  address:       string
  bpCode:        string | null
  tinNumber:     string | null
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
  tinNumber:     string
}
