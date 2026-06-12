<script setup lang="ts">
import { Star, Trash2, FileText, UserPlus, Handshake, ShieldCheck, AlertCircle } from 'lucide-vue-next'
import { typeColor } from '@/types/notification'
import type { Notification, NotifType } from '@/types/notification'
import type { Component } from 'vue'

defineProps<{ notif: Notification }>()
defineEmits<{ 'toggle-read': [id: string]; 'toggle-favorite': [id: string]; 'delete': [id: string] }>()

const typeIcon: Record<NotifType, Component> = {
  contract: FileText,
  user:     UserPlus,
  partner:  Handshake,
  system:   ShieldCheck,
  reminder: AlertCircle,
}
</script>

<template>
  <div class="flex items-center gap-4 px-6 py-4 border-l-2 hover:bg-black/1.2 transition-colors group"
    :class="!notif.isRead ? 'bg-[#2E85D8]/[0.06] border-l-[#2E85D8]' : 'border-l-transparent'">

    <div class="w-2 shrink-0 flex justify-center">
      <div class="w-2 h-2 rounded-full transition-colors"
        :class="!notif.isRead ? 'bg-[#2E85D8]' : 'bg-transparent'" />
    </div>

    <button @click="$emit('toggle-favorite', notif.id)" class="shrink-0 transition-colors"
      :class="notif.isFavorite ? 'text-amber-400' : 'text-black/20 hover:text-black/40'">
      <Star class="w-4 h-4" :class="notif.isFavorite ? 'fill-amber-400' : ''" />
    </button>

    <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0"
      :class="typeColor[notif.type]">
      <component :is="typeIcon[notif.type]" class="w-4 h-4" />
    </div>

    <p class="flex-1 text-sm leading-snug cursor-pointer select-none"
      :class="!notif.isRead ? 'text-black font-medium' : 'text-black/55'"
      @click="$emit('toggle-read', notif.id)">
      {{ notif.message }}
    </p>

    <span class="text-xs text-black/35 shrink-0 whitespace-nowrap">{{ notif.timestamp }}</span>

    <button @click="$emit('delete', notif.id)"
      class="shrink-0 w-7 h-7 flex items-center justify-center rounded-lg opacity-0 group-hover:opacity-100 transition-all text-black/30 hover:text-red-500 hover:bg-red-50">
      <Trash2 class="w-3.5 h-3.5" />
    </button>
  </div>
</template>
