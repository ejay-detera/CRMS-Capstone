<script setup lang="ts">
import type { RiskFinding } from '@/types/riskAssessment'
import { severityBadge } from '@/types/riskAssessment'

defineProps<{
  findings: RiskFinding[]
}>()
</script>

<template>
  <div class="bg-white rounded-lg border border-black/8 shadow-sm overflow-hidden">
    <div class="px-6 pt-5 pb-4 border-b border-black/5">
      <h3 class="text-sm font-semibold text-black">Flagged Clauses</h3>
      <p class="text-xs text-black/40 mt-0.5">Each finding cites the playbook clause it deviates from.</p>
    </div>

    <div v-if="findings.length === 0" class="px-6 py-10 text-center text-sm text-black/35">
      No flagged clauses were identified in this assessment.
    </div>

    <div v-else class="divide-y divide-black/[0.04]">
      <div v-for="finding in findings" :key="finding.id" class="px-6 py-4">
        <div class="flex items-center justify-between gap-3 mb-2">
          <span class="px-2.5 py-1 text-[11px] font-semibold rounded-full border" :class="severityBadge[finding.severity]">
            {{ finding.severity.toUpperCase() }}
          </span>
          <span v-if="finding.playbookClauseCode" class="text-[11px] font-mono text-black/40">
            {{ finding.playbookClauseCode }} — {{ finding.playbookClauseTitle }}
          </span>
        </div>

        <p class="text-xs text-black/70 leading-relaxed mb-2">
          <span class="font-semibold text-black/50 uppercase tracking-wide text-[10px] block mb-1">Excerpt</span>
          {{ finding.clauseReference }}
        </p>
        <p class="text-xs text-black/70 leading-relaxed mb-2">
          <span class="font-semibold text-black/50 uppercase tracking-wide text-[10px] block mb-1">Deviation Reason</span>
          {{ finding.deviationReason }}
        </p>
        <p class="text-xs text-black/70 leading-relaxed">
          <span class="font-semibold text-black/50 uppercase tracking-wide text-[10px] block mb-1">Recommended Remediation</span>
          {{ finding.recommendedRemediation }}
        </p>
      </div>
    </div>
  </div>
</template>
