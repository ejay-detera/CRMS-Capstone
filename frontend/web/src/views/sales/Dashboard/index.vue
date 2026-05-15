<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { FileText, Clock, AlertTriangle, CheckCircle } from 'lucide-vue-next'
import { remainingDays } from '@/types/contract'
import type { Contract } from '@/types/contract'
import type { ContractRequest } from '@/types/contractRequest'
import RecentRequestsTable from './RecentRequestsTable.vue'
import ContractStatusPanel from './ContractStatusPanel.vue'

// ── Live clock ──────────────────────────────────────────────────
const now = ref(new Date())
let timer: ReturnType<typeof setInterval>
onMounted(() => { timer = setInterval(() => { now.value = new Date() }, 1000) })
onUnmounted(() => clearInterval(timer))

const greeting = computed(() => {
  const h = now.value.getHours()
  if (h < 12) return 'Good morning'
  if (h < 17) return 'Good afternoon'
  return 'Good evening'
})
const formattedDate = computed(() =>
  now.value.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' })
)
const formattedTime = computed(() =>
  now.value.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit' })
)

// ── Dummy data ───────────────────────────────────────────────────
const contracts = ref<Contract[]>([
  { id: 'CTR-001', businessPartner: 'Philippine National Bank', category: 'Service Agreement',     itemCode: 'ITM-0041', description: 'ATM Maintenance Unit',      serialNo: 'SN-2024-0041', region: 'Luzon',    startDate: '2026-01-01', endDate: '2026-12-31', status: 'Notarized PDF', contractLink: '#', createdBy: 'Shadrack Castro' },
  { id: 'CTR-002', businessPartner: 'Globe Telecom',            category: 'Partnership Agreement', itemCode: 'ITM-0082', description: 'Network Infrastructure',   serialNo: 'SN-2024-0082', region: 'Luzon',    startDate: '2025-10-01', endDate: '2026-09-30', status: 'Client Review', contractLink: '#', createdBy: 'Shadrack Castro' },
  { id: 'CTR-003', businessPartner: 'MedLine Philippines',      category: 'Supply Contract',       itemCode: 'ITM-0113', description: 'Surgical Supply Agreement', serialNo: 'SN-2025-0113', region: 'Luzon',    startDate: '2025-08-15', endDate: '2026-08-15', status: 'Notarized PDF', contractLink: '#', createdBy: 'Shadrack Castro' },
  { id: 'CTR-004', businessPartner: 'BDO Unibank',              category: 'Equipment Lease',       itemCode: 'ITM-0054', description: 'Vault Security System',     serialNo: 'SN-2025-0054', region: 'Luzon',    startDate: '2026-01-01', endDate: '2026-07-01', status: 'SBSI Review',   contractLink: '#', createdBy: 'Shadrack Castro' },
  { id: 'CTR-005', businessPartner: 'PLDT',                     category: 'Service Agreement',     itemCode: 'ITM-0095', description: 'Fiber Optic Maintenance',   serialNo: 'SN-2024-0095', region: 'Mindanao', startDate: '2026-01-01', endDate: '2026-06-30', status: 'Client Review', contractLink: '#', createdBy: 'Shadrack Castro' },
  { id: 'CTR-006', businessPartner: 'Bio-Tech Logistics',       category: 'Supply Contract',       itemCode: 'ITM-0076', description: 'Cold Chain Equipment',      serialNo: 'SN-2025-0076', region: 'Luzon',    startDate: '2025-12-15', endDate: '2026-06-15', status: 'Notarized PDF', contractLink: '#', createdBy: 'Shadrack Castro' },
  { id: 'CTR-007', businessPartner: 'Cebu Pacific Air',         category: 'Service Agreement',     itemCode: 'ITM-0037', description: 'Cargo Handling Equipment',  serialNo: 'SN-2025-0037', region: 'Visayas',  startDate: '2025-11-20', endDate: '2026-05-20', status: 'SBSI Review',   contractLink: '#', createdBy: 'Shadrack Castro' },
  { id: 'CTR-008', businessPartner: 'SM Prime Holdings',        category: 'Equipment Lease',       itemCode: 'ITM-0068', description: 'HVAC System Unit B',        serialNo: 'SN-2025-0068', region: 'Luzon',    startDate: '2025-11-25', endDate: '2026-05-25', status: 'Client Review', contractLink: '#', createdBy: 'Shadrack Castro' },
  { id: 'CTR-009', businessPartner: 'Global Pharma Inc.',       category: 'Supply Contract',       itemCode: 'ITM-0019', description: 'Pharmaceutical Dispenser',  serialNo: 'SN-2025-0019', region: 'Visayas',  startDate: '2025-11-28', endDate: '2026-05-28', status: 'Notarized PDF', contractLink: '#', createdBy: 'Shadrack Castro' },
  { id: 'CTR-010', businessPartner: 'BioGenesis Research',      category: 'Equipment Maintenance', itemCode: 'ITM-0150', description: 'PCR Machine Unit 3',        serialNo: 'SN-2024-0150', region: 'Mindanao', startDate: '2025-10-30', endDate: '2026-04-30', status: 'Notarized PDF', contractLink: '#', createdBy: 'Shadrack Castro' },
  { id: 'CTR-011', businessPartner: 'Stellar Lab Equipment',    category: 'Equipment Lease',       itemCode: 'ITM-0121', description: 'Centrifuge Model X200',      serialNo: 'SN-2024-0121', region: 'Luzon',    startDate: '2025-09-15', endDate: '2026-03-15', status: 'SBSI Review',   contractLink: '#', createdBy: 'Shadrack Castro' },
  { id: 'CTR-012', businessPartner: 'Metrobank',                category: 'Service Agreement',     itemCode: 'ITM-0087', description: 'Cash Counting Machine',      serialNo: 'SN-2024-0087', region: 'Mindanao', startDate: '2026-02-01', endDate: '2027-01-31', status: 'Notarized PDF', contractLink: '#', createdBy: 'Shadrack Castro' },
])

const recentRequests = ref<ContractRequest[]>([
  { id: 'REQ-001', businessPartner: 'ABS-CBN Corporation', category: 'Service Agreement',     description: 'Broadcast Equipment Lease',  region: 'Luzon',    requestDate: '2026-05-10', startDate: '2026-06-01', endDate: '2027-05-31', priority: 'High',   status: 'Under Review', notes: 'Urgent renewal needed.', rejectionReason: '',                     contractLink: '#', createdBy: 'Shadrack Castro' },
  { id: 'REQ-002', businessPartner: 'Jollibee Foods Corp.',  category: 'Equipment Maintenance', description: 'Kitchen Equipment Service',   region: 'Luzon',    requestDate: '2026-05-08', startDate: '2026-06-15', endDate: '2027-06-14', priority: 'Medium', status: 'Pending',      notes: '',                       rejectionReason: '',                     contractLink: '#', createdBy: 'Shadrack Castro' },
  { id: 'REQ-003', businessPartner: 'San Miguel Brewery',    category: 'Supply Contract',       description: 'Filtration System Supply',   region: 'Visayas',  requestDate: '2026-05-05', startDate: '2026-06-01', endDate: '2026-11-30', priority: 'Low',    status: 'Approved',     notes: '',                       rejectionReason: '',                     contractLink: '#', createdBy: 'Shadrack Castro' },
  { id: 'REQ-004', businessPartner: 'Ayala Land Inc.',       category: 'Equipment Lease',       description: 'CCTV Infrastructure',        region: 'Luzon',    requestDate: '2026-04-28', startDate: '2026-05-15', endDate: '2027-05-14', priority: 'High',   status: 'Rejected',     notes: '',                       rejectionReason: 'Budget constraints for Q2.', contractLink: '#', createdBy: 'Shadrack Castro' },
  { id: 'REQ-005', businessPartner: 'Meralco',               category: 'Partnership Agreement', description: 'Smart Meter Deployment',     region: 'Luzon',    requestDate: '2026-04-20', startDate: '2026-07-01', endDate: '2027-06-30', priority: 'Medium', status: 'Approved',     notes: 'Fast-tracked by manager.', rejectionReason: '',                   contractLink: '#', createdBy: 'Shadrack Castro' },
  { id: 'REQ-006', businessPartner: 'Robinsons Retail',      category: 'Service Agreement',     description: 'POS System Maintenance',     region: 'Visayas',  requestDate: '2026-04-15', startDate: '2026-05-01', endDate: '2026-10-31', priority: 'Low',    status: 'Pending',      notes: '',                       rejectionReason: '',                     contractLink: '#', createdBy: 'Shadrack Castro' },
])

const withDays = computed(() => contracts.value.map(c => ({ ...c, days: remainingDays(c.endDate) })))

const statCards = computed(() => [
  {
    label: 'My Contracts',
    value: withDays.value.length,
    sub: 'Total contracts owned',
    icon: FileText,
    iconBg: 'bg-[#252578]/8',
    iconColor: 'text-[#252578]',
    valueClass: 'text-black',
    change: '+2 this month',
    positive: true,
  },
  {
    label: 'Pending Approval',
    value: recentRequests.value.filter(r => r.status === 'Pending' || r.status === 'Under Review').length,
    sub: 'Awaiting manager review',
    icon: Clock,
    iconBg: 'bg-amber-50',
    iconColor: 'text-amber-500',
    valueClass: 'text-amber-500',
    change: 'Needs attention',
    positive: false,
  },
  {
    label: 'Expiring Soon',
    value: withDays.value.filter(c => c.days >= 0 && c.days <= 30).length,
    sub: 'Within the next 30 days',
    icon: AlertTriangle,
    iconBg: 'bg-red-50',
    iconColor: 'text-red-400',
    valueClass: 'text-red-500',
    change: 'Action required',
    positive: false,
  },
  {
    label: 'Approved',
    value: recentRequests.value.filter(r => r.status === 'Approved').length,
    sub: 'Requests approved',
    icon: CheckCircle,
    iconBg: 'bg-emerald-50',
    iconColor: 'text-emerald-600',
    valueClass: 'text-emerald-600',
    change: '+2 this month',
    positive: true,
  },
])
</script>

<template>
  <div class="p-8 space-y-6">

    <!-- Greeting header -->
    <div class="flex items-center justify-between">
      <div>
        <p class="text-xs font-semibold text-black/35 uppercase tracking-widest mb-0.5">Sales Portal</p>
        <h1 class="text-xl font-semibold text-black">{{ greeting }}, Shadrack.</h1>
        <p class="text-sm text-black/40 mt-0.5">Here's an overview of your contracts and requests.</p>
      </div>
      <div class="text-right hidden sm:block">
        <p class="text-sm font-semibold text-black tabular-nums">{{ formattedTime }}</p>
        <p class="text-xs text-black/40 mt-0.5">{{ formattedDate }}</p>
      </div>
    </div>

    <!-- KPI stat cards -->
    <div class="grid grid-cols-2 xl:grid-cols-4 gap-4">
      <div v-for="card in statCards" :key="card.label"
        class="bg-white rounded-lg border border-black/8 px-5 py-4 shadow-sm flex items-start gap-4">
        <div class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0" :class="card.iconBg">
          <component :is="card.icon" class="w-4 h-4" :class="card.iconColor" />
        </div>
        <div class="flex-1 min-w-0">
          <p class="text-xs font-medium text-black/40 uppercase tracking-wide leading-none mb-1.5">{{ card.label }}</p>
          <p class="text-2xl font-semibold tabular-nums leading-none" :class="card.valueClass">{{ card.value }}</p>
          <p class="text-[11px] text-black/35 mt-1.5 leading-none">{{ card.sub }}</p>
        </div>
        <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded shrink-0 mt-0.5"
          :class="card.positive ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-500'">
          {{ card.change }}
        </span>
      </div>
    </div>

    <!-- Main content: requests table + status panel -->
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
      <div class="xl:col-span-2">
        <RecentRequestsTable :requests="recentRequests" />
      </div>
      <div>
        <ContractStatusPanel :contracts="withDays" :requests="recentRequests" />
      </div>
    </div>

  </div>
</template>
