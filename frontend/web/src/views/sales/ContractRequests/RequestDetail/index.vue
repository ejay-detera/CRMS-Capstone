<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import {
  ArrowLeft, ClipboardList, MapPin, CalendarDays, FileText,
  Bell, CheckCircle, AlertCircle, ExternalLink, XCircle, RefreshCw
} from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { useAuth } from '@/composables/useAuth'
import { useToast } from '@/composables/useToast'
import { useApiCache } from '@/composables/useApiCache'
import { requestStatusBadge, priorityBadge, fmtReqDate } from '@/types/contractRequest'
import type { ContractRequest } from '@/types/contractRequest'
import { safeHref } from '@/utils/sanitize'

const route = useRoute()
const router = useRouter()
const { state: authState } = useAuth()
const { success, error } = useToast()
const { state: cacheState, fetchRequests, updateRequestStatusInCache } = useApiCache()

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
  loading.value = true
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

const durationMonths = computed(() => {
  if (!request.value) return 0
  const start = new Date(request.value.startDate).getTime()
  const end = new Date(request.value.endDate).getTime()
  return Math.max(1, Math.round((end - start) / (1000 * 60 * 60 * 24 * 30)))
})
</script>

<template>
  <div class="p-8 space-y-6">

    <!-- Loading State -->
    <div v-if="loading" class="space-y-6">
      <div class="flex items-center justify-between border-b border-black/5 pb-5">
        <div class="space-y-2.5">
          <div class="h-3.5 w-16 bg-black/5 animate-pulse rounded"></div>
          <div class="h-6 w-64 bg-black/5 animate-pulse rounded"></div>
          <div class="h-4.5 w-40 bg-black/5 animate-pulse rounded"></div>
        </div>
        <div class="h-9 w-24 bg-black/5 animate-pulse rounded-lg"></div>
      </div>
      <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <div class="xl:col-span-2 bg-white rounded-lg border border-black/8 shadow-sm p-6 space-y-5">
          <div class="h-4 w-32 bg-black/5 animate-pulse rounded"></div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4">
            <div v-for="i in 6" :key="i" class="space-y-2">
              <div class="h-3 w-20 bg-black/5 animate-pulse rounded"></div>
              <div class="h-4.5 w-40 bg-black/5 animate-pulse rounded"></div>
            </div>
          </div>
        </div>
        <div class="bg-white rounded-lg border border-black/8 shadow-sm p-6 h-fit space-y-4">
          <div class="h-4 w-24 bg-black/5 animate-pulse rounded"></div>
          <div class="h-9 w-full bg-black/5 animate-pulse rounded-lg"></div>
        </div>
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

    <!-- Request Details View -->
    <template v-else>
      <!-- Header -->
      <div class="flex items-start gap-4">
        <!-- Back button -->
        <button @click="router.push(backPath)"
          class="flex items-center justify-center w-9 h-9 rounded-lg border border-black/10 bg-white hover:bg-black/4 text-black/50 hover:text-black transition shrink-0 mt-0.5">
          <ArrowLeft class="w-4 h-4" />
        </button>

        <!-- Icon & Title -->
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
              <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full border border-black/10" :class="priorityBadge[request.priority]">
                {{ request.priority }} Priority
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Main Columns -->
      <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <!-- Details Card -->
        <div class="xl:col-span-2 space-y-6">
          <div class="bg-white rounded-lg border border-black/8 shadow-sm overflow-hidden">
            <!-- Title -->
            <div class="px-6 py-5 border-b border-black/6">
              <h2 class="text-xs font-semibold text-black/40 uppercase tracking-widest">Request details</h2>
            </div>

            <!-- Stats strip -->
            <div class="grid grid-cols-3 divide-x divide-black/6 border-b border-black/6 bg-black/[0.01]">
              <div class="px-6 py-4">
                <p class="text-[9px] font-bold text-black/30 uppercase tracking-widest mb-1.5">Region</p>
                <p class="text-sm font-bold text-black flex items-center gap-1.5">
                  <MapPin class="w-3.5 h-3.5 text-black/40" />
                  {{ request.region }}
                </p>
              </div>
              <div class="px-6 py-4">
                <p class="text-[9px] font-bold text-black/30 uppercase tracking-widest mb-1.5">Requested Date</p>
                <p class="text-sm font-bold text-black tabular-nums">{{ fmtReqDate(request.requestDate) }}</p>
              </div>
              <div class="px-6 py-4">
                <p class="text-[9px] font-bold text-black/30 uppercase tracking-widest mb-1.5">Proposed Duration</p>
                <p class="text-sm font-bold text-black tabular-nums">{{ durationMonths }} Months</p>
              </div>
            </div>

            <!-- Detail Rows -->
            <div class="px-6 py-2 divide-y divide-black/4">
              <!-- Description -->
              <div class="flex items-start gap-4 py-4.5">
                <FileText class="w-4 h-4 text-black/30 shrink-0 mt-0.5" />
                <div class="flex-1 min-w-0">
                  <span class="text-[10px] font-semibold text-black/35 uppercase tracking-wider block mb-1">Description</span>
                  <span class="text-sm text-black leading-relaxed">{{ request.description }}</span>
                </div>
              </div>

              <!-- Proposed Schedule -->
              <div class="flex items-start gap-4 py-4.5">
                <CalendarDays class="w-4 h-4 text-black/30 shrink-0 mt-0.5" />
                <div class="flex-1 min-w-0">
                  <span class="text-[10px] font-semibold text-black/35 uppercase tracking-wider block mb-2">Proposed Timeline</span>
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-black/[0.015] border border-black/5 rounded-lg px-4 py-3">
                      <span class="text-[10px] font-semibold text-black/35 uppercase tracking-wider block mb-0.5">Proposed Start</span>
                      <span class="text-sm font-semibold text-black tabular-nums">{{ fmtReqDate(request.startDate) }}</span>
                    </div>
                    <div class="bg-black/[0.015] border border-black/5 rounded-lg px-4 py-3">
                      <span class="text-[10px] font-semibold text-black/35 uppercase tracking-wider block mb-0.5">Proposed End</span>
                      <span class="text-sm font-semibold text-black tabular-nums">{{ fmtReqDate(request.endDate) }}</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Notes -->
              <div v-if="request.notes" class="flex items-start gap-4 py-4.5">
                <ClipboardList class="w-4 h-4 text-black/30 shrink-0 mt-0.5" />
                <div class="flex-1 min-w-0">
                  <span class="text-[10px] font-semibold text-black/35 uppercase tracking-wider block mb-1">Notes</span>
                  <span class="text-sm text-black/75 leading-relaxed">{{ request.notes }}</span>
                </div>
              </div>

              <!-- Rejection Reason -->
              <div v-if="request.status === 'Rejected' && request.rejectionReason" class="flex items-start gap-4 py-4.5 bg-red-50/30 -mx-6 px-6">
                <AlertCircle class="w-4 h-4 text-red-500 shrink-0 mt-0.5" />
                <div class="flex-1 min-w-0">
                  <span class="text-[10px] font-semibold text-red-500/70 uppercase tracking-wider block mb-1">Rejection Reason</span>
                  <span class="text-sm text-red-600 font-medium leading-relaxed">{{ request.rejectionReason }}</span>
                </div>
              </div>
            </div>

            <!-- Creator -->
            <div class="px-6 py-4 border-t border-black/6 bg-black/[0.01] flex items-center justify-between">
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
        </div>

        <!-- Action Column -->
        <div class="space-y-6">
          <div class="bg-white rounded-lg border border-black/8 shadow-sm p-6 space-y-4">
            <h3 class="text-xs font-semibold text-black/40 uppercase tracking-widest">Actions</h3>

            <!-- Contract PDF external view -->
            <a :href="safeHref(request.contractLink)" target="_blank" rel="noopener noreferrer"
              class="w-full h-9 inline-flex items-center justify-center gap-2 text-sm font-semibold rounded-lg border border-black/10 bg-white hover:bg-black/4 text-black/65 hover:text-black transition">
              <ExternalLink class="w-4 h-4" /> View Proposed PDF
            </a>

            <!-- Follow up action (Sales Pending requests only) -->
            <div v-if="!isManager && request.status === 'Pending'" class="pt-2">
              <Button v-if="!isFollowedUp" @click="handleFollowUp"
                class="w-full h-9 bg-amber-500 hover:bg-amber-600 text-white gap-2 font-semibold shadow-sm">
                <Bell class="w-4 h-4" /> Follow Up Manager
              </Button>
              <div v-else class="w-full h-9 flex items-center justify-center gap-1.5 text-sm font-semibold text-emerald-600 bg-emerald-50 rounded-lg border border-emerald-100">
                <CheckCircle class="w-4 h-4" /> Follow-up sent
              </div>
            </div>

            <!-- Approve/Reject/Set Reviewing (Manager Pending/Under Review requests only) -->
            <div v-if="isManager && (request.status === 'Pending' || request.status === 'Under Review')" class="space-y-3 pt-2">
              
              <!-- Reviewing action (Pending only) -->
              <Button v-if="request.status === 'Pending' && !showRejectInput" @click="handleSetReviewing" variant="outline"
                class="w-full h-9 border-[#2E85D8]/20 text-[#2E85D8] hover:bg-[#2E85D8]/5 gap-2 font-semibold">
                <RefreshCw class="w-4 h-4" /> Set Reviewing
              </Button>

              <!-- Main actions -->
              <template v-if="!showRejectInput">
                <Button @click="handleApprove"
                  class="w-full h-9 bg-emerald-600 hover:bg-emerald-700 text-white gap-2 font-semibold shadow-sm">
                  <CheckCircle class="w-4 h-4" /> Approve Request
                </Button>

                <Button @click="showRejectInput = true" variant="outline"
                  class="w-full h-9 border-red-200 text-red-600 hover:bg-red-50 gap-2 font-semibold">
                  <XCircle class="w-4 h-4" /> Reject Request
                </Button>
              </template>

              <!-- Rejection workflow details -->
              <template v-else>
                <div class="space-y-2 pb-1.5">
                  <label class="text-xs font-semibold text-black/50 block">Rejection Reason</label>
                  <textarea v-model="rejectReason" rows="3" placeholder="Provide a reason for rejection..."
                    class="w-full rounded-lg border border-black/10 bg-white px-3 py-2 text-sm placeholder:text-black/25 focus:border-[#2E85D8] focus:outline-none focus:ring-2 focus:ring-[#2E85D8]/15 transition resize-none" />
                </div>

                <div class="flex items-center gap-2">
                  <Button variant="outline" @click="showRejectInput = false; rejectReason = ''"
                    class="flex-1 h-9 border-black/10 text-black/60 hover:text-black">
                    Cancel
                  </Button>
                  <Button @click="confirmReject" :disabled="!rejectReason.trim()"
                    class="flex-1 h-9 bg-red-600 hover:bg-red-700 text-white gap-2 font-semibold disabled:opacity-40 shadow-sm">
                    <XCircle class="w-4 h-4" /> Confirm Reject
                  </Button>
                </div>
              </template>

            </div>
          </div>
        </div>
      </div>
    </template>

  </div>
</template>
