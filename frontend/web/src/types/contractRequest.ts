import type { UploadedDoc } from './contract'

export type RequestStatus   = 'Pending' | 'Approved' | 'Rejected'
export type RequestFilterTab = 'all' | 'pending' | 'approved' | 'rejected'

export interface ContractRequest {
  id:               string
  businessPartner:  string
  category:         string
  description:      string
  region:           'Luzon' | 'Visayas' | 'Mindanao'
  requestDate:      string
  startDate:        string
  endDate:          string
  status:           RequestStatus
  notes:            string
  rejectionReason:  string
  contractLink:     string
  createdBy:        string
  docs:             UploadedDoc[]
  itemCode:         string
  serialNo:         string
  sbuNumber?:       string
  prsActivityId?:   number
}

export const requestStatusBadge: Record<RequestStatus, string> = {
  'Pending':      'bg-black/5 text-black/50 border-black/10',
  'Approved':     'bg-[#252578]/8 text-[#252578] border-[#252578]/20',
  'Rejected':     'bg-black/5 text-black/40 border-black/8',
}

export function fmtReqDate(iso: string): string {
  return new Date(iso).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
}
