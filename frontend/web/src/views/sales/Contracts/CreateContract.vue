<script setup lang="ts">
import { reactive, computed, ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { ArrowLeft, ScanLine } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { useToast } from '@/composables/useToast'
import { useAuth } from '@/composables/useAuth'
import { useApiCache } from '@/composables/useApiCache'
import DocumentUpload from './DocumentUpload.vue'
import type { ContractRegion, UploadedDoc } from '@/types/contract'
import { useCreateContractDraft } from '@/composables/useCreateContractDraft'

import ConfirmationDialog from '@/components/shared/ConfirmationDialog.vue'

const router = useRouter()
const route = useRoute()
const { success, error } = useToast()
const { state: authState } = useAuth()
const { invalidateContracts, invalidateRequests } = useApiCache()
const { draft, saveDraft, restoreDraft, clearDraft } = useCreateContractDraft()


const loading      = ref(false)
const showConfirm  = ref(false)
const contractDocs = ref<UploadedDoc[]>([])

const today = new Date().toISOString().split('T')[0]
const maxDate = (() => {
  const d = new Date()
  d.setFullYear(d.getFullYear() + 100)
  return d.toISOString().split('T')[0]
})()
const minDate = '1999-01-01'

interface FormState {
  businessPartner: string
  category:        string
  itemCode:        string
  description:     string
  serialNo:        string
  sbuNumber:       string
  region:          ContractRegion | ''
  startDate:       string
  endDate:         string
  prsActivityId:   number | ''
}

const form = reactive<FormState>({
  businessPartner: '',
  category:        '',
  itemCode:        '',
  description:     '',
  serialNo:        '',
  sbuNumber:       '',
  region:          '',
  startDate:       '',
  endDate:         '',
  prsActivityId:   '',
})

const touched = reactive<Record<keyof FormState, boolean>>({
  businessPartner: false,
  category:        false,
  itemCode:        false,
  description:     false,
  serialNo:        false,
  sbuNumber:       false,
  region:          false,
  startDate:       false,
  endDate:         false,
  prsActivityId:   false,
})

const errors = computed(() => ({
  businessPartner: touched.businessPartner && !String(form.businessPartner || '').trim()
    ? 'Business partner is required.'
    : touched.businessPartner && suspendedVendorMatch.value
    ? 'This vendor is suspended and cannot be assigned to a new contract.'
    : '',
  category:        touched.category        && !form.category                  ? 'Category is required.' : '',
  itemCode:        touched.itemCode        && !String(form.itemCode || '').trim()
    ? 'Item code is required.'
    : touched.itemCode && !/^ITM-\d{4}$/.test(String(form.itemCode || '').trim())
    ? 'Item code must start with ITM- followed by 4 digits (e.g., ITM-0041).'
    : '',
  description:     touched.description     && !String(form.description || '').trim()        ? 'Description is required.' : '',
  serialNo:        touched.serialNo        && !String(form.serialNo || '').trim()
    ? 'Serial number is required.'
    : touched.serialNo && !/^SN-\d{4}-\d{4}$/.test(String(form.serialNo || '').trim())
    ? 'Serial number must be in the format SN-YYYY-xxxx (e.g., SN-2024-0041).'
    : '',
  sbuNumber:       touched.sbuNumber       && !String(form.sbuNumber || '').trim()
    ? 'SBU number is required.'
    : touched.sbuNumber && !/^SBU-\d{3}$/.test(String(form.sbuNumber || '').trim())
    ? 'SBU number must start with SBU- followed by 3 digits (e.g., SBU-001).'
    : '',
  region:          touched.region          && !form.region                    ? 'Region is required.' : '',
  startDate:       touched.startDate       && !form.startDate
    ? 'Start date is required.'
    : touched.startDate && form.startDate < minDate
    ? 'Start date cannot be before 1999.'
    : touched.startDate && form.startDate > maxDate
    ? 'Start date cannot exceed 100 years from today.'
    : '',
  endDate:         touched.endDate && !form.endDate
    ? 'End date is required.'
    : touched.endDate && form.endDate > maxDate
    ? 'End date cannot exceed 100 years from today.'
    : touched.endDate && form.startDate && form.endDate <= form.startDate
    ? 'End date must be after start date.'
    : '',
}))

const categories = [
  'Service Agreement',
  'Partnership Agreement',
  'Supply Contract',
  'Equipment Lease',
  'Equipment Maintenance',
]

const regions: ContractRegion[] = ['Luzon', 'Visayas', 'Mindanao']

function touchAll() {
  (Object.keys(touched) as (keyof FormState)[]).forEach(k => { touched[k] = true })
}

function isValid() {
  return (
    String(form.businessPartner || '').trim() &&
    !suspendedVendorMatch.value &&
    form.category &&
    String(form.itemCode || '').trim() && /^ITM-\d{4}$/.test(String(form.itemCode || '').trim()) &&
    String(form.description || '').trim() &&
    String(form.serialNo || '').trim() && /^SN-\d{4}-\d{4}$/.test(String(form.serialNo || '').trim()) &&
    String(form.sbuNumber || '').trim() && /^SBU-\d{3}$/.test(String(form.sbuNumber || '').trim()) &&
    form.region &&
    form.startDate && form.startDate >= minDate && form.startDate <= maxDate &&
    form.endDate && form.endDate <= maxDate &&
    form.endDate > form.startDate
  )
}

const isUploadingOrScanFailed = computed(() => {
  return contractDocs.value.some(d => d.uploadStatus === 'uploading' || d.uploadStatus === 'scanning' || d.uploadStatus === 'error')
})

function getApiErrorMessage(data: any) {
  if (data?.errors && typeof data.errors === 'object') {
    const firstError = Object.values(data.errors).flat().find(Boolean)
    if (firstError) return String(firstError)
  }

  return data?.message ?? 'Something went wrong.'
}

function handleSubmit() {
  touchAll()
  if (!isValid()) return
  showConfirm.value = true
}

async function confirmSubmit() {
  loading.value = true
  showConfirm.value = false
  try {
    const payload = {
      bp_name:       form.businessPartner,
      category:      form.category,
      item_code:     form.itemCode,
      description:   form.description,
      serial_number: form.serialNo,
      sbu_number:    form.sbuNumber,
      region:        form.region,
      start_date:    form.startDate,
      end_date:      form.endDate,
      document_ids:  contractDocs.value.filter(d => d.id).map(d => d.id),
      prs_activity_id: form.prsActivityId ? Number(form.prsActivityId) : null,
    }

    const res = await fetch(`${import.meta.env.VITE_CONTRACT_API_URL}/contracts`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': `Bearer ${authState.token}`,
      },
      body: JSON.stringify(payload),
    })

    const data = await res.json().catch(() => ({}))

    if (!res.ok) {
      error('Failed to create contract', getApiErrorMessage(data))
      return
    }

    contractDocs.value = []
    clearDraft()
    invalidateContracts()
    invalidateRequests()
    success('Contract created', `${form.businessPartner}'s contract has been saved.`)
    router.push(`/sales/contracts/${data.data.contract_id}`)
  } catch {
    error('Network error', 'Could not reach the server. Please try again.')
  } finally {
    loading.value = false
  }
}

import { onClickOutside } from '@vueuse/core'

interface VendorOption { name: string; status: string }
const vendorOptions = ref<VendorOption[]>([])

async function fetchPartnerNames() {
  try {
    const apiBase = import.meta.env.VITE_VENDOR_API_URL || 'http://localhost:8001/api'
    const headers = {
      'Authorization': `Bearer ${authState.token || ''}`,
      'Accept': 'application/json'
    }
    const [resP, resS] = await Promise.all([
      fetch(`${apiBase}/partners?per_page=100`, { headers }),
      fetch(`${apiBase}/suppliers?per_page=100`, { headers })
    ])
    const vendors: VendorOption[] = []
    if (resP.ok) {
      const json = await resP.json()
      vendors.push(...(json.data || []).map((p: any) => ({ name: p.partner_name, status: p.status })))
    }
    if (resS.ok) {
      const json = await resS.json()
      vendors.push(...(json.data || []).map((s: any) => ({ name: s.supplier_name, status: s.status })))
    }
    const seen = new Set<string>()
    vendorOptions.value = vendors.filter(v => {
      const key = v.name.toLowerCase()
      if (seen.has(key)) return false
      seen.add(key)
      return true
    })
  } catch (err) {
    console.error('Failed to fetch partner names:', err)
  }
}

const prefilledFromPrs = ref(false)

onMounted(() => {
  fetchPartnerNames()
  fetchPrsActivities()

  // 1. Restore draft if active
  if (draft.active && draft.role === 'sales') {
    const saved = restoreDraft()
    if (saved) {
      Object.assign(form, saved.form)
      contractDocs.value = [...saved.docs]
      if (saved.form.prsActivityId) prefilledFromPrs.value = true
    }
    clearDraft()
    return
  }

  // 2. Otherwise auto-populate fields from PRS integration query params
  const prsIdParam = route.query.prs_activity_id
  if (prsIdParam) {
    form.prsActivityId = Number(prsIdParam)
    prefilledFromPrs.value = true
  }
  const bpNameParam = route.query.bp_name
  if (bpNameParam) {
    form.businessPartner = String(bpNameParam)
  }
  const sbuParam = route.query.sbu
  if (sbuParam) {
    form.sbuNumber = String(sbuParam)
  }
  const itemCodeParam = route.query.item_code
  if (itemCodeParam) {
    form.itemCode = String(itemCodeParam)
  }
  const descParam = route.query.description
  if (descParam) {
    form.description = String(descParam)
  }
})

function previewDocument(doc: UploadedDoc) {
  if (!doc.id) return
  saveDraft(form, contractDocs.value, 'sales')
  router.push({
    path: `/sales/contracts/preview/documents/${doc.id}`,
    query: {
      from: 'create',
      role: 'sales',
      docName: doc.name,
      docSize: String(doc.size),
      docType: doc.type,
    }
  })
}

const showSuggestions = ref(false)
const suggestionsContainer = ref<HTMLElement | null>(null)

const partnerSuggestions = computed(() => {
  const query = String(form.businessPartner || '').trim().toLowerCase()
  const available = vendorOptions.value.filter(v => v.status !== 'Suspended')
  if (!query) return available.map(v => v.name)
  return available.filter(v => v.name.toLowerCase().includes(query)).map(v => v.name)
})

const suspendedVendorMatch = computed(() => {
  const name = String(form.businessPartner || '').trim().toLowerCase()
  if (!name) return false
  return vendorOptions.value.some(v => v.name.toLowerCase() === name && v.status === 'Suspended')
})

function selectSuggestion(name: string) {
  form.businessPartner = name
  showSuggestions.value = false
  touched.businessPartner = true
}

onClickOutside(suggestionsContainer, () => {
  showSuggestions.value = false
})

interface PrsActivityOption {
  id: number
  institution: string
  sbu: string | null
  description: string | null
  activity_date: string | null
  product_name: string | null
  status: string
}

const prsActivities = ref<PrsActivityOption[]>([])
const loadingPrsActivities = ref(false)
const showPrsDropdown = ref(false)
const prsContainer = ref<HTMLElement | null>(null)

async function fetchPrsActivities() {
  loadingPrsActivities.value = true
  try {
    const apiBase = import.meta.env.VITE_PRS_API_URL || '/api/prs'
    const headers = {
      'Authorization': `Bearer ${authState.token || ''}`,
      'Accept': 'application/json'
    }
    const res = await fetch(`${apiBase}/analytics/submissions?activity_type=Closing&per_page=100`, { headers })
    if (res.ok) {
      const json = await res.json()
      prsActivities.value = (json.data || []).map((item: any) => ({
        id: item.id,
        institution: item.institution,
        sbu: item.sbu,
        description: item.description,
        activity_date: item.activity_date,
        product_name: item.product_name || (item.demo_submissions?.[0]?.product?.product_name) || null,
        status: item.status
      }))
    }
  } catch (err) {
    console.error('Failed to fetch PRS activities:', err)
  } finally {
    loadingPrsActivities.value = false
  }
}

function selectPrsActivity(activity: PrsActivityOption) {
  form.prsActivityId = activity.id
  if (activity.institution) {
    form.businessPartner = String(activity.institution)
  }
  if (activity.sbu) {
    form.sbuNumber = String(activity.sbu)
  }
  if (activity.description) {
    form.description = String(activity.description)
  }
  if (activity.product_name) {
    form.itemCode = String(activity.product_name)
  }
  showPrsDropdown.value = false
}

onClickOutside(prsContainer, () => {
  showPrsDropdown.value = false
})
</script>

<template>
  <div class="p-8 space-y-6">

    <!-- Header -->
    <div class="flex items-center gap-4">
      <button @click="router.push('/sales/contracts')"
        class="flex items-center justify-center w-9 h-9 rounded-lg border border-black/10 bg-white hover:bg-black/4 text-black/50 hover:text-black transition shrink-0">
        <ArrowLeft class="w-4 h-4" />
      </button>
      <div class="flex-1">
        <h1 class="text-xl font-semibold text-black">Create New Contract</h1>
        <p class="text-sm text-black/40 mt-0.5">Fill in the details below to create a new contract.</p>
      </div>
    </div>

    <!-- Form card -->
    <div class="bg-white rounded-lg border border-black/8 shadow-sm overflow-hidden">

      <!-- Section: Contract Info -->
      <div class="px-6 py-5 border-b border-black/6">
        <h2 class="text-xs font-semibold text-black/40 uppercase tracking-widest mb-4">Contract Info</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

          <!-- Business Partner -->
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-black/55">Business Partner <span class="text-red-500">*</span></label>
            <div class="relative w-full" ref="suggestionsContainer">
              <input
                v-model="form.businessPartner"
                @focus="showSuggestions = true"
                @blur="touched.businessPartner = true"
                type="text"
                placeholder="e.g. Globe Telecom"
                class="h-9 rounded-lg border px-3 text-sm placeholder:text-black/25 focus:outline-none focus:ring-2 transition w-full"
                :class="errors.businessPartner
                  ? 'border-red-400 focus:border-red-400 focus:ring-red-200/50'
                  : 'border-black/12 focus:border-[#2E85D8] focus:ring-[#2E85D8]/15'"
              />
              <div
                v-if="showSuggestions && partnerSuggestions.length > 0"
                class="absolute left-0 right-0 top-[calc(100%+4px)] z-50 max-h-48 overflow-y-auto bg-white border border-black/8 rounded-lg shadow-lg py-1 font-poppins divide-y divide-black/[0.04]"
              >
                <button
                  v-for="name in partnerSuggestions"
                  :key="name"
                  type="button"
                  @click="selectSuggestion(name)"
                  class="w-full text-left px-3.5 py-2 text-xs text-black/75 hover:bg-black/[0.03] hover:text-[#2E85D8] font-medium transition-colors"
                >
                  {{ name }}
                </button>
              </div>
            </div>
            <p v-if="errors.businessPartner" class="text-xs text-red-500">{{ errors.businessPartner }}</p>
          </div>

          <!-- Category -->
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-black/55">Category <span class="text-red-500">*</span></label>
            <select
              v-model="form.category"
              @blur="touched.category = true"
              class="h-9 rounded-lg border px-3 text-sm bg-white focus:outline-none focus:ring-2 transition"
              :class="[
                !form.category ? 'text-black/30' : 'text-black',
                errors.category
                  ? 'border-red-400 focus:border-red-400 focus:ring-red-200/50'
                  : 'border-black/12 focus:border-[#2E85D8] focus:ring-[#2E85D8]/15'
              ]">
              <option value="" disabled>Select category</option>
              <option v-for="c in categories" :key="c" :value="c">{{ c }}</option>
            </select>
            <p v-if="errors.category" class="text-xs text-red-500">{{ errors.category }}</p>
          </div>

          <!-- Connected PRS Activity ID (Optional) -->
          <div class="flex flex-col gap-1.5 relative" ref="prsContainer">
            <label class="text-xs font-semibold text-black/55">Connected PRS Activity ID (Optional)</label>
            <div class="relative w-full">
              <input
                v-model="form.prsActivityId"
                @focus="!prefilledFromPrs && (showPrsDropdown = true)"
                type="number"
                :readonly="prefilledFromPrs"
                placeholder="e.g. 12 (Select or type)"
                class="h-9 rounded-lg border px-3 text-sm placeholder:text-black/25 focus:outline-none focus:ring-2 transition w-full border-black/12 focus:border-[#2E85D8] focus:ring-[#2E85D8]/15"
                :class="prefilledFromPrs ? 'bg-black/[0.02] border-black/8 cursor-not-allowed' : ''"
              />
              <p v-if="prefilledFromPrs" class="text-xs text-emerald-600 mt-1 font-medium">
                ✓ Linked from PRS Activity #{{ form.prsActivityId }}
              </p>
              <div
                v-if="showPrsDropdown && prsActivities.length > 0"
                class="absolute left-0 right-0 top-[calc(100%+4px)] z-50 max-h-48 overflow-y-auto bg-white border border-black/8 rounded-lg shadow-lg py-1 font-poppins divide-y divide-black/[0.04]"
              >
                <button
                  v-for="act in prsActivities"
                  :key="act.id"
                  type="button"
                  @click="selectPrsActivity(act)"
                  class="w-full text-left px-3.5 py-2 text-xs text-black/75 hover:bg-black/[0.03] hover:text-[#2E85D8] font-medium transition-colors"
                >
                  <div class="flex items-center justify-between font-semibold">
                    <span>ID: {{ act.id }} - {{ act.institution }}</span>
                    <span :class="[
                      'text-[9px] px-1.5 py-0.5 rounded font-bold uppercase tracking-wider',
                      act.status === 'Approved' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' :
                      act.status === 'Pending' ? 'bg-amber-50 text-amber-700 border border-amber-200' :
                      'bg-red-50 text-red-700 border border-red-200'
                    ]">
                      {{ act.status }}
                    </span>
                  </div>
                  <div class="text-[10px] text-black/40">{{ act.description || 'No description' }} | {{ act.sbu || 'No SBU' }}</div>
                </button>
              </div>
            </div>
          </div>

        </div>
      </div>

      <!-- Section: Item Details -->
      <div class="px-6 py-5 border-b border-black/6">
        <h2 class="text-xs font-semibold text-black/40 uppercase tracking-widest mb-4">Item Details</h2>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">

          <!-- Item Code -->
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-black/55">Item Code <span class="text-red-500">*</span></label>
            <input
              v-model="form.itemCode"
              @blur="touched.itemCode = true"
              type="text"
              placeholder="e.g. ITM-0041"
              class="h-9 rounded-lg border px-3 text-sm font-mono placeholder:text-black/25 focus:outline-none focus:ring-2 transition"
              :class="errors.itemCode
                ? 'border-red-400 focus:border-red-400 focus:ring-red-200/50'
                : 'border-black/12 focus:border-[#2E85D8] focus:ring-[#2E85D8]/15'"
            />
            <p v-if="errors.itemCode" class="text-xs text-red-500">{{ errors.itemCode }}</p>
          </div>

          <!-- Description -->
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-black/55">Description <span class="text-red-500">*</span></label>
            <input
              v-model="form.description"
              @blur="touched.description = true"
              type="text"
              placeholder="e.g. Network Infrastructure"
              class="h-9 rounded-lg border px-3 text-sm placeholder:text-black/25 focus:outline-none focus:ring-2 transition"
              :class="errors.description
                ? 'border-red-400 focus:border-red-400 focus:ring-red-200/50'
                : 'border-black/12 focus:border-[#2E85D8] focus:ring-[#2E85D8]/15'"
            />
            <p v-if="errors.description" class="text-xs text-red-500">{{ errors.description }}</p>
          </div>

          <!-- Serial No -->
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-black/55">Serial No <span class="text-red-500">*</span></label>
            <input
              v-model="form.serialNo"
              @blur="touched.serialNo = true"
              type="text"
              placeholder="e.g. SN-2024-0041"
              class="h-9 rounded-lg border px-3 text-sm font-mono placeholder:text-black/25 focus:outline-none focus:ring-2 transition"
              :class="errors.serialNo
                ? 'border-red-400 focus:border-red-400 focus:ring-red-200/50'
                : 'border-black/12 focus:border-[#2E85D8] focus:ring-[#2E85D8]/15'"
            />
            <p v-if="errors.serialNo" class="text-xs text-red-500">{{ errors.serialNo }}</p>
          </div>

          <!-- SBU Number -->
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-black/55">SBU Number <span class="text-red-500">*</span></label>
            <input
              v-model="form.sbuNumber"
              @blur="touched.sbuNumber = true"
              type="text"
              placeholder="e.g. SBU-001"
              class="h-9 rounded-lg border px-3 text-sm font-mono placeholder:text-black/25 focus:outline-none focus:ring-2 transition"
              :class="errors.sbuNumber
                ? 'border-red-400 focus:border-red-400 focus:ring-red-200/50'
                : 'border-black/12 focus:border-[#2E85D8] focus:ring-[#2E85D8]/15'"
            />
            <p v-if="errors.sbuNumber" class="text-xs text-red-500">{{ errors.sbuNumber }}</p>
          </div>

        </div>
      </div>

      <!-- Section: Schedule & Location -->
      <div class="px-6 py-5 border-b border-black/6">
        <h2 class="text-xs font-semibold text-black/40 uppercase tracking-widest mb-4">Schedule & Location</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

          <!-- Region -->
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-black/55">Region <span class="text-red-500">*</span></label>
            <select
              v-model="form.region"
              @blur="touched.region = true"
              class="h-9 rounded-lg border px-3 text-sm bg-white focus:outline-none focus:ring-2 transition"
              :class="[
                !form.region ? 'text-black/30' : 'text-black',
                errors.region
                  ? 'border-red-400 focus:border-red-400 focus:ring-red-200/50'
                  : 'border-black/12 focus:border-[#2E85D8] focus:ring-[#2E85D8]/15'
              ]">
              <option value="" disabled>Select region</option>
              <option v-for="r in regions" :key="r" :value="r">{{ r }}</option>
            </select>
            <p v-if="errors.region" class="text-xs text-red-500">{{ errors.region }}</p>
          </div>

          <!-- Start Date -->
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-black/55">Start Date <span class="text-red-500">*</span></label>
            <input
              v-model="form.startDate"
              @blur="touched.startDate = true"
              type="date"
              :min="minDate"
              :max="maxDate"
              class="h-9 rounded-lg border px-3 text-sm focus:outline-none focus:ring-2 transition"
              :class="errors.startDate
                ? 'border-red-400 focus:border-red-400 focus:ring-red-200/50'
                : 'border-black/12 focus:border-[#2E85D8] focus:ring-[#2E85D8]/15'"
            />
            <p v-if="errors.startDate" class="text-xs text-red-500">{{ errors.startDate }}</p>
          </div>

          <!-- End Date -->
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-black/55">End Date <span class="text-red-500">*</span></label>
            <input
              v-model="form.endDate"
              @blur="touched.endDate = true"
              type="date"
              :min="form.startDate || today"
              :max="maxDate"
              class="h-9 rounded-lg border px-3 text-sm focus:outline-none focus:ring-2 transition"
              :class="errors.endDate
                ? 'border-red-400 focus:border-red-400 focus:ring-red-200/50'
                : 'border-black/12 focus:border-[#2E85D8] focus:ring-[#2E85D8]/15'"
            />
            <p v-if="errors.endDate" class="text-xs text-red-500">{{ errors.endDate }}</p>
          </div>

        </div>
      </div>

      <!-- Section: Documents -->
      <div class="px-6 py-5 border-b border-black/6">
        <h2 class="text-xs font-semibold text-black/40 uppercase tracking-widest mb-1">Documents</h2>
        <p class="text-xs text-black/35 mb-4">Attach all documents. Accepted formats: PDF, DOCX · Max 10 MB per file.</p>
        <DocumentUpload v-model="contractDocs" :on-preview="previewDocument" />
      </div>

      <!-- Footer -->
      <div class="px-6 py-4 flex items-center justify-end gap-3">
        <Button variant="outline" @click="router.push('/sales/contracts')"
          class="h-9 px-5 text-sm border-black/15 text-black/60 hover:text-black">
          Cancel
        </Button>
        <Button @click="handleSubmit" :disabled="loading || isUploadingOrScanFailed"
          class="h-9 px-5 text-sm bg-[#252578] hover:bg-[#2F2F73] text-white shadow-sm disabled:opacity-50 disabled:cursor-not-allowed">
          {{ loading ? 'Saving…' : 'Create Contract' }}
        </Button>
      </div>

    </div>
  </div>



  <ConfirmationDialog
    v-model:open="showConfirm"
    title="Create Contract"
    description="Are you sure you want to create this contract? This will save the contract record to the system."
    confirm-label="Create"
    variant="default"
    :loading="loading"
    @confirm="confirmSubmit"
  />
</template>
