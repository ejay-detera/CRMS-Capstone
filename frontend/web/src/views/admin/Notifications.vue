<script setup lang="ts">
import { ref, computed } from 'vue'
import {
  Bell, Star, Trash2, Search, CheckCheck,
  FileText, UserPlus, RefreshCw, AlertCircle, Handshake, ShieldCheck,
} from 'lucide-vue-next'

// ── Types ───────────────────────────────────────────────────────────
type NotifType = 'contract' | 'user' | 'partner' | 'system' | 'reminder'
type TabKey    = 'all' | 'archive' | 'favorite'

interface Notification {
  id:         string
  type:       NotifType
  message:    string
  timestamp:  string
  isRead:     boolean
  isFavorite: boolean
  isArchived: boolean
}

// ── Static data ─────────────────────────────────────────────────────
const rawNotifications = ref<Notification[]>([
  { id: 'N-001', type: 'contract',  message: 'Contract CNT-2023-001 with Medical Supplies Co. has been approved and notarized.',                            timestamp: 'Just now',       isRead: false, isFavorite: false, isArchived: false },
  { id: 'N-002', type: 'user',      message: 'A new user account has been created for Maria Santos with role Sales.',                                       timestamp: '30 min ago',     isRead: false, isFavorite: true,  isArchived: false },
  { id: 'N-003', type: 'reminder',  message: 'Reminder: Contract CNT-2023-042 with Bio-Tech Logistics is expiring in 7 days. Please review before renewal.',timestamp: '2 days ago',     isRead: false, isFavorite: false, isArchived: false },
  { id: 'N-004', type: 'partner',   message: 'A new business partner BioGenesis Research has been added to the system.',                                    timestamp: '5 days ago',     isRead: true,  isFavorite: true,  isArchived: false },
  { id: 'N-005', type: 'contract',  message: 'Contract CNT-2023-089 with Global Pharma Inc. has been updated to Draft SBSI status.',                       timestamp: '07 Feb, 2025',   isRead: true,  isFavorite: false, isArchived: false },
  { id: 'N-006', type: 'system',    message: 'System configuration was updated by Admin. Role permissions for Manager have been modified.',                 timestamp: '01 Feb, 2025',   isRead: true,  isFavorite: false, isArchived: true  },
  { id: 'N-007', type: 'reminder',  message: 'Reminder: Review the contract proposal for Stellar Lab Equipment currently under negotiation.',               timestamp: '28 Jan, 2025',   isRead: true,  isFavorite: false, isArchived: false },
  { id: 'N-008', type: 'contract',  message: 'Contract CNT-2023-112 with Stellar Lab Equipment has been successfully renewed for another 12 months.',       timestamp: '27 Jan, 2025',   isRead: true,  isFavorite: true,  isArchived: false },
  { id: 'N-009', type: 'partner',   message: 'Supplier MedLine Philippines has updated their contact information and primary account manager.',             timestamp: '26 Jan, 2025',   isRead: true,  isFavorite: false, isArchived: false },
  { id: 'N-010', type: 'user',      message: 'User account for Bob Johnson has been deactivated by Admin.',                                                 timestamp: '25 Jan, 2025',   isRead: true,  isFavorite: false, isArchived: true  },
  { id: 'N-011', type: 'system',    message: 'Scheduled system maintenance is set for Feb 20, 2025 from 12:00 AM to 2:00 AM. Expect brief downtime.',       timestamp: '24 Jan, 2025',   isRead: true,  isFavorite: false, isArchived: true  },
  { id: 'N-012', type: 'contract',  message: 'Contract CNT-2023-134 with BioGenesis Research is awaiting client signature. Follow up recommended.',         timestamp: '23 Jan, 2025',   isRead: true,  isFavorite: false, isArchived: false },
])

// ── State ────────────────────────────────────────────────────────────
const activeTab   = ref<TabKey>('all')
const searchQuery = ref('')

// ── Derived counts ───────────────────────────────────────────────────
const allCount      = computed(() => rawNotifications.value.filter(n => !n.isArchived).length)
const archiveCount  = computed(() => rawNotifications.value.filter(n => n.isArchived).length)
const favoriteCount = computed(() => rawNotifications.value.filter(n => n.isFavorite && !n.isArchived).length)
const unreadCount   = computed(() => rawNotifications.value.filter(n => !n.isRead && !n.isArchived).length)

const tabs = computed(() => [
  { key: 'all'     as TabKey, label: 'All',     count: allCount.value     },
  { key: 'archive' as TabKey, label: 'Archive',  count: archiveCount.value },
  { key: 'favorite'as TabKey, label: 'Favorite', count: favoriteCount.value},
])

// ── Filtered list ────────────────────────────────────────────────────
const filtered = computed(() => {
  let list = rawNotifications.value

  if (activeTab.value === 'all')      list = list.filter(n => !n.isArchived)
  if (activeTab.value === 'archive')  list = list.filter(n =>  n.isArchived)
  if (activeTab.value === 'favorite') list = list.filter(n =>  n.isFavorite && !n.isArchived)

  if (searchQuery.value.trim()) {
    const q = searchQuery.value.toLowerCase()
    list = list.filter(n => n.message.toLowerCase().includes(q))
  }

  return list
})

// ── Notification type icon / color ───────────────────────────────────
const typeIcon: Record<NotifType, typeof Bell> = {
  contract: FileText,
  user:     UserPlus,
  partner:  Handshake,
  system:   ShieldCheck,
  reminder: AlertCircle,
}

const typeColor: Record<NotifType, string> = {
  contract: 'text-[#2E85D8] bg-[#2E85D8]/8',
  user:     'text-[#252578] bg-[#252578]/8',
  partner:  'text-[#2F2F73] bg-[#2F2F73]/8',
  system:   'text-amber-600 bg-amber-50',
  reminder: 'text-emerald-600 bg-emerald-50',
}

// ── Actions ──────────────────────────────────────────────────────────
function toggleRead(id: string) {
  const n = rawNotifications.value.find(x => x.id === id)
  if (n) n.isRead = !n.isRead
}

function toggleFavorite(id: string) {
  const n = rawNotifications.value.find(x => x.id === id)
  if (n) n.isFavorite = !n.isFavorite
}

function deleteNotif(id: string) {
  const idx = rawNotifications.value.findIndex(x => x.id === id)
  if (idx >= 0) rawNotifications.value.splice(idx, 1)
}

function markAllRead() {
  rawNotifications.value
    .filter(n => !n.isRead && !n.isArchived)
    .forEach(n => { n.isRead = true })
}
</script>

<template>
  <div class="p-8 space-y-6">

    <!-- ── Header ─────────────────────────────────────────────────── -->
    <div class="flex items-start justify-between">
      <div>
        <h1 class="text-xl font-semibold text-black">Notifications</h1>
        <p class="text-sm text-black/40 mt-0.5">View and manage all system notifications.</p>
      </div>
      <button
        v-if="unreadCount > 0"
        @click="markAllRead"
        class="flex items-center gap-2 text-sm font-medium text-[#2E85D8] hover:text-[#252578] transition-colors"
      >
        <CheckCheck class="w-4 h-4" />
        Mark all as read
      </button>
    </div>

    <!-- ── Notification Panel ─────────────────────────────────────── -->
    <div class="bg-white rounded-lg border border-black/8 shadow-sm overflow-hidden">

      <!-- Panel Header -->
      <div class="px-6 pt-5 pb-4 border-b border-black/5 flex items-center justify-between gap-4">
        <div class="flex items-center gap-2">
          <h2 class="text-sm font-semibold text-black">
            List Notifications
          </h2>
          <span
            v-if="unreadCount > 0"
            class="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-[#2E85D8] text-white tabular-nums"
          >
            {{ unreadCount }} unread
          </span>
        </div>

        <!-- Search -->
        <div class="relative w-64">
          <Search class="w-3.5 h-3.5 text-black/35 absolute left-3 top-1/2 -translate-y-1/2" />
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search notifications..."
            class="w-full rounded-lg border border-black/10 bg-black/[0.02] py-2 pl-8 pr-3 text-sm text-black placeholder:text-black/35 focus:border-[#2E85D8] focus:outline-none transition-colors"
          />
        </div>
      </div>

      <!-- Tabs + Count -->
      <div class="px-6 py-3 border-b border-black/5 flex items-center justify-between">
        <div class="flex items-center gap-0.5 bg-black/4 rounded-md p-1">
          <button
            v-for="tab in tabs"
            :key="tab.key"
            @click="activeTab = tab.key; searchQuery = ''"
            class="flex items-center gap-1.5 px-3 py-1 text-xs rounded transition-all font-medium"
            :class="activeTab === tab.key
              ? 'bg-white text-black shadow-sm'
              : 'text-black/40 hover:text-black/60'"
          >
            {{ tab.label }}
            <span
              class="text-[10px] font-semibold tabular-nums px-1.5 py-0.5 rounded-full min-w-[18px] text-center"
              :class="activeTab === tab.key
                ? 'bg-[#252578]/8 text-[#252578]'
                : 'bg-black/8 text-black/40'"
            >
              {{ tab.count }}
            </span>
          </button>
        </div>
        <p class="text-xs text-black/35">{{ filtered.length }} notification{{ filtered.length !== 1 ? 's' : '' }}</p>
      </div>

      <!-- Notification List -->
      <div class="divide-y divide-black/[0.04]">

        <!-- Empty state -->
        <div v-if="filtered.length === 0" class="flex flex-col items-center justify-center py-16 text-center">
          <div class="w-12 h-12 rounded-full bg-black/4 flex items-center justify-center mb-3">
            <Bell class="w-5 h-5 text-black/30" />
          </div>
          <p class="text-sm font-medium text-black/50">No notifications</p>
          <p class="text-xs text-black/30 mt-0.5">You're all caught up!</p>
        </div>

        <!-- Notification Row -->
        <div
          v-for="notif in filtered"
          :key="notif.id"
          class="flex items-center gap-4 px-6 py-4 hover:bg-black/[0.012] transition-colors group"
          :class="!notif.isRead ? 'bg-[#2E85D8]/[0.025]' : ''"
        >
          <!-- Unread dot -->
          <div class="w-2 shrink-0 flex justify-center">
            <div
              class="w-2 h-2 rounded-full transition-colors"
              :class="!notif.isRead ? 'bg-[#2E85D8]' : 'bg-transparent'"
            />
          </div>

          <!-- Favorite star -->
          <button
            @click="toggleFavorite(notif.id)"
            class="shrink-0 transition-colors"
            :class="notif.isFavorite ? 'text-amber-400' : 'text-black/20 hover:text-black/40'"
          >
            <Star class="w-4 h-4" :class="notif.isFavorite ? 'fill-amber-400' : ''" />
          </button>

          <!-- Type icon -->
          <div
            class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0"
            :class="typeColor[notif.type]"
          >
            <component :is="typeIcon[notif.type]" class="w-4 h-4" />
          </div>

          <!-- Message -->
          <p
            class="flex-1 text-sm leading-snug cursor-pointer select-none"
            :class="!notif.isRead ? 'text-black font-medium' : 'text-black/55'"
            @click="toggleRead(notif.id)"
          >
            {{ notif.message }}
          </p>

          <!-- Timestamp -->
          <span class="text-xs text-black/35 shrink-0 whitespace-nowrap">{{ notif.timestamp }}</span>

          <!-- Delete -->
          <button
            @click="deleteNotif(notif.id)"
            class="shrink-0 w-7 h-7 flex items-center justify-center rounded-lg opacity-0 group-hover:opacity-100 transition-all text-black/30 hover:text-red-500 hover:bg-red-50"
          >
            <Trash2 class="w-3.5 h-3.5" />
          </button>
        </div>

      </div>
    </div>
  </div>
</template>
