<script setup lang="ts">
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'

const props = withDefaults(defineProps<{
  open: boolean
  title: string
  description: string
  confirmLabel?: string
  cancelLabel?: string
  variant?: 'default' | 'destructive' | 'warning'
  loading?: boolean
}>(), {
  confirmLabel: 'Confirm',
  cancelLabel: 'Cancel',
  variant: 'default',
  loading: false,
})

const emit = defineEmits<{
  (e: 'update:open', val: boolean): void
  (e: 'confirm'): void
  (e: 'cancel'): void
}>()

function handleOpenUpdate(val: boolean) {
  emit('update:open', val)
}

function handleConfirm() {
  emit('confirm')
}

function handleCancel() {
  emit('cancel')
  emit('update:open', false)
}
</script>

<template>
  <Dialog :open="open" @update:open="handleOpenUpdate">
    <DialogContent class="max-w-md p-6 gap-4" @pointer-down-outside="loading ? undefined : handleCancel">
      <DialogHeader>
        <DialogTitle class="text-sm font-bold text-black">{{ title }}</DialogTitle>
        <DialogDescription class="text-xs text-black/40 mt-1.5 leading-relaxed">{{ description }}</DialogDescription>
      </DialogHeader>

      <DialogFooter class="flex items-center justify-end gap-3 mt-2">
        <Button variant="outline" @click="handleCancel" :disabled="loading"
          class="h-9 px-4 text-sm border-black/15 text-black/65 hover:text-black hover:bg-black/4">
          {{ cancelLabel }}
        </Button>
        <Button @click="handleConfirm" :disabled="loading"
          class="h-9 px-4 text-sm text-white shadow-sm"
          :class="{
            'bg-[#252578] hover:bg-[#2F2F73]': variant === 'default',
            'bg-red-600 hover:bg-red-700': variant === 'destructive',
            'bg-amber-600 hover:bg-amber-700': variant === 'warning',
          }">
          {{ loading ? 'Processing...' : confirmLabel }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
