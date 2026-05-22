export type ContractWorkflowStatus  = 'Notarized PDF' | 'Client Review' | 'SBSI Review'
export type ContractApprovalStatus  = 'Pending' | 'Approved' | 'Rejected'

export interface UploadedDoc {
  file?:       File
  name:        string
  size:        number
  type:        'pdf' | 'docx'
  previewUrl?: string
}
export type ContractRegion = 'Luzon' | 'Visayas' | 'Mindanao'
export type FilterTab      = 'all' | 'active' | 'expiring' | 'expired'
export type StatusFilter   = '' | ContractApprovalStatus | ContractWorkflowStatus

export interface Contract {
  id:              string
  businessPartner: string
  category:        string
  itemCode:        string
  description:     string
  serialNo:        string
  sbuNumber?:      string
  region:          ContractRegion
  startDate:       string
  endDate:         string
  approvalStatus:  ContractApprovalStatus
  workflowStatus:  ContractWorkflowStatus | null
  contractLink:    string
  createdBy:       string
  docs:            UploadedDoc[]
}

export const approvalStatusBadge: Record<ContractApprovalStatus, string> = {
  'Pending':  'bg-black/5 text-black/50 border-black/10',
  'Approved': 'bg-emerald-50 text-emerald-700 border-emerald-200',
  'Rejected': 'bg-red-50 text-red-600 border-red-200',
}

export const workflowStatusBadge: Record<ContractWorkflowStatus, string> = {
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
