<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import { Bell, Search, Trash2, Check, Minus } from 'lucide-vue-next'
import NotificationItem from './NotificationItem.vue'
import ConfirmationDialog from './ConfirmationDialog.vue'
import type { Notification, TabKey } from '@/types/notification'

const props = defineProps<{
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
  'delete-selected':    [ids: string[]]
}>()

const showDeleteConfirm = ref(false)
const notifIdToDelete = ref<string | null>(null)

function triggerDelete(id: string) {
  notifIdToDelete.value = id
  showDeleteConfirm.value = true
}

function confirmDelete() {
  if (notifIdToDelete.value) {
    emit('delete', notifIdToDelete.value)
  }
  showDeleteConfirm.value = false
  notifIdToDelete.value = null
}

const showDeleteAllConfirm = ref(false)
const selectedIds = ref<Set<string>>(new Set())

const allSelected = computed(() => props.filtered.length > 0 && selectedIds.value.size === props.filtered.length)
const someSelected = computed(() => selectedIds.value.size > 0 && selectedIds.value.size < props.filtered.length)

watch(() => props.filtered, () => {
  selectedIds.value.clear()
})

function toggleSelectAll() {
  if (allSelected.value) {
    selectedIds.value.clear()
  } else {
    props.filtered.forEach(n => selectedIds.value.add(n.id))
  }
}

function confirmDeleteAll() {
  emit('delete-selected', Array.from(selectedIds.value))
  selectedIds.value.clear()
  showDeleteAllConfirm.value = false
}
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
      <div class="flex items-center gap-4">
        <button type="button" v-if="filtered.length > 0 && activeTab !== 'email_logs'" @click="toggleSelectAll"
          class="flex items-center gap-2 px-2.5 py-1.5 rounded-md hover:bg-black/[0.04] active:bg-black/[0.06] transition-colors group select-none -ml-2">
          <div class="w-4 h-4 rounded-[4px] flex items-center justify-center border transition-all duration-200"
            :class="allSelected || someSelected ? 'bg-[#2E85D8] border-[#2E85D8] text-white shadow-sm' : 'border-black/20 bg-white group-hover:border-black/40 text-transparent'">
            <Check v-if="allSelected" class="w-3 h-3" stroke-width="3.5" />
            <Minus v-else-if="someSelected" class="w-3 h-3" stroke-width="3.5" />
          </div>
          <span class="text-xs font-semibold text-black/60 group-hover:text-black/80 transition-colors">
            Select All
          </span>
        </button>
        <button v-if="selectedIds.size > 0 && activeTab !== 'email_logs'" @click="showDeleteAllConfirm = true"
          class="flex items-center gap-1.5 text-xs font-semibold text-red-500 hover:text-red-700 transition-colors">
          <Trash2 class="w-3.5 h-3.5" />
          Delete Selected ({{ selectedIds.size }})
        </button>
        <p class="text-xs text-black/35">{{ filtered.length }} notification{{ filtered.length !== 1 ? 's' : '' }}</p>
      </div>
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
          :selected="selectedIds.has(notif.id)"
          @update:selected="$event ? selectedIds.add(notif.id) : selectedIds.delete(notif.id)"
          @toggle-read="emit('toggle-read', $event)"
          @toggle-favorite="emit('toggle-favorite', $event)"
          @delete="triggerDelete"
        />
      </template>
    </div>

    <ConfirmationDialog
      v-model:open="showDeleteConfirm"
      :title="activeTab === 'archive' ? 'Delete Permanently' : 'Delete Notification'"
      :description="activeTab === 'archive' ? 'Are you sure you want to permanently delete this notification? This action cannot be undone.' : 'Are you sure you want to delete this notification? This action will archive and remove it from your notifications list.'"
      :confirm-label="activeTab === 'archive' ? 'Delete Permanently' : 'Delete'"
      variant="destructive"
      @confirm="confirmDelete"
    />

    <ConfirmationDialog
      v-model:open="showDeleteAllConfirm"
      :title="activeTab === 'archive' ? 'Delete Selected Permanently' : 'Delete Selected Notifications'"
      :description="activeTab === 'archive' ? `Are you sure you want to permanently delete the ${selectedIds.size} selected notification(s)? This action cannot be undone.` : `Are you sure you want to delete the ${selectedIds.size} selected notification(s)? This action will archive them.`"
      :confirm-label="activeTab === 'archive' ? 'Delete Permanently' : 'Delete Selected'"
      variant="destructive"
      @confirm="confirmDeleteAll"
    />
  </div>
</template>
