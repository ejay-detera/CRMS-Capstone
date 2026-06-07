import { ref } from 'vue'
import { useAuth } from './useAuth'
import type { EmailPreference } from '@/types/notification'

const BASE_URL = import.meta.env.VITE_NOTIFICATION_API_URL as string

function makeHeaders(): HeadersInit {
  const { state } = useAuth()
  return {
    'Accept':        'application/json',
    'Content-Type':  'application/json',
    'Authorization': `Bearer ${state.token}`,
  }
}

export function useEmailPreferences() {
  const preferences = ref<EmailPreference>({
    emailNotificationsEnabled: true,
    contractExpiryAlerts: true,
  })
  const loading = ref(false)

  async function fetchPreferences(): Promise<void> {
    loading.value = true
    try {
      const res = await fetch(`${BASE_URL}/email-preferences`, { headers: makeHeaders() })
      const json = await res.json()
      if (res.ok && json.data) {
        preferences.value = {
          emailNotificationsEnabled: json.data.email_notifications_enabled ?? true,
          contractExpiryAlerts: json.data.contract_expiry_alerts ?? true,
        }
      }
    } catch (e) {
      console.error('Failed to fetch email preferences', e)
    } finally {
      loading.value = false
    }
  }

  async function savePreferences(updated: EmailPreference): Promise<boolean> {
    loading.value = true
    try {
      const res = await fetch(`${BASE_URL}/email-preferences`, {
        method: 'PUT',
        headers: makeHeaders(),
        body: JSON.stringify({
          email_notifications_enabled: updated.emailNotificationsEnabled,
          contract_expiry_alerts: updated.contractExpiryAlerts,
        }),
      })
      const json = await res.json()
      if (res.ok && json.data) {
        preferences.value = {
          emailNotificationsEnabled: json.data.email_notifications_enabled ?? true,
          contractExpiryAlerts: json.data.contract_expiry_alerts ?? true,
        }
        return true;
      }
      return false;
    } catch (e) {
      console.error('Failed to save email preferences', e)
      return false;
    } finally {
      loading.value = false
    }
  }

  return {
    preferences,
    loading,
    fetchPreferences,
    savePreferences,
  }
}
