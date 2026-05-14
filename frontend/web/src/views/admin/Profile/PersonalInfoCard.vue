<script setup lang="ts">
import { reactive, watch, computed } from 'vue'
import { UserRound } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'

const props = defineProps<{
  profile: {
    firstName: string; lastName: string; middleName: string
    email: string; phone: string; role: string; department: string
  }
}>()

const emit = defineEmits<{ save: [data: Partial<typeof props.profile>] }>()

const form = reactive({ ...props.profile })
watch(() => props.profile, p => Object.assign(form, p), { deep: true })

const touched = reactive({
  firstName: false, lastName: false, email: false, phone: false,
})

const emailValid = computed(() => !form.email || /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email))
const phoneValid = computed(() => !form.phone || /^[+]?[\d\s\-(). ]{7,}$/.test(form.phone))

function onNameInput(field: 'firstName' | 'lastName' | 'middleName', e: Event) {
  const el = e.target as HTMLInputElement
  const clean = el.value.replace(/[^a-zA-Z\s\-'.]/g, '')
  form[field] = clean
  el.value = clean
  if (field !== 'middleName') touched[field as keyof typeof touched] = true
}

function fieldCls(field: keyof typeof touched, invalid: boolean) {
  return touched[field] && invalid
    ? 'border-red-400 focus:border-red-400 focus:ring-red-400/15'
    : 'border-black/12 focus:border-[#2E85D8] focus:ring-[#2E85D8]/15'
}

function save() {
  Object.assign(touched, { firstName: true, lastName: true, email: true, phone: true })
  if (!form.firstName || !form.lastName || !form.email || !emailValid.value || !form.phone || !phoneValid.value) return
  emit('save', { ...form })
}
</script>

<template>
  <div class="bg-white rounded-lg border border-black/8 shadow-sm">

    <div class="px-6 pt-5 pb-4 border-b border-black/5 flex items-center gap-3">
      <div class="w-8 h-8 rounded-lg bg-[#252578]/8 flex items-center justify-center shrink-0">
        <UserRound class="w-4 h-4 text-[#252578]" />
      </div>
      <div>
        <h3 class="text-sm font-semibold text-black">Personal Information</h3>
        <p class="text-xs text-black/40">Update your name, contact details and department.</p>
      </div>
    </div>

    <form @submit.prevent="save" class="px-6 py-5 space-y-4">

      <div class="grid grid-cols-2 gap-4">
        <div class="space-y-1.5">
          <label class="text-xs font-semibold text-black/55 uppercase tracking-wide">First name <span class="text-red-500">*</span></label>
          <input :value="form.firstName" @input="onNameInput('firstName', $event)" @blur="touched.firstName = true"
            type="text" placeholder="e.g. Shadrack"
            :class="['w-full h-9 rounded-md border bg-white px-3 text-sm placeholder:text-black/25 focus:outline-none focus:ring-2 transition', fieldCls('firstName', !form.firstName)]" />
          <p v-if="touched.firstName && !form.firstName" class="text-xs text-red-500">Required.</p>
        </div>
        <div class="space-y-1.5">
          <label class="text-xs font-semibold text-black/55 uppercase tracking-wide">Last name <span class="text-red-500">*</span></label>
          <input :value="form.lastName" @input="onNameInput('lastName', $event)" @blur="touched.lastName = true"
            type="text" placeholder="e.g. Castro"
            :class="['w-full h-9 rounded-md border bg-white px-3 text-sm placeholder:text-black/25 focus:outline-none focus:ring-2 transition', fieldCls('lastName', !form.lastName)]" />
          <p v-if="touched.lastName && !form.lastName" class="text-xs text-red-500">Required.</p>
        </div>
      </div>

      <div class="space-y-1.5">
        <label class="text-xs font-semibold text-black/55 uppercase tracking-wide">
          Middle name <span class="normal-case font-normal text-black/30 ml-1">(optional)</span>
        </label>
        <input :value="form.middleName" @input="onNameInput('middleName', $event)" type="text" placeholder="e.g. Miguel"
          class="w-full h-9 rounded-md border border-black/12 bg-white px-3 text-sm placeholder:text-black/25 focus:border-[#2E85D8] focus:outline-none focus:ring-2 focus:ring-[#2E85D8]/15 transition" />
      </div>

      <div class="space-y-1.5">
        <label class="text-xs font-semibold text-black/55 uppercase tracking-wide">Email <span class="text-red-500">*</span></label>
        <input v-model="form.email" @blur="touched.email = true" type="text" placeholder="you@sbsi.com"
          :class="['w-full h-9 rounded-md border bg-white px-3 text-sm placeholder:text-black/25 focus:outline-none focus:ring-2 transition', fieldCls('email', !form.email || !emailValid)]" />
        <p v-if="touched.email && !form.email" class="text-xs text-red-500">Required.</p>
        <p v-else-if="touched.email && !emailValid" class="text-xs text-red-500">Enter a valid email address.</p>
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div class="space-y-1.5">
          <label class="text-xs font-semibold text-black/55 uppercase tracking-wide">Phone <span class="text-red-500">*</span></label>
          <input v-model="form.phone" @blur="touched.phone = true" type="text" placeholder="+63 2 8xxx xxxx"
            :class="['w-full h-9 rounded-md border bg-white px-3 text-sm placeholder:text-black/25 focus:outline-none focus:ring-2 transition', fieldCls('phone', !form.phone || !phoneValid)]" />
          <p v-if="touched.phone && !form.phone" class="text-xs text-red-500">Required.</p>
          <p v-else-if="touched.phone && !phoneValid" class="text-xs text-red-500">Enter a valid phone number.</p>
        </div>
        <div class="space-y-1.5">
          <label class="text-xs font-semibold text-black/55 uppercase tracking-wide">Department</label>
          <Select v-model="form.department">
            <SelectTrigger class="h-9 rounded-md border-black/12 text-sm"><SelectValue /></SelectTrigger>
            <SelectContent>
              <SelectItem value="IT Department">IT Department</SelectItem>
              <SelectItem value="Sales Department">Sales Department</SelectItem>
              <SelectItem value="Operations">Operations</SelectItem>
              <SelectItem value="Finance">Finance</SelectItem>
            </SelectContent>
          </Select>
        </div>
      </div>

      <div class="space-y-1.5">
        <label class="text-xs font-semibold text-black/55 uppercase tracking-wide">Role</label>
        <input :value="form.role" disabled type="text"
          class="w-full h-9 rounded-md border border-black/8 bg-black/2.5 px-3 text-sm text-black/40 cursor-not-allowed" />
        <p class="text-[11px] text-black/30">Role is managed by system administrators.</p>
      </div>

      <div class="pt-1">
        <Button type="submit" class="h-9 px-5 text-sm bg-[#252578] hover:bg-[#2F2F73] text-white">
          Save changes
        </Button>
      </div>

    </form>
  </div>
</template>
