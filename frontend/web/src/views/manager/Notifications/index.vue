<script setup lang="ts">
import { computed, ref } from 'vue'
import { CheckCheck } from 'lucide-vue-next'
import NotificationList from '@/components/shared/NotificationList.vue'
import type { Notification, TabKey } from '@/types/notification'

const rawNotifications = ref<Notification[]>([
  { id: 'N-001', type: 'contract',  message: 'Contract CNT-2025-001 with Philippine National Bank has been approved and notarized.',                            timestamp: 'Just now',     isRead: false, isFavorite: false, isArchived: false },
  { id: 'N-002', type: 'reminder',  message: 'Reminder: Contract CNT-2024-089 with Bio-Tech Logistics is expiring in 5 days. Please review before renewal.',   timestamp: '1 hour ago',   isRead: false, isFavorite: true,  isArchived: false },
  { id: 'N-003', type: 'contract',  message: 'Contract CNT-2025-003 with SM Prime Holdings has been submitted for SBSI Review.',                               timestamp: '3 hours ago',  isRead: false, isFavorite: false, isArchived: false },
  { id: 'N-004', type: 'reminder',  message: 'Reminder: Contract CNT-2024-112 with Stellar Lab Equipment expires in 10 days.',                                  timestamp: '1 day ago',    isRead: true,  isFavorite: true,  isArchived: false },
  { id: 'N-005', type: 'contract',  message: 'Contract CNT-2025-002 with Meralco has been updated to Client Review status.',                                    timestamp: '2 days ago',   isRead: true,  isFavorite: false, isArchived: false },
  { id: 'N-006', type: 'partner',   message: 'Business partner Ayala Corporation has updated their contact information and account manager.',                   timestamp: '5 days ago',   isRead: true,  isFavorite: false, isArchived: false },
  { id: 'N-007', type: 'contract',  message: 'Contract CNT-2025-004 with Jollibee Foods Corp. has been successfully created and is pending notarization.',      timestamp: '07 Feb, 2025', isRead: true,  isFavorite: false, isArchived: false },
  { id: 'N-008', type: 'reminder',  message: 'Reminder: Review the contract proposal for BioGenesis Research currently under negotiation.',                     timestamp: '05 Feb, 2025', isRead: true,  isFavorite: true,  isArchived: false },
  { id: 'N-009', type: 'system',    message: 'System maintenance is scheduled for Feb 20, 2025 from 12:00 AM to 2:00 AM. Expect brief downtime.',              timestamp: '01 Feb, 2025', isRead: true,  isFavorite: false, isArchived: true  },
  { id: 'N-010', type: 'contract',  message: 'Contract CNT-2024-256 with Global Pharma Inc. is expiring in 26 days. Renewal recommended.',                     timestamp: '28 Jan, 2025', isRead: true,  isFavorite: false, isArchived: false },
])

const activeTab   = ref<TabKey>('all')
const searchQuery = ref('')

const allCount      = computed(() => rawNotifications.value.filter(n => !n.isArchived).length)
const archiveCount  = computed(() => rawNotifications.value.filter(n => n.isArchived).length)
const favoriteCount = computed(() => rawNotifications.value.filter(n => n.isFavorite && !n.isArchived).length)
const unreadCount   = computed(() => rawNotifications.value.filter(n => !n.isRead && !n.isArchived).length)

const tabs = computed(() => [
  { key: 'all'      as TabKey, label: 'All',     count: allCount.value      },
  { key: 'archive'  as TabKey, label: 'Archive',  count: archiveCount.value  },
  { key: 'favorite' as TabKey, label: 'Favorite', count: favoriteCount.value },
])

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
  rawNotifications.value.filter(n => !n.isRead && !n.isArchived).forEach(n => { n.isRead = true })
}
</script>

<template>
  <div class="p-8 space-y-6">

    <div class="flex items-start justify-between">
      <div>
        <h1 class="text-xl font-semibold text-black">Notifications</h1>
        <p class="text-sm text-black/40 mt-0.5">View and manage all your notifications.</p>
      </div>
      <button v-if="unreadCount > 0" @click="markAllRead"
        class="flex items-center gap-2 text-sm font-medium text-[#2E85D8] hover:text-[#252578] transition-colors">
        <CheckCheck class="w-4 h-4" /> Mark all as read
      </button>
    </div>

    <NotificationList
      :tabs="tabs"
      :filtered="filtered"
      :unread-count="unreadCount"
      v-model:active-tab="activeTab"
      v-model:search-query="searchQuery"
      @toggle-read="toggleRead"
      @toggle-favorite="toggleFavorite"
      @delete="deleteNotif"
    />

  </div>
</template>
