<script setup lang="ts">
import { reactive, watch, computed } from 'vue'
import { FilePenLine } from 'lucide-vue-next'
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import type { Contract, ContractWorkflowStatus, ContractApprovalStatus, ContractRegion } from '@/types/contract'

const props = defineProps<{ open: boolean; contract: Contract | null }>()
const emit  = defineEmits<{
  'update:open': [v: boolean]
  submit: [data: Omit<Contract, 'id' | 'createdBy'>]
}>()

const form = reactive({
  businessPartner: '',
  category:        '',
  itemCode:        '',
  description:     '',
  serialNo:        '',
  region:          '' as ContractRegion | '',
  startDate:       '',
  endDate:         '',
  approvalStatus:  '' as ContractApprovalStatus | '',
  workflowStatus:  null as ContractWorkflowStatus | null,
  contractLink:    '',
})

const touched = reactive({
  businessPartner: false, category: false, itemCode: false,
  description: false, serialNo: false, region: false,
  startDate: false, endDate: false, workflowStatus: false, contractLink: false,
})

const dateError = computed(() =>
  touched.startDate && touched.endDate && form.startDate && form.endDate
    ? form.endDate <= form.startDate ? 'End date must be after start date.' : ''
    : ''
)
const urlValid = computed(() =>
  !form.contractLink || /^https?:\/\/.+\..+/.test(form.contractLink)
)

watch(() => props.contract, c => {
  if (!c) return
  Object.assign(form, {
    businessPartner: c.businessPartner,
    category:        c.category,
    itemCode:        c.itemCode,
    description:     c.description,
    serialNo:        c.serialNo,
    region:          c.region,
    startDate:       c.startDate,
    endDate:         c.endDate,
    approvalStatus:  c.approvalStatus,
    workflowStatus:  c.workflowStatus,
    contractLink:    c.contractLink,
  })
  Object.keys(touched).forEach(k => ((touched as Record<string, boolean>)[k] = false))
})

function fieldCls(field: keyof typeof touched, invalid: boolean) {
  return touched[field] && invalid
    ? 'border-red-400 focus:border-red-400 focus:ring-red-400/15'
    : 'border-black/12 focus:border-[#2E85D8] focus:ring-[#2E85D8]/15'
}

function submit() {
  Object.keys(touched).forEach(k => ((touched as Record<string, boolean>)[k] = true))
  if (!form.businessPartner || !form.category || !form.itemCode || !form.description ||
      !form.serialNo || !form.region || !form.startDate || !form.endDate ||
      dateError.value || !urlValid.value) return
  emit('submit', {
    businessPartner: form.businessPartner,
    category:        form.category,
    itemCode:        form.itemCode,
    description:     form.description,
    serialNo:        form.serialNo,
    region:          form.region  as ContractRegion,
    startDate:       form.startDate,
    endDate:         form.endDate,
    approvalStatus:  form.approvalStatus as ContractApprovalStatus,
    workflowStatus:  form.workflowStatus,
    contractLink:    form.contractLink,
  })
  emit('update:open', false)
}
</script>

<template>
  <Dialog :open="open" @update:open="$emit('update:open', $event)">
    <DialogContent class="max-w-xl p-0 overflow-hidden" @pointer-down-outside="$emit('update:open', false)">

      <!-- Header -->
      <div class="px-6 pt-6 pb-5 border-b border-black/6">
        <div class="flex items-center gap-3">
          <div class="w-9 h-9 rounded-lg bg-[#2E85D8]/8 flex items-center justify-center shrink-0">
            <FilePenLine class="w-4 h-4 text-[#2E85D8]" />
          </div>
          <DialogHeader>
            <DialogTitle>Edit Contract</DialogTitle>
            <DialogDescription>
              Editing <span class="font-semibold text-black/70">{{ contract?.id }}</span> — {{ contract?.businessPartner }}
            </DialogDescription>
          </DialogHeader>
        </div>
      </div>

      <!-- Form -->
      <form @submit.prevent="submit" class="px-6 py-5 space-y-4 max-h-[70vh] overflow-y-auto">

        <!-- Business Partner -->
        <div class="space-y-1.5">
          <label class="text-xs font-semibold text-black/55 uppercase tracking-wide">
            Business Partner <span class="text-red-500">*</span>
          </label>
          <input v-model="form.businessPartner" @blur="touched.businessPartner = true"
            type="text" placeholder="e.g. Philippine National Bank" maxlength="100"
            :class="['w-full h-9 rounded-md border bg-white px-3 text-sm placeholder:text-black/25 focus:outline-none focus:ring-2 transition',
              fieldCls('businessPartner', !form.businessPartner)]" />
          <p v-if="touched.businessPartner && !form.businessPartner" class="text-xs text-red-500">Required.</p>
        </div>

        <!-- Category + Region -->
        <div class="grid grid-cols-2 gap-4">
          <div class="space-y-1.5">
            <label class="text-xs font-semibold text-black/55 uppercase tracking-wide">
              Category <span class="text-red-500">*</span>
            </label>
            <Select v-model="form.category" @update:model-value="touched.category = true">
              <SelectTrigger class="h-9 rounded-md text-sm"
                :class="touched.category && !form.category ? 'border-red-400' : 'border-black/12 focus:border-[#2E85D8]'">
                <SelectValue placeholder="Select category" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="Service Agreement">Service Agreement</SelectItem>
                <SelectItem value="Supply Contract">Supply Contract</SelectItem>
                <SelectItem value="Equipment Maintenance">Equipment Maintenance</SelectItem>
                <SelectItem value="Equipment Lease">Equipment Lease</SelectItem>
                <SelectItem value="Partnership Agreement">Partnership Agreement</SelectItem>
              </SelectContent>
            </Select>
            <p v-if="touched.category && !form.category" class="text-xs text-red-500">Required.</p>
          </div>
          <div class="space-y-1.5">
            <label class="text-xs font-semibold text-black/55 uppercase tracking-wide">
              Region <span class="text-red-500">*</span>
            </label>
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

        <!-- Item Code + Serial No -->
        <div class="grid grid-cols-2 gap-4">
          <div class="space-y-1.5">
            <label class="text-xs font-semibold text-black/55 uppercase tracking-wide">
              Item Code <span class="text-red-500">*</span>
            </label>
            <input v-model="form.itemCode" @blur="touched.itemCode = true"
              type="text" placeholder="e.g. ITM-0041" maxlength="20"
              :class="['w-full h-9 rounded-md border bg-white px-3 text-sm font-mono placeholder:text-black/25 focus:outline-none focus:ring-2 transition',
                fieldCls('itemCode', !form.itemCode)]" />
            <p v-if="touched.itemCode && !form.itemCode" class="text-xs text-red-500">Required.</p>
          </div>
          <div class="space-y-1.5">
            <label class="text-xs font-semibold text-black/55 uppercase tracking-wide">
              Serial No <span class="text-red-500">*</span>
            </label>
            <input v-model="form.serialNo" @blur="touched.serialNo = true"
              type="text" placeholder="e.g. SN-2024-0041" maxlength="30"
              :class="['w-full h-9 rounded-md border bg-white px-3 text-sm font-mono placeholder:text-black/25 focus:outline-none focus:ring-2 transition',
                fieldCls('serialNo', !form.serialNo)]" />
            <p v-if="touched.serialNo && !form.serialNo" class="text-xs text-red-500">Required.</p>
          </div>
        </div>

        <!-- Description -->
        <div class="space-y-1.5">
          <label class="text-xs font-semibold text-black/55 uppercase tracking-wide">
            Description / Machine <span class="text-red-500">*</span>
          </label>
          <input v-model="form.description" @blur="touched.description = true"
            type="text" placeholder="e.g. ATM Maintenance Unit" maxlength="200"
            :class="['w-full h-9 rounded-md border bg-white px-3 text-sm placeholder:text-black/25 focus:outline-none focus:ring-2 transition',
              fieldCls('description', !form.description)]" />
          <p v-if="touched.description && !form.description" class="text-xs text-red-500">Required.</p>
        </div>

        <!-- Start Date + End Date -->
        <div class="grid grid-cols-2 gap-4">
          <div class="space-y-1.5">
            <label class="text-xs font-semibold text-black/55 uppercase tracking-wide">
              Start Date <span class="text-red-500">*</span>
            </label>
            <input v-model="form.startDate" @blur="touched.startDate = true"
              type="date"
              :class="['w-full h-9 rounded-md border bg-white px-3 text-sm text-black focus:outline-none focus:ring-2 transition',
                fieldCls('startDate', !form.startDate)]" />
            <p v-if="touched.startDate && !form.startDate" class="text-xs text-red-500">Required.</p>
          </div>
          <div class="space-y-1.5">
            <label class="text-xs font-semibold text-black/55 uppercase tracking-wide">
              End Date <span class="text-red-500">*</span>
            </label>
            <input v-model="form.endDate" @blur="touched.endDate = true"
              type="date"
              :class="['w-full h-9 rounded-md border bg-white px-3 text-sm text-black focus:outline-none focus:ring-2 transition',
                fieldCls('endDate', !form.endDate || !!dateError)]" />
            <p v-if="touched.endDate && !form.endDate" class="text-xs text-red-500">Required.</p>
            <p v-else-if="dateError" class="text-xs text-red-500">{{ dateError }}</p>
          </div>
        </div>

        <!-- Workflow Status -->
        <div class="space-y-1.5">
          <label class="text-xs font-semibold text-black/55 uppercase tracking-wide">
            Workflow Status
            <span class="normal-case font-normal text-black/30 ml-1">(optional)</span>
          </label>
          <Select v-model="form.workflowStatus" @update:model-value="touched.workflowStatus = true">
            <SelectTrigger class="h-9 rounded-md text-sm border-black/12 focus:border-[#2E85D8]">
              <SelectValue placeholder="No workflow status yet" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem :value="null">— None (Pending) —</SelectItem>
              <SelectItem value="Notarized PDF">Notarized PDF</SelectItem>
              <SelectItem value="Client Review">Client Review</SelectItem>
              <SelectItem value="SBSI Review">SBSI Review</SelectItem>
            </SelectContent>
          </Select>
        </div>

        <!-- Contract Link -->
        <div class="space-y-1.5">
          <label class="text-xs font-semibold text-black/55 uppercase tracking-wide">
            Contract Link
            <span class="normal-case font-normal text-black/30 ml-1">(optional)</span>
          </label>
          <input v-model="form.contractLink" @blur="touched.contractLink = true"
            type="url" placeholder="https://drive.google.com/..." maxlength="500"
            :class="['w-full h-9 rounded-md border bg-white px-3 text-sm placeholder:text-black/25 focus:outline-none focus:ring-2 transition',
              touched.contractLink && !urlValid ? 'border-red-400 focus:border-red-400 focus:ring-red-400/15' : 'border-black/12 focus:border-[#2E85D8] focus:ring-[#2E85D8]/15']" />
          <p v-if="touched.contractLink && !urlValid" class="text-xs text-red-500">Enter a valid URL (must start with http:// or https://).</p>
        </div>

        <!-- Footer -->
        <div class="border-t border-black/6 pt-4">
          <DialogFooter>
            <Button type="button" variant="outline" @click="$emit('update:open', false)"
              class="h-9 px-4 text-sm border-black/15 text-black/60 hover:text-black">Cancel</Button>
            <Button type="submit" class="h-9 px-5 text-sm bg-[#252578] hover:bg-[#2F2F73] text-white">
              Save changes
            </Button>
          </DialogFooter>
        </div>

      </form>
    </DialogContent>
  </Dialog>
</template>
