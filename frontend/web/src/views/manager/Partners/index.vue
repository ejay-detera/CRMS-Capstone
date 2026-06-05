<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { Building2, Truck, Search, LayoutGrid, List, Upload } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import * as XLSX from 'xlsx'
import { useToast } from '@/composables/useToast'
import { useVendorService } from '@/composables/useVendorService'
import PartnersGrid  from '@/views/admin/Partners/PartnersGrid.vue'
import PartnersTable from '@/views/admin/Partners/PartnersTable.vue'
import type { Partner, TabKey } from '@/types/partner'

const router = useRouter()
const { error } = useToast()
const { fetchPartners, fetchSuppliers } = useVendorService()

const businessPartners = ref<Partner[]>([])
const suppliersData    = ref<Partner[]>([])
const loading          = ref(false)

async function loadData() {
  loading.value = true
  try {
    const [p, s] = await Promise.all([fetchPartners(), fetchSuppliers()])
    businessPartners.value = p
    suppliersData.value    = s
  } catch {
    error('Load failed', 'Could not load partners and suppliers from server.')
  } finally {
    loading.value = false
  }
}

onMounted(loadData)

const activeTab      = ref<TabKey>('partners')
const viewMode       = ref<'card' | 'table'>('card')
const search         = ref('')
const regionFilter   = ref('All')
const statusFilter   = ref('All')
const industryFilter = ref('All')
const regions        = ['All', 'Luzon', 'Visayas', 'Mindanao']
const statusOptions  = ['All', 'Active', 'Inactive', 'Suspended'] as const

const source = computed(() => activeTab.value === 'partners' ? businessPartners.value : suppliersData.value)

const industries = computed(() => {
  const vals = source.value.map(p => p.industry).filter(Boolean)
  return ['All', ...[...new Set(vals)].sort()]
})

const filtered = computed(() =>
  source.value.filter(p => {
    const q = search.value.toLowerCase()
    return (
      (!q || p.name.toLowerCase().includes(q) || p.industry.toLowerCase().includes(q)) &&
      (regionFilter.value   === 'All' || p.region   === regionFilter.value) &&
      (statusFilter.value   === 'All' || p.status   === statusFilter.value) &&
      (industryFilter.value === 'All' || p.industry === industryFilter.value)
    )
  })
)

const currentPage  = ref(1)
const itemsPerPage = 15
watch([search, regionFilter, statusFilter, industryFilter], () => { currentPage.value = 1 })
watch(activeTab, () => {
  search.value         = ''
  regionFilter.value   = 'All'
  statusFilter.value   = 'All'
  industryFilter.value = 'All'
  currentPage.value    = 1
})
const paginated = computed(() => filtered.value.slice((currentPage.value - 1) * itemsPerPage, currentPage.value * itemsPerPage))

const selectedIds     = ref<number[]>([])
const allPageSelected = computed(() =>
  paginated.value.length > 0 && paginated.value.every(p => selectedIds.value.includes(p.id))
)
function toggleSelectAll() {
  const ids = paginated.value.map(p => p.id)
  if (allPageSelected.value) selectedIds.value = selectedIds.value.filter(id => !ids.includes(id))
  else ids.forEach(id => { if (!selectedIds.value.includes(id)) selectedIds.value.push(id) })
}
function toggleRow(id: number) {
  const i = selectedIds.value.indexOf(id)
  i >= 0 ? selectedIds.value.splice(i, 1) : selectedIds.value.push(id)
}

function openDetail(p: Partner) {
  const prefix = activeTab.value === 'partners' ? 'BP' : 'SP'
  const code   = `${prefix}-${String(p.id).padStart(4, '0')}`
  router.push(`/manager/partners/${code}`)
}

function exportXLSX() {
  const type = activeTab.value === 'partners' ? 'Business Partner' : 'Supplier'
  const rows = filtered.value.map(p => ({
    'ID':             p.id,
    'Name':           p.name,
    'Type':           type,
    'Industry':       p.industry,
    'Region':         p.region ?? '',
    'Status':         p.status,
    'Contact Person': p.contactPerson,
    'Phone':          p.phone,
    'Email':          p.email,
    'Address':        p.address,
    ...(activeTab.value === 'partners' ? { 'BP Code': p.bpCode ?? '' } : { 'TIN': p.tinNumber ?? '' }),
  }))
  const ws = XLSX.utils.json_to_sheet(rows)
  const wb = XLSX.utils.book_new()
  XLSX.utils.book_append_sheet(wb, ws, type === 'Business Partner' ? 'Partners' : 'Suppliers')
  XLSX.writeFile(wb, `sbsi-${activeTab.value}.xlsx`)
  success('Export complete', `${filtered.value.length} records exported.`)
}
</script>

<template>
  <div class="p-8 space-y-6">

    <div class="flex items-start justify-between">
      <div>
        <h1 class="text-xl font-semibold text-black">Partners & Suppliers</h1>
        <p class="text-sm text-black/40 mt-0.5">View and manage business relationships.</p>
      </div>
      <Button @click="exportXLSX" variant="outline" class="h-9 gap-2 text-sm font-medium border-black/15 text-black/65 hover:text-black">
        <Upload class="w-4 h-4" /> Export XLSX
      </Button>
    </div>

    <div class="flex items-center gap-3">
      <div class="flex items-center gap-0.5 bg-black/4 rounded-md p-1">
        <button @click="activeTab = 'partners'"
          class="flex items-center gap-2 px-4 py-1.5 text-sm rounded transition-all font-medium"
          :class="activeTab === 'partners' ? 'bg-white text-black shadow-sm' : 'text-black/40 hover:text-black/60'">
          <Building2 class="w-3.5 h-3.5" /> Business Partners ({{ businessPartners.length }})
        </button>
        <button @click="activeTab = 'suppliers'"
          class="flex items-center gap-2 px-4 py-1.5 text-sm rounded transition-all font-medium"
          :class="activeTab === 'suppliers' ? 'bg-white text-black shadow-sm' : 'text-black/40 hover:text-black/60'">
          <Truck class="w-3.5 h-3.5" /> Suppliers ({{ suppliersData.length }})
        </button>
      </div>
      <div class="ml-auto flex items-center gap-0.5 bg-black/4 rounded-lg p-1">
        <button @click="viewMode = 'card'" class="p-2 rounded-md transition-all"
          :class="viewMode === 'card' ? 'bg-white shadow-sm text-black' : 'text-black/35 hover:text-black/60'">
          <LayoutGrid class="w-4 h-4" />
        </button>
        <button @click="viewMode = 'table'" class="p-2 rounded-md transition-all"
          :class="viewMode === 'table' ? 'bg-white shadow-sm text-black' : 'text-black/35 hover:text-black/60'">
          <List class="w-4 h-4" />
        </button>
      </div>
    </div>

    <!-- Status filter pills -->
    <div class="flex items-center gap-0.5 bg-black/4 rounded-md p-1 w-fit">
      <button v-for="s in statusOptions" :key="s" @click="statusFilter = s"
        class="px-3 py-1.5 text-sm rounded transition-all font-medium"
        :class="statusFilter === s ? 'bg-white text-black shadow-sm' : 'text-black/40 hover:text-black/60'">
        {{ s }}
      </button>
    </div>

    <div class="flex items-center gap-3">
      <div class="relative flex-1">
        <Search class="w-3.5 h-3.5 text-black/30 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" />
        <input v-model="search" type="text" :placeholder="`Search ${activeTab === 'partners' ? 'partners' : 'suppliers'}...`"
          class="w-full h-9 rounded-lg border border-black/10 bg-white pl-8.5 pr-3 text-sm placeholder:text-black/25 focus:border-[#2E85D8] focus:outline-none transition" />
      </div>
      <select v-model="regionFilter"
        class="h-9 rounded-lg border border-black/10 bg-white px-3 text-sm text-black focus:border-[#2E85D8] focus:outline-none transition-colors">
        <option v-for="r in regions" :key="r" :value="r">{{ r === 'All' ? 'All Regions' : r }}</option>
      </select>
      <select v-model="industryFilter"
        class="h-9 rounded-lg border border-black/10 bg-white px-3 text-sm text-black focus:border-[#2E85D8] focus:outline-none transition-colors">
        <option v-for="ind in industries" :key="ind" :value="ind">{{ ind === 'All' ? 'All Industries' : ind }}</option>
      </select>
    </div>

    <PartnersGrid v-if="viewMode === 'card'" :partners="filtered" :active-tab="activeTab" :loading="loading" @open-detail="openDetail" />
    <PartnersTable v-else
      :paginated="paginated" :filtered="filtered" :active-tab="activeTab"
      :selected-ids="selectedIds" :all-page-selected="allPageSelected"
      :current-page="currentPage" :items-per-page="itemsPerPage"
      :loading="loading"
      @open-detail="openDetail"
      @toggle-row="toggleRow" @toggle-select-all="toggleSelectAll"
      @update:current-page="currentPage = $event"
    />

  </div>

</template>
