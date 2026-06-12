<script setup lang="ts">
import { reactive, computed, ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { ArrowLeft, ScanLine } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { useToast } from '@/composables/useToast'
import { useAuth } from '@/composables/useAuth'
import { useApiCache } from '@/composables/useApiCache'
import OCRUploadDialog from '@/views/sales/Contracts/OCRUploadDialog.vue'
import DocumentUpload from '@/views/sales/Contracts/DocumentUpload.vue'
import type { ContractRegion, UploadedDoc } from '@/types/contract'

const router = useRouter()
const { success, error } = useToast()
const { state: authState } = useAuth()
const { invalidateContracts, invalidateRequests } = useApiCache()

const showOCR      = ref(false)
const loading      = ref(false)
const contractDocs = ref<UploadedDoc[]>([])

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
})

const errors = computed(() => ({
  businessPartner: touched.businessPartner && !form.businessPartner.trim()
    ? 'Business partner is required.'
    : touched.businessPartner && suspendedVendorMatch.value
    ? 'This vendor is suspended and cannot be assigned to a new contract.'
    : '',
  category:        touched.category        && !form.category                  ? 'Category is required.' : '',
  itemCode:        touched.itemCode        && !form.itemCode.trim()           ? 'Item code is required.' : '',
  description:     touched.description     && !form.description.trim()        ? 'Description is required.' : '',
  serialNo:        touched.serialNo        && !form.serialNo.trim()           ? 'Serial number is required.' : '',
  sbuNumber:       touched.sbuNumber       && !form.sbuNumber.trim()          ? 'SBU number is required.' : '',
  region:          touched.region          && !form.region                    ? 'Region is required.' : '',
  startDate:       touched.startDate       && !form.startDate                 ? 'Start date is required.' : '',
  endDate:         touched.endDate && !form.endDate
    ? 'End date is required.'
    : touched.endDate && form.startDate && form.endDate && form.endDate <= form.startDate
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
    form.businessPartner.trim() &&
    !suspendedVendorMatch.value &&
    form.category &&
    form.itemCode.trim() &&
    form.description.trim() &&
    form.serialNo.trim() &&
    form.sbuNumber.trim() &&
    form.region &&
    form.startDate &&
    form.endDate &&
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

async function handleSubmit() {
  touchAll()
  if (!isValid()) return

  loading.value = true
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
    invalidateContracts()
    invalidateRequests()
    success('Contract created', `${form.businessPartner}'s contract has been saved.`)
    router.push(`/admin/contracts/${data.data.contract_id}`)
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

onMounted(() => {
  fetchPartnerNames()
})

const showSuggestions = ref(false)
const suggestionsContainer = ref<HTMLElement | null>(null)

const partnerSuggestions = computed(() => {
  const query = form.businessPartner.trim().toLowerCase()
  const available = vendorOptions.value.filter(v => v.status !== 'Suspended')
  if (!query) return available.map(v => v.name)
  return available.filter(v => v.name.toLowerCase().includes(query)).map(v => v.name)
})

const suspendedVendorMatch = computed(() => {
  const name = form.businessPartner.trim().toLowerCase()
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
</script>

<template>
  <div class="p-8 space-y-6">

    <!-- Header -->
    <div class="flex items-center gap-4">
      <button @click="router.push('/admin/contracts')"
        class="flex items-center justify-center w-9 h-9 rounded-lg border border-black/10 bg-white hover:bg-black/4 text-black/50 hover:text-black transition shrink-0">
        <ArrowLeft class="w-4 h-4" />
      </button>
      <div class="flex-1">
        <h1 class="text-xl font-semibold text-black">Create New Contract</h1>
        <p class="text-sm text-black/40 mt-0.5">Fill in the details below to create a new contract.</p>
      </div>
      <Button @click="showOCR = true" variant="outline"
        class="h-9 gap-2 text-sm font-medium border-[#252578]/25 text-[#252578] hover:bg-[#252578]/5 hover:border-[#252578]/40 shrink-0">
        <ScanLine class="w-4 h-4" />
        Autofill with OCR
      </Button>
    </div>

    <!-- Form card -->
    <div class="bg-white rounded-lg border border-black/8 shadow-sm overflow-hidden">

      <!-- Section: Contract Info -->
      <div class="px-6 py-5 border-b border-black/6">
        <h2 class="text-xs font-semibold text-black/40 uppercase tracking-widest mb-4">Contract Info</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

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
        <DocumentUpload v-model="contractDocs" />
      </div>

      <!-- Footer -->
      <div class="px-6 py-4 flex items-center justify-end gap-3">
        <Button variant="outline" @click="router.push('/admin/contracts')"
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

  <OCRUploadDialog v-model:open="showOCR" />
</template>
