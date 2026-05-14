<script setup lang="ts">
import { inject } from 'vue'
import { Send, FileText } from 'lucide-vue-next'
import type { SystemCfg } from './index.vue'
import SettingCard from '@/components/shared/SettingCard.vue'
import ToggleRow   from '@/components/shared/ToggleRow.vue'

const cfg = inject<SystemCfg>('cfg')!
</script>

<template>
  <div class="space-y-4">

    <SettingCard title="Delivery Channels" description="Choose how notifications are sent to users."
      :icon="Send" icon-bg="bg-[#2E85D8]/8" icon-color="text-[#2E85D8]">
      <div class="divide-y divide-black/[0.04]">
        <ToggleRow v-model="cfg.emailNotifs"  label="Email Notifications"  description="Send alerts via email to relevant users." />
        <ToggleRow v-model="cfg.inAppNotifs"  label="In-App Notifications" description="Show notification bell alerts inside the system." />
        <ToggleRow v-model="cfg.smsNotifs"    label="SMS Notifications"    description="Send critical alerts via SMS (requires SMS gateway)." />
      </div>
    </SettingCard>

    <SettingCard title="Contract Alerts" description="Configure when contract-related notifications are triggered."
      :icon="FileText">
      <div class="divide-y divide-black/[0.04]">

        <div class="px-6 py-4 space-y-3">
          <ToggleRow v-model="cfg.contractExpiryAlerts"
            label="Contract Expiry Alerts"
            description="Notify users when contracts are approaching expiry." />
          <div v-if="cfg.contractExpiryAlerts" class="bg-black/[0.02] rounded-lg border border-black/[0.06] p-4">
            <label class="text-[11px] font-semibold text-black/40 uppercase tracking-wider">Days before expiry to notify</label>
            <div class="relative mt-2">
              <input v-model.number="cfg.daysBeforeExpiry" type="number" min="1" max="365"
                class="w-full rounded-lg border border-black/10 bg-white px-3 py-2 pr-12 text-sm text-black focus:border-[#2E85D8] focus:outline-none transition-colors" />
              <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-black/35 pointer-events-none">days</span>
            </div>
          </div>
        </div>

        <ToggleRow v-model="cfg.approvalAlerts"    label="Approval Request Alerts" description="Notify managers when a contract request is submitted." />
        <ToggleRow v-model="cfg.renewalReminders"  label="Renewal Reminders"       description="Send reminders when contracts are due for renewal." />
      </div>
    </SettingCard>

  </div>
</template>
