<script setup lang="ts">
import { fmtDate } from '@/types/contract'
import type { ContractRegion, ContractWorkflowStatus } from '@/types/contract'
import type { StoredContract } from '@/composables/useContractStore'

const props = defineProps<{
  contract:   StoredContract
  isEditing:  boolean
  isManager:  boolean
  isOwner:    boolean
  isApproved: boolean
  editForm:   {
    businessPartner: string
    category:        string
    itemCode:        string
    description:     string
    serialNo:        string
    sbuNumber:       string
    region:          ContractRegion | ''
    startDate:       string
    endDate:         string
    workflowStatus:  ContractWorkflowStatus | ''
  }
  touched:   Record<string, boolean>
  dateError: string
}>()

const palette = ['#252578', '#2E85D8', '#2F2F73']

function initials(name: string) {
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}

function avatarColor(name: string) {
  let h = 0
  for (const c of name) h = (h * 31 + c.charCodeAt(0)) & 0xffff
  return palette[h % palette.length]
}

function fieldCls(field: string, invalid: boolean) {
  return props.touched[field] && invalid
    ? 'border-red-400 focus:border-red-400 focus:ring-red-200/50'
    : 'border-black/12 focus:border-[#2E85D8] focus:ring-[#2E85D8]/15'
}

const categories = [
  'Service Agreement',
  'Partnership Agreement',
  'Supply Contract',
  'Equipment Lease',
  'Equipment Maintenance',
]
</script>

<template>
  <div class="bg-white rounded-lg border border-black/8 shadow-sm overflow-hidden">

    <!-- Section: Contract Info -->
    <div class="px-6 py-5 border-b border-black/6">
      <h2 class="text-xs font-semibold text-black/40 uppercase tracking-widest mb-4">Contract Info</h2>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

        <!-- Business Partner -->
        <div class="flex flex-col gap-1.5">
          <label class="text-xs font-semibold text-black/55">
            Business Partner <span v-if="isEditing" class="text-red-500">*</span>
          </label>
          <p v-if="!isEditing" class="text-sm text-black py-1">{{ contract.businessPartner }}</p>
          <template v-else>
            <input
              v-model="editForm.businessPartner"
              @blur="touched.businessPartner = true"
              type="text"
              placeholder="e.g. Globe Telecom"
              class="h-9 rounded-lg border px-3 text-sm placeholder:text-black/25 focus:outline-none focus:ring-2 transition"
              :class="fieldCls('businessPartner', !editForm.businessPartner)"
            />
            <p v-if="touched.businessPartner && !editForm.businessPartner" class="text-xs text-red-500">Business partner is required.</p>
          </template>
        </div>

        <!-- Category -->
        <div class="flex flex-col gap-1.5">
          <label class="text-xs font-semibold text-black/55">
            Category <span v-if="isEditing" class="text-red-500">*</span>
          </label>
          <p v-if="!isEditing" class="text-sm text-black py-1">{{ contract.category }}</p>
          <template v-else>
            <select
              v-model="editForm.category"
              @blur="touched.category = true"
              class="h-9 rounded-lg border px-3 text-sm bg-white focus:outline-none focus:ring-2 transition"
              :class="[
                !editForm.category ? 'text-black/30' : 'text-black',
                fieldCls('category', !editForm.category)
              ]">
              <option value="" disabled>Select category</option>
              <option v-for="c in categories" :key="c" :value="c">{{ c }}</option>
            </select>
            <p v-if="touched.category && !editForm.category" class="text-xs text-red-500">Category is required.</p>
          </template>
        </div>

        <!-- Workflow Status (manager or contract owner) -->
        <div v-if="isEditing && (isManager || isOwner)" class="flex flex-col gap-1.5">
          <label class="text-xs font-semibold text-black/55">Workflow Status</label>
          <select
            v-model="editForm.workflowStatus"
            class="h-9 rounded-lg border px-3 text-sm bg-white focus:outline-none focus:ring-2 transition border-black/12 focus:border-[#2E85D8] focus:ring-[#2E85D8]/15"
            :class="!editForm.workflowStatus ? 'text-black/30' : 'text-black'">
            <option value="">No status</option>
            <option value="SBSI Review">SBSI Review</option>
            <option value="Client Review">Client Review</option>
            <option value="Notarized PDF">Notarized PDF</option>
          </select>
        </div>

      </div>
    </div>

    <!-- Section: Item Details -->
    <div class="px-6 py-5 border-b border-black/6">
      <h2 class="text-xs font-semibold text-black/40 uppercase tracking-widest mb-4">Item Details</h2>
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">

        <!-- Item Code -->
        <div class="flex flex-col gap-1.5">
          <label class="text-xs font-semibold text-black/55">
            Item Code <span v-if="isEditing" class="text-red-500">*</span>
          </label>
          <p v-if="!isEditing" class="text-sm font-mono text-black py-1">{{ contract.itemCode }}</p>
          <template v-else>
            <input
              v-model="editForm.itemCode"
              @blur="touched.itemCode = true"
              type="text"
              placeholder="e.g. ITM-0041"
              class="h-9 rounded-lg border px-3 text-sm font-mono placeholder:text-black/25 focus:outline-none focus:ring-2 transition"
              :class="fieldCls('itemCode', !editForm.itemCode)"
            />
            <p v-if="touched.itemCode && !editForm.itemCode" class="text-xs text-red-500">Item code is required.</p>
          </template>
        </div>

        <!-- Description -->
        <div class="flex flex-col gap-1.5">
          <label class="text-xs font-semibold text-black/55">
            Description <span v-if="isEditing" class="text-red-500">*</span>
          </label>
          <p v-if="!isEditing" class="text-sm text-black py-1">{{ contract.description }}</p>
          <template v-else>
            <input
              v-model="editForm.description"
              @blur="touched.description = true"
              type="text"
              placeholder="e.g. Network Infrastructure"
              class="h-9 rounded-lg border px-3 text-sm placeholder:text-black/25 focus:outline-none focus:ring-2 transition"
              :class="fieldCls('description', !editForm.description)"
            />
            <p v-if="touched.description && !editForm.description" class="text-xs text-red-500">Description is required.</p>
          </template>
        </div>

        <!-- Serial No -->
        <div class="flex flex-col gap-1.5">
          <label class="text-xs font-semibold text-black/55">
            Serial No <span v-if="isEditing" class="text-red-500">*</span>
          </label>
          <p v-if="!isEditing" class="text-sm font-mono text-black py-1">{{ contract.serialNo }}</p>
          <template v-else>
            <input
              v-model="editForm.serialNo"
              @blur="touched.serialNo = true"
              type="text"
              placeholder="e.g. SN-2024-0041"
              class="h-9 rounded-lg border px-3 text-sm font-mono placeholder:text-black/25 focus:outline-none focus:ring-2 transition"
              :class="fieldCls('serialNo', !editForm.serialNo)"
            />
            <p v-if="touched.serialNo && !editForm.serialNo" class="text-xs text-red-500">Serial number is required.</p>
          </template>
        </div>

        <!-- SBU Number -->
        <div class="flex flex-col gap-1.5">
          <label class="text-xs font-semibold text-black/55">
            SBU Number <span v-if="isEditing" class="text-red-500">*</span>
          </label>
          <p v-if="!isEditing" class="text-sm font-mono text-black py-1">{{ contract.sbuNumber || '—' }}</p>
          <template v-else>
            <input
              v-model="editForm.sbuNumber"
              @blur="touched.sbuNumber = true"
              type="text"
              placeholder="e.g. SBU-001"
              class="h-9 rounded-lg border px-3 text-sm font-mono placeholder:text-black/25 focus:outline-none focus:ring-2 transition"
              :class="fieldCls('sbuNumber', !editForm.sbuNumber)"
            />
            <p v-if="touched.sbuNumber && !editForm.sbuNumber" class="text-xs text-red-500">SBU number is required.</p>
          </template>
        </div>

      </div>
    </div>

    <!-- Section: Schedule & Location -->
    <div class="px-6 py-5 border-b border-black/6">
      <h2 class="text-xs font-semibold text-black/40 uppercase tracking-widest mb-4">Schedule & Location</h2>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

        <!-- Region -->
        <div class="flex flex-col gap-1.5">
          <label class="text-xs font-semibold text-black/55">
            Region <span v-if="isEditing" class="text-red-500">*</span>
          </label>
          <p v-if="!isEditing" class="text-sm text-black py-1">{{ contract.region }}</p>
          <template v-else>
            <select
              v-model="editForm.region"
              @blur="touched.region = true"
              class="h-9 rounded-lg border px-3 text-sm bg-white focus:outline-none focus:ring-2 transition"
              :class="[
                !editForm.region ? 'text-black/30' : 'text-black',
                fieldCls('region', !editForm.region)
              ]">
              <option value="" disabled>Select region</option>
              <option value="Luzon">Luzon</option>
              <option value="Visayas">Visayas</option>
              <option value="Mindanao">Mindanao</option>
            </select>
            <p v-if="touched.region && !editForm.region" class="text-xs text-red-500">Region is required.</p>
          </template>
        </div>

        <!-- Start Date -->
        <div class="flex flex-col gap-1.5">
          <label class="text-xs font-semibold text-black/55">
            Start Date <span v-if="isEditing" class="text-red-500">*</span>
          </label>
          <p v-if="!isEditing" class="text-sm text-black py-1 tabular-nums">{{ fmtDate(contract.startDate) }}</p>
          <template v-else>
            <input
              v-model="editForm.startDate"
              @blur="touched.startDate = true"
              type="date"
              class="h-9 rounded-lg border px-3 text-sm focus:outline-none focus:ring-2 transition"
              :class="fieldCls('startDate', !editForm.startDate)"
            />
            <p v-if="touched.startDate && !editForm.startDate" class="text-xs text-red-500">Start date is required.</p>
          </template>
        </div>

        <!-- End Date -->
        <div class="flex flex-col gap-1.5">
          <label class="text-xs font-semibold text-black/55">
            End Date <span v-if="isEditing" class="text-red-500">*</span>
          </label>
          <p v-if="!isEditing" class="text-sm text-black py-1 tabular-nums">{{ fmtDate(contract.endDate) }}</p>
          <template v-else>
            <input
              v-model="editForm.endDate"
              @blur="touched.endDate = true"
              type="date"
              class="h-9 rounded-lg border px-3 text-sm focus:outline-none focus:ring-2 transition"
              :class="fieldCls('endDate', !editForm.endDate || !!dateError)"
            />
            <p v-if="touched.endDate && !editForm.endDate" class="text-xs text-red-500">End date is required.</p>
            <p v-else-if="dateError" class="text-xs text-red-500">{{ dateError }}</p>
          </template>
        </div>

      </div>
    </div>

    <!-- Footer: Created By -->
    <div class="px-6 py-4 flex items-center gap-3">
      <span class="text-xs font-semibold text-black/40">Created by</span>
      <div class="flex items-center gap-2">
        <div class="w-6 h-6 rounded-full flex items-center justify-center text-white text-[9px] font-bold shrink-0 select-none"
          :style="{ backgroundColor: avatarColor(contract.createdBy) }">
          {{ initials(contract.createdBy) }}
        </div>
        <span class="text-sm font-medium text-black">{{ contract.createdBy }}</span>
      </div>
    </div>

  </div>
</template>
