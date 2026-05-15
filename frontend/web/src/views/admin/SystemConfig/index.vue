<script setup lang="ts">
import { provide, reactive } from 'vue'
import { Bell, FileText, Users, Monitor, Save } from 'lucide-vue-next'
import { useToast } from '@/composables/useToast'
import NotificationsTab  from './NotificationsTab.vue'
import ContractRulesTab  from './ContractRulesTab.vue'
import UserAccessTab     from './UserAccessTab.vue'
import SystemTab         from './SystemTab.vue'
import { ref } from 'vue'

const { success } = useToast()

type TabKey = 'notifications' | 'contracts' | 'access' | 'system'
const activeTab = ref<TabKey>('notifications')

const tabs: { key: TabKey; label: string; icon: typeof Bell }[] = [
  { key: 'notifications', label: 'Notifications',  icon: Bell     },
  { key: 'contracts',     label: 'Contract Rules',  icon: FileText },
  { key: 'access',        label: 'User & Access',   icon: Users    },
  { key: 'system',        label: 'System',          icon: Monitor  },
]

export interface SystemCfg {
  emailNotifs: boolean; inAppNotifs: boolean; smsNotifs: boolean
  contractExpiryAlerts: boolean; daysBeforeExpiry: number
  approvalAlerts: boolean; renewalReminders: boolean
  autoRenew: boolean; dualApproval: boolean; allowDraftSubmit: boolean
  requireNotarization: boolean; maxContractDays: number; expiryWarningDays: number
  allowSelfReg: boolean; requireEmailVerify: boolean
  sessionTimeout: number; maxLoginAttempts: number; minPasswordLength: number
  requireStrongPass: boolean; twoFactorAdmins: boolean; twoFactorAll: boolean
  systemName: string; supportEmail: string; timezone: string; dateFormat: string
  auditLogging: boolean; autoBackups: boolean; backupFrequency: string; dataRetentionDays: number
}

const cfg = reactive<SystemCfg>({
  emailNotifs: true, inAppNotifs: true, smsNotifs: false,
  contractExpiryAlerts: true, daysBeforeExpiry: 30,
  approvalAlerts: true, renewalReminders: true,
  autoRenew: false, dualApproval: true, allowDraftSubmit: true,
  requireNotarization: true, maxContractDays: 365, expiryWarningDays: 30,
  allowSelfReg: false, requireEmailVerify: true,
  sessionTimeout: 480, maxLoginAttempts: 5, minPasswordLength: 8,
  requireStrongPass: true, twoFactorAdmins: false, twoFactorAll: false,
  systemName: 'ContractMS', supportEmail: 'support@sbsi.com',
  timezone: 'Asia/Manila', dateFormat: 'MM/DD/YYYY',
  auditLogging: true, autoBackups: true, backupFrequency: 'daily', dataRetentionDays: 365,
})

provide('cfg', cfg)

function saveChanges() {
  success('Settings saved', 'System configuration has been updated.')
}
</script>

<template>
  <div class="p-8 space-y-6">

    <div class="flex items-start justify-between">
      <div>
        <h1 class="text-xl font-semibold text-black">System Configuration</h1>
        <p class="text-sm text-black/40 mt-0.5">Manage system-wide settings, notifications, and access controls.</p>
      </div>
      <button
        @click="saveChanges"
        class="flex items-center gap-2 bg-[#252578] hover:bg-[#2F2F73] text-white text-sm font-semibold px-4 py-2.5 rounded-lg transition-colors"
      >
        <Save class="w-4 h-4" />
        Save Changes
      </button>
    </div>

    <div class="flex items-center gap-0.5 bg-black/4 rounded-lg p-1 w-fit">
      <button
        v-for="tab in tabs"
        :key="tab.key"
        @click="activeTab = tab.key"
        class="flex items-center gap-2 px-4 py-2 text-sm rounded-md transition-all font-medium"
        :class="activeTab === tab.key
          ? 'bg-white text-black shadow-sm'
          : 'text-black/40 hover:text-black/60'"
      >
        <component :is="tab.icon" class="w-3.5 h-3.5" />
        {{ tab.label }}
      </button>
    </div>

    <NotificationsTab  v-if="activeTab === 'notifications'" />
    <ContractRulesTab  v-if="activeTab === 'contracts'" />
    <UserAccessTab     v-if="activeTab === 'access'" />
    <SystemTab         v-if="activeTab === 'system'" />

  </div>
</template>
