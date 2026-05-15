import { reactive } from 'vue'

type ToastType = 'success' | 'error' | 'info' | 'warning'

interface Toast {
  id:           number
  type:         ToastType
  title:        string
  description?: string
}

let _id = 0
const toasts = reactive<Toast[]>([])

export function useToast() {
  function add(type: ToastType, title: string, description?: string, duration = 3800) {
    const id = ++_id
    toasts.push({ id, type, title, description })
    setTimeout(() => remove(id), duration)
  }

  function remove(id: number) {
    const idx = toasts.findIndex(t => t.id === id)
    if (idx >= 0) toasts.splice(idx, 1)
  }

  return {
    toasts,
    success: (title: string, description?: string) => add('success', title, description),
    error:   (title: string, description?: string) => add('error',   title, description),
    info:    (title: string, description?: string) => add('info',    title, description),
    warning: (title: string, description?: string) => add('warning', title, description),
    remove,
  }
}
