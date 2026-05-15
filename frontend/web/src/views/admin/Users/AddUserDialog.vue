<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import { UserPlus, Eye, EyeOff } from 'lucide-vue-next'
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import type { Role } from '@/types/user'

const props = defineProps<{ open: boolean }>()
const emit  = defineEmits<{ 'update:open': [v: boolean]; submit: [data: { name: string; email: string; role: Role }] }>()

const form = reactive({
  firstName: '', lastName: '', middleName: '',
  email: '', password: '', confirmPassword: '',
  role: '' as Role | '', department: '',
})
const touched = reactive({
  firstName: false, lastName: false,
  email: false, password: false, confirmPassword: false,
})
const showPassword        = ref(false)
const showConfirmPassword = ref(false)

const emailValid = computed(() => !form.email || /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email))
const pwRules    = computed(() => ({
  minLength:  form.password.length >= 8,
  hasUpper:   /[A-Z]/.test(form.password),
  hasNumber:  /\d/.test(form.password),
  hasSpecial: /[!@#$%^&*()\-_=+\[\]{};:'",.<>?/\\|`~]/.test(form.password),
}))
const passwordValid    = computed(() => Object.values(pwRules.value).every(Boolean))
const passwordMismatch = computed(() => form.confirmPassword.length > 0 && form.password !== form.confirmPassword)

function onNameInput(field: 'firstName' | 'lastName' | 'middleName', e: Event) {
  const el = e.target as HTMLInputElement
  const clean = el.value.replace(/[^a-zA-Z\s\-']/g, '')
  form[field] = clean
  el.value = clean
  if (field !== 'middleName') touched[field] = true
}

function reset() {
  Object.assign(form, { firstName: '', lastName: '', middleName: '', email: '', password: '', confirmPassword: '', role: '', department: '' })
  Object.assign(touched, { firstName: false, lastName: false, email: false, password: false, confirmPassword: false })
  showPassword.value = false
  showConfirmPassword.value = false
}

watch(() => props.open, open => { if (!open) reset() })

function submit() {
  Object.assign(touched, { firstName: true, lastName: true, email: true, password: true, confirmPassword: true })
  if (!form.firstName || !form.lastName || !form.email || !emailValid.value ||
      !passwordValid.value || passwordMismatch.value || !form.role || !form.department) return
  const fullName = [form.firstName, form.middleName, form.lastName].filter(Boolean).join(' ')
  emit('submit', { name: fullName, email: form.email, role: form.role as Role })
  emit('update:open', false)
}
</script>

<template>
  <Dialog :open="open" @update:open="$emit('update:open', $event)">
    <DialogContent class="max-w-xl" @pointer-down-outside="$emit('update:open', false)">

      <div class="px-6 pt-6 pb-5 border-b border-black/6">
        <div class="flex items-center gap-3 mb-1">
          <div class="w-9 h-9 rounded-lg bg-[#252578]/8 flex items-center justify-center shrink-0">
            <UserPlus class="w-4.5 h-4.5 text-[#252578]" />
          </div>
          <DialogHeader>
            <DialogTitle>Add new user</DialogTitle>
            <DialogDescription>Fill in the details below to create a new team member account.</DialogDescription>
          </DialogHeader>
        </div>
      </div>

      <form @submit.prevent="submit" class="px-6 py-5 space-y-4">

        <div class="grid grid-cols-2 gap-4">
          <div class="space-y-1.5">
            <label class="text-xs font-semibold text-black/55 uppercase tracking-wide">First name <span class="text-red-500">*</span></label>
            <input :value="form.firstName" @input="onNameInput('firstName', $event)" @blur="touched.firstName = true"
              type="text" placeholder="e.g. Sarah"
              :class="['w-full h-9 rounded-md border bg-white px-3 text-sm placeholder:text-black/25 focus:outline-none focus:ring-2 transition',
                touched.firstName && !form.firstName ? 'border-red-400 focus:border-red-400 focus:ring-red-400/15' : 'border-black/12 focus:border-[#2E85D8] focus:ring-[#2E85D8]/15']" />
            <p v-if="touched.firstName && !form.firstName" class="text-xs text-red-500">Required.</p>
          </div>
          <div class="space-y-1.5">
            <label class="text-xs font-semibold text-black/55 uppercase tracking-wide">Last name <span class="text-red-500">*</span></label>
            <input :value="form.lastName" @input="onNameInput('lastName', $event)" @blur="touched.lastName = true"
              type="text" placeholder="e.g. Jenkins"
              :class="['w-full h-9 rounded-md border bg-white px-3 text-sm placeholder:text-black/25 focus:outline-none focus:ring-2 transition',
                touched.lastName && !form.lastName ? 'border-red-400 focus:border-red-400 focus:ring-red-400/15' : 'border-black/12 focus:border-[#2E85D8] focus:ring-[#2E85D8]/15']" />
            <p v-if="touched.lastName && !form.lastName" class="text-xs text-red-500">Required.</p>
          </div>
        </div>

        <div class="space-y-1.5">
          <label class="text-xs font-semibold text-black/55 uppercase tracking-wide">
            Middle name <span class="normal-case font-normal text-black/30 ml-1">(optional)</span>
          </label>
          <input :value="form.middleName" @input="onNameInput('middleName', $event)"
            type="text" placeholder="e.g. Anne"
            class="w-full h-9 rounded-md border border-black/12 bg-white px-3 text-sm placeholder:text-black/25 focus:border-[#2E85D8] focus:outline-none focus:ring-2 focus:ring-[#2E85D8]/15 transition" />
          <p class="text-[11px] text-black/30">Letters only — no numbers or special characters.</p>
        </div>

        <div class="space-y-1.5">
          <label class="text-xs font-semibold text-black/55 uppercase tracking-wide">Email address <span class="text-red-500">*</span></label>
          <input v-model="form.email" @blur="touched.email = true" type="text" placeholder="e.g. sarah.j@sbsi.com"
            :class="['w-full h-9 rounded-md border bg-white px-3 text-sm placeholder:text-black/25 focus:outline-none focus:ring-2 transition',
              touched.email && (!form.email || !emailValid) ? 'border-red-400 focus:border-red-400 focus:ring-red-400/15' : 'border-black/12 focus:border-[#2E85D8] focus:ring-[#2E85D8]/15']" />
          <p v-if="touched.email && !form.email" class="text-xs text-red-500">Required.</p>
          <p v-else-if="touched.email && !emailValid" class="text-xs text-red-500">Enter a valid email address.</p>
        </div>

        <div class="space-y-1.5">
          <label class="text-xs font-semibold text-black/55 uppercase tracking-wide">Password <span class="text-red-500">*</span></label>
          <div class="relative">
            <input v-model="form.password" @blur="touched.password = true"
              :type="showPassword ? 'text' : 'password'" placeholder="Min. 8 characters"
              :class="['w-full h-9 rounded-md border bg-white pl-3 pr-10 text-sm placeholder:text-black/25 focus:outline-none focus:ring-2 transition',
                touched.password && !passwordValid ? 'border-red-400 focus:border-red-400 focus:ring-red-400/15' : 'border-black/12 focus:border-[#2E85D8] focus:ring-[#2E85D8]/15']" />
            <button type="button" @click="showPassword = !showPassword" tabindex="-1"
              class="absolute right-3 top-1/2 -translate-y-1/2 text-black/30 hover:text-black/60 transition-colors">
              <EyeOff v-if="showPassword" class="w-4 h-4" /><Eye v-else class="w-4 h-4" />
            </button>
          </div>
          <div v-if="touched.password || form.password.length > 0" class="flex flex-wrap gap-x-4 gap-y-1 pt-1">
            <span class="flex items-center gap-1 text-[11px]" :class="pwRules.minLength ? 'text-emerald-600' : 'text-black/35'">
              <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="pwRules.minLength ? 'bg-emerald-500' : 'bg-black/20'" /> 8+ characters
            </span>
            <span class="flex items-center gap-1 text-[11px]" :class="pwRules.hasUpper ? 'text-emerald-600' : 'text-black/35'">
              <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="pwRules.hasUpper ? 'bg-emerald-500' : 'bg-black/20'" /> One uppercase
            </span>
            <span class="flex items-center gap-1 text-[11px]" :class="pwRules.hasNumber ? 'text-emerald-600' : 'text-black/35'">
              <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="pwRules.hasNumber ? 'bg-emerald-500' : 'bg-black/20'" /> One number
            </span>
            <span class="flex items-center gap-1 text-[11px]" :class="pwRules.hasSpecial ? 'text-emerald-600' : 'text-black/35'">
              <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="pwRules.hasSpecial ? 'bg-emerald-500' : 'bg-black/20'" /> One special character
            </span>
          </div>
        </div>

        <div class="space-y-1.5">
          <label class="text-xs font-semibold text-black/55 uppercase tracking-wide">Confirm password <span class="text-red-500">*</span></label>
          <div class="relative">
            <input v-model="form.confirmPassword" @blur="touched.confirmPassword = true"
              :type="showConfirmPassword ? 'text' : 'password'" placeholder="Repeat password"
              :class="['w-full h-9 rounded-md border bg-white pl-3 pr-10 text-sm placeholder:text-black/25 focus:outline-none focus:ring-2 transition',
                passwordMismatch ? 'border-red-400 focus:border-red-400 focus:ring-red-400/15' : 'border-black/12 focus:border-[#2E85D8] focus:ring-[#2E85D8]/15']" />
            <button type="button" @click="showConfirmPassword = !showConfirmPassword" tabindex="-1"
              class="absolute right-3 top-1/2 -translate-y-1/2 text-black/30 hover:text-black/60 transition-colors">
              <EyeOff v-if="showConfirmPassword" class="w-4 h-4" /><Eye v-else class="w-4 h-4" />
            </button>
          </div>
          <p v-if="passwordMismatch" class="text-xs text-red-500">Passwords do not match.</p>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div class="space-y-1.5">
            <label class="text-xs font-semibold text-black/55 uppercase tracking-wide">Role <span class="text-red-500">*</span></label>
            <Select v-model="form.role">
              <SelectTrigger class="h-9 rounded-md border-black/12 text-sm focus:ring-[#2E85D8]/15 focus:border-[#2E85D8]">
                <SelectValue placeholder="Select role" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="Admin">Admin</SelectItem>
                <SelectItem value="Manager">Manager</SelectItem>
                <SelectItem value="Sales">Sales</SelectItem>
              </SelectContent>
            </Select>
          </div>
          <div class="space-y-1.5">
            <label class="text-xs font-semibold text-black/55 uppercase tracking-wide">Department <span class="text-red-500">*</span></label>
            <Select v-model="form.department">
              <SelectTrigger class="h-9 rounded-md border-black/12 text-sm focus:ring-[#2E85D8]/15 focus:border-[#2E85D8]">
                <SelectValue placeholder="Select department" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="Sales Department">Sales Department</SelectItem>
              </SelectContent>
            </Select>
          </div>
        </div>

        <div class="border-t border-black/6 pt-4">
          <DialogFooter>
            <Button type="button" variant="outline" @click="$emit('update:open', false)"
              class="h-9 px-4 text-sm border-black/15 text-black/60 hover:text-black">Cancel</Button>
            <Button type="submit" class="h-9 px-5 text-sm bg-[#252578] hover:bg-[#2F2F73] text-white">Add user</Button>
          </DialogFooter>
        </div>

      </form>
    </DialogContent>
  </Dialog>
</template>
