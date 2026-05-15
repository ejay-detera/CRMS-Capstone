<script setup lang="ts">
import { computed, ref } from 'vue'
import { CheckCheck } from 'lucide-vue-next'
import NotificationList from '@/components/shared/NotificationList.vue'
import type { Notification, TabKey } from '@/types/notification'

const rawNotifications = ref<Notification[]>([
  { id: 'N-001', type: 'contract',  message: 'Your contract request REQ-001 for ABS-CBN Corporation has been moved to Under Review by the manager.',               timestamp: 'Just now',     isRead: false, isFavorite: false, isArchived: false },
  { id: 'N-002', type: 'reminder',  message: 'Reminder: Contract CTR-007 with Cebu Pacific Air is expiring in 5 days. Please initiate a renewal request.',         timestamp: '2 hours ago',  isRead: false, isFavorite: true,  isArchived: false },
  { id: 'N-003', type: 'contract',  message: 'Your contract request REQ-003 for San Miguel Brewery has been approved. You may proceed with notarization.',          timestamp: '5 hours ago',  isRead: false, isFavorite: false, isArchived: false },
  { id: 'N-004', type: 'contract',  message: 'Contract request REQ-004 for Ayala Land Inc. was rejected. Reason: Budget constraints for Q2. Please revise.',        timestamp: '1 day ago',    isRead: true,  isFavorite: false, isArchived: false },
  { id: 'N-005', type: 'reminder',  message: 'Reminder: Contract CTR-011 with Stellar Lab Equipment expires in 10 days. Submit a renewal request as soon as possible.', timestamp: '2 days ago', isRead: true,  isFavorite: true,  isArchived: false },
  { id: 'N-006', type: 'contract',  message: 'Your contract request REQ-005 for Meralco has been approved and fast-tracked by the manager.',                         timestamp: '4 days ago',   isRead: true,  isFavorite: false, isArchived: false },
  { id: 'N-007', type: 'contract',  message: 'Contract CTR-002 with Globe Telecom has been updated to Client Review status. Await further instructions.',            timestamp: '07 May, 2026', isRead: true,  isFavorite: false, isArchived: false },
  { id: 'N-008', type: 'reminder',  message: 'Reminder: Your pending request REQ-002 for Jollibee Foods Corp. has been awaiting review for 3 days.',                 timestamp: '05 May, 2026', isRead: true,  isFavorite: true,  isArchived: false },
  { id: 'N-009', type: 'system',    message: 'System maintenance is scheduled for May 20, 2026 from 12:00 AM to 2:00 AM. Expect brief downtime.',                   timestamp: '01 May, 2026', isRead: true,  isFavorite: false, isArchived: true  },
  { id: 'N-010', type: 'contract',  message: 'Contract CTR-010 with BioGenesis Research is expiring in 26 days. Initiate a renewal request to avoid a lapse.',      timestamp: '28 Apr, 2026', isRead: true,  isFavorite: false, isArchived: false },
])

const activeTab   = ref<TabKey>('all')
const searchQuery = ref('')

const allCount      = computed(() => rawNotifications.value.filter(n => !n.isArchived).length)
const archiveCount  = computed(() => rawNotifications.value.filter(n => n.isArchived).length)
const favoriteCount = computed(() => rawNotifications.value.filter(n => n.isFavorite && !n.isArchived).length)
const unreadCount   = computed(() => rawNotifications.value.filter(n => !n.isRead && !n.isArchived).length)

const tabs = computed(() => [
  { key: 'all'      as TabKey, label: 'All',      count: allCount.value      },
  { key: 'archive'  as TabKey, label: 'Archive',   count: archiveCount.value  },
  { key: 'favorite' as TabKey, label: 'Favorite',  count: favoriteCount.value },
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
