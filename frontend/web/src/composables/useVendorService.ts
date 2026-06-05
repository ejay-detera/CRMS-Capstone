import { useAuth } from './useAuth'
import type { Partner, AddPartnerForm, Region } from '@/types/partner'

const BASE_URL = import.meta.env.VITE_VENDOR_API_URL as string

function makeHeaders(): HeadersInit {
  const { state } = useAuth()
  return {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
    'Authorization': `Bearer ${state.token}`,
  }
}

function mapPartner(d: any): Partner {
  return {
    id:            d.partner_id,
    name:          d.partner_name   ?? '',
    industry:      d.industry       ?? '',
    region:        (d.region        ?? null) as Partner['region'],
    status:        (d.status        ?? 'Active') as Partner['status'],
    contactPerson: d.contact_person ?? '',
    email:         d.email          ?? '',
    phone:         d.contact_number ?? '',
    address:       d.address        ?? '',
    bpCode:        d.bp_code        ?? null,
    tinNumber:     null,
  }
}

function mapSupplier(d: any): Partner {
  return {
    id:            d.supplier_id,
    name:          d.supplier_name  ?? '',
    industry:      d.industry       ?? '',
    region:        (d.region        ?? null) as Partner['region'],
    status:        (d.status        ?? 'Active') as Partner['status'],
    contactPerson: d.contact_person ?? '',
    email:         d.email          ?? '',
    phone:         d.contact_number ?? '',
    address:       d.address        ?? '',
    bpCode:        null,
    tinNumber:     d.tin_number     ?? null,
  }
}

async function fetchAllPages(firstUrl: string): Promise<any[]> {
  let url: string | null = firstUrl
  const all: any[] = []
  while (url) {
    const res = await fetch(url, { headers: makeHeaders() })
    if (!res.ok) throw new Error(`Request failed: ${res.status}`)
    const json = await res.json()
    all.push(...(json.data ?? []))
    url = json.next_page_url ?? null
  }
  return all
}

async function fetchPartners(region?: string): Promise<Partner[]> {
  const url = new URL(`${BASE_URL}/partners`)
  if (region) url.searchParams.set('region', region)
  const rows = await fetchAllPages(url.toString())
  return rows.map(mapPartner)
}

async function createPartner(form: AddPartnerForm): Promise<{ partner: Partner; warnings: any[] }> {
  const res = await fetch(`${BASE_URL}/partners`, {
    method: 'POST',
    headers: makeHeaders(),
    body: JSON.stringify({
      bp_code:        `BP-${Date.now()}`,
      partner_name:   form.name,
      industry:       form.industry,
      contact_person: form.contactPerson,
      contact_number: form.phone,
      email:          form.email,
      address:        form.address,
      region:         form.region || null,
      status:         form.status,
    }),
  })
  const json = await res.json()
  if (!res.ok) throw json
  return { partner: mapPartner(json.data), warnings: json.warnings ?? [] }
}

async function updatePartner(id: number, form: AddPartnerForm, bpCode: string | null): Promise<{ partner: Partner; warnings: any[] }> {
  const res = await fetch(`${BASE_URL}/partners/${id}`, {
    method: 'PUT',
    headers: makeHeaders(),
    body: JSON.stringify({
      bp_code:        bpCode ?? `BP-${Date.now()}`,
      partner_name:   form.name,
      industry:       form.industry,
      contact_person: form.contactPerson,
      contact_number: form.phone,
      email:          form.email,
      address:        form.address,
      region:         form.region || null,
      status:         form.status,
    }),
  })
  const json = await res.json()
  if (!res.ok) throw json
  return { partner: mapPartner(json.data), warnings: json.warnings ?? [] }
}

async function fetchPartnerById(id: number): Promise<Partner> {
  const res = await fetch(`${BASE_URL}/partners/${id}`, { headers: makeHeaders() })
  const json = await res.json()
  if (!res.ok) throw json
  return mapPartner(json.data)
}

async function deletePartner(id: number): Promise<void> {
  const res = await fetch(`${BASE_URL}/partners/${id}`, {
    method: 'DELETE',
    headers: makeHeaders(),
  })
  if (!res.ok) {
    const json = await res.json().catch(() => ({}))
    throw json
  }
}

async function fetchSuppliers(region?: string): Promise<Partner[]> {
  const url = new URL(`${BASE_URL}/suppliers`)
  if (region) url.searchParams.set('region', region)
  const rows = await fetchAllPages(url.toString())
  return rows.map(mapSupplier)
}

async function createSupplier(form: AddPartnerForm): Promise<{ partner: Partner; warnings: any[] }> {
  const res = await fetch(`${BASE_URL}/suppliers`, {
    method: 'POST',
    headers: makeHeaders(),
    body: JSON.stringify({
      supplier_name:  form.name,
      tin_number:     form.tinNumber || null,
      industry:       form.industry,
      contact_person: form.contactPerson,
      contact_number: form.phone,
      email:          form.email,
      address:        form.address,
      region:         form.region || null,
      status:         form.status,
    }),
  })
  const json = await res.json()
  if (!res.ok) throw json
  return { partner: mapSupplier(json.data), warnings: json.warnings ?? [] }
}

async function updateSupplier(id: number, form: AddPartnerForm): Promise<{ partner: Partner; warnings: any[] }> {
  const res = await fetch(`${BASE_URL}/suppliers/${id}`, {
    method: 'PUT',
    headers: makeHeaders(),
    body: JSON.stringify({
      supplier_name:  form.name,
      tin_number:     form.tinNumber || null,
      industry:       form.industry,
      contact_person: form.contactPerson,
      contact_number: form.phone,
      email:          form.email,
      address:        form.address,
      region:         form.region || null,
      status:         form.status,
    }),
  })
  const json = await res.json()
  if (!res.ok) throw json
  return { partner: mapSupplier(json.data), warnings: json.warnings ?? [] }
}

async function fetchSupplierById(id: number): Promise<Partner> {
  const res = await fetch(`${BASE_URL}/suppliers/${id}`, { headers: makeHeaders() })
  const json = await res.json()
  if (!res.ok) throw json
  return mapSupplier(json.data)
}

async function deleteSupplier(id: number): Promise<void> {
  const res = await fetch(`${BASE_URL}/suppliers/${id}`, {
    method: 'DELETE',
    headers: makeHeaders(),
  })
  if (!res.ok) {
    const json = await res.json().catch(() => ({}))
    throw json
  }
}

export function useVendorService() {
  return {
    fetchPartners,
    fetchPartnerById,
    createPartner,
    updatePartner,
    deletePartner,
    fetchSuppliers,
    fetchSupplierById,
    createSupplier,
    updateSupplier,
    deleteSupplier,
  }
}
