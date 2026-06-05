import { ref } from 'vue'
import { useAuth } from './useAuth'
import type { Partner, TabKey, Region, Status, LinkedContract } from '@/types/partner'

export function usePartners() {
  const { state: authState } = useAuth()

  const loading = ref(false)
  const partners = ref<Partner[]>([])
  const totalItems = ref(0)
  const lastPage = ref(1)

  const fetchPartners = async (
    type: TabKey,
    page = 1,
    perPage = 8,
    search = '',
    region = 'All'
  ) => {
    loading.value = true
    try {
      const apiBase = import.meta.env.VITE_VENDOR_API_URL || 'http://localhost:8001/api'
      const endpoint = type === 'partners' ? 'partners' : 'suppliers'
      const url = new URL(`${apiBase}/${endpoint}`)
      
      url.searchParams.append('page', String(page))
      url.searchParams.append('per_page', String(perPage))
      if (search) {
        url.searchParams.append('search', search)
      }
      if (region && region !== 'All') {
        url.searchParams.append('region', region)
      }

      const res = await fetch(url.toString(), {
        headers: {
          'Authorization': `Bearer ${authState.token || ''}`,
          'Accept': 'application/json'
        }
      })

      if (!res.ok) throw new Error(`Failed to fetch ${type}`)

      const json = await res.json()
      
      totalItems.value = json.total || 0
      lastPage.value = json.last_page || 1

      partners.value = (json.data || []).map((item: any) => {
        if (type === 'partners') {
          return {
            id: item.bp_code || `BP-${String(item.partner_id).padStart(3, '0')}`,
            db_id: item.partner_id,
            name: item.partner_name || '',
            industry: item.industry || 'Banking & Finance',
            region: (item.region || 'Luzon') as Region,
            status: (item.status || 'Active') as Status,
            contracts: item.associations_count || 0,
            totalValue: '₱0',
            contactPerson: item.contact_person || '',
            email: item.email || '',
            phone: item.contact_number || '',
            address: item.address || ''
          }
        } else {
          return {
            id: `SP-${String(item.supplier_id).padStart(3, '0')}`,
            db_id: item.supplier_id,
            name: item.supplier_name || '',
            industry: item.industry || 'Medical Supplies',
            region: (item.region || 'Luzon') as Region,
            status: (item.status || 'Active') as Status,
            contracts: item.associations_count || 0,
            totalValue: '₱0',
            contactPerson: item.contact_person || '',
            email: item.email || '',
            phone: item.contact_number || '',
            address: item.address || ''
          }
        }
      })
    } catch (err) {
      console.error(err)
      throw err
    } finally {
      loading.value = false
    }
  }

  const fetchVendorContracts = async (type: TabKey, dbId: number): Promise<LinkedContract[]> => {
    try {
      const apiBase = import.meta.env.VITE_VENDOR_API_URL || 'http://localhost:8001/api'
      const endpoint = type === 'partners' ? 'partners' : 'suppliers'
      
      const res = await fetch(`${apiBase}/${endpoint}/${dbId}/contracts`, {
        headers: {
          'Authorization': `Bearer ${authState.token || ''}`,
          'Accept': 'application/json'
        }
      })

      if (!res.ok) throw new Error('Failed to fetch linked contracts')

      const json = await res.json()
      const data = Array.isArray(json) ? json : (json.data || [])

      return data.map((item: any) => ({
        associationId: String(item.association_id),
        contractId: String(item.contract_id),
        description: item.contract?.description || 'No description',
        businessPartner: item.contract?.bp_name || '',
        startDate: item.contract?.start_date || '',
        endDate: item.contract?.end_date || '',
        engagementStatus: item.engagement_status || 'active',
        attachedBy: item.attached_by ? `User #${item.attached_by}` : '—'
      }))
    } catch (err) {
      console.error(err)
      return []
    }
  }

  const addPartner = async (
    type: TabKey,
    form: Omit<Partner, 'contracts' | 'totalValue'>
  ) => {
    loading.value = true
    try {
      const apiBase = import.meta.env.VITE_VENDOR_API_URL || 'http://localhost:8001/api'
      const endpoint = type === 'partners' ? 'partners' : 'suppliers'
      
      let payload: any = {}
      if (type === 'partners') {
        payload = {
          bp_code: form.id || `BP-${Date.now().toString().slice(-6)}`,
          partner_name: form.name,
          contact_number: form.phone,
          email: form.email,
          address: form.address,
          region: form.region,
          industry: form.industry,
          status: form.status,
          contact_person: form.contactPerson
        }
      } else {
        payload = {
          supplier_name: form.name,
          contact_number: form.phone,
          email: form.email,
          address: form.address,
          region: form.region,
          industry: form.industry,
          status: form.status,
          contact_person: form.contactPerson
        }
      }

      const res = await fetch(`${apiBase}/${endpoint}`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${authState.token || ''}`,
          'Accept': 'application/json'
        },
        body: JSON.stringify(payload)
      })

      const json = await res.json()
      if (!res.ok) {
        if (res.status === 422 && json.errors) {
          const firstErr = Object.values(json.errors as Record<string, string[]>)[0]?.[0]
          throw new Error(firstErr || json.message)
        }
        throw new Error(json.message || `Failed to add ${type}`)
      }
      return json
    } finally {
      loading.value = false
    }
  }

  const deletePartner = async (type: TabKey, dbId: number) => {
    loading.value = true
    try {
      const apiBase = import.meta.env.VITE_VENDOR_API_URL || 'http://localhost:8001/api'
      const endpoint = type === 'partners' ? 'partners' : 'suppliers'
      const res = await fetch(`${apiBase}/${endpoint}/${dbId}`, {
        method: 'DELETE',
        headers: {
          'Authorization': `Bearer ${authState.token || ''}`,
          'Accept': 'application/json'
        }
      })
      if (!res.ok) {
        const json = await res.json().catch(() => ({}))
        throw new Error(json.message || `Failed to delete ${type}`)
      }
    } finally {
      loading.value = false
    }
  }

  const linkContract = async (type: TabKey, dbId: number, contractId: string) => {
    try {
      const apiBase = import.meta.env.VITE_VENDOR_API_URL || 'http://localhost:8001/api'
      const endpoint = type === 'partners' ? 'partners' : 'suppliers'
      
      const res = await fetch(`${apiBase}/${endpoint}/${dbId}/contracts`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${authState.token || ''}`,
          'Accept': 'application/json'
        },
        body: JSON.stringify({ contract_id: Number(contractId) })
      })

      const json = await res.json()
      if (!res.ok) {
        throw new Error(json.message || 'Failed to link contract')
      }
      return json
    } catch (err) {
      console.error(err)
      throw err
    }
  }

  const detachContract = async (type: TabKey, dbId: number, contractId: string) => {
    try {
      const apiBase = import.meta.env.VITE_VENDOR_API_URL || 'http://localhost:8001/api'
      const endpoint = type === 'partners' ? 'partners' : 'suppliers'
      
      const res = await fetch(`${apiBase}/${endpoint}/${dbId}/contracts/${contractId}`, {
        method: 'DELETE',
        headers: {
          'Authorization': `Bearer ${authState.token || ''}`,
          'Accept': 'application/json'
        }
      })

      if (!res.ok) {
        const json = await res.json().catch(() => ({}))
        throw new Error(json.message || 'Failed to detach contract')
      }
    } catch (err) {
      console.error(err)
      throw err
    }
  }

  return {
    loading,
    partners,
    totalItems,
    lastPage,
    fetchPartners,
    fetchVendorContracts,
    addPartner,
    deletePartner,
    linkContract,
    detachContract
  }
}
