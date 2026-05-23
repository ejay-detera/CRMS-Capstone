<script setup lang="ts">
import type { RoleMeta, Category } from '@/types/role'

// Props now use activeRoleName (string) + activeRoleMeta instead of a map lookup,
// and activePermissions is still string[] (slugs) — unchanged for the template.
const props = defineProps<{
  activeRoleName:  string
  isLocked:        boolean
  activeRoleMeta:  RoleMeta
  categories:      Category[]
  activePermissions: string[]   // slugs of currently-enabled permissions
}>()

const emit = defineEmits<{
  'toggle-permission': [categoryKey: string, permKey: string]
  'toggle-category':   [cat: Category]
}>()

function isCategoryAllChecked(cat: Category): boolean {
  return cat.permissions.every(p => props.activePermissions.includes(p.key))
}
</script>

<template>
  <div class="bg-white rounded-xl border border-black/8 shadow-sm overflow-hidden">

    <!-- Panel header -->
    <div class="px-6 py-4 border-b border-black/5 flex items-center gap-3">
      <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-[#252578]/8">
        <component :is="activeRoleMeta.icon" class="w-4 h-4 text-[#252578]" />
      </div>
      <div>
        <h2 class="text-sm font-semibold text-black">{{ activeRoleName }} Role Permissions</h2>
        <p class="text-xs text-black/40 mt-0.5">
          {{ isLocked
            ? `${activeRoleName} has all permissions (cannot be restricted)`
            : `Customize what ${activeRoleName} users can access` }}
        </p>
      </div>
      <span v-if="isLocked"
        class="ml-auto text-xs font-medium text-amber-600 bg-amber-50 border border-amber-200 px-2.5 py-1 rounded-full">
        Locked
      </span>
    </div>

    <!-- Permission categories -->
    <div class="divide-y divide-black/5">
      <div v-for="cat in categories" :key="cat.key" class="px-6 py-5">

        <!-- Category header -->
        <div class="flex items-center justify-between mb-4">
          <span class="text-[11px] font-semibold text-black/40 uppercase tracking-wider">
            {{ cat.label }}
          </span>
          <button
            v-if="!isLocked"
            @click="emit('toggle-category', cat)"
            class="text-xs font-medium transition-colors"
            :class="isCategoryAllChecked(cat)
              ? 'text-[#2E85D8] hover:text-[#252578]'
              : 'text-black/35 hover:text-black/60'"
          >
            {{ isCategoryAllChecked(cat) ? 'Deselect all' : 'Select all' }}
          </button>
        </div>

        <!-- Permission pills (slug is used as key, matching activePermissions slugs) -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
          <button
            v-for="perm in cat.permissions"
            :key="perm.key"
            @click="emit('toggle-permission', cat.key, perm.key)"
            :disabled="isLocked"
            class="flex items-center justify-between gap-2 px-5 py-2.5 rounded-full border text-left transition-all duration-200 shrink-0"
            :class="activePermissions.includes(perm.key)
              ? 'bg-[#252578]/6 border-[#252578]/25 text-[#252578] shadow-sm'
              : 'bg-black/[0.02] border-black/8 text-black/45 hover:bg-black/[0.04] hover:border-black/15'"
          >
            <div class="flex items-center gap-2.5">
              <div
                class="w-4 h-4 rounded-full border flex items-center justify-center shrink-0 transition-all duration-200"
                :class="activePermissions.includes(perm.key)
                  ? 'bg-[#252578] border-[#252578]'
                  : 'bg-white border-black/20'"
              >
                <div
                  class="w-1.5 h-1.5 rounded-full bg-white transition-all duration-200"
                  :class="activePermissions.includes(perm.key) ? 'scale-100 opacity-100' : 'scale-0 opacity-0'"
                ></div>
              </div>
              <span class="text-sm font-medium">{{ perm.label }}</span>
            </div>

            <!-- Lock icon for locked roles -->
            <svg v-if="isLocked" viewBox="0 0 24 24"
              class="w-3.5 h-3.5 shrink-0 fill-none stroke-current opacity-40 stroke-[1.8]">
              <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
              <path d="M7 11V7a5 5 0 0 1 10 0v4" />
            </svg>
          </button>
        </div>

      </div>
    </div>

  </div>
</template>
