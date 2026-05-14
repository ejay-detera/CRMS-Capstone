<script setup lang="ts">
import { reactive, computed, watch } from 'vue'
import { Building2, Truck } from 'lucide-vue-next'
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import type { Partner, TabKey, AddPartnerForm } from '@/types/partner'

const props = defineProps<{ open: boolean; activeTab: TabKey }>()
const emit  = defineEmits<{ 'update:open': [v: boolean]; submit: [p: Omit<Partner, 'contracts' | 'totalValue'>] }>()

const form = reactive<AddPartnerForm>({
  name: '', industry: '', region: '', status: 'Active',
  contactPerson: '', email: '', phone: '', address: '',
})
const touched = reactive({
  name: false, industry: false, region: false,
  contactPerson: false, email: false, phone: false, address: false,
})

const emailValid = computed(() =>
  !form.email || /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)
)
const phoneValid = computed(() =>
  !form.phone || /^[+]?[\d\s\-(). ]{7,}$/.test(form.phone)
)

function onNameInput(field: 'contactPerson', e: Event) {
  const el = e.target as HTMLInputElement
  const clean = el.value.replace(/[^a-zA-Z\s\-'.]/g, '')
  form[field] = clean
  el.value = clean
  touched[field] = true
}

function reset() {
  Object.assign(form, { name: '', industry: '', region: '', status: 'Active', contactPerson: '', email: '', phone: '', address: '' })
  Object.assign(touched, { name: false, industry: false, region: false, contactPerson: false, email: false, phone: false, address: false })
}

watch(() => props.open, open => { if (!open) reset() })

function submit() {
  Object.keys(touched).forEach(k => ((touched as Record<string, boolean>)[k] = true))
  if (!form.name || !form.industry || !form.region || !form.contactPerson || !form.email || !emailValid.value || !form.phone || !phoneValid.value || !form.address) return
  emit('submit', { id: '', name: form.name, industry: form.industry, region: form.region as any, status: form.status, contactPerson: form.contactPerson, email: form.email, phone: form.phone, address: form.address })
  emit('update:open', false)
}

function err(field: keyof typeof touched, extra = true) {
  return touched[field] && extra ? 'border-red-400 focus:border-red-400 focus:ring-red-400/15' : 'border-black/12 focus:border-[#2E85D8] focus:ring-[#2E85D8]/15'
}
</script>

<template>
  <Dialog :open="open" @update:open="$emit('update:open', $event)">
    <DialogContent class="max-w-lg p-0 overflow-hidden" @pointer-down-outside="$emit('update:open', false)">

      <!-- Header -->
      <div class="px-6 pt-6 pb-5 border-b border-black/6">
        <div class="flex items-center gap-3">
          <div class="w-9 h-9 rounded-lg bg-[#252578]/8 flex items-center justify-center shrink-0">
            <component :is="activeTab === 'partners' ? Building2 : Truck" class="w-4.5 h-4.5 text-[#252578]" />
          </div>
          <DialogHeader>
            <DialogTitle class="text-base font-semibold text-black">
              Add {{ activeTab === 'partners' ? 'Business Partner' : 'Supplier' }}
            </DialogTitle>
            <DialogDescription class="text-xs text-black/40 mt-0.5">
              Fill in the details below to add a new {{ activeTab === 'partners' ? 'partner' : 'supplier' }}.
            </DialogDescription>
          </DialogHeader>
        </div>
      </div>

      <!-- Form -->
      <form @submit.prevent="submit" class="px-6 py-5 space-y-4">

        <!-- Name -->
        <div class="space-y-1.5">
          <label class="text-xs font-semibold text-black/55 uppercase tracking-wide">Name <span class="text-red-500">*</span></label>
          <input v-model="form.name" @blur="touched.name = true" type="text" placeholder="Organisation name"
            :class="['w-full h-9 rounded-md border bg-white px-3 text-sm placeholder:text-black/25 focus:outline-none focus:ring-2 transition', err('name', !form.name)]" />
          <p v-if="touched.name && !form.name" class="text-xs text-red-500">Name is required.</p>
        </div>

        <!-- Industry + Region -->
        <div class="grid grid-cols-2 gap-4">
          <div class="space-y-1.5">
            <label class="text-xs font-semibold text-black/55 uppercase tracking-wide">Industry <span class="text-red-500">*</span></label>
            <input v-model="form.industry" @blur="touched.industry = true" type="text" placeholder="e.g. Healthcare"
              :class="['w-full h-9 rounded-md border bg-white px-3 text-sm placeholder:text-black/25 focus:outline-none focus:ring-2 transition', err('industry', !form.industry)]" />
            <p v-if="touched.industry && !form.industry" class="text-xs text-red-500">Required.</p>
          </div>
          <div class="space-y-1.5">
            <label class="text-xs font-semibold text-black/55 uppercase tracking-wide">Region <span class="text-red-500">*</span></label>
            <Select v-model="form.region" @update:model-value="touched.region = true">
              <SelectTrigger class="h-9 rounded-md text-sm"
                :class="touched.region && !form.region ? 'border-red-400' : 'border-black/12 focus:border-[#2E85D8]'">
                <SelectValue placeholder="Select region" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="Luzon">Luzon</SelectItem>
                <SelectItem value="Visayas">Visayas</SelectItem>
                <SelectItem value="Mindanao">Mindanao</SelectItem>
              </SelectContent>
            </Select>
            <p v-if="touched.region && !form.region" class="text-xs text-red-500">Required.</p>
          </div>
        </div>

        <!-- Contact Person + Phone -->
        <div class="grid grid-cols-2 gap-4">
          <div class="space-y-1.5">
            <label class="text-xs font-semibold text-black/55 uppercase tracking-wide">Contact Person <span class="text-red-500">*</span></label>
            <input :value="form.contactPerson" @input="onNameInput('contactPerson', $event)" @blur="touched.contactPerson = true"
              type="text" placeholder="Full name"
              :class="['w-full h-9 rounded-md border bg-white px-3 text-sm placeholder:text-black/25 focus:outline-none focus:ring-2 transition', err('contactPerson', !form.contactPerson)]" />
            <p v-if="touched.contactPerson && !form.contactPerson" class="text-xs text-red-500">Required.</p>
          </div>
          <div class="space-y-1.5">
            <label class="text-xs font-semibold text-black/55 uppercase tracking-wide">Phone <span class="text-red-500">*</span></label>
            <input v-model="form.phone" @blur="touched.phone = true" type="text" placeholder="+63 2 8xxx xxxx"
              :class="['w-full h-9 rounded-md border bg-white px-3 text-sm placeholder:text-black/25 focus:outline-none focus:ring-2 transition', err('phone', !form.phone || !phoneValid)]" />
            <p v-if="touched.phone && !form.phone" class="text-xs text-red-500">Required.</p>
            <p v-else-if="touched.phone && !phoneValid" class="text-xs text-red-500">Enter a valid phone number.</p>
          </div>
        </div>

        <!-- Email -->
        <div class="space-y-1.5">
          <label class="text-xs font-semibold text-black/55 uppercase tracking-wide">Email <span class="text-red-500">*</span></label>
          <input v-model="form.email" @blur="touched.email = true" type="email" placeholder="contact@company.com"
            :class="['w-full h-9 rounded-md border bg-white px-3 text-sm placeholder:text-black/25 focus:outline-none focus:ring-2 transition', err('email', !form.email || !emailValid)]" />
          <p v-if="touched.email && (!form.email || !emailValid)" class="text-xs text-red-500">Valid email required.</p>
        </div>

        <!-- Address -->
        <div class="space-y-1.5">
          <label class="text-xs font-semibold text-black/55 uppercase tracking-wide">Address <span class="text-red-500">*</span></label>
          <input v-model="form.address" @blur="touched.address = true" type="text" placeholder="Street, City"
            :class="['w-full h-9 rounded-md border bg-white px-3 text-sm placeholder:text-black/25 focus:outline-none focus:ring-2 transition', err('address', !form.address)]" />
          <p v-if="touched.address && !form.address" class="text-xs text-red-500">Required.</p>
        </div>

        <!-- Status -->
        <div class="space-y-1.5">
          <label class="text-xs font-semibold text-black/55 uppercase tracking-wide">Status</label>
          <Select v-model="form.status">
            <SelectTrigger class="h-9 rounded-md border-black/12 text-sm"><SelectValue /></SelectTrigger>
            <SelectContent>
              <SelectItem value="Active">Active</SelectItem>
              <SelectItem value="Inactive">Inactive</SelectItem>
            </SelectContent>
          </Select>
        </div>

        <!-- Footer -->
        <div class="border-t border-black/6 pt-4">
          <DialogFooter>
            <Button type="button" variant="outline" class="h-9 px-4 text-sm border-black/15 text-black/60 hover:text-black"
              @click="$emit('update:open', false)">Cancel</Button>
            <Button type="submit" class="h-9 px-5 text-sm bg-[#252578] hover:bg-[#2F2F73] text-white">
              Add {{ activeTab === 'partners' ? 'Partner' : 'Supplier' }}
            </Button>
          </DialogFooter>
        </div>

      </form>
    </DialogContent>
  </Dialog>
</template>
