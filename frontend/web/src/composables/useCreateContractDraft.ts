import { reactive } from 'vue'
import type { ContractRegion, UploadedDoc } from '@/types/contract'

export interface DraftForm {
  businessPartner: string
  category:        string
  itemCode:        string
  description:     string
  serialNo:        string
  sbuNumber:       string
  region:          ContractRegion | ''
  startDate:       string
  endDate:         string
  prsActivityId?:  number | ''
}

export interface Draft {
  active: boolean
  form:   DraftForm
  docs:   UploadedDoc[]
  role:   'sales' | 'manager' | 'admin' | ''
}

// Module-level singleton
const draft = reactive<Draft>({
  active: false,
  form: {
    businessPartner: '',
    category: '',
    itemCode: '',
    description: '',
    serialNo: '',
    sbuNumber: '',
    region: '',
    startDate: '',
    endDate: '',
    prsActivityId: ''
  },
  docs: [],
  role: ''
})

export function useCreateContractDraft() {
  function saveDraft(form: DraftForm, docs: UploadedDoc[], role: Draft['role']) {
    Object.assign(draft.form, form)
    draft.docs   = docs
    draft.role   = role
    draft.active = true
  }

  function restoreDraft() {
    return draft.active ? { ...draft } : null
  }

  function clearDraft() {
    draft.active = false
    draft.docs   = []
    draft.role   = ''
    Object.keys(draft.form).forEach(k => { 
      (draft.form as any)[k] = '' 
    })
  }

  return { draft, saveDraft, restoreDraft, clearDraft }
}
