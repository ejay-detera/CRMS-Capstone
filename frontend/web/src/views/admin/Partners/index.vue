<script setup lang="ts">
import { computed, ref, watch, onMounted } from 'vue'
import { Building2, Truck, Search, LayoutGrid, List, Plus, Upload } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import * as XLSX from 'xlsx'
import { useToast } from '@/composables/useToast'
import { useAuth } from '@/composables/useAuth'
import { usePartners } from '@/composables/usePartners'
import PartnersGrid       from './PartnersGrid.vue'
import PartnersTable      from './PartnersTable.vue'
import PartnerDetailDialog from './PartnerDetailDialog.vue'
import DeleteConfirmDialog from './DeleteConfirmDialog.vue'
import AddPartnerDialog    from './AddPartnerDialog.vue'
import type { Partner, TabKey } from '@/types/partner'

const { success, error } = useToast()
const { state: authState } = useAuth()

const {
  partners,
  totalItems,
  fetchPartners,
  fetchVendorContracts,
  addPartner,
  deletePartner,
  linkContract,
  detachContract
} = usePartners()

const activeTab    = ref<TabKey>('partners')
const viewMode     = ref<'card' | 'table'>('card')
const search       = ref('')
const regionFilter = ref('All')
const regions      = ['All', 'Luzon', 'Visayas', 'Mindanao']

const partnersCount = ref(0)
const suppliersCount = ref(0)

const filtered = computed(() => new Array(totalItems.value))

const currentPage  = ref(1)
const itemsPerPage = 8

async function loadData() {
  try {
    await fetchPartners(activeTab.value, currentPage.value, itemsPerPage, search.value, regionFilter.value)
  } catch (err) {
    error('Fetch failed', 'Could not load data from server.')
  }
}

async function fetchCounts() {
  try {
    const apiBase = import.meta.env.VITE_VENDOR_API_URL || 'http://localhost:8001/api'
    const headers = {
      'Authorization': `Bearer ${authState.token || ''}`,
      'Accept': 'application/json'
    }
    const [resP, resS] = await Promise.all([
      fetch(`${apiBase}/partners?per_page=1`, { headers }),
      fetch(`${apiBase}/suppliers?per_page=1`, { headers })
    ])
    if (resP.ok) {
      const json = await resP.json()
      partnersCount.value = json.total || 0
    }
    if (resS.ok) {
      const json = await resS.json()
      suppliersCount.value = json.total || 0
    }
  } catch (err) {
    console.error('Failed to fetch counts:', err)
  }
}

watch([activeTab, search, regionFilter], () => {
  currentPage.value = 1
  loadData()
})

watch(currentPage, () => {
  loadData()
})

onMounted(() => {
  loadData()
  fetchCounts()
})

const selectedIds     = ref<string[]>([])
const allPageSelected = computed(() =>
  partners.value.length > 0 && partners.value.every(p => selectedIds.value.includes(p.id))
)
function toggleSelectAll() {
  const ids = partners.value.map(p => p.id)
  if (allPageSelected.value) selectedIds.value = selectedIds.value.filter(id => !ids.includes(id))
  else ids.forEach(id => { if (!selectedIds.value.includes(id)) selectedIds.value.push(id) })
}
function toggleRow(id: string) {
  const i = selectedIds.value.indexOf(id)
  i >= 0 ? selectedIds.value.splice(i, 1) : selectedIds.value.push(id)
}

const showDetail      = ref(false)
const selectedPartner = ref<Partner | null>(null)

async function openDetail(p: Partner) {
  selectedPartner.value = p
  showDetail.value = true
  if (p.db_id) {
    const contracts = await fetchVendorContracts(activeTab.value, p.db_id)
    if (selectedPartner.value && selectedPartner.value.id === p.id) {
      selectedPartner.value.linkedContracts = contracts
    }
  }
}

const showDelete   = ref(false)
const deleteTarget = ref<Partner | null>(null)
function openDelete(p: Partner) { deleteTarget.value = p; showDelete.value = true }

async function confirmDelete() {
  if (!deleteTarget.value || !deleteTarget.value.db_id) return
  const name = deleteTarget.value.name
  try {
    await deletePartner(activeTab.value, deleteTarget.value.db_id)
    showDelete.value  = false
    deleteTarget.value = null
    success('Entry deleted', `${name} has been removed.`)
    loadData()
    fetchCounts()
  } catch (err: any) {
    error('Delete failed', err.message || 'Could not delete entry.')
  }
}

const showAdd = ref(false)
async function handleAdd(partial: Omit<Partner, 'contracts' | 'totalValue'>) {
  try {
    await addPartner(activeTab.value, partial)
    success(`${activeTab.value === 'partners' ? 'Partner' : 'Supplier'} added`, `${partial.name} has been added successfully.`)
    showAdd.value = false
    loadData()
    fetchCounts()
  } catch (err: any) {
    error('Addition failed', err.message || 'Could not add entry.')
  }
}

function exportXLSX() {
  const type = activeTab.value === 'partners' ? 'Business Partner' : 'Supplier'
  const rows = partners.value.map(p => ({
    'ID': p.id, 'Name': p.name, 'Type': type, 'Industry': p.industry, 'Region': p.region,
    'Contracts': p.contracts, 'Total Value': p.totalValue, 'Status': p.status,
    'Contact Person': p.contactPerson, 'Email': p.email, 'Phone': p.phone, 'Address': p.address,
  }))
  const ws = XLSX.utils.json_to_sheet(rows)
  const wb = XLSX.utils.book_new()
  XLSX.utils.book_append_sheet(wb, ws, type === 'Business Partner' ? 'Partners' : 'Suppliers')
  XLSX.writeFile(wb, `sbsi-${activeTab.value}.xlsx`)
  success('Export complete', `${partners.value.length} records exported.`)
}

async function handleDetachContract(associationId: string) {
  if (!selectedPartner.value || !selectedPartner.value.db_id) return
  const foundContract = selectedPartner.value.linkedContracts?.find(c => c.associationId === associationId)
  if (foundContract) {
    try {
      await detachContract(activeTab.value, selectedPartner.value.db_id, foundContract.contractId)
      const contracts = await fetchVendorContracts(activeTab.value, selectedPartner.value.db_id)
      selectedPartner.value.linkedContracts = contracts
      selectedPartner.value.contracts = contracts.length
      loadData()
      success('Contract unlinked', 'The contract has been detached successfully.')
    } catch (err: any) {
      error('Detach failed', err.message || 'Could not detach contract.')
    }
  }
}

async function handleLinkContract(contractId: string) {
  if (!selectedPartner.value || !selectedPartner.value.db_id) return
  try {
    await linkContract(activeTab.value, selectedPartner.value.db_id, contractId)
    const contracts = await fetchVendorContracts(activeTab.value, selectedPartner.value.db_id)
    selectedPartner.value.linkedContracts = contracts
    selectedPartner.value.contracts = contracts.length
    loadData()
    success('Contract linked', `Contract is now associated with ${selectedPartner.value.name}.`)
  } catch (err: any) {
    error('Link failed', err.message || 'Could not link contract.')
  }
}

</script>

<template>
  <div class="p-8 space-y-6">

    <div class="flex items-start justify-between">
      <div>
        <h1 class="text-xl font-semibold text-black">Partners & Suppliers</h1>
        <p class="text-sm text-black/40 mt-0.5">Manage business relationships.</p>
      </div>
      <div class="flex items-center gap-2">
        <Button @click="exportXLSX" variant="outline" class="h-9 gap-2 text-sm font-medium border-black/15 text-black/65 hover:text-black">
          <Upload class="w-4 h-4" /> Export XLSX
        </Button>
        <Button @click="showAdd = true" class="h-9 w-9 p-0 bg-[#252578] hover:bg-[#2F2F73] text-white rounded-lg shadow-sm">
          <Plus class="w-5 h-5" />
        </Button>
      </div>
    </div>

    <div class="flex items-center gap-3">
      <div class="flex items-center gap-0.5 bg-black/4 rounded-md p-1">
        <button @click="activeTab = 'partners'; search = ''; regionFilter = 'All'"
          class="flex items-center gap-2 px-4 py-1.5 text-sm rounded transition-all font-medium"
          :class="activeTab === 'partners' ? 'bg-white text-black shadow-sm' : 'text-black/40 hover:text-black/60'">
          <Building2 class="w-3.5 h-3.5" /> Business Partners ({{ partnersCount }})
        </button>
        <button @click="activeTab = 'suppliers'; search = ''; regionFilter = 'All'"
          class="flex items-center gap-2 px-4 py-1.5 text-sm rounded transition-all font-medium"
          :class="activeTab === 'suppliers' ? 'bg-white text-black shadow-sm' : 'text-black/40 hover:text-black/60'">
          <Truck class="w-3.5 h-3.5" /> Suppliers ({{ suppliersCount }})
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

    <PartnersGrid v-if="viewMode === 'card'" :partners="partners" :active-tab="activeTab" @open-detail="openDetail" />

    <PartnersTable v-else
      :paginated="partners" :filtered="filtered" :active-tab="activeTab"
      :selected-ids="selectedIds" :all-page-selected="allPageSelected"
      :current-page="currentPage" :items-per-page="itemsPerPage"
      @open-detail="openDetail" @open-delete="openDelete"
      @toggle-row="toggleRow" @toggle-select-all="toggleSelectAll"
      @update:current-page="currentPage = $event"
    />

  </div>

  <PartnerDetailDialog
    v-model:open="showDetail"
    :partner="selectedPartner"
    :active-tab="activeTab"
    @detach-contract="handleDetachContract"
    @link-contract="handleLinkContract"
  />
  <DeleteConfirmDialog v-model:open="showDelete" :partner="deleteTarget"   :active-tab="activeTab" @confirm="confirmDelete" />
  <AddPartnerDialog    v-model:open="showAdd"    :active-tab="activeTab"   @submit="handleAdd" />
</template>
