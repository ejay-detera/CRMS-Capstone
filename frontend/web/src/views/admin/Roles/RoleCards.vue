<script setup lang="ts">
import type { RoleKey, RoleMeta } from '@/types/role'

defineProps<{
  roles:        RoleKey[]
  activeRole:   RoleKey
  roleMeta:     Record<RoleKey, RoleMeta>
  enabledCounts: Record<RoleKey, number>
}>()

defineEmits<{ 'update:activeRole': [role: RoleKey] }>()
</script>

<template>
  <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <button v-for="role in roles" :key="role"
      @click="$emit('update:activeRole', role)"
      class="text-left p-5 rounded-xl border transition-all duration-200"
      :class="activeRole === role
        ? 'bg-[#252578]/5 border-[#252578]/30 ring-1 ring-[#252578]/20'
        : 'bg-white border-black/8 hover:border-black/15'">

      <div class="flex items-center gap-3 mb-3">
        <div class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0"
          :class="activeRole === role ? 'bg-[#252578]/10' : 'bg-black/5'">
          <component :is="roleMeta[role].icon" class="w-4.5 h-4.5"
            :class="activeRole === role ? 'text-[#252578]' : 'text-black/40'" />
        </div>
        <span class="text-xs font-semibold px-2.5 py-1 rounded-full border"
          :class="activeRole === role
            ? 'bg-[#252578]/8 text-[#252578] border-[#252578]/20'
            : 'bg-black/4 text-black/50 border-black/8'">
          {{ role.toUpperCase() }}
        </span>
      </div>

      <p class="text-xs text-black/50 leading-relaxed mb-3">{{ roleMeta[role].description }}</p>
      <p class="text-xs font-semibold" :class="activeRole === role ? 'text-[#252578]' : 'text-black/40'">
        {{ enabledCounts[role] }} permissions enabled
      </p>
    </button>
  </div>
</template>
