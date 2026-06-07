<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { CheckCheck } from 'lucide-vue-next'
import NotificationList from '@/components/shared/NotificationList.vue'
import EmailLogTable from '@/views/admin/Notifications/EmailLogTable.vue'
import { useNotifications } from '@/composables/useNotifications'
import { useEmailLogs } from '@/composables/useEmailLogs'
import type { TabKey } from '@/types/notification'

const { notifications, unreadCount, fetchNotifications, markRead, markAllRead, updateState } = useNotifications()
const { logs, loading: logsLoading, currentPage, lastPage, totalLogs, fetchLogs } = useEmailLogs()

const activeTab   = ref<TabKey>('all')
const searchQuery = ref('')

const allCount      = computed(() => notifications.value.filter(n => !n.isArchived).length)
const archiveCount  = computed(() => notifications.value.filter(n => n.isArchived).length)
const favoriteCount = computed(() => notifications.value.filter(n => n.isFavorite && !n.isArchived).length)

const tabs = computed(() => [
  { key: 'all'      as TabKey, label: 'All',       count: allCount.value      },
  { key: 'archive'  as TabKey, label: 'Archive',   count: archiveCount.value  },
  { key: 'favorite' as TabKey, label: 'Favorite',  count: favoriteCount.value },
  { key: 'email_logs' as TabKey, label: 'Email Log', count: totalLogs.value     },
])

const filtered = computed(() => {
  let list = notifications.value
  if (activeTab.value === 'all')      list = list.filter(n => !n.isArchived)
  if (activeTab.value === 'archive')  list = list.filter(n =>  n.isArchived)
  if (activeTab.value === 'favorite') list = list.filter(n =>  n.isFavorite && !n.isArchived)
  if (searchQuery.value.trim()) {
    const q = searchQuery.value.toLowerCase()
    list = list.filter(n => n.message.toLowerCase().includes(q))
  }
  return list
})

function toggleRead(id: string)     { markRead(id) }
function toggleFavorite(id: string) { const n = notifications.value.find(x => x.id === id); if (n) updateState(id, { isFavorite: !n.isFavorite }) }
function deleteNotif(id: string)    { updateState(id, { isArchived: true }) }

watch(activeTab, (newTab) => {
  if (newTab === 'email_logs') {
    fetchLogs(1)
  }
})

onMounted(async () => {
  await fetchNotifications()
  await fetchLogs(1)
})
</script>

<template>
  <div class="p-8 space-y-6">

    <div class="flex items-start justify-between">
      <div>
        <h1 class="text-xl font-semibold text-black">Notifications</h1>
        <p class="text-sm text-black/40 mt-0.5">View and manage all system notifications.</p>
      </div>
      <button v-if="unreadCount > 0 && activeTab !== 'email_logs'" @click="markAllRead"
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
    >
      <template #email-logs>
        <EmailLogTable
          :logs="logs"
          :loading="logsLoading"
          :current-page="currentPage"
          :last-page="lastPage"
          @change-page="fetchLogs"
          @refresh="fetchLogs(currentPage)"
        />
      </template>
    </NotificationList>

  </div>
</template>
