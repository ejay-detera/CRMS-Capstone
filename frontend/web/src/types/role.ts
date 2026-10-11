import type { Component } from 'vue'

// Widened from a closed union to a plain string: admins can create arbitrary
// custom roles (e.g. "Regulatory Officer"), so this can no longer be a fixed
// set of literal names. Role identity/behavior should be derived from role
// data (permissions, nav_group) rather than from matching this string.
export type RoleKey = string

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

// ── System access ─────────────────────────────────────────────────────
// Describes an external system that a role can be granted access to visit.
// `available: false` renders as a "Coming soon" placeholder.
export interface SystemDefinition {
  key:         string   // 'cms' | 'prs' | 'hrm' | 'fin'
  label:       string   // display name, e.g. 'CMS'
  description: string   // short subtitle shown in the card
  available:   boolean  // false = disabled Coming-soon placeholder
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

