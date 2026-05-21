<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import {
  ArrowLeft, ClipboardList,
  Bell, CheckCircle, AlertCircle, ExternalLink, XCircle, RefreshCw,
  FileX, FileType2, FilePenLine, Loader2
} from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { useAuth } from '@/composables/useAuth'
import { useToast } from '@/composables/useToast'
import { useApiCache } from '@/composables/useApiCache'
import { requestStatusBadge, fmtReqDate } from '@/types/contractRequest'
import type { ContractRequest } from '@/types/contractRequest'
import { safeHref } from '@/utils/sanitize'

const route = useRoute()
const router = useRouter()
const { state: authState } = useAuth()
const { success, error } = useToast()
const { state: cacheState, fetchRequests, updateRequestStatusInCache, updateRequestInCache } = useApiCache()

const id = route.params.id as string
const request = ref<ContractRequest | null>(null)
const loading = ref(true)

const backPath = computed(() => {
  if (route.path.startsWith('/manager')) return '/manager/contract-requests'
  return '/sales/contract-requests'
})

const isManager = computed(() => route.path.startsWith('/manager'))

// --- Follow up handling (Sales) ---
const followedUpIds = ref<string[]>(JSON.parse(localStorage.getItem('followed_up_requests') || '[]'))

const isFollowedUp = computed(() => {
  return request.value ? followedUpIds.value.includes(request.value.id) : false
})

function handleFollowUp() {
  if (!request.value) return
  const rid = request.value.id
  if (followedUpIds.value.includes(rid)) return
  followedUpIds.value.push(rid)
  localStorage.setItem('followed_up_requests', JSON.stringify(followedUpIds.value))
  success('Follow-up sent', `The manager has been notified about ${request.value.businessPartner}.`)
}

// --- Action handling (Manager) ---
const showRejectInput = ref(false)
const rejectReason = ref('')

function handleApprove() {
  if (!request.value) return
  updateRequestStatusInCache(id, 'Approved')
  success('Request approved', `${request.value.businessPartner}'s contract request has been approved.`)
  // Refresh local ref
  loadLocalRequest()
}

function handleSetReviewing() {
  if (!request.value) return
  updateRequestStatusInCache(id, 'Under Review')
  success('Status updated', `${request.value.businessPartner}'s request is now under review.`)
  loadLocalRequest()
}

function confirmReject() {
  if (!request.value || !rejectReason.value.trim()) return
  updateRequestStatusInCache(id, 'Rejected', { rejectionReason: rejectReason.value.trim() })
  success('Request rejected', `${request.value.businessPartner}'s contract request has been rejected.`)
  showRejectInput.value = false
  rejectReason.value = ''
  loadLocalRequest()
}

function loadLocalRequest() {
  const req = (cacheState.requests || []).find(r => r.id === id)
  if (req) {
    request.value = req
  }
}

async function loadRequest() {
  loadLocalRequest()
  if (request.value) {
    loading.value = false
  } else {
    loading.value = true
  }

  try {
    const userId = isManager.value ? undefined : authState.user?.id
    await fetchRequests(userId)
    loadLocalRequest()
  } catch {
    error('Error loading requests', 'Could not load contract requests.')
  } finally {
    loading.value = false
  }
}

onMounted(loadRequest)

// --- Avatar coloring helpers ---
const palette = ['#252578', '#2E85D8', '#2F2F73']
function initials(name: string) {
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}
function avatarColor(name: string) {
  let h = 0
  for (const c of name) h = (h * 31 + c.charCodeAt(0)) & 0xffff
  return palette[h % palette.length]
}

// --- Inline edit state ---
const isEditing = ref(false)
const savingEdit = ref(false)

const editForm = reactive({
  businessPartner: '',
  category:        '',
  itemCode:        '',
  description:     '',
  serialNo:        '',
  sbuNumber:       '',
  region:          '' as ContractRequest['region'] | '',
  startDate:       '',
  endDate:         '',
})

const touched = reactive<Record<string, boolean>>({
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

const dateError = computed(() =>
  touched.startDate && touched.endDate && editForm.startDate && editForm.endDate
    ? editForm.endDate <= editForm.startDate ? 'End date must be after start date.' : ''
    : ''
)

const isFormValid = computed(() =>
  !!editForm.businessPartner &&
  !!editForm.category &&
  !!editForm.itemCode &&
  !!editForm.description &&
  !!editForm.serialNo &&
  !!editForm.sbuNumber &&
  !!editForm.region &&
  !!editForm.startDate &&
  !!editForm.endDate &&
  !dateError.value
)

function fieldCls(field: string, invalid: boolean) {
  return touched[field] && invalid
    ? 'border-red-400 focus:border-red-400 focus:ring-red-200/50'
    : 'border-black/12 focus:border-[#2E85D8] focus:ring-[#2E85D8]/15'
}

function startEdit() {
  if (!request.value) return
  Object.assign(editForm, {
    businessPartner: request.value.businessPartner,
    category:        request.value.category,
    itemCode:        request.value.itemCode ?? '',
    description:     request.value.description,
    serialNo:        request.value.serialNo ?? '',
    sbuNumber:       request.value.sbuNumber ?? '',
    region:          request.value.region,
    startDate:       request.value.startDate ? request.value.startDate.split('T')[0] : '',
    endDate:         request.value.endDate ? request.value.endDate.split('T')[0] : '',
  })
  Object.keys(touched).forEach(k => (touched[k] = false))
  isEditing.value = true
}

function cancelEdit() {
  isEditing.value = false
}

const apiBase = import.meta.env.VITE_CONTRACT_API_URL as string

const STATUS_MAP: Record<string, any> = {
  'Pending':      'Pending',
  'Under Review': 'Under Review',
  'Approved':     'Approved',
  'Rejected':     'Rejected',
}

async function saveEdit() {
  Object.keys(touched).forEach(k => (touched[k] = true))
  if (!isFormValid.value) return

  savingEdit.value = true
  try {
    const payload = {
      bp_name:       editForm.businessPartner,
      category:      editForm.category,
      item_code:     editForm.itemCode,
      description:   editForm.description,
      serial_number: editForm.serialNo,
      sbu_number:    editForm.sbuNumber,
      region:        editForm.region,
      start_date:    editForm.startDate,
      end_date:      editForm.endDate,
    }

    const dbId = parseInt(id.replace('REQ-', ''), 10)
    const res = await fetch(`${apiBase}/contracts/${dbId}`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': `Bearer ${authState.token}`,
      },
      body: JSON.stringify(payload),
    })

    const data = await res.json()

    if (!res.ok) {
      error('Failed to save', data.message ?? 'Something went wrong.')
      return
    }

    const user = authState.user
    const isCreatedByCurrentUser = user !== null && data.data.created_by === user.id
    const createdBy = isCreatedByCurrentUser
      ? `${user.first_name || ''} ${user.last_name || ''}`.trim() || 'Me'
      : data.data.created_by ? `User #${data.data.created_by}` : '—'

    const updatedRequest: ContractRequest = {
      id:              `REQ-${String(data.data.contract_id).padStart(3, '0')}`,
      businessPartner: data.data.bp_name        ?? '',
      category:        data.data.category       ?? '',
      description:     data.data.description    ?? '',
      region:          (data.data.region        ?? 'Luzon') as ContractRequest['region'],
      requestDate:     data.data.created_at     ?? '',
      startDate:       data.data.start_date     ?? '',
      endDate:         data.data.end_date       ?? '',
      status:          data.data.workflow_status ? 'Under Review' : (STATUS_MAP[data.data.approval_status ?? 'Pending'] ?? 'Pending'),
      notes:           '',
      rejectionReason: '',
      contractLink:    '',
      createdBy,
      docs: (data.data.documents ?? []).map((doc: any) => ({
        name: doc.file_name,
        type: doc.file_type as 'pdf' | 'docx',
        size: doc.file_size ?? 0,
      })),
      itemCode:        data.data.item_code      ?? '',
      serialNo:        data.data.serial_number  ?? '',
      sbuNumber:       data.data.sbu_number     ?? '',
    }

    request.value = updatedRequest
    updateRequestInCache(id, updatedRequest)
    isEditing.value = false
    success('Request updated', 'Changes have been saved.')
  } catch (err) {
    console.error('Error saving request edit:', err)
    error('Network error', 'Could not reach the server. Please try again.')
  } finally {
    savingEdit.value = false
  }
}

const durationMonths = computed(() => {
  const startStr = isEditing.value ? editForm.startDate : request.value?.startDate
  const endStr = isEditing.value ? editForm.endDate : request.value?.endDate
  if (!startStr || !endStr) return 0
  const start = new Date(startStr).getTime()
  const end = new Date(endStr).getTime()
  return Math.max(1, Math.round((end - start) / (1000 * 60 * 60 * 24 * 30)))
})

const categories = [
  'Service Agreement',
  'Partnership Agreement',
  'Supply Contract',
  'Equipment Lease',
  'Equipment Maintenance',
]

// --- Document list processing ---
const allDocs = computed(() => {
  const list = [...(request.value?.docs || [])]
  if (request.value?.contractLink && list.length === 0) {
    list.push({
      name: 'Proposed Contract.pdf',
      type: 'pdf',
      size: 0,
      previewUrl: request.value.contractLink,
    })
  }
  return list
})

function fmtSize(bytes: number) {
  return (bytes / 1_048_576).toFixed(2) + ' MB'
}
</script>

<template>
  <div class="p-8 space-y-6">

    <!-- Loading State (matches ContractDetail layout skeleton) -->
    <div v-if="loading" class="space-y-6">
      <!-- Header Skeleton -->
      <div class="flex items-center justify-between border-b border-black/5 pb-5">
        <div class="space-y-2.5">
          <div class="h-3.5 w-16 bg-black/5 animate-pulse rounded"></div>
          <div class="h-6 w-64 bg-black/5 animate-pulse rounded"></div>
          <div class="h-4.5 w-40 bg-black/5 animate-pulse rounded"></div>
        </div>
        <div class="flex gap-2">
          <div class="h-9 w-20 bg-black/5 animate-pulse rounded-lg"></div>
          <div class="h-9 w-24 bg-black/5 animate-pulse rounded-lg"></div>
        </div>
      </div>
      <!-- Info Section Skeleton -->
      <div class="bg-white rounded-lg border border-black/8 shadow-sm p-6 space-y-5">
        <div class="h-4.5 w-32 bg-black/5 animate-pulse rounded"></div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div v-for="i in 9" :key="i" class="space-y-2">
            <div class="h-3 w-20 bg-black/5 animate-pulse rounded"></div>
            <div class="h-4 w-40 bg-black/5 animate-pulse rounded"></div>
          </div>
        </div>
      </div>
      <!-- Documents Section Skeleton -->
      <div class="bg-white rounded-lg border border-black/8 shadow-sm p-6 space-y-4">
        <div class="h-4.5 w-32 bg-black/5 animate-pulse rounded"></div>
        <div class="h-12 w-full bg-black/5 animate-pulse rounded-lg"></div>
      </div>
    </div>

    <!-- Request Not Found State -->
    <div v-else-if="!request" class="flex flex-col items-center gap-3 py-24 text-black/30">
      <ClipboardList class="w-12 h-12" />
      <p class="text-base font-semibold">Request not found</p>
      <p class="text-sm text-black/25">The contract request may have been deleted or the ID is invalid.</p>
      <Button variant="outline" @click="router.push(backPath)"
        class="mt-2 h-9 px-5 text-sm border-black/15 text-black/60 hover:text-black">
        Back to Requests
      </Button>
    </div>

    <!-- Unified Details View -->
    <template v-else>
      <!-- Header (layout and styling match ContractDetailHeader) -->
      <div class="flex items-start gap-4">
        <!-- Back button -->
        <button @click="router.push(backPath)"
          class="flex items-center justify-center w-9 h-9 rounded-lg border border-black/10 bg-white hover:bg-black/4 text-black/50 hover:text-black transition shrink-0 mt-0.5">
          <ArrowLeft class="w-4 h-4" />
        </button>

        <!-- Icon & Title block -->
        <div class="flex items-start gap-3.5 flex-1 min-w-0">
          <div class="w-11 h-11 rounded-xl flex items-center justify-center text-white shrink-0 bg-[#252578]">
            <ClipboardList class="w-5 h-5" />
          </div>
          <div class="flex-1 min-w-0">
            <h1 class="text-xl font-semibold text-black leading-snug truncate">{{ request.businessPartner }}</h1>
            <p class="text-sm text-black/45 mt-0.5">{{ request.category }}</p>
            <div class="flex items-center gap-2 mt-2 flex-wrap">
              <span class="text-[10px] font-mono text-black/30 bg-black/4 px-1.5 py-0.5 rounded">{{ request.id }}</span>
              <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full border border-black/10" :class="requestStatusBadge[request.status]">
                {{ request.status }}
              </span>
            </div>
          </div>
        </div>

        <!-- Action buttons on the far right -->
        <div class="flex items-center gap-2 shrink-0">
          <!-- Editing actions -->
          <template v-if="isEditing">
            <Button variant="outline" @click="cancelEdit" :disabled="savingEdit"
              class="h-9 border-black/10 text-black/60 hover:text-black">
              Cancel
            </Button>
            <Button @click="saveEdit" :disabled="savingEdit || !isFormValid"
              class="h-9 bg-[#252578] hover:bg-[#2F2F73] text-white gap-2 font-semibold shadow-sm">
              <Loader2 v-if="savingEdit" class="w-4 h-4 animate-spin" />
              <CheckCircle v-else class="w-4 h-4" /> Save Changes
            </Button>
          </template>

          <template v-else>
            <!-- Edit Request button (Pending / Under Review only) -->
            <Button v-if="(request.status === 'Pending' || request.status === 'Under Review') && !showRejectInput"
              @click="startEdit" variant="outline"
              class="h-9 border-black/10 text-black/60 hover:text-black gap-2 font-semibold">
              <FilePenLine class="w-4 h-4" /> Edit Request
            </Button>

            <!-- Follow up action (Sales Pending requests only) -->
            <template v-if="!isManager && request.status === 'Pending'">
              <Button v-if="!isFollowedUp" @click="handleFollowUp"
                class="h-9 bg-amber-500 hover:bg-amber-600 text-white gap-2 font-semibold shadow-sm">
                <Bell class="w-4 h-4" /> Follow Up Manager
              </Button>
              <div v-else class="h-9 px-4 flex items-center justify-center gap-1.5 text-sm font-semibold text-emerald-600 bg-emerald-50 rounded-lg border border-emerald-100">
                <CheckCircle class="w-4 h-4" /> Follow-up sent
              </div>
            </template>

            <!-- Approve/Reject/Set Reviewing (Manager Pending/Under Review requests only) -->
            <template v-if="isManager && (request.status === 'Pending' || request.status === 'Under Review')">
              <template v-if="!showRejectInput">
                <!-- Reviewing action (Pending only) -->
                <Button v-slot="{}" v-if="request.status === 'Pending'" @click="handleSetReviewing" variant="outline"
                  class="h-9 border-[#2E85D8]/20 text-[#2E85D8] hover:bg-[#2E85D8]/5 gap-2 font-semibold">
                  <RefreshCw class="w-4 h-4" /> Set Reviewing
                </Button>
                <Button @click="handleApprove"
                  class="h-9 bg-emerald-600 hover:bg-emerald-700 text-white gap-2 font-semibold shadow-sm">
                  <CheckCircle class="w-4 h-4" /> Approve Request
                </Button>
                <Button @click="showRejectInput = true" variant="outline"
                  class="h-9 border-red-200 text-red-600 hover:bg-red-50 gap-2 font-semibold">
                  <XCircle class="w-4 h-4" /> Reject Request
                </Button>
              </template>
              <template v-else>
                <!-- Rejection active actions -->
                <Button variant="outline" @click="showRejectInput = false; rejectReason = ''"
                  class="h-9 border-black/10 text-black/60 hover:text-black">
                  Cancel
                </Button>
                <Button @click="confirmReject" :disabled="!rejectReason.trim()"
                  class="h-9 bg-red-600 hover:bg-red-700 text-white gap-2 font-semibold disabled:opacity-40 shadow-sm">
                  <XCircle class="w-4 h-4" /> Confirm Reject
                </Button>
              </template>
            </template>
          </template>
        </div>
      </div>

      <!-- Card 1: Request Info -->
      <div class="bg-white rounded-lg border border-black/8 shadow-sm p-6">
        <h2 class="text-xs font-semibold text-black/40 uppercase tracking-widest mb-4">Request Info</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <!-- Business Partner -->
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-black/55">
              Business Partner <span v-if="isEditing" class="text-red-500">*</span>
            </label>
            <p v-if="!isEditing" class="text-sm text-black py-1 font-medium">{{ request.businessPartner }}</p>
            <template v-else>
              <input
                v-model="editForm.businessPartner"
                @blur="touched.businessPartner = true"
                type="text"
                placeholder="e.g. Globe Telecom"
                class="h-9 rounded-lg border px-3 text-sm placeholder:text-black/25 focus:outline-none focus:ring-2 transition"
                :class="fieldCls('businessPartner', !editForm.businessPartner)"
              />
              <p v-if="touched.businessPartner && !editForm.businessPartner" class="text-xs text-red-500">Business partner is required.</p>
            </template>
          </div>

          <!-- Category -->
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-black/55">
              Category <span v-if="isEditing" class="text-red-500">*</span>
            </label>
            <p v-if="!isEditing" class="text-sm text-black py-1 font-medium">{{ request.category }}</p>
            <template v-else>
              <select
                v-model="editForm.category"
                @blur="touched.category = true"
                class="h-9 rounded-lg border px-3 text-sm bg-white focus:outline-none focus:ring-2 transition"
                :class="[
                  !editForm.category ? 'text-black/30' : 'text-black',
                  fieldCls('category', !editForm.category)
                ]">
                <option value="" disabled>Select category</option>
                <option v-for="c in categories" :key="c" :value="c">{{ c }}</option>
              </select>
              <p v-if="touched.category && !editForm.category" class="text-xs text-red-500">Category is required.</p>
            </template>
          </div>
        </div>

        <!-- Footer: Submitted By -->
        <div class="mt-6 pt-4 border-t border-black/6 flex items-center gap-3">
          <span class="text-xs font-semibold text-black/40">Submitted by</span>
          <div class="flex items-center gap-2">
            <div class="w-6 h-6 rounded-full flex items-center justify-center text-white text-[9px] font-bold shrink-0 select-none"
              :style="{ backgroundColor: avatarColor(request.createdBy) }">
              {{ initials(request.createdBy) }}
            </div>
            <span class="text-sm font-medium text-black">{{ request.createdBy }}</span>
          </div>
        </div>
      </div>

      <!-- Card 2: Item Details -->
      <div class="bg-white rounded-lg border border-black/8 shadow-sm p-6">
        <h2 class="text-xs font-semibold text-black/40 uppercase tracking-widest mb-4">Item Details</h2>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <!-- Item Code -->
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-black/55">
              Item Code <span v-if="isEditing" class="text-red-500">*</span>
            </label>
            <p v-if="!isEditing" class="text-sm font-mono text-black py-1">{{ request.itemCode || '—' }}</p>
            <template v-else>
              <input
                v-model="editForm.itemCode"
                @blur="touched.itemCode = true"
                type="text"
                placeholder="e.g. ITM-0041"
                class="h-9 rounded-lg border px-3 text-sm font-mono placeholder:text-black/25 focus:outline-none focus:ring-2 transition"
                :class="fieldCls('itemCode', !editForm.itemCode)"
              />
              <p v-if="touched.itemCode && !editForm.itemCode" class="text-xs text-red-500">Item code is required.</p>
            </template>
          </div>

          <!-- Description -->
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-black/55">
              Description <span v-if="isEditing" class="text-red-500">*</span>
            </label>
            <p v-if="!isEditing" class="text-sm text-black py-1 leading-relaxed">{{ request.description }}</p>
            <template v-else>
              <input
                v-model="editForm.description"
                @blur="touched.description = true"
                type="text"
                placeholder="e.g. Network Infrastructure"
                class="h-9 rounded-lg border px-3 text-sm placeholder:text-black/25 focus:outline-none focus:ring-2 transition"
                :class="fieldCls('description', !editForm.description)"
              />
              <p v-if="touched.description && !editForm.description" class="text-xs text-red-500">Description is required.</p>
            </template>
          </div>

          <!-- Serial No -->
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-black/55">
              Serial No <span v-if="isEditing" class="text-red-500">*</span>
            </label>
            <p v-if="!isEditing" class="text-sm font-mono text-black py-1">{{ request.serialNo || '—' }}</p>
            <template v-else>
              <input
                v-model="editForm.serialNo"
                @blur="touched.serialNo = true"
                type="text"
                placeholder="e.g. SN-2024-0041"
                class="h-9 rounded-lg border px-3 text-sm font-mono placeholder:text-black/25 focus:outline-none focus:ring-2 transition"
                :class="fieldCls('serialNo', !editForm.serialNo)"
              />
              <p v-if="touched.serialNo && !editForm.serialNo" class="text-xs text-red-500">Serial number is required.</p>
            </template>
          </div>

          <!-- SBU Number -->
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-black/55">
              SBU Number <span v-if="isEditing" class="text-red-500">*</span>
            </label>
            <p v-if="!isEditing" class="text-sm font-mono text-black py-1">{{ request.sbuNumber || '—' }}</p>
            <template v-else>
              <input
                v-model="editForm.sbuNumber"
                @blur="touched.sbuNumber = true"
                type="text"
                placeholder="e.g. SBU-001"
                class="h-9 rounded-lg border px-3 text-sm font-mono placeholder:text-black/25 focus:outline-none focus:ring-2 transition"
                :class="fieldCls('sbuNumber', !editForm.sbuNumber)"
              />
              <p v-if="touched.sbuNumber && !editForm.sbuNumber" class="text-xs text-red-500">SBU number is required.</p>
            </template>
          </div>
        </div>
      </div>

      <!-- Card 3: Schedule & Location -->
      <div class="bg-white rounded-lg border border-black/8 shadow-sm p-6">
        <h2 class="text-xs font-semibold text-black/40 uppercase tracking-widest mb-4">Schedule & Location</h2>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <!-- Region -->
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-black/55">
              Region <span v-if="isEditing" class="text-red-500">*</span>
            </label>
            <p v-if="!isEditing" class="text-sm text-black py-1 font-medium">{{ request.region }}</p>
            <template v-else>
              <select
                v-model="editForm.region"
                @blur="touched.region = true"
                class="h-9 rounded-lg border px-3 text-sm bg-white focus:outline-none focus:ring-2 transition"
                :class="[
                  !editForm.region ? 'text-black/30' : 'text-black',
                  fieldCls('region', !editForm.region)
                ]">
                <option value="" disabled>Select region</option>
                <option value="Luzon">Luzon</option>
                <option value="Visayas">Visayas</option>
                <option value="Mindanao">Mindanao</option>
              </select>
              <p v-if="touched.region && !editForm.region" class="text-xs text-red-500">Region is required.</p>
            </template>
          </div>

          <!-- Requested Date -->
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-black/55">Requested Date</label>
            <p class="text-sm text-black py-1 tabular-nums">{{ fmtReqDate(request.requestDate) }}</p>
          </div>

          <!-- Proposed Start Date -->
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-black/55">
              Proposed Start Date <span v-if="isEditing" class="text-red-500">*</span>
            </label>
            <p v-if="!isEditing" class="text-sm text-black py-1 tabular-nums">{{ fmtReqDate(request.startDate) }}</p>
            <template v-else>
              <input
                v-model="editForm.startDate"
                @blur="touched.startDate = true"
                type="date"
                class="h-9 rounded-lg border px-3 text-sm focus:outline-none focus:ring-2 transition"
                :class="fieldCls('startDate', !editForm.startDate)"
              />
              <p v-if="touched.startDate && !editForm.startDate" class="text-xs text-red-500">Start date is required.</p>
            </template>
          </div>

          <!-- Proposed End Date -->
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-black/55">
              Proposed End Date <span v-if="isEditing" class="text-red-500">*</span>
            </label>
            <p v-if="!isEditing" class="text-sm text-black py-1 tabular-nums flex items-center gap-2">
              {{ fmtReqDate(request.endDate) }}
              <span class="text-xs text-black/45">({{ durationMonths }} Months)</span>
            </p>
            <template v-else>
              <input
                v-model="editForm.endDate"
                @blur="touched.endDate = true"
                type="date"
                class="h-9 rounded-lg border px-3 text-sm focus:outline-none focus:ring-2 transition"
                :class="fieldCls('endDate', !editForm.endDate || !!dateError)"
              />
              <p v-if="touched.endDate && !editForm.endDate" class="text-xs text-red-500">End date is required.</p>
              <p v-else-if="dateError" class="text-xs text-red-500">{{ dateError }}</p>
            </template>
          </div>
        </div>
      </div>

      <!-- Card 4: Notes & Status Info -->
      <div v-if="request.notes || (request.status === 'Rejected' && request.rejectionReason) || showRejectInput" 
        class="bg-white rounded-lg border border-black/8 shadow-sm p-6">
        <h2 class="text-xs font-semibold text-black/40 uppercase tracking-widest mb-4">Notes & Status Info</h2>
        <div class="space-y-4">
          <!-- Notes -->
          <div v-if="request.notes" class="flex flex-col gap-1.5">
            <span class="text-xs font-semibold text-black/55">Notes</span>
            <p class="text-sm text-black py-1 leading-relaxed">{{ request.notes }}</p>
          </div>

          <!-- Rejection input text box (visible only when manager is rejecting) -->
          <div v-if="showRejectInput" class="bg-red-50/20 border border-red-100 rounded-lg p-4 space-y-2">
            <label class="text-xs font-semibold text-red-700 block mb-1">Rejection Reason <span class="text-red-500">*</span></label>
            <textarea v-model="rejectReason" rows="3" placeholder="Provide a reason for rejection..."
              class="w-full rounded-lg border border-red-200 bg-white px-3 py-2 text-sm placeholder:text-black/25 focus:border-red-400 focus:outline-none focus:ring-2 focus:ring-red-200/15 transition resize-none" />
          </div>

          <!-- Rejection Reason Badge (if status is Rejected) -->
          <div v-if="request.status === 'Rejected' && request.rejectionReason" class="flex items-start gap-3 bg-red-50/30 border border-red-100 rounded-lg p-4">
            <AlertCircle class="w-4.5 h-4.5 text-red-500 shrink-0 mt-0.5" />
            <div class="flex-1 min-w-0">
              <span class="text-[10px] font-semibold text-red-500/70 uppercase tracking-wider block mb-1">Rejection Reason</span>
              <span class="text-sm text-red-600 font-medium leading-relaxed">{{ request.rejectionReason }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Proposed Documents Card (single full-width card matching ContractDocumentsSection) -->
      <div class="bg-white rounded-lg border border-black/8 shadow-sm overflow-hidden">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-black/6 flex items-center gap-2">
          <h2 class="text-xs font-semibold text-black/40 uppercase tracking-widest">Proposed Documents</h2>
          <span class="text-[10px] font-bold text-black/35 bg-black/5 px-1.5 py-0.5 rounded-full tabular-nums">
            {{ allDocs.length }}
          </span>
        </div>

        <!-- Documents list -->
        <div class="p-5">
          <div v-if="allDocs.length === 0" class="flex flex-col items-center gap-2 py-10 text-black/25">
            <FileX class="w-8 h-8" />
            <p class="text-sm font-medium">No documents attached</p>
          </div>
          <div v-else class="flex flex-wrap gap-4">
            <a v-for="doc in allDocs" :key="doc.name" :href="safeHref(doc.previewUrl)" target="_blank" rel="noopener noreferrer"
              class="relative w-36 rounded-lg border border-black/8 overflow-hidden shadow-sm bg-white flex flex-col group hover:border-[#2E85D8]/50 transition-colors">
              
              <!-- PDF Preview -->
              <template v-if="doc.type === 'pdf'">
                <div class="w-full h-44 bg-black/4 overflow-hidden relative">
                  <iframe :src="safeHref(doc.previewUrl)" class="w-full h-full pointer-events-none" />
                  <div class="absolute inset-0 bg-black/0 group-hover:bg-black/5 transition-colors flex items-center justify-center">
                    <ExternalLink class="w-5 h-5 text-white opacity-0 group-hover:opacity-100 transition-opacity drop-shadow" />
                  </div>
                </div>
              </template>
              
              <!-- DOCX representation -->
              <template v-else>
                <div class="w-full h-44 bg-blue-50 flex flex-col items-center justify-center gap-2">
                  <FileType2 class="w-10 h-10 text-blue-400" />
                  <span class="text-[10px] font-bold text-blue-400 uppercase tracking-wider">DOCX</span>
                </div>
              </template>

              <!-- Filename strip -->
              <div class="px-2 py-1.5 border-t border-black/6 bg-white">
                <p class="text-[10px] font-medium text-black/60 truncate" :title="doc.name">{{ doc.name }}</p>
                <p class="text-[9px] text-black/30 tabular-nums">
                  {{ doc.size > 0 ? fmtSize(doc.size) : 'Proposed Link' }}
                </p>
              </div>
            </a>
          </div>
        </div>
      </div>
    </template>

  </div>
</template>
