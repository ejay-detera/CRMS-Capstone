<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import {
  Building2, Truck, Search, LayoutGrid, List, Plus,
  Upload, MoreHorizontal, Eye, Trash2, MapPin,
  FileText, Phone, Mail, User, AlertTriangle,
} from 'lucide-vue-next'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import {
  Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
} from '@/components/ui/table'
import {
  DropdownMenu, DropdownMenuContent, DropdownMenuItem,
  DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import {
  Pagination, PaginationContent, PaginationEllipsis,
  PaginationItem, PaginationNext, PaginationPrevious,
} from '@/components/ui/pagination'
import {
  Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter,
} from '@/components/ui/dialog'
import {
  Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/components/ui/select'
import * as XLSX from 'xlsx'
import { useToast } from '@/composables/useToast'

const { success } = useToast()

// ── Types ───────────────────────────────────────────────────────────
type TabKey = 'partners' | 'suppliers'
type Region = 'Luzon' | 'Visayas' | 'Mindanao'
type Status = 'Active' | 'Inactive'

interface Partner {
  id: string; name: string; industry: string; region: Region
  status: Status; contracts: number; totalValue: string
  contactPerson: string; email: string; phone: string; address: string
}

// ── Data ────────────────────────────────────────────────────────────
const businessPartners: Partner[] = [
  { id: 'BP-001', name: 'Philippine National Bank', industry: 'Banking & Finance',    region: 'Luzon',    status: 'Active',   contracts: 2, totalValue: '₱3.2M', contactPerson: 'Jose Reyes',       email: 'j.reyes@pnb.com.ph',              phone: '+63 2 8573 8888', address: 'PNB Financial Center, Roxas Blvd, Pasay City'       },
  { id: 'BP-002', name: 'BDO Unibank',              industry: 'Banking & Finance',    region: 'Luzon',    status: 'Active',   contracts: 1, totalValue: '₱1.8M', contactPerson: 'Maria Dela Cruz',  email: 'm.delacruz@bdo.com.ph',           phone: '+63 2 8840 7000', address: 'BDO Corporate Center, Makati City'                  },
  { id: 'BP-003', name: 'Cebu Pacific Air',         industry: 'Aviation & Transport', region: 'Visayas',  status: 'Active',   contracts: 1, totalValue: '₱0.9M', contactPerson: 'Anna Santos',      email: 'a.santos@cebupacificair.com',     phone: '+63 32 230 8888', address: 'Gokongwei Building, Pasay City'                     },
  { id: 'BP-004', name: 'SM Prime Holdings',        industry: 'Real Estate & Retail', region: 'Luzon',    status: 'Active',   contracts: 0, totalValue: '₱3.2M', contactPerson: 'Carlos Tan',       email: 'c.tan@smprime.com',               phone: '+63 2 8831 1000', address: 'Mall of Asia Complex, Pasay City'                   },
  { id: 'BP-005', name: 'Metrobank',                industry: 'Banking & Finance',    region: 'Mindanao', status: 'Active',   contracts: 1, totalValue: '₱1.5M', contactPerson: 'Luis Garcia',      email: 'l.garcia@metrobank.com.ph',       phone: '+63 82 226 3891', address: 'Metrobank Plaza, Davao City'                        },
  { id: 'BP-006', name: 'Globe Telecom',            industry: 'Telecommunications',  region: 'Luzon',    status: 'Active',   contracts: 1, totalValue: '₱4.5M', contactPerson: 'Rachel Lim',       email: 'r.lim@globe.com.ph',              phone: '+63 2 7730 1000', address: 'The Globe Tower, Bonifacio Global City'             },
  { id: 'BP-007', name: 'Philippine Airlines',      industry: 'Aviation & Transport', region: 'Visayas',  status: 'Active',   contracts: 1, totalValue: '₱2.1M', contactPerson: 'Miguel Torres',    email: 'm.torres@philippineairlines.com', phone: '+63 2 8855 8888', address: 'Andrews Avenue, Pasay City'                         },
  { id: 'BP-008', name: 'PLDT',                     industry: 'Telecommunications',  region: 'Mindanao', status: 'Active',   contracts: 1, totalValue: '₱5.8M', contactPerson: 'Sara Uy',          email: 's.uy@pldt.com.ph',                phone: '+63 2 8816 8888', address: 'PLDT-Smart Tower, Makati City'                      },
]

const suppliersData: Partner[] = [
  { id: 'SP-001', name: 'MedLine Philippines',   industry: 'Medical Supplies', region: 'Luzon',    status: 'Active',   contracts: 3, totalValue: '₱2.1M', contactPerson: 'Dr. Elena Ramos',  email: 'e.ramos@medline.ph',        phone: '+63 2 8123 4567', address: '123 Bonifacio St., Mandaluyong City' },
  { id: 'SP-002', name: 'Bio-Tech Logistics',    industry: 'Logistics',        region: 'Luzon',    status: 'Active',   contracts: 2, totalValue: '₱1.3M', contactPerson: 'Ryan Cruz',        email: 'r.cruz@biotech-log.com',    phone: '+63 2 8987 6543', address: '456 Ortigas Ave., Pasig City'       },
  { id: 'SP-003', name: 'Global Pharma Inc.',    industry: 'Pharmaceutical',   region: 'Visayas',  status: 'Active',   contracts: 1, totalValue: '₱0.8M', contactPerson: 'Dr. Peter Go',     email: 'p.go@globalpharma.com',     phone: '+63 32 412 3456', address: '789 Colon St., Cebu City'          },
  { id: 'SP-004', name: 'Stellar Lab Equipment', industry: 'Equipment',        region: 'Luzon',    status: 'Inactive', contracts: 0, totalValue: '₱1.5M', contactPerson: 'Nina Bautista',    email: 'n.bautista@stellarlab.com', phone: '+63 2 8765 4321', address: '321 Science Drive, Quezon City'    },
  { id: 'SP-005', name: 'BioGenesis Research',   industry: 'Research',         region: 'Mindanao', status: 'Active',   contracts: 2, totalValue: '₱3.8M', contactPerson: 'Dr. James Molo',   email: 'j.molo@biogenesis.ph',      phone: '+63 82 300 1234', address: '55 Quimpo Blvd., Davao City'       },
  { id: 'SP-006', name: 'PharmaCare Dist.',      industry: 'Pharmaceutical',   region: 'Luzon',    status: 'Active',   contracts: 1, totalValue: '₱2.5M', contactPerson: 'Lyn Navarro',      email: 'l.navarro@pharmacare.ph',   phone: '+63 2 8234 5678', address: '88 Shaw Blvd., Mandaluyong City'   },
  { id: 'SP-007', name: 'LabTech Solutions',     industry: 'Equipment',        region: 'Visayas',  status: 'Active',   contracts: 1, totalValue: '₱1.2M', contactPerson: 'Mark Villanueva',  email: 'm.villanueva@labtech.com',  phone: '+63 33 509 8765', address: '77 Iznart St., Iloilo City'        },
  { id: 'SP-008', name: 'MediSource PH',         industry: 'Medical Supplies', region: 'Mindanao', status: 'Inactive', contracts: 0, totalValue: '₱0.7M', contactPerson: 'Donna Flores',     email: 'd.flores@medisource.ph',    phone: '+63 88 857 6543', address: '12 Cagayan de Oro City'            },
]

// ── State ────────────────────────────────────────────────────────────
const activeTab    = ref<TabKey>('partners')
const viewMode     = ref<'card' | 'table'>('card')
const search       = ref('')
const regionFilter = ref('All')
const regions      = ['All', 'Luzon', 'Visayas', 'Mindanao']

// ── Source + filter ──────────────────────────────────────────────────
const source = computed(() => activeTab.value === 'partners' ? businessPartners : suppliersData)

const filtered = computed(() =>
  source.value.filter(p => {
    const q = search.value.toLowerCase()
    const matchSearch = !q || p.name.toLowerCase().includes(q) || p.industry.toLowerCase().includes(q)
    const matchRegion = regionFilter.value === 'All' || p.region === regionFilter.value
    return matchSearch && matchRegion
  })
)

// ── Pagination ────────────────────────────────────────────────────────
const currentPage  = ref(1)
const itemsPerPage = 8

watch([activeTab, search, regionFilter], () => { currentPage.value = 1 })

const paginated = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage
  return filtered.value.slice(start, start + itemsPerPage)
})

// ── Row selection ─────────────────────────────────────────────────────
const selectedIds = ref<string[]>([])

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

// ── Detail dialog ─────────────────────────────────────────────────────
const showDetail      = ref(false)
const selectedPartner = ref<Partner | null>(null)
function openDetail(p: Partner) { selectedPartner.value = p; showDetail.value = true }

// ── Delete dialog ─────────────────────────────────────────────────────
const showDeleteConfirm = ref(false)
const deleteTarget      = ref<Partner | null>(null)

function openDeleteConfirm(p: Partner) { deleteTarget.value = p; showDeleteConfirm.value = true }

function confirmDelete() {
  if (!deleteTarget.value) return
  const list = activeTab.value === 'partners' ? businessPartners : suppliersData
  const idx  = list.findIndex(p => p.id === deleteTarget.value!.id)
  if (idx >= 0) list.splice(idx, 1)
  selectedIds.value       = selectedIds.value.filter(id => id !== deleteTarget.value!.id)
  const name              = deleteTarget.value.name
  showDeleteConfirm.value = false
  deleteTarget.value      = null
  success('Entry deleted', `${name} has been removed.`)
}

// ── Add Partner dialog ────────────────────────────────────────────────
const showAddPartner = ref(false)

interface AddForm {
  name: string; industry: string; region: Region | ''
  status: Status; contactPerson: string; email: string
  phone: string; address: string
}

const addForm = reactive<AddForm>({
  name: '', industry: '', region: '',
  status: 'Active', contactPerson: '', email: '',
  phone: '', address: '',
})

const addTouched = reactive({
  name: false, industry: false, region: false,
  contactPerson: false, email: false, phone: false, address: false,
})

const addEmailValid = computed(() =>
  !addForm.email || /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(addForm.email)
)

function resetAddForm() {
  Object.assign(addForm, {
    name: '', industry: '', region: '',
    status: 'Active', contactPerson: '', email: '',
    phone: '', address: '',
  })
  Object.assign(addTouched, {
    name: false, industry: false, region: false,
    contactPerson: false, email: false, phone: false, address: false,
  })
}

watch(showAddPartner, open => { if (!open) resetAddForm() })

function submitAddPartner() {
  Object.keys(addTouched).forEach(k => ((addTouched as Record<string, boolean>)[k] = true))

  if (!addForm.name || !addForm.industry || !addForm.region ||
      !addForm.contactPerson || !addForm.email || !addEmailValid.value ||
      !addForm.phone || !addForm.address) return

  const list   = activeTab.value === 'partners' ? businessPartners : suppliersData
  const prefix = activeTab.value === 'partners' ? 'BP' : 'SP'
  const pad    = String(list.length + 1).padStart(3, '0')

  list.push({
    id:            `${prefix}-${pad}`,
    name:          addForm.name,
    industry:      addForm.industry,
    region:        addForm.region as Region,
    status:        addForm.status,
    contracts:     0,
    totalValue:    '₱0',
    contactPerson: addForm.contactPerson,
    email:         addForm.email,
    phone:         addForm.phone,
    address:       addForm.address,
  })

  showAddPartner.value = false
  success(
    `${activeTab.value === 'partners' ? 'Partner' : 'Supplier'} added`,
    `${addForm.name} has been added successfully.`,
  )
}

// ── Helpers ───────────────────────────────────────────────────────────
const palette = ['#252578', '#2E85D8', '#2F2F73']
function avatarColor(id: string) {
  const idx = parseInt(id.split('-')[1]) - 1
  return palette[idx % palette.length]
}

// ── Export XLSX ───────────────────────────────────────────────────────
function exportXLSX() {
  const type = activeTab.value === 'partners' ? 'Business Partner' : 'Supplier'
  const rows = filtered.value.map(p => ({
    'ID':             p.id,
    'Name':           p.name,
    'Type':           type,
    'Industry':       p.industry,
    'Region':         p.region,
    'Contracts':      p.contracts,
    'Total Value':    p.totalValue,
    'Status':         p.status,
    'Contact Person': p.contactPerson,
    'Email':          p.email,
    'Phone':          p.phone,
    'Address':        p.address,
  }))

  const ws = XLSX.utils.json_to_sheet(rows)
  ws['!cols'] = [
    { wch: 10 }, { wch: 28 }, { wch: 18 }, { wch: 22 }, { wch: 10 },
    { wch: 11 }, { wch: 13 }, { wch: 10 }, { wch: 22 }, { wch: 30 },
    { wch: 18 }, { wch: 38 },
  ]
  const wb = XLSX.utils.book_new()
  XLSX.utils.book_append_sheet(wb, ws, type === 'Business Partner' ? 'Partners' : 'Suppliers')
  XLSX.writeFile(wb, `sbsi-${activeTab.value}.xlsx`)
  success('Export complete', `${filtered.value.length} records exported.`)
}
</script>

<template>
  <div class="p-8 space-y-6">

    <!-- ── Header ─────────────────────────────────────────────────── -->
    <div class="flex items-start justify-between">
      <div>
        <h1 class="text-xl font-semibold text-black">Partners & Suppliers</h1>
        <p class="text-sm text-black/40 mt-0.5">Manage business relationships.</p>
      </div>
      <div class="flex items-center gap-2">
        <Button @click="exportXLSX" variant="outline" class="h-9 gap-2 text-sm font-medium border-black/15 text-black/65 hover:text-black">
          <Upload class="w-4 h-4" />
          Export XLSX
        </Button>
        <Button @click="showAddPartner = true" class="h-9 w-9 p-0 bg-[#252578] hover:bg-[#2F2F73] text-white rounded-lg shadow-sm">
          <Plus class="w-5 h-5" />
        </Button>
      </div>
    </div>

    <!-- ── Tabs + View toggle ─────────────────────────────────────── -->
    <div class="flex items-center gap-3">
      <div class="flex items-center gap-0.5 bg-black/4 rounded-md p-1">
        <button
          @click="activeTab = 'partners'; search = ''; regionFilter = 'All'"
          class="flex items-center gap-2 px-4 py-1.5 text-sm rounded transition-all font-medium"
          :class="activeTab === 'partners' ? 'bg-white text-black shadow-sm' : 'text-black/40 hover:text-black/60'"
        >
          <Building2 class="w-3.5 h-3.5" />
          Business Partners ({{ businessPartners.length }})
        </button>
        <button
          @click="activeTab = 'suppliers'; search = ''; regionFilter = 'All'"
          class="flex items-center gap-2 px-4 py-1.5 text-sm rounded transition-all font-medium"
          :class="activeTab === 'suppliers' ? 'bg-white text-black shadow-sm' : 'text-black/40 hover:text-black/60'"
        >
          <Truck class="w-3.5 h-3.5" />
          Suppliers ({{ suppliersData.length }})
        </button>
      </div>

      <!-- View toggle (right side) -->
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

    <!-- ── Search + Region (shared toolbar) ───────────────────────── -->
    <div class="flex items-center gap-3">
      <div class="relative flex-1">
        <Search class="w-3.5 h-3.5 text-black/30 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" />
        <input
          v-model="search"
          type="text"
          :placeholder="`Search ${activeTab === 'partners' ? 'partners' : 'suppliers'}...`"
          class="w-full h-9 rounded-lg border border-black/10 bg-white pl-8.5 pr-3 text-sm placeholder:text-black/25 focus:border-[#2E85D8] focus:outline-none focus:ring-2 focus:ring-[#2E85D8]/15 transition"
        />
      </div>
      <select
        v-model="regionFilter"
        class="h-9 rounded-lg border border-black/10 bg-white px-3 text-sm text-black focus:border-[#2E85D8] focus:outline-none transition-colors"
      >
        <option v-for="r in regions" :key="r" :value="r">{{ r === 'All' ? 'All Regions' : r }}</option>
      </select>
    </div>

    <!-- ── Card View ───────────────────────────────────────────────── -->
    <div v-if="viewMode === 'card'" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
      <button
        v-for="partner in filtered"
        :key="partner.id"
        @click="openDetail(partner)"
        class="text-left bg-white rounded-xl border border-black/8 shadow-sm p-5 hover:border-[#2E85D8]/40 hover:shadow-md transition-all duration-200 group"
      >
        <div class="flex items-start justify-between mb-4">
          <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 bg-[#252578]/8 text-[#252578]">
            <component :is="activeTab === 'partners' ? Building2 : Truck" class="w-5 h-5" />
          </div>
          <span class="text-xs font-medium px-2.5 py-0.5 rounded-full border"
            :class="partner.status === 'Active'
              ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
              : 'bg-black/4 text-black/35 border-black/8'">
            {{ partner.status }}
          </span>
        </div>
        <p class="text-sm font-semibold text-black leading-snug group-hover:text-[#252578] transition-colors">{{ partner.name }}</p>
        <p class="text-xs text-black/40 mt-0.5 mb-4">{{ partner.industry }}</p>
        <div class="space-y-1.5 border-t border-black/5 pt-3">
          <div class="flex items-center justify-between text-xs">
            <span class="text-black/40">Region</span>
            <span class="font-medium text-black">{{ partner.region }}</span>
          </div>
          <div class="flex items-center justify-between text-xs">
            <span class="text-black/40">Contracts</span>
            <span class="font-semibold" :class="partner.contracts > 0 ? 'text-[#2E85D8]' : 'text-black/35'">
              {{ partner.contracts }} active
            </span>
          </div>
          <div class="flex items-center justify-between text-xs">
            <span class="text-black/40">Total Value</span>
            <span class="font-medium text-black">{{ partner.totalValue }}</span>
          </div>
        </div>
      </button>
      <div v-if="filtered.length === 0" class="col-span-full py-16 text-center">
        <p class="text-sm font-medium text-black/40">No results found.</p>
        <p class="text-xs text-black/25 mt-1">Try adjusting your search or region filter.</p>
      </div>
    </div>

    <!-- ── Table View ──────────────────────────────────────────────── -->
    <div v-else class="bg-white rounded-lg border border-black/8 shadow-sm overflow-hidden">

      <!-- Section heading -->
      <div class="px-6 pt-5 pb-4 border-b border-black/5">
        <h2 class="text-sm font-semibold text-black">
          {{ activeTab === 'partners' ? 'Business Partners' : 'Suppliers' }}
          <span class="text-black/30 font-normal">({{ filtered.length }})</span>
        </h2>
      </div>

      <Table>
        <TableHeader class="bg-black/[0.018]">
          <TableRow class="border-b border-black/[0.04] hover:bg-transparent">
            <TableHead class="w-12 pl-6 py-3">
              <input type="checkbox" :checked="allPageSelected" @change="toggleSelectAll"
                class="w-4 h-4 rounded border-black/20 accent-[#252578] cursor-pointer" />
            </TableHead>
            <TableHead class="text-[11px] font-semibold text-black/40 uppercase tracking-wider py-3">Name</TableHead>
            <TableHead class="text-[11px] font-semibold text-black/40 uppercase tracking-wider py-3">Industry</TableHead>
            <TableHead class="text-[11px] font-semibold text-black/40 uppercase tracking-wider py-3">Region</TableHead>
            <TableHead class="text-[11px] font-semibold text-black/40 uppercase tracking-wider py-3">Contracts</TableHead>
            <TableHead class="text-[11px] font-semibold text-black/40 uppercase tracking-wider py-3">Total Value</TableHead>
            <TableHead class="text-[11px] font-semibold text-black/40 uppercase tracking-wider py-3">Status</TableHead>
            <TableHead class="w-14 py-3" />
          </TableRow>
        </TableHeader>

        <TableBody>
          <TableRow
            v-for="partner in paginated"
            :key="partner.id"
            class="border-b border-black/4 last:border-0 transition-colors"
            :class="selectedIds.includes(partner.id) ? 'bg-[#252578]/2.5' : 'hover:bg-black/[0.012]'"
          >
            <!-- Checkbox -->
            <TableCell class="pl-6 py-4">
              <input type="checkbox" :checked="selectedIds.includes(partner.id)" @change="toggleRow(partner.id)"
                class="w-4 h-4 rounded border-black/20 accent-[#252578] cursor-pointer" />
            </TableCell>

            <!-- Name -->
            <TableCell class="py-4">
              <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0 bg-[#252578]/8 text-[#252578]">
                  <component :is="activeTab === 'partners' ? Building2 : Truck" class="w-4 h-4" />
                </div>
                <div>
                  <p class="text-sm font-medium text-black leading-snug">{{ partner.name }}</p>
                  <p class="text-xs text-black/35 mt-0.5">{{ partner.id }}</p>
                </div>
              </div>
            </TableCell>

            <!-- Industry -->
            <TableCell class="py-4 text-sm text-black/60">{{ partner.industry }}</TableCell>

            <!-- Region -->
            <TableCell class="py-4 text-sm text-black/60">{{ partner.region }}</TableCell>

            <!-- Contracts -->
            <TableCell class="py-4">
              <span class="text-sm font-semibold" :class="partner.contracts > 0 ? 'text-[#2E85D8]' : 'text-black/30'">
                {{ partner.contracts }} active
              </span>
            </TableCell>

            <!-- Total Value -->
            <TableCell class="py-4 text-sm font-medium text-black">{{ partner.totalValue }}</TableCell>

            <!-- Status -->
            <TableCell class="py-4">
              <span class="text-xs font-medium px-2.5 py-0.5 rounded-full border"
                :class="partner.status === 'Active'
                  ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                  : 'bg-black/4 text-black/35 border-black/8'">
                {{ partner.status }}
              </span>
            </TableCell>

            <!-- Actions (3-dot) -->
            <TableCell class="py-4 pr-4">
              <DropdownMenu>
                <DropdownMenuTrigger as-child>
                  <Button variant="ghost" size="icon"
                    class="h-8 w-8 text-black/25 hover:text-black hover:bg-black/5 data-[state=open]:bg-black/5 data-[state=open]:text-black">
                    <MoreHorizontal class="w-4 h-4" />
                  </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" class="w-44">
                  <DropdownMenuLabel class="text-xs font-semibold text-black/38 pb-1">Actions</DropdownMenuLabel>
                  <DropdownMenuSeparator />
                  <DropdownMenuItem @click="openDetail(partner)" class="gap-2.5 text-sm cursor-pointer">
                    <Eye class="w-3.5 h-3.5 text-black/40" />
                    View details
                  </DropdownMenuItem>
                  <DropdownMenuSeparator />
                  <DropdownMenuItem @click="openDeleteConfirm(partner)"
                    class="gap-2.5 text-sm cursor-pointer text-red-600 focus:text-red-600 focus:bg-red-50">
                    <Trash2 class="w-3.5 h-3.5" />
                    Delete
                  </DropdownMenuItem>
                </DropdownMenuContent>
              </DropdownMenu>
            </TableCell>
          </TableRow>

          <TableRow v-if="paginated.length === 0">
            <TableCell colspan="8" class="text-center py-16">
              <p class="text-sm font-semibold text-black/28">No records found</p>
              <p class="text-xs text-black/20 mt-1">Try a different search or region filter</p>
            </TableCell>
          </TableRow>
        </TableBody>
      </Table>

      <!-- Pagination -->
      <Pagination :total="filtered.length" :sibling-count="1" :items-per-page="itemsPerPage" v-model:page="currentPage">
        <div class="grid grid-cols-3 items-center px-6 py-4 border-t border-black/5">
          <div class="flex justify-start">
            <PaginationPrevious />
          </div>
          <div class="flex justify-center">
            <PaginationContent v-slot="{ items }" class="flex items-center gap-1">
              <template v-for="(item, index) in items">
                <PaginationItem v-if="item.type === 'page'" :key="index" :value="item.value"
                  :is-active="item.value === currentPage"
                  :class="item.value === currentPage
                    ? 'bg-[#252578] text-white hover:bg-[#2F2F73] hover:text-white border-transparent font-semibold'
                    : 'text-black/50 hover:bg-black/5'">
                  {{ item.value }}
                </PaginationItem>
                <PaginationEllipsis v-else :key="item.type" :index="index" />
              </template>
            </PaginationContent>
          </div>
          <div class="flex justify-end">
            <PaginationNext />
          </div>
        </div>
      </Pagination>
    </div>

  </div>

  <!-- ── Detail Dialog ───────────────────────────────────────────────── -->
  <Dialog :open="showDetail" @update:open="showDetail = $event">
    <DialogContent class="max-w-md p-0 overflow-hidden" @pointer-down-outside="showDetail = false">
      <template v-if="selectedPartner">
        <div class="px-6 pt-6 pb-5 bg-[#252578]/6">
          <div class="flex items-start justify-between">
            <div class="flex items-center gap-4">
              <div class="w-12 h-12 rounded-xl flex items-center justify-center text-white shrink-0 bg-[#252578]">
                <component :is="activeTab === 'partners' ? Building2 : Truck" class="w-6 h-6" />
              </div>
              <div>
                <DialogTitle class="text-base font-semibold text-black leading-snug">{{ selectedPartner.name }}</DialogTitle>
                <p class="text-xs text-black/50 mt-0.5">{{ selectedPartner.industry }}</p>
              </div>
            </div>
            <span class="text-xs font-medium px-2.5 py-0.5 rounded-full border shrink-0"
              :class="selectedPartner.status === 'Active'
                ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                : 'bg-black/4 text-black/35 border-black/8'">
              {{ selectedPartner.status }}
            </span>
          </div>
        </div>

        <div class="px-6 py-4 space-y-3">
          <div class="grid grid-cols-2 gap-3">
            <div class="bg-black/2.5 rounded-lg px-4 py-3">
              <p class="text-[11px] text-black/40 uppercase tracking-wide font-medium mb-1">Contracts</p>
              <p class="text-lg font-semibold" :class="selectedPartner.contracts > 0 ? 'text-[#2E85D8]' : 'text-black/30'">
                {{ selectedPartner.contracts }} active
              </p>
            </div>
            <div class="bg-black/2.5 rounded-lg px-4 py-3">
              <p class="text-[11px] text-black/40 uppercase tracking-wide font-medium mb-1">Total Value</p>
              <p class="text-lg font-semibold text-black">{{ selectedPartner.totalValue }}</p>
            </div>
          </div>
          <div class="space-y-2.5 pt-1">
            <div class="flex items-center gap-3">
              <MapPin class="w-4 h-4 text-black/30 shrink-0" />
              <div>
                <p class="text-[11px] text-black/40 uppercase tracking-wide font-medium">Region</p>
                <p class="text-sm font-medium text-black">{{ selectedPartner.region }}</p>
              </div>
            </div>
            <div class="flex items-center gap-3">
              <User class="w-4 h-4 text-black/30 shrink-0" />
              <div>
                <p class="text-[11px] text-black/40 uppercase tracking-wide font-medium">Contact Person</p>
                <p class="text-sm font-medium text-black">{{ selectedPartner.contactPerson }}</p>
              </div>
            </div>
            <div class="flex items-center gap-3">
              <Mail class="w-4 h-4 text-black/30 shrink-0" />
              <div>
                <p class="text-[11px] text-black/40 uppercase tracking-wide font-medium">Email</p>
                <p class="text-sm font-medium text-black">{{ selectedPartner.email }}</p>
              </div>
            </div>
            <div class="flex items-center gap-3">
              <Phone class="w-4 h-4 text-black/30 shrink-0" />
              <div>
                <p class="text-[11px] text-black/40 uppercase tracking-wide font-medium">Phone</p>
                <p class="text-sm font-medium text-black">{{ selectedPartner.phone }}</p>
              </div>
            </div>
            <div class="flex items-start gap-3">
              <FileText class="w-4 h-4 text-black/30 shrink-0 mt-0.5" />
              <div>
                <p class="text-[11px] text-black/40 uppercase tracking-wide font-medium">Address</p>
                <p class="text-sm font-medium text-black leading-snug">{{ selectedPartner.address }}</p>
              </div>
            </div>
          </div>
        </div>

        <div class="px-6 pb-5 flex justify-end">
          <button @click="showDetail = false"
            class="px-4 py-2 rounded-lg border border-black/10 text-sm font-medium text-black/60 hover:bg-black/4 transition-colors">
            Close
          </button>
        </div>
      </template>
    </DialogContent>
  </Dialog>

  <!-- ── Delete Confirmation Dialog ─────────────────────────────────── -->
  <Dialog v-model:open="showDeleteConfirm">
    <DialogContent class="max-w-sm">
      <template v-if="deleteTarget">
        <div class="px-6 pt-6 pb-5 text-center">
          <div class="w-12 h-12 rounded-full bg-red-50 flex items-center justify-center mx-auto mb-4">
            <AlertTriangle class="w-5 h-5 text-red-500" />
          </div>
          <DialogTitle class="text-base font-bold text-black">Delete entry?</DialogTitle>
          <DialogDescription class="mt-2 text-sm text-black/50 leading-relaxed">
            You're about to permanently delete
            <span class="font-semibold text-black/70">{{ deleteTarget.name }}</span>.
            This action cannot be undone.
          </DialogDescription>

          <div class="flex items-center gap-2.5 mt-4 p-3 rounded-lg bg-black/3 border border-black/6 text-left">
            <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0 bg-[#252578]/8 text-[#252578]">
              <component :is="activeTab === 'partners' ? Building2 : Truck" class="w-4 h-4" />
            </div>
            <div class="min-w-0 flex-1">
              <p class="text-sm font-semibold text-black truncate">{{ deleteTarget.name }}</p>
              <p class="text-xs text-black/40 truncate">{{ deleteTarget.industry }} · {{ deleteTarget.region }}</p>
            </div>
            <span class="text-xs font-medium px-2.5 py-0.5 rounded-full border shrink-0"
              :class="deleteTarget.status === 'Active'
                ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                : 'bg-black/4 text-black/35 border-black/8'">
              {{ deleteTarget.status }}
            </span>
          </div>

          <div class="flex gap-3 mt-5">
            <Button variant="outline" @click="showDeleteConfirm = false"
              class="flex-1 h-9 text-sm border-black/15 text-black/60 hover:text-black">
              Cancel
            </Button>
            <Button @click="confirmDelete"
              class="flex-1 h-9 text-sm bg-red-600 hover:bg-red-700 text-white">
              Yes, delete
            </Button>
          </div>
        </div>
      </template>
    </DialogContent>
  </Dialog>

  <!-- ── Add Partner / Supplier Dialog ─────────────────────────────── -->
  <Dialog v-model:open="showAddPartner">
    <DialogContent class="max-w-xl">

      <!-- Header -->
      <div class="px-6 pt-6 pb-5 border-b border-black/6">
        <div class="flex items-center gap-3">
          <div class="w-9 h-9 rounded-lg bg-[#252578]/8 flex items-center justify-center shrink-0">
            <component :is="activeTab === 'partners' ? Building2 : Truck" class="w-4.5 h-4.5 text-[#252578]" />
          </div>
          <DialogHeader>
            <DialogTitle>Add {{ activeTab === 'partners' ? 'Business Partner' : 'Supplier' }}</DialogTitle>
            <DialogDescription>Fill in the details to register a new {{ activeTab === 'partners' ? 'business partner' : 'supplier' }}.</DialogDescription>
          </DialogHeader>
        </div>
      </div>

      <!-- Form -->
      <form @submit.prevent="submitAddPartner" class="px-6 py-5 space-y-4">

        <!-- Name -->
        <div class="space-y-1.5">
          <label class="text-xs font-semibold text-black/55 uppercase tracking-wide">
            Name <span class="text-red-500">*</span>
          </label>
          <input
            v-model="addForm.name"
            @blur="addTouched.name = true"
            type="text"
            :placeholder="`e.g. ${activeTab === 'partners' ? 'Philippine National Bank' : 'MedLine Philippines'}`"
            :class="[
              'w-full h-9 rounded-md border bg-white px-3 text-sm placeholder:text-black/25 focus:outline-none focus:ring-2 transition',
              addTouched.name && !addForm.name
                ? 'border-red-400 focus:border-red-400 focus:ring-red-400/15'
                : 'border-black/12 focus:border-[#2E85D8] focus:ring-[#2E85D8]/15',
            ]"
          />
          <p v-if="addTouched.name && !addForm.name" class="text-xs text-red-500">Required.</p>
        </div>

        <!-- Industry + Region -->
        <div class="grid grid-cols-2 gap-4">
          <div class="space-y-1.5">
            <label class="text-xs font-semibold text-black/55 uppercase tracking-wide">
              Industry <span class="text-red-500">*</span>
            </label>
            <input
              v-model="addForm.industry"
              @blur="addTouched.industry = true"
              type="text"
              placeholder="e.g. Banking & Finance"
              :class="[
                'w-full h-9 rounded-md border bg-white px-3 text-sm placeholder:text-black/25 focus:outline-none focus:ring-2 transition',
                addTouched.industry && !addForm.industry
                  ? 'border-red-400 focus:border-red-400 focus:ring-red-400/15'
                  : 'border-black/12 focus:border-[#2E85D8] focus:ring-[#2E85D8]/15',
              ]"
            />
            <p v-if="addTouched.industry && !addForm.industry" class="text-xs text-red-500">Required.</p>
          </div>
          <div class="space-y-1.5">
            <label class="text-xs font-semibold text-black/55 uppercase tracking-wide">
              Region <span class="text-red-500">*</span>
            </label>
            <Select v-model="addForm.region">
              <SelectTrigger
                :class="[
                  'h-9 rounded-md text-sm',
                  addTouched.region && !addForm.region
                    ? 'border-red-400 focus:ring-red-400/15'
                    : 'border-black/12 focus:ring-[#2E85D8]/15',
                ]"
              >
                <SelectValue placeholder="Select region" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="Luzon">Luzon</SelectItem>
                <SelectItem value="Visayas">Visayas</SelectItem>
                <SelectItem value="Mindanao">Mindanao</SelectItem>
              </SelectContent>
            </Select>
            <p v-if="addTouched.region && !addForm.region" class="text-xs text-red-500">Required.</p>
          </div>
        </div>

        <!-- Contact Person + Phone -->
        <div class="grid grid-cols-2 gap-4">
          <div class="space-y-1.5">
            <label class="text-xs font-semibold text-black/55 uppercase tracking-wide">
              Contact Person <span class="text-red-500">*</span>
            </label>
            <input
              v-model="addForm.contactPerson"
              @blur="addTouched.contactPerson = true"
              type="text"
              placeholder="e.g. Juan dela Cruz"
              :class="[
                'w-full h-9 rounded-md border bg-white px-3 text-sm placeholder:text-black/25 focus:outline-none focus:ring-2 transition',
                addTouched.contactPerson && !addForm.contactPerson
                  ? 'border-red-400 focus:border-red-400 focus:ring-red-400/15'
                  : 'border-black/12 focus:border-[#2E85D8] focus:ring-[#2E85D8]/15',
              ]"
            />
            <p v-if="addTouched.contactPerson && !addForm.contactPerson" class="text-xs text-red-500">Required.</p>
          </div>
          <div class="space-y-1.5">
            <label class="text-xs font-semibold text-black/55 uppercase tracking-wide">
              Phone <span class="text-red-500">*</span>
            </label>
            <input
              v-model="addForm.phone"
              @blur="addTouched.phone = true"
              type="text"
              placeholder="e.g. +63 2 8123 4567"
              :class="[
                'w-full h-9 rounded-md border bg-white px-3 text-sm placeholder:text-black/25 focus:outline-none focus:ring-2 transition',
                addTouched.phone && !addForm.phone
                  ? 'border-red-400 focus:border-red-400 focus:ring-red-400/15'
                  : 'border-black/12 focus:border-[#2E85D8] focus:ring-[#2E85D8]/15',
              ]"
            />
            <p v-if="addTouched.phone && !addForm.phone" class="text-xs text-red-500">Required.</p>
          </div>
        </div>

        <!-- Email -->
        <div class="space-y-1.5">
          <label class="text-xs font-semibold text-black/55 uppercase tracking-wide">
            Email <span class="text-red-500">*</span>
          </label>
          <input
            v-model="addForm.email"
            @blur="addTouched.email = true"
            type="text"
            placeholder="e.g. contact@company.com"
            :class="[
              'w-full h-9 rounded-md border bg-white px-3 text-sm placeholder:text-black/25 focus:outline-none focus:ring-2 transition',
              addTouched.email && (!addForm.email || !addEmailValid)
                ? 'border-red-400 focus:border-red-400 focus:ring-red-400/15'
                : 'border-black/12 focus:border-[#2E85D8] focus:ring-[#2E85D8]/15',
            ]"
          />
          <p v-if="addTouched.email && !addForm.email" class="text-xs text-red-500">Required.</p>
          <p v-else-if="addTouched.email && !addEmailValid" class="text-xs text-red-500">Enter a valid email address.</p>
        </div>

        <!-- Address -->
        <div class="space-y-1.5">
          <label class="text-xs font-semibold text-black/55 uppercase tracking-wide">
            Address <span class="text-red-500">*</span>
          </label>
          <input
            v-model="addForm.address"
            @blur="addTouched.address = true"
            type="text"
            placeholder="e.g. 123 Ayala Ave., Makati City"
            :class="[
              'w-full h-9 rounded-md border bg-white px-3 text-sm placeholder:text-black/25 focus:outline-none focus:ring-2 transition',
              addTouched.address && !addForm.address
                ? 'border-red-400 focus:border-red-400 focus:ring-red-400/15'
                : 'border-black/12 focus:border-[#2E85D8] focus:ring-[#2E85D8]/15',
            ]"
          />
          <p v-if="addTouched.address && !addForm.address" class="text-xs text-red-500">Required.</p>
        </div>

        <!-- Status -->
        <div class="space-y-1.5">
          <label class="text-xs font-semibold text-black/55 uppercase tracking-wide">Status</label>
          <Select v-model="addForm.status">
            <SelectTrigger class="h-9 rounded-md border-black/12 text-sm focus:ring-[#2E85D8]/15">
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="Active">Active</SelectItem>
              <SelectItem value="Inactive">Inactive</SelectItem>
            </SelectContent>
          </Select>
        </div>

        <!-- Footer -->
        <div class="border-t border-black/6 pt-4">
          <DialogFooter>
            <Button type="button" variant="outline" @click="showAddPartner = false"
              class="h-9 px-4 text-sm border-black/15 text-black/60 hover:text-black">
              Cancel
            </Button>
            <Button type="submit" class="h-9 px-5 text-sm bg-[#252578] hover:bg-[#2F2F73] text-white">
              Add {{ activeTab === 'partners' ? 'Partner' : 'Supplier' }}
            </Button>
          </DialogFooter>
        </div>

      </form>
    </DialogContent>
  </Dialog>

</template>
