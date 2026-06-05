<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import {
  Building2, Truck, ArrowLeft, Pencil,
  User, Phone, Mail, MapPin, Hash, Briefcase, Globe, FileText,
} from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { useToast } from '@/composables/useToast'
import { useVendorService } from '@/composables/useVendorService'
import AddPartnerDialog from '@/views/admin/Partners/AddPartnerDialog.vue'
import type { Partner, TabKey, AddPartnerForm } from '@/types/partner'

const route  = useRoute()
const router = useRouter()
const { success, error, warning } = useToast()
const { fetchPartnerById, fetchSupplierById, updatePartner, updateSupplier } = useVendorService()

const type = route.params.type as string          // 'bp' | 'sp'
const id   = Number(route.params.id)
const activeTab = computed<TabKey>(() => type === 'bp' ? 'partners' : 'suppliers')

const partner   = ref<Partner | null>(null)
const loading   = ref(true)
const isAdmin   = computed(() => route.path.startsWith('/admin'))

const backPath = computed(() =>
  route.path.startsWith('/admin')   ? '/admin/partners'
: route.path.startsWith('/manager') ? '/manager/partners'
: '/sales/partners'
)

async function loadPartner() {
  loading.value = true
  try {
    partner.value = type === 'bp'
      ? await fetchPartnerById(id)
      : await fetchSupplierById(id)
  } catch {
    partner.value = null
  } finally {
    loading.value = false
  }
}

onMounted(loadPartner)

// ── Edit ─────────────────────────────────────────────────────────────────────

const showEdit   = ref(false)
const editTarget = ref<Partner | null>(null)

function openEdit() {
  editTarget.value = partner.value
  showEdit.value   = true
}

function onEditClose(val: boolean) {
  showEdit.value = val
  if (!val) editTarget.value = null
}

async function handleSubmit(form: AddPartnerForm) {
  const target = editTarget.value
  if (!target) return
  try {
    if (activeTab.value === 'partners') {
      const { partner: updated, warnings } = await updatePartner(target.id, form, target.bpCode)
      partner.value = updated
      success('Partner updated', `${updated.name} has been updated.`)
      if (warnings.length) warning('Duplicate warning', warnings[0].message)
    } else {
      const { partner: updated, warnings } = await updateSupplier(target.id, form)
      partner.value = updated
      success('Supplier updated', `${updated.name} has been updated.`)
      if (warnings.length) warning('Duplicate warning', warnings[0].message)
    }
  } catch (err: any) {
    error('Save failed', err?.message ?? 'An error occurred. Please try again.')
  } finally {
    editTarget.value = null
  }
}

// ── Helpers ───────────────────────────────────────────────────────────────────

const displayId = computed(() => {
  if (!partner.value) return ''
  const prefix = type === 'bp' ? 'BP' : 'SP'
  return `${prefix}-${String(partner.value.id).padStart(4, '0')}`
})

const statusClass = computed(() => {
  switch (partner.value?.status) {
    case 'Active':    return 'bg-emerald-50 text-emerald-700 border-emerald-200'
    case 'Inactive':  return 'bg-black/4 text-black/40 border-black/8'
    case 'Suspended': return 'bg-amber-50 text-amber-700 border-amber-200'
    default:          return 'bg-black/4 text-black/40 border-black/8'
  }
})
</script>

<template>
  <div class="p-8 space-y-6">

    <!-- Loading skeleton -->
    <template v-if="loading">
      <div class="flex items-center justify-between">
        <div class="h-4 w-28 bg-black/5 animate-pulse rounded" />
        <div class="h-9 w-20 bg-black/5 animate-pulse rounded-lg" />
      </div>
      <div class="bg-white rounded-xl border border-black/8 shadow-sm p-6 space-y-4">
        <div class="flex items-center gap-4">
          <div class="w-14 h-14 bg-black/5 animate-pulse rounded-xl shrink-0" />
          <div class="space-y-2 flex-1">
            <div class="h-5 w-48 bg-black/5 animate-pulse rounded" />
            <div class="h-4 w-32 bg-black/5 animate-pulse rounded" />
          </div>
          <div class="h-6 w-20 bg-black/5 animate-pulse rounded-full" />
        </div>
      </div>
      <div class="bg-white rounded-xl border border-black/8 shadow-sm p-6">
        <div class="h-4 w-36 bg-black/5 animate-pulse rounded mb-5" />
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
          <div v-for="i in 4" :key="i" class="space-y-2">
            <div class="h-3 w-16 bg-black/5 animate-pulse rounded" />
            <div class="h-4 w-24 bg-black/5 animate-pulse rounded" />
          </div>
        </div>
      </div>
      <div class="bg-white rounded-xl border border-black/8 shadow-sm p-6">
        <div class="h-4 w-36 bg-black/5 animate-pulse rounded mb-5" />
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
          <div v-for="i in 4" :key="i" class="space-y-2">
            <div class="h-3 w-16 bg-black/5 animate-pulse rounded" />
            <div class="h-4 w-24 bg-black/5 animate-pulse rounded" />
          </div>
        </div>
      </div>
    </template>

    <!-- Not found -->
    <template v-else-if="!partner">
      <div class="flex flex-col items-center gap-3 py-24 text-black/30">
        <component :is="type === 'bp' ? Building2 : Truck" class="w-12 h-12" />
        <p class="text-base font-semibold text-black/40">Record not found</p>
        <p class="text-sm text-black/25">This record may have been deleted or the ID is invalid.</p>
        <Button variant="outline" @click="router.push(backPath)"
          class="mt-2 h-9 px-5 text-sm border-black/15 text-black/60 hover:text-black">
          Back to Partners
        </Button>
      </div>
    </template>

    <!-- Detail content -->
    <template v-else>

      <!-- Top bar: back + edit -->
      <div class="flex items-center justify-between">
        <button @click="router.push(backPath)"
          class="flex items-center gap-1.5 text-sm text-black/45 hover:text-black transition-colors">
          <ArrowLeft class="w-4 h-4" />
          Back to Partners
        </button>
        <Button v-if="isAdmin" @click="openEdit"
          class="h-9 px-4 gap-2 text-sm bg-[#252578] hover:bg-[#2F2F73] text-white">
          <Pencil class="w-3.5 h-3.5" /> Edit
        </Button>
      </div>

      <!-- Hero card -->
      <div class="bg-white rounded-xl border border-black/8 shadow-sm p-6">
        <div class="flex items-center gap-4">
          <div class="w-14 h-14 rounded-xl flex items-center justify-center shrink-0 bg-[#252578]/8 text-[#252578]">
            <component :is="type === 'bp' ? Building2 : Truck" class="w-7 h-7" />
          </div>
          <div class="flex-1 min-w-0">
            <h1 class="text-xl font-bold text-black leading-snug truncate">{{ partner.name }}</h1>
            <p class="text-sm text-black/45 mt-0.5">{{ partner.industry || '—' }}</p>
            <div class="flex items-center gap-2 mt-2">
              <span class="text-xs font-mono text-black/30 bg-black/4 px-2 py-0.5 rounded">{{ displayId }}</span>
            </div>
          </div>
          <span class="text-sm font-semibold px-3 py-1 rounded-full border shrink-0" :class="statusClass">
            {{ partner.status }}
          </span>
        </div>
      </div>

      <!-- Business details card -->
      <div class="bg-white rounded-xl border border-black/8 shadow-sm p-6">
        <h2 class="text-xs font-bold text-black/35 uppercase tracking-widest mb-5">Business Details</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-x-6 gap-y-5">

          <div class="space-y-1">
            <div class="flex items-center gap-1.5 text-[10px] font-semibold text-black/35 uppercase tracking-wider">
              <Briefcase class="w-3 h-3" /> Industry
            </div>
            <p class="text-sm font-medium text-black">{{ partner.industry || '—' }}</p>
          </div>

          <div class="space-y-1">
            <div class="flex items-center gap-1.5 text-[10px] font-semibold text-black/35 uppercase tracking-wider">
              <Globe class="w-3 h-3" /> Region
            </div>
            <p class="text-sm font-medium text-black">{{ partner.region ?? '—' }}</p>
          </div>

          <div class="space-y-1">
            <div class="flex items-center gap-1.5 text-[10px] font-semibold text-black/35 uppercase tracking-wider">
              <Hash class="w-3 h-3" /> Status
            </div>
            <span class="inline-flex text-xs font-semibold px-2.5 py-0.5 rounded-full border" :class="statusClass">
              {{ partner.status }}
            </span>
          </div>

          <div class="space-y-1">
            <div class="flex items-center gap-1.5 text-[10px] font-semibold text-black/35 uppercase tracking-wider">
              <FileText class="w-3 h-3" />
              {{ type === 'bp' ? 'BP Code' : 'TIN Number' }}
            </div>
            <p class="text-sm font-mono text-black/70">
              {{ type === 'bp' ? (partner.bpCode ?? '—') : (partner.tinNumber ?? '—') }}
            </p>
          </div>

        </div>
      </div>

      <!-- Contact information card -->
      <div class="bg-white rounded-xl border border-black/8 shadow-sm p-6">
        <h2 class="text-xs font-bold text-black/35 uppercase tracking-widest mb-5">Contact Information</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-10 gap-y-0 divide-y divide-black/4">

          <div class="flex items-start gap-3 py-3.5">
            <User class="w-4 h-4 text-black/25 shrink-0 mt-0.5" />
            <div class="flex-1 min-w-0">
              <p class="text-[10px] font-semibold text-black/35 uppercase tracking-wider mb-0.5">Contact Person</p>
              <p class="text-sm font-medium text-black">{{ partner.contactPerson || '—' }}</p>
            </div>
          </div>

          <div class="flex items-start gap-3 py-3.5">
            <Phone class="w-4 h-4 text-black/25 shrink-0 mt-0.5" />
            <div class="flex-1 min-w-0">
              <p class="text-[10px] font-semibold text-black/35 uppercase tracking-wider mb-0.5">Phone</p>
              <p class="text-sm font-medium text-black tabular-nums">{{ partner.phone || '—' }}</p>
            </div>
          </div>

          <div class="flex items-start gap-3 py-3.5">
            <Mail class="w-4 h-4 text-black/25 shrink-0 mt-0.5" />
            <div class="flex-1 min-w-0">
              <p class="text-[10px] font-semibold text-black/35 uppercase tracking-wider mb-0.5">Email</p>
              <p class="text-sm text-[#2E85D8] break-all">{{ partner.email || '—' }}</p>
            </div>
          </div>

          <div class="flex items-start gap-3 py-3.5">
            <MapPin class="w-4 h-4 text-black/25 shrink-0 mt-0.5" />
            <div class="flex-1 min-w-0">
              <p class="text-[10px] font-semibold text-black/35 uppercase tracking-wider mb-0.5">Address</p>
              <p class="text-sm font-medium text-black leading-snug">{{ partner.address || '—' }}</p>
            </div>
          </div>

        </div>
      </div>

    </template>
  </div>

  <AddPartnerDialog
    v-if="isAdmin"
    :open="showEdit"
    :active-tab="activeTab"
    :edit-target="editTarget"
    @update:open="onEditClose"
    @submit="handleSubmit"
  />
</template>
