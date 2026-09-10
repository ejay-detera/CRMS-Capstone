import { BRAND_PALETTE } from '@/constants/theme'

export type Role   = 'Admin' | 'Manager' | 'Employee'
export type Status = 'Active' | 'Inactive'

export interface User {
  id:        string
  name:      string
  email:     string
  role:      Role
  status:    Status
  dateAdded: string
  department?: string
}

export const roleBadge: Record<Role, string> = {
  Admin:   'bg-brand-navy/8 text-brand-navy border-brand-navy/20',
  Manager: 'bg-brand-dark/8 text-brand-dark border-brand-dark/20',
  Employee:'bg-brand-blue/8 text-brand-blue border-brand-blue/20',
}

export const palette = BRAND_PALETTE

export function getInitials(name: string) {
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}

export function avatarColor(idx: number) {
  return palette[idx % palette.length]
}
