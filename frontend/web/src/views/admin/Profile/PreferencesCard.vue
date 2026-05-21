<script setup lang="ts">
import { reactive, watch } from 'vue'
import { SlidersHorizontal } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import ToggleRow from '@/components/shared/ToggleRow.vue'

const props = defineProps<{
  preferences: {
    emailNotifications: boolean; systemAlerts: boolean
    smsNotifications: boolean; contractExpiry: boolean
    loginAlerts: boolean; timezone: string
    language: string; dateFormat: string
  }
}>()

const emit = defineEmits<{ save: [] }>()

const form = reactive({ ...props.preferences })
watch(() => props.preferences, p => Object.assign(form, p), { deep: true })
</script>

<template>
  <div class="bg-white rounded-lg border border-black/8 shadow-sm">

    <div class="px-6 pt-5 pb-4 border-b border-black/5 flex items-center gap-3">
      <div class="w-8 h-8 rounded-lg bg-[#2E85D8]/8 flex items-center justify-center shrink-0">
        <SlidersHorizontal class="w-4 h-4 text-[#2E85D8]" />
      </div>
      <div>
        <h3 class="text-sm font-semibold text-black">Preferences</h3>
        <p class="text-xs text-black/40">Notifications, language and display settings.</p>
      </div>
    </div>

    <!-- Notifications -->
    <div class="px-6 pt-4 pb-1">
      <p class="text-[10px] font-bold text-black/35 uppercase tracking-widest mb-1">Notifications</p>
    </div>
    <div class="divide-y divide-black/4">
      <ToggleRow label="Email notifications" description="Receive alerts via email"
        :model-value="form.emailNotifications" @update:model-value="form.emailNotifications = $event" />
      <ToggleRow label="System alerts" description="In-app notifications and banners"
        :model-value="form.systemAlerts" @update:model-value="form.systemAlerts = $event" />
      <ToggleRow label="SMS notifications" description="Text messages for critical events"
        :model-value="form.smsNotifications" @update:model-value="form.smsNotifications = $event" />
      <ToggleRow label="Contract expiry alerts" description="Notify 15 days before expiry"
        :model-value="form.contractExpiry" @update:model-value="form.contractExpiry = $event" />
      <ToggleRow label="Login alerts" description="Email me when a new login is detected"
        :model-value="form.loginAlerts" @update:model-value="form.loginAlerts = $event" />
    </div>

    <!-- Display -->
    <div class="px-6 pt-4 pb-1">
      <p class="text-[10px] font-bold text-black/35 uppercase tracking-widest mb-1">Display</p>
    </div>
    <div class="px-6 pb-5 space-y-4">
      <div class="grid grid-cols-2 gap-4">
        <div class="space-y-1.5">
          <label class="text-xs font-semibold text-black/55 uppercase tracking-wide">Language</label>
          <Select v-model="form.language">
            <SelectTrigger class="h-9 rounded-md border-black/12 text-sm"><SelectValue /></SelectTrigger>
            <SelectContent>
              <SelectItem value="English">English</SelectItem>
              <SelectItem value="Filipino">Filipino</SelectItem>
            </SelectContent>
          </Select>
        </div>
        <div class="space-y-1.5">
          <label class="text-xs font-semibold text-black/55 uppercase tracking-wide">Date Format</label>
          <Select v-model="form.dateFormat">
            <SelectTrigger class="h-9 rounded-md border-black/12 text-sm"><SelectValue /></SelectTrigger>
            <SelectContent>
              <SelectItem value="MM/DD/YYYY">MM/DD/YYYY</SelectItem>
              <SelectItem value="DD/MM/YYYY">DD/MM/YYYY</SelectItem>
              <SelectItem value="YYYY-MM-DD">YYYY-MM-DD</SelectItem>
            </SelectContent>
          </Select>
        </div>
      </div>
      <div class="space-y-1.5">
        <label class="text-xs font-semibold text-black/55 uppercase tracking-wide">Timezone</label>
        <Select v-model="form.timezone">
          <SelectTrigger class="h-9 rounded-md border-black/12 text-sm"><SelectValue /></SelectTrigger>
          <SelectContent>
            <SelectItem value="Asia/Manila">Asia/Manila (UTC+8)</SelectItem>
            <SelectItem value="Asia/Singapore">Asia/Singapore (UTC+8)</SelectItem>
            <SelectItem value="UTC">UTC (UTC+0)</SelectItem>
          </SelectContent>
        </Select>
      </div>
      <Button @click="$emit('save')" class="h-9 px-5 text-sm bg-[#252578] hover:bg-[#2F2F73] text-white">
        Save preferences
      </Button>
    </div>

  </div>
</template>
