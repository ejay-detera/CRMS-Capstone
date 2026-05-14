<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { Info, MoreVertical, ArrowUpRight, ChevronUp, ChevronDown } from "lucide-vue-next";
import { ref } from "vue";

const statCards = [
  {
    title: "Total Contracts",
    value: "1,284",
    change: "+2.1%",
    changeType: "positive",
    changeLabel: "+12 vs last week",
    metaColor: "text-[#2E85D8]",
  },
  {
    title: "Expiring Contracts",
    value: "28",
    change: "+5.2%",
    changeType: "positive",
    changeLabel: "+3 vs last week",
    metaColor: "text-[#2F2F73]",
  },
  {
    title: "Expired Contracts",
    value: "14",
    change: "-1.3%",
    changeType: "negative",
    changeLabel: "-2 vs last week",
    metaColor: "text-[#2F2F73]",
  },
  {
    title: "Total Employees",
    value: "452",
    change: "+4.0%",
    changeType: "positive",
    changeLabel: "+4 this month",
    metaColor: "text-[#2E85D8]",
  },
];

const recentContracts = [
  {
    id: "CNT-2023-001",
    partner: "Medical Supplies Co.",
    category: "Supply",
    status: "Notarized",
    endDate: "Dec 12, 2024",
  },
  {
    id: "CNT-2023-042",
    partner: "Bio-Tech Logistics",
    category: "Logistics",
    status: "Draft Client",
    endDate: "Dec 11, 2024",
  },
  {
    id: "CNT-2023-089",
    partner: "Global Pharma Inc.",
    category: "Pharmaceutical",
    status: "Draft SBSI",
    endDate: "Dec 10, 2024",
  },
  {
    id: "CNT-2023-112",
    partner: "Stellar Lab Equipment",
    category: "Equipment",
    status: "Notarized",
    endDate: "Dec 09, 2024",
  },
];

const auditLogs = [
  {
    action: "Contract created",
    user: "Alex Rivera",
    timestamp: "June 21, 8pm",
  },
  {
    action: "Contract renewed",
    user: "Maria Santos",
    timestamp: "June 20, 3pm",
  },
  {
    action: "Contract approved",
    user: "John Doe",
    timestamp: "June 19, 11am",
  },
];

const users = [
  {
    id: "USR-001",
    name: "John Doe",
    email: "john.doe@sbsi.com",
    role: "Admin",
    status: "Active",
    activity: "+5",
    activityType: "positive",
  },
  {
    id: "USR-002",
    name: "Alice Smith",
    email: "alice.smith@sbsi.com",
    role: "Manager",
    status: "Active",
    activity: "+3",
    activityType: "positive",
  },
  {
    id: "USR-003",
    name: "Maria Santos",
    email: "maria.santos@sbsi.com",
    role: "Sales",
    status: "Active",
    activity: "+8",
    activityType: "positive",
  },
  {
    id: "USR-004",
    name: "Bob Johnson",
    email: "bob.johnson@sbsi.com",
    role: "Manager",
    status: "Inactive",
    activity: "-2",
    activityType: "negative",
  },
  {
    id: "USR-005",
    name: "Emma Wilson",
    email: "emma.wilson@sbsi.com",
    role: "Sales",
    status: "Active",
    activity: "+4",
    activityType: "positive",
  },
];

const selectedTimeFilter = ref("1D");
const selectedUserFilter = ref("All");

</script>

<template>
  <div class="p-6 space-y-6 min-h-full">
    <!-- Header -->
    <div class="space-y-1">
      <h1 class="text-2xl font-semibold text-black">Dashboard</h1>
      <p class="text-sm text-black/60">
        Welcome back! Manage your biotech contracts efficiently.
      </p>
    </div>

    <!-- Stat Cards - 4 cards in grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
      <Card
        v-for="card in statCards"
        :key="card.title"
        class="rounded-2xl border border-[#2F2F73]/20 shadow-sm hover:shadow-md transition-shadow"
        style="background: linear-gradient(to top, #FFFFFF 50%, #E8F0FF 100%);"
      >
        <CardContent class="p-5">
          <div class="flex items-start justify-between mb-4">
            <div class="flex items-center gap-2">
              <p class="text-sm font-semibold text-black">{{ card.title }}</p>
              <Info class="w-4 h-4 text-black/40" />
            </div>
            <MoreVertical class="w-4 h-4 text-black/40 cursor-pointer hover:text-black/60" />
          </div>
          <div class="flex items-baseline gap-3 mb-3">
            <p class="text-3xl font-bold text-black">
              {{ card.value }}
            </p>
            <div
              class="flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold"
              :class="
                card.changeType === 'positive'
                  ? 'bg-green-100 text-green-700'
                  : 'bg-red-100 text-red-700'
              "
            >
              <component
                :is="card.changeType === 'positive' ? ChevronUp : ChevronDown"
                class="w-3 h-3"
              />
              {{ card.change }}
            </div>
          </div>
          <div class="flex items-center justify-between">
            <p class="text-xs text-black/60">{{ card.changeLabel }}</p>
            <button class="flex items-center gap-1 text-xs font-semibold text-[#2E85D8] hover:text-[#252578] transition-colors">
              See Details
              <ArrowUpRight class="w-3 h-3" />
            </button>
          </div>
        </CardContent>
      </Card>
    </div>

    <!-- Recent Contracts and Audit Logs -->
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
      <!-- Recent Contracts Created -->
      <Card class="rounded-2xl border border-black/10 bg-white shadow-sm">
        <CardHeader class="px-6 pt-6 pb-4">
          <div class="flex items-center justify-between">
            <CardTitle class="text-base font-semibold text-black">
              Recent Contracts
            </CardTitle>
            <div class="flex items-center gap-2">
              <div class="flex items-center gap-1 bg-black/5 rounded-lg p-1">
                <button
                  v-for="filter in ['1D', '1W', '1M', 'All']"
                  :key="filter"
                  @click="selectedTimeFilter = filter"
                  class="px-3 py-1 text-xs font-medium rounded-md transition-colors"
                  :class="
                    selectedTimeFilter === filter
                      ? 'bg-white text-black shadow-sm'
                      : 'text-black/60 hover:text-black'
                  "
                >
                  {{ filter }}
                </button>
              </div>
            </div>
          </div>
        </CardHeader>
        <CardContent class="px-6 py-4">
          <Table>
            <TableHeader>
              <TableRow class="border-b border-black/10">
                <TableHead class="text-[11px] font-semibold text-black/70">ID</TableHead>
                <TableHead class="text-[11px] font-semibold text-black/70">Partner</TableHead>
                <TableHead class="text-[11px] font-semibold text-black/70">Category</TableHead>
                <TableHead class="text-[11px] font-semibold text-black/70">Status</TableHead>
                <TableHead class="text-[11px] font-semibold text-black/70">End Date</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow
                v-for="(contract, index) in recentContracts"
                :key="contract.id"
                class="hover:bg-black/5 border-b border-black/5 last:border-0"
              >
                <TableCell class="text-xs font-medium text-black">
                  {{ contract.id }}
                </TableCell>
                <TableCell class="text-xs text-black/70">
                  {{ contract.partner }}
                </TableCell>
                <TableCell class="text-xs text-black/70">
                  {{ contract.category }}
                </TableCell>
                <TableCell>
                  <span
                    class="inline-flex rounded-full border px-2 py-0.5 text-[10px] font-semibold"
                    :class="
                      contract.status === 'Notarized'
                        ? 'border-green-500/40 text-green-600 bg-green-50'
                        : contract.status === 'Draft Client'
                          ? 'border-yellow-500/40 text-yellow-600 bg-yellow-50'
                          : 'border-blue-500/40 text-blue-600 bg-blue-50'
                    "
                  >
                    {{ contract.status }}
                  </span>
                </TableCell>
                <TableCell class="text-xs text-black/70">
                  {{ contract.endDate }}
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>
      </Card>

      <!-- Audit Logs -->
      <Card class="rounded-2xl border border-black/10 bg-white shadow-sm">
        <CardHeader class="px-6 pt-6 pb-4">
          <div class="flex items-center justify-between">
            <CardTitle class="text-base font-semibold text-black">
              Recent Logs
            </CardTitle>
            <button class="text-xs font-semibold text-[#2E85D8]">
              See all activity
            </button>
          </div>
        </CardHeader>
        <CardContent class="px-6 py-4">
          <Table>
            <TableHeader>
              <TableRow class="border-b border-black/10">
                <TableHead class="text-[11px] font-semibold text-black/70">Action</TableHead>
                <TableHead class="text-[11px] font-semibold text-black/70">User</TableHead>
                <TableHead class="text-[11px] font-semibold text-black/70">Timestamp</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow
                v-for="log in auditLogs"
                :key="log.action + log.user"
                class="hover:bg-black/5 border-b border-black/5 last:border-0"
              >
                <TableCell class="text-xs font-medium text-black">
                  {{ log.action }}
                </TableCell>
                <TableCell class="text-xs text-black/70">
                  {{ log.user }}
                </TableCell>
                <TableCell class="text-xs text-black/70">
                  {{ log.timestamp }}
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>
      </Card>
    </div>

    <!-- User List Table -->
    <Card class="rounded-2xl border border-black/10 bg-white shadow-sm">
      <CardHeader class="px-6 pt-6 pb-4">
        <div class="flex items-center justify-between">
          <CardTitle class="text-base font-semibold text-black">
            User List
          </CardTitle>
          <div class="flex items-center gap-2">
            <div class="flex items-center gap-1 bg-black/5 rounded-lg p-1">
              <button
                v-for="filter in ['All', 'Active', 'Inactive']"
                :key="filter"
                @click="selectedUserFilter = filter"
                class="px-3 py-1 text-xs font-medium rounded-md transition-colors"
                :class="
                  selectedUserFilter === filter
                    ? 'bg-white text-black shadow-sm'
                    : 'text-black/60 hover:text-black'
                "
              >
                {{ filter }}
              </button>
            </div>
          </div>
        </div>
      </CardHeader>
      <CardContent class="p-0">
        <Table>
          <TableHeader>
            <TableRow class="border-b border-black/10">
              <TableHead class="text-[11px] font-semibold text-black/70">User ID</TableHead>
              <TableHead class="text-[11px] font-semibold text-black/70">Name</TableHead>
              <TableHead class="text-[11px] font-semibold text-black/70">Email</TableHead>
              <TableHead class="text-[11px] font-semibold text-black/70">Role</TableHead>
              <TableHead class="text-[11px] font-semibold text-black/70 text-right">Activity</TableHead>
              <TableHead class="text-[11px] font-semibold text-black/70">Status</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow
              v-for="user in users"
              :key="user.id"
              class="hover:bg-black/5 border-b border-black/5 last:border-0"
            >
              <TableCell class="text-xs font-medium text-black">
                {{ user.id }}
              </TableCell>
              <TableCell class="text-xs font-medium text-black">
                {{ user.name }}
              </TableCell>
              <TableCell class="text-xs text-black/70">
                {{ user.email }}
              </TableCell>
              <TableCell class="text-xs text-black/70">
                {{ user.role }}
              </TableCell>
              <TableCell class="text-xs font-semibold text-right">
                <span
                  :class="user.activityType === 'positive' ? 'text-green-600' : 'text-red-500'"
                >
                  {{ user.activity }} actions
                </span>
              </TableCell>
              <TableCell>
                <span
                  class="inline-flex rounded-full border px-2 py-0.5 text-[10px] font-semibold"
                  :class="
                    user.status === 'Active'
                      ? 'border-green-500/40 text-green-600 bg-green-50'
                      : 'border-red-500/40 text-red-600 bg-red-50'
                  "
                >
                  {{ user.status }}
                </span>
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </CardContent>
    </Card>
  </div>
</template>
