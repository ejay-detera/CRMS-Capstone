export type RequestStatus   = 'Pending' | 'Under Review' | 'Approved' | 'Rejected'
export type RequestPriority = 'High' | 'Medium' | 'Low'
export type RequestFilterTab = 'all' | 'pending' | 'reviewing' | 'approved' | 'rejected'

export interface ContractRequest {
  id:               string
  businessPartner:  string
  category:         string
  description:      string
  region:           'Luzon' | 'Visayas' | 'Mindanao'
  requestDate:      string
  startDate:        string
  endDate:          string
  priority:         RequestPriority
  status:           RequestStatus
  notes:            string
  rejectionReason:  string
  contractLink:     string
  createdBy:        string
}

export const requestStatusBadge: Record<RequestStatus, string> = {
  'Pending':      'bg-black/5 text-black/50 border-black/10',
  'Under Review': 'bg-[#2E85D8]/8 text-[#2E85D8] border-[#2E85D8]/20',
  'Approved':     'bg-[#252578]/8 text-[#252578] border-[#252578]/20',
  'Rejected':     'bg-black/5 text-black/40 border-black/8',
}

export const priorityBadge: Record<RequestPriority, string> = {
  'High':   'bg-black/8 text-black border-black/15',
  'Medium': 'bg-black/4 text-black/60 border-black/10',
  'Low':    'bg-transparent text-black/35 border-black/8',
}

export function fmtReqDate(iso: string): string {
  return new Date(iso).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
}
