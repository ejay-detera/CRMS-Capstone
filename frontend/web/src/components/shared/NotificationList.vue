<script setup lang="ts">
import { Bell, Search } from 'lucide-vue-next'
import NotificationItem from './NotificationItem.vue'
import type { Notification, TabKey } from '@/types/notification'

defineProps<{
  tabs:        { key: TabKey; label: string; count: number }[]
  activeTab:   TabKey
  searchQuery: string
  filtered:    Notification[]
  unreadCount: number
}>()

const emit = defineEmits<{
  'update:activeTab':   [tab: TabKey]
  'update:searchQuery': [q: string]
  'toggle-read':        [id: string]
  'toggle-favorite':    [id: string]
  'delete':             [id: string]
}>()
</script>

<template>
  <div class="bg-white rounded-lg border border-black/8 shadow-sm overflow-hidden">

    <div class="px-6 pt-5 pb-4 border-b border-black/5 flex items-center justify-between gap-4">
      <div class="flex items-center gap-2">
        <h2 class="text-sm font-semibold text-black">List Notifications</h2>
        <span v-if="unreadCount > 0"
          class="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-[#2E85D8] text-white tabular-nums">
          {{ unreadCount }} unread
        </span>
      </div>
      <div class="relative w-64">
        <Search class="w-3.5 h-3.5 text-black/35 absolute left-3 top-1/2 -translate-y-1/2" />
        <input :value="searchQuery" @input="emit('update:searchQuery', ($event.target as HTMLInputElement).value.trim())"
          type="text" placeholder="Search notifications..."
          class="w-full rounded-lg border border-black/10 bg-black/2 py-2 pl-8 pr-3 text-sm text-black placeholder:text-black/35 focus:border-[#2E85D8] focus:outline-none transition-colors" />
      </div>
    </div>

    <div class="px-6 py-3 border-b border-black/5 flex items-center justify-between">
      <div class="flex items-center gap-0.5 bg-black/4 rounded-md p-1">
        <button v-for="tab in tabs" :key="tab.key"
          @click="emit('update:activeTab', tab.key); emit('update:searchQuery', '')"
          class="flex items-center gap-1.5 px-3 py-1 text-xs rounded transition-all font-medium"
          :class="activeTab === tab.key ? 'bg-white text-black shadow-sm' : 'text-black/40 hover:text-black/60'">
          {{ tab.label }}
          <span class="text-[10px] font-semibold tabular-nums px-1.5 py-0.5 rounded-full min-w-4.5 text-center"
            :class="activeTab === tab.key ? 'bg-[#252578]/8 text-[#252578]' : 'bg-black/8 text-black/40'">
            {{ tab.count }}
          </span>
        </button>
      </div>
      <p class="text-xs text-black/35">{{ filtered.length }} notification{{ filtered.length !== 1 ? 's' : '' }}</p>
    </div>

    <div class="divide-y divide-black/4">
      <slot v-if="activeTab === 'email_logs'" name="email-logs" />
      <template v-else>
        <div v-if="filtered.length === 0" class="flex flex-col items-center justify-center py-16 text-center">
          <div class="w-12 h-12 rounded-full bg-black/4 flex items-center justify-center mb-3">
            <Bell class="w-5 h-5 text-black/30" />
          </div>
          <p class="text-sm font-medium text-black/50">No notifications</p>
          <p class="text-xs text-black/30 mt-0.5">You're all caught up!</p>
        </div>

        <NotificationItem
          v-for="notif in filtered"
          :key="notif.id"
          :notif="notif"
          @toggle-read="emit('toggle-read', $event)"
          @toggle-favorite="emit('toggle-favorite', $event)"
          @delete="emit('delete', $event)"
        />
      </template>
    </div>

  </div>
</template>
