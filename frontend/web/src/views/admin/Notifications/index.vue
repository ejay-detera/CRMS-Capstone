<script setup lang="ts">
import { computed, ref } from 'vue'
import { CheckCheck } from 'lucide-vue-next'
import NotificationList from './NotificationList.vue'
import type { Notification, TabKey } from '@/types/notification'

const rawNotifications = ref<Notification[]>([
  { id: 'N-001', type: 'contract',  message: 'Contract CNT-2023-001 with Medical Supplies Co. has been approved and notarized.',                             timestamp: 'Just now',     isRead: false, isFavorite: false, isArchived: false },
  { id: 'N-002', type: 'user',      message: 'A new user account has been created for Maria Santos with role Sales.',                                        timestamp: '30 min ago',   isRead: false, isFavorite: true,  isArchived: false },
  { id: 'N-003', type: 'reminder',  message: 'Reminder: Contract CNT-2023-042 with Bio-Tech Logistics is expiring in 7 days. Please review before renewal.', timestamp: '2 days ago',   isRead: false, isFavorite: false, isArchived: false },
  { id: 'N-004', type: 'partner',   message: 'A new business partner BioGenesis Research has been added to the system.',                                     timestamp: '5 days ago',   isRead: true,  isFavorite: true,  isArchived: false },
  { id: 'N-005', type: 'contract',  message: 'Contract CNT-2023-089 with Global Pharma Inc. has been updated to Draft SBSI status.',                        timestamp: '07 Feb, 2025', isRead: true,  isFavorite: false, isArchived: false },
  { id: 'N-006', type: 'system',    message: 'System configuration was updated by Admin. Role permissions for Manager have been modified.',                  timestamp: '01 Feb, 2025', isRead: true,  isFavorite: false, isArchived: true  },
  { id: 'N-007', type: 'reminder',  message: 'Reminder: Review the contract proposal for Stellar Lab Equipment currently under negotiation.',                timestamp: '28 Jan, 2025', isRead: true,  isFavorite: false, isArchived: false },
  { id: 'N-008', type: 'contract',  message: 'Contract CNT-2023-112 with Stellar Lab Equipment has been successfully renewed for another 12 months.',        timestamp: '27 Jan, 2025', isRead: true,  isFavorite: true,  isArchived: false },
  { id: 'N-009', type: 'partner',   message: 'Supplier MedLine Philippines has updated their contact information and primary account manager.',              timestamp: '26 Jan, 2025', isRead: true,  isFavorite: false, isArchived: false },
  { id: 'N-010', type: 'user',      message: 'User account for Bob Johnson has been deactivated by Admin.',                                                  timestamp: '25 Jan, 2025', isRead: true,  isFavorite: false, isArchived: true  },
  { id: 'N-011', type: 'system',    message: 'Scheduled system maintenance is set for Feb 20, 2025 from 12:00 AM to 2:00 AM. Expect brief downtime.',        timestamp: '24 Jan, 2025', isRead: true,  isFavorite: false, isArchived: true  },
  { id: 'N-012', type: 'contract',  message: 'Contract CNT-2023-134 with BioGenesis Research is awaiting client signature. Follow up recommended.',          timestamp: '23 Jan, 2025', isRead: true,  isFavorite: false, isArchived: false },
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
        <p class="text-sm text-black/40 mt-0.5">View and manage all system notifications.</p>
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
