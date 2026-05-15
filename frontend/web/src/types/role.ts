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
