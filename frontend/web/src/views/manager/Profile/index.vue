<script setup lang="ts">
import { reactive } from 'vue'
import { useToast } from '@/composables/useToast'
import ProfileHero      from '@/views/admin/Profile/ProfileHero.vue'
import PersonalInfoCard from '@/views/admin/Profile/PersonalInfoCard.vue'
import SecurityCard     from '@/views/admin/Profile/SecurityCard.vue'
import PreferencesCard  from '@/views/admin/Profile/PreferencesCard.vue'

const { success } = useToast()

const profile = reactive({
  firstName:  'Shadrack',
  lastName:   'Castro',
  middleName: '',
  email:      'shadrack.castro@sbsi.com',
  phone:      '+63 2 8123 4567',
  role:       'Manager',
  department: 'IT Department',
  dateJoined: 'January 5, 2025',
})

const preferences = reactive({
  emailNotifications: true,
  systemAlerts:       true,
  smsNotifications:   false,
  contractExpiry:     true,
  loginAlerts:        true,
  timezone:           'Asia/Manila',
  language:           'English',
  dateFormat:         'MM/DD/YYYY',
})

function handleProfileSave(data: Partial<typeof profile>) {
  Object.assign(profile, data)
  success('Profile updated', 'Your personal details have been saved.')
}

function handlePasswordChange() {
  success('Password changed', 'Your password has been updated successfully.')
}

function handlePreferencesSave() {
  success('Preferences saved', 'Your settings have been applied.')
}
</script>

<template>
  <div class="p-8 space-y-6">

    <div>
      <h1 class="text-xl font-semibold text-black">My Profile</h1>
      <p class="text-sm text-black/40 mt-0.5">Manage your account details and preferences.</p>
    </div>

    <ProfileHero :profile="profile" />

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
      <PersonalInfoCard :profile="profile" @save="handleProfileSave" />
      <PreferencesCard  :preferences="preferences" @save="handlePreferencesSave" />
    </div>

    <SecurityCard @save="handlePasswordChange" />

  </div>
</template>
