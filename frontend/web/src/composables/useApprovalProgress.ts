import { ref, onUnmounted } from 'vue'
import { useAuth } from './useAuth'
import type { TrackerStatus, WorkflowProgress } from '@/types/contractWorkflow'
import { mapProgressResponse } from '@/types/contractWorkflow'

/**
 * Live-data wiring for the Visual Contract Workflow Tracker (Phase 6).
 * Fetches contract-management's GET /contracts/{id}/approval-progress
 * and maps it onto the presentational WorkflowProgress shape.
 * `engine: false` (legacy single-manager flow, decision #9) maps to the
 * tracker's `empty` status. Aborts in-flight requests on unmount.
 */
export function useApprovalProgress() {
  const { state: authState } = useAuth()

  const workflow = ref<WorkflowProgress | null>(null)
  const status = ref<TrackerStatus>('loading')
  const error = ref<string | null>(null)
  let aborter: AbortController | null = null

  function apiBase(): string {
    return import.meta.env.VITE_CONTRACT_API_URL as string
  }

  async function fetchProgress(contractId: string | number): Promise<void> {
    aborter?.abort()
    aborter = new AbortController()
    status.value = 'loading'
    error.value = null

    try {
      const res = await fetch(`${apiBase()}/contracts/${contractId}/approval-progress`, {
        headers: {
          Accept: 'application/json',
          Authorization: `Bearer ${authState.token || ''}`,
        },
        signal: aborter.signal,
      })
      if (!res.ok) throw new Error('Failed to load approval progress.')
      const json = await res.json()
      const data = json.data ?? json

      if (!data.engine || !data.runs?.length) {
        workflow.value = null
        status.value = 'empty'
        return
      }

      workflow.value = mapProgressResponse(String(contractId), data)
      status.value = 'ready'
    } catch (err) {
      if (err instanceof DOMException && err.name === 'AbortError') return
      error.value = err instanceof Error ? err.message : 'Failed to load approval progress.'
      status.value = 'error'
    }
  }

  function retry(contractId: string | number): void {
    void fetchProgress(contractId)
  }

  onUnmounted(() => aborter?.abort())

  return { workflow, status, error, fetchProgress, retry }
}
