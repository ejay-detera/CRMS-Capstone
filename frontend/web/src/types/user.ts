export type Role   = 'Admin' | 'Manager' | 'Sales'
export type Status = 'Active' | 'Inactive'

export interface User {
  id:        string
  name:      string
  email:     string
  role:      Role
  status:    Status
  dateAdded: string
}

export const roleBadge: Record<Role, string> = {
  Admin:   'bg-emerald-50  text-emerald-700 border-emerald-200',
  Manager: 'bg-[#2E85D8]/8 text-[#2E85D8]   border-[#2E85D8]/20',
  Sales:   'bg-[#252578]/6 text-[#252578]   border-[#252578]/20',
}

export const palette = ['#252578', '#2E85D8', '#2F2F73']

export function getInitials(name: string) {
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}

export function avatarColor(idx: number) {
  return palette[idx % palette.length]
}
