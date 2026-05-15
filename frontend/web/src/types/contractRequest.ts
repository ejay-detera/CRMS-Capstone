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
}

export const requestStatusBadge: Record<RequestStatus, string> = {
  'Pending':      'bg-amber-50 text-amber-700 border-amber-200',
  'Under Review': 'bg-[#2E85D8]/8 text-[#2E85D8] border-[#2E85D8]/20',
  'Approved':     'bg-emerald-50 text-emerald-700 border-emerald-200',
  'Rejected':     'bg-red-50 text-red-600 border-red-200',
}

export const priorityBadge: Record<RequestPriority, string> = {
  'High':   'bg-red-50 text-red-600 border-red-200',
  'Medium': 'bg-amber-50 text-amber-700 border-amber-200',
  'Low':    'bg-black/4 text-black/50 border-black/10',
}

export function fmtReqDate(iso: string): string {
  return new Date(iso).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
}
