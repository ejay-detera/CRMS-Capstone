import type { Component } from 'vue'

export type RoleKey = 'Admin' | 'Manager' | 'Sales'

export interface Permission {
  key:   string
  label: string
}

export interface Category {
  key:         string
  label:       string
  permissions: Permission[]
}

export interface RolePermissions {
  [categoryKey: string]: string[]
}

export interface RoleMeta {
  icon:        Component
  description: string
  locked:      boolean
}

// ── API-shaped types (from auth-service) ─────────────────────────────
export interface ApiRole {
  id:          number
  name:        string
  description: string
}

export interface ApiPermission {
  id:     number
  name:   string
  slug:   string
  system: string
}

