<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { Building2, Truck, Search, LayoutGrid, List, Plus, Upload } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import * as XLSX from 'xlsx'
import { useAuth } from '@/composables/useAuth'
import { useToast } from '@/composables/useToast'
import PartnersGrid       from '@/views/admin/Partners/PartnersGrid.vue'
import PartnersTable      from '@/views/admin/Partners/PartnersTable.vue'
import PartnerDetailDialog from '@/views/admin/Partners/PartnerDetailDialog.vue'
import DeleteConfirmDialog from '@/views/admin/Partners/DeleteConfirmDialog.vue'
import AddPartnerDialog    from '@/views/admin/Partners/AddPartnerDialog.vue'
import type { Partner, TabKey } from '@/types/partner'
import { initialBusinessPartners, initialSuppliersData } from '@/views/admin/Partners/mockPartners'

const { hasPermission } = useAuth()
const { success } = useToast()

// Permission gates
const canCreate = computed(() => hasPermission('crms.partners.create'))
const canDelete  = computed(() => hasPermission('crms.partners.delete'))

const businessPartners = ref<Partner[]>([...initialBusinessPartners])

const suppliersData = ref<Partner[]>([...initialSuppliersData])

const activeTab    = ref<TabKey>('partners')
const viewMode     = ref<'card' | 'table'>('card')
const search       = ref('')
const regionFilter = ref('All')
const regions      = ['All', 'Luzon', 'Visayas', 'Mindanao']

const source   = computed(() => activeTab.value === 'partners' ? businessPartners.value : suppliersData.value)
const filtered = computed(() =>
  source.value.filter(p => {
    const q = search.value.toLowerCase()
    return (!q || p.name.toLowerCase().includes(q) || p.industry.toLowerCase().includes(q))
      && (regionFilter.value === 'All' || p.region === regionFilter.value)
  })
)

const currentPage  = ref(1)
const itemsPerPage = 8
watch([activeTab, search, regionFilter], () => { currentPage.value = 1 })
const paginated = computed(() => filtered.value.slice((currentPage.value - 1) * itemsPerPage, currentPage.value * itemsPerPage))

const selectedIds     = ref<string[]>([])
const allPageSelected = computed(() =>
  paginated.value.length > 0 && paginated.value.every(p => selectedIds.value.includes(p.id))
)
function toggleSelectAll() {
  const ids = paginated.value.map(p => p.id)
  if (allPageSelected.value) selectedIds.value = selectedIds.value.filter(id => !ids.includes(id))
  else ids.forEach(id => { if (!selectedIds.value.includes(id)) selectedIds.value.push(id) })
}
function toggleRow(id: string) {
  const i = selectedIds.value.indexOf(id)
  i >= 0 ? selectedIds.value.splice(i, 1) : selectedIds.value.push(id)
}

const showDetail      = ref(false)
const selectedPartner = ref<Partner | null>(null)
function openDetail(p: Partner) { selectedPartner.value = p; showDetail.value = true }

const showDelete   = ref(false)
const deleteTarget = ref<Partner | null>(null)
function openDelete(p: Partner) {
  if (!canDelete.value) return
  deleteTarget.value = p; showDelete.value = true
}
function confirmDelete() {
  if (!deleteTarget.value) return
  const list = activeTab.value === 'partners' ? businessPartners : suppliersData
  const idx  = list.value.findIndex(p => p.id === deleteTarget.value!.id)
  if (idx >= 0) list.value.splice(idx, 1)
  selectedIds.value = selectedIds.value.filter(id => id !== deleteTarget.value!.id)
  const name = deleteTarget.value.name
  showDelete.value  = false
  deleteTarget.value = null
  success('Entry deleted', `${name} has been removed.`)
}

const showAdd = ref(false)
function handleAdd(partial: Omit<Partner, 'contracts' | 'totalValue'>) {
  const list   = activeTab.value === 'partners' ? businessPartners : suppliersData
  const prefix = activeTab.value === 'partners' ? 'BP' : 'SP'
  const pad    = String(list.value.length + 1).padStart(3, '0')
  list.value.push({ ...partial, id: `${prefix}-${pad}`, contracts: 0, totalValue: '₱0' })
  success(`${activeTab.value === 'partners' ? 'Partner' : 'Supplier'} added`, `${partial.name} has been added successfully.`)
}

function exportXLSX() {
  const type = activeTab.value === 'partners' ? 'Business Partner' : 'Supplier'
  const rows = filtered.value.map(p => ({
    'ID': p.id, 'Name': p.name, 'Type': type, 'Industry': p.industry, 'Region': p.region,
    'Contracts': p.contracts, 'Total Value': p.totalValue, 'Status': p.status,
    'Contact Person': p.contactPerson, 'Email': p.email, 'Phone': p.phone, 'Address': p.address,
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
      <div class="flex items-center gap-2">
        <Button @click="exportXLSX" variant="outline" class="h-9 gap-2 text-sm font-medium border-black/15 text-black/65 hover:text-black">
          <Upload class="w-4 h-4" /> Export XLSX
        </Button>
        <!-- Add button only shown when user has create permission -->
        <Button
          v-if="canCreate"
          @click="showAdd = true"
          class="h-9 w-9 p-0 bg-[#252578] hover:bg-[#2F2F73] text-white rounded-lg shadow-sm"
        >
          <Plus class="w-5 h-5" />
        </Button>
      </div>
    </div>

    <div class="flex items-center gap-3">
      <div class="flex items-center gap-0.5 bg-black/4 rounded-md p-1">
        <button @click="activeTab = 'partners'; search = ''; regionFilter = 'All'"
          class="flex items-center gap-2 px-4 py-1.5 text-sm rounded transition-all font-medium"
          :class="activeTab === 'partners' ? 'bg-white text-black shadow-sm' : 'text-black/40 hover:text-black/60'">
          <Building2 class="w-3.5 h-3.5" /> Business Partners ({{ businessPartners.length }})
        </button>
        <button @click="activeTab = 'suppliers'; search = ''; regionFilter = 'All'"
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
    </div>

    <PartnersGrid v-if="viewMode === 'card'" :partners="filtered" :active-tab="activeTab" @open-detail="openDetail" />

    <PartnersTable v-else
      :paginated="paginated" :filtered="filtered" :active-tab="activeTab"
      :selected-ids="selectedIds" :all-page-selected="allPageSelected"
      :current-page="currentPage" :items-per-page="itemsPerPage"
      @open-detail="openDetail" @open-delete="openDelete"
      @toggle-row="toggleRow" @toggle-select-all="toggleSelectAll"
      @update:current-page="currentPage = $event"
    />

  </div>

  <PartnerDetailDialog v-model:open="showDetail" :partner="selectedPartner" :active-tab="activeTab" />
  <DeleteConfirmDialog v-if="canDelete" v-model:open="showDelete" :partner="deleteTarget" :active-tab="activeTab" @confirm="confirmDelete" />
  <AddPartnerDialog    v-if="canCreate" v-model:open="showAdd"   :active-tab="activeTab" @submit="handleAdd" />
</template>
