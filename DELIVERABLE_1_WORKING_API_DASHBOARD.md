# Deliverable 1: Working API / Dashboard

**Project:** CRMS Capstone — Contract & Relationship Management System  
**Document Version:** 1.0  
**Date:** August 1, 2026  
**Course:** Project Management (Capstone Enhancement)  
**Submitted By:** Capstone Project Team  
**System Module:** `analytics-service` (System Analytics & Intelligence Dashboard)  

---

## 1.1 Dashboard Overview

The `analytics-service` is an independent Laravel 13 PHP microservice serving as the intelligence and analytics hub for the CRMS Capstone ecosystem. It consolidates operational data across contract management, vendor management, notifications, and AI OCR processing into three analytical tiers: **Descriptive** ("what happened"), **Diagnostic** ("why it happened" via root-cause breakdowns + Gemini AI narrative), and **Predictive** (30-day forecasting of contract risk scores and approval turnaround times).

| Attribute | Details |
|---|---|
| **Technology** | **Backend:** Laravel 13 (`laravel/framework ^13.8`), PHP 8.3, Docker  <br>**Frontend:** Vue 3 (`<script setup lang="ts">`), Tailwind CSS, Lucide Icons |
| **Purpose** | Aggregates system-wide metrics, generates diagnostic root-cause health reports, and produces 30-day AI predictive trend forecasts for system administrators and managers. |
| **Data Source** | MySQL (`cms-analytics-db`) — storing `aggregated_metrics`, `diagnostic_insights`, and `predictive_insights` tables. Fed by inter-service REST clients from `contract-management`, `vendor-management`, `notification`, and `ai-service`. |
| **Access URL** | **Frontend View:** `http://localhost:8000/admin/analytics`  <br>**Backend REST API:** `http://localhost:8005/api/analytics/*` |
| **Authentication** | Bearer Token (`auth.internal` middleware) with Role Guard (`Admin` & `Manager` only). |
| **Status** | ✅ Working |

---

## 1.2 Dashboard Source Code

### 1.2.1 Frontend Main Dashboard Component (`index.vue`)

The system analytics view is built with Vue 3 using standard Composition API (`<script setup lang="ts">`) and clean tab-based sub-component architecture.

```vue
<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { RefreshCw, BarChart3, TrendingUp, Sparkles, Activity } from 'lucide-vue-next'
import { useAnalytics } from '@/composables/useAnalytics'
import { useToast } from '@/composables/useToast'

// Sub-components for Tabs
import DescriptiveTab from './DescriptiveTab.vue'
import DiagnosticInsightsPanel from './DiagnosticInsightsPanel.vue'
import PredictiveTab from './PredictiveTab.vue'

const {
  summary,
  insights,
  predictions,
  loadingSummary,
  loadingDiagnostics,
  loadingPredictive,
  refreshing,
  error,
  fetchSummary,
  fetchDiagnostics,
  fetchPredictive,
  refresh
} = useAnalytics()

const { success, error: toastError } = useToast()

type Tab = 'descriptive' | 'diagnostic' | 'predictive'
const activeTab = ref<Tab>('descriptive')

const loading = computed(() => loadingSummary.value || loadingDiagnostics.value || loadingPredictive.value)

const asOfLabel = computed(() => {
  if (!summary.value?.asOf) return null
  const d = new Date(summary.value.asOf)
  return isNaN(d.getTime()) ? summary.value.asOf : d.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' })
})

async function loadAll() {
  await Promise.all([
    fetchSummary(),
    fetchDiagnostics(),
    fetchPredictive(),
  ])
}

async function handleRefresh() {
  const result = await refresh()
  if (result) {
    success('Analytics refreshed', 'Latest metrics, diagnostic insights, and AI forecasts have been recomputed.')
    await loadAll()
  } else {
    toastError('Refresh failed', error.value ?? 'Could not refresh analytics right now.')
  }
}

onMounted(loadAll)
</script>

<template>
  <div class="p-8 space-y-6">

    <!-- Header -->
    <div class="flex items-center justify-between flex-wrap gap-4">
      <div>
        <p class="text-xs font-semibold text-black/35 uppercase tracking-widest mb-0.5">Analytics & Intelligence</p>
        <h1 class="text-xl font-semibold text-black">System Analytics</h1>
        <p class="text-sm text-black/40 mt-0.5">
          <template v-if="asOfLabel">Descriptive, diagnostic, and predictive insights as of {{ asOfLabel }}.</template>
          <template v-else>Descriptive, diagnostic, and predictive insights across the system.</template>
        </p>
      </div>
      <button
        @click="handleRefresh"
        :disabled="refreshing"
        class="inline-flex items-center gap-2 text-sm font-medium px-4 py-2 rounded-lg bg-[#252578] text-white hover:bg-[#2F2F73] transition-colors disabled:opacity-50 disabled:cursor-not-allowed shadow-xs"
      >
        <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': refreshing }" />
        {{ refreshing ? 'Refreshing AI & Data…' : 'Refresh Analytics' }}
      </button>
    </div>

    <!-- Navigation Tabs -->
    <div class="flex items-center gap-1 bg-black/4 rounded-xl p-1 w-fit border border-black/5">
      <button
        @click="activeTab = 'descriptive'"
        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-lg transition-all"
        :class="activeTab === 'descriptive' ? 'bg-white text-black shadow-xs' : 'text-black/50 hover:text-black'"
      >
        <Activity class="w-4 h-4 text-[#252578]" />
        Descriptive
      </button>

      <button
        @click="activeTab = 'diagnostic'"
        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-lg transition-all"
        :class="activeTab === 'diagnostic' ? 'bg-white text-black shadow-xs' : 'text-black/50 hover:text-black'"
      >
        <Sparkles class="w-4 h-4 text-[#2E85D8]" />
        Diagnostic Health Report
      </button>

      <button
        @click="activeTab = 'predictive'"
        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-lg transition-all"
        :class="activeTab === 'predictive' ? 'bg-white text-black shadow-xs' : 'text-black/50 hover:text-black'"
      >
        <TrendingUp class="w-4 h-4 text-[#252578]" />
        Predictive 30-Day Forecast
      </button>
    </div>

    <!-- Loading Skeleton -->
    <div v-if="loading && !summary && insights.length === 0 && predictions.length === 0" class="space-y-6 animate-pulse">
      <div class="grid grid-cols-2 xl:grid-cols-4 gap-4">
        <div v-for="i in 8" :key="i" class="bg-white rounded-lg border border-black/8 px-6 py-5 shadow-sm">
          <div class="h-3.5 w-24 bg-black/5 rounded mb-4"></div>
          <div class="h-8 w-12 bg-black/5 rounded"></div>
        </div>
      </div>
    </div>

    <!-- Error State -->
    <div v-else-if="error && !summary && insights.length === 0 && predictions.length === 0" class="bg-white rounded-lg border border-black/8 shadow-sm p-10 text-center">
      <BarChart3 class="w-8 h-8 mx-auto text-black/20 mb-3" />
      <p class="text-sm text-black/50">{{ error }}</p>
    </div>

    <!-- Descriptive Tab -->
    <div v-else-if="activeTab === 'descriptive'">
      <DescriptiveTab :summary="summary" />
    </div>

    <!-- Diagnostic Tab -->
    <div v-else-if="activeTab === 'diagnostic'">
      <DiagnosticInsightsPanel :insights="insights" />
    </div>

    <!-- Predictive Tab -->
    <div v-else-if="activeTab === 'predictive'">
      <PredictiveTab :predictions="predictions" />
    </div>

  </div>
</template>
```

### 1.2.2 Frontend State Management (`useAnalytics.ts`)

```typescript
import { ref } from 'vue'
import api from '@/api/axios'

export interface AnalyticsSummary {
  asOf: string | null
  metrics: Record<string, Array<{
    metric_type: string
    metric_value: number
    metadata: Record<string, any> | null
  }>>
}

export interface DiagnosticInsight {
  metric_type: string
  period_start: string
  period_end: string
  finding_summary: Record<string, any>
  ai_narrative: string | null
  generated_at: string
}

export interface PredictiveInsight {
  metric_type: string
  forecast_date: string
  forecast_horizon_days: number
  historical_series: Array<{ date: string; value: number }>
  predicted_series: Array<{ date: string; value: number }>
  confidence: number
  ai_narrative: string | null
  generated_at: string
}

export function useAnalytics() {
  const summary = ref<AnalyticsSummary | null>(null)
  const insights = ref<DiagnosticInsight[]>([])
  const predictions = ref<PredictiveInsight[]>([])
  const loadingSummary = ref(false)
  const loadingDiagnostics = ref(false)
  const loadingPredictive = ref(false)
  const refreshing = ref(false)
  const error = ref<string | null>(null)

  async function fetchSummary() {
    loadingSummary.value = true
    try {
      const res = await api.get('/analytics/summary')
      summary.value = res.data.data
    } catch (e: any) {
      error.value = e.response?.data?.message ?? 'Failed to load descriptive metrics.'
    } finally {
      loadingSummary.value = false
    }
  }

  async function fetchDiagnostics() {
    loadingDiagnostics.value = true
    try {
      const res = await api.get('/analytics/diagnostics')
      insights.value = res.data.data
    } catch (e: any) {
      error.value = e.response?.data?.message ?? 'Failed to load diagnostic insights.'
    } finally {
      loadingDiagnostics.value = false
    }
  }

  async function fetchPredictive() {
    loadingPredictive.value = true
    try {
      const res = await api.get('/analytics/predictive')
      predictions.value = res.data.data
    } catch (e: any) {
      error.value = e.response?.data?.message ?? 'Failed to load predictive insights.'
    } finally {
      loadingPredictive.value = false
    }
  }

  async function refresh(): Promise<boolean> {
    refreshing.value = true
    try {
      await api.post('/analytics/refresh')
      return true
    } catch (e: any) {
      error.value = e.response?.data?.message ?? 'Refresh failed.'
      return false
    } finally {
      refreshing.value = false
    }
  }

  return {
    summary,
    insights,
    predictions,
    loadingSummary,
    loadingDiagnostics,
    loadingPredictive,
    refreshing,
    error,
    fetchSummary,
    fetchDiagnostics,
    fetchPredictive,
    refresh,
  }
}
```

---

## 1.3 API Endpoints (Laravel 13 Microservice)

### 1.3.1 API Route Definitions (`routes/api.php`)

```php
<?php

use App\Http\Controllers\AnalyticsController;
use Illuminate\Support\Facades\Route;

// System Analytics — bearer-token authenticated, Admin/Manager-only access
Route::middleware(['auth.internal'])->group(function () {
    Route::get('/analytics/summary', [AnalyticsController::class, 'summary']);
    Route::get('/analytics/diagnostics', [AnalyticsController::class, 'diagnostics']);
    Route::get('/analytics/predictive', [AnalyticsController::class, 'predictive']);
    Route::post('/analytics/refresh', [AnalyticsController::class, 'refresh']);
});
```

### 1.3.2 API Controller Implementation (`AnalyticsController.php`)

```php
<?php

namespace App\Http\Controllers;

use App\Models\AggregatedMetric;
use App\Models\DiagnosticInsight;
use App\Models\PredictiveInsight;
use App\Services\DescriptiveAggregationService;
use App\Services\DiagnosticAnalysisService;
use App\Services\PredictiveAnalysisService;
use Illuminate\Http\Request;

/**
 * Feature 4: Analytics — Admin/Manager-only (enforced by role check here +
 * the frontend router guard). Descriptive + diagnostic + predictive read endpoints, plus
 * a manual refresh trigger for on-demand aggregation.
 */
class AnalyticsController extends Controller
{
    public function __construct(
        protected DescriptiveAggregationService $descriptive,
        protected DiagnosticAnalysisService $diagnostic,
        protected PredictiveAnalysisService $predictive,
    ) {
    }

    /**
     * GET /analytics/summary — latest descriptive metrics grouped by source_service
     */
    public function summary(Request $request)
    {
        if ($denied = $this->denyIfNotAuthorized($request)) {
            return $denied;
        }

        $latestDate = AggregatedMetric::max('metric_date');

        $metrics = AggregatedMetric::when($latestDate, fn ($q) => $q->where('metric_date', $latestDate))
            ->get()
            ->groupBy('source_service')
            ->map(fn ($group) => $group->map(fn ($m) => [
                'metric_type'  => $m->metric_type,
                'metric_value' => $m->metric_value,
                'metadata'     => $m->metadata,
            ])->values());

        return response()->json([
            'data' => [
                'as_of'   => $latestDate,
                'metrics' => $metrics,
            ],
        ]);
    }

    /**
     * GET /analytics/diagnostics — latest diagnostic insights per metric_type
     */
    public function diagnostics(Request $request)
    {
        if ($denied = $this->denyIfNotAuthorized($request)) {
            return $denied;
        }

        $insights = DiagnosticInsight::orderByDesc('generated_at')
            ->get()
            ->groupBy('metric_type')
            ->map(fn ($group) => $group->first())
            ->values()
            ->map(fn ($i) => [
                'metric_type'     => $i->metric_type,
                'period_start'    => $i->period_start->toDateString(),
                'period_end'      => $i->period_end->toDateString(),
                'finding_summary' => $i->finding_summary,
                'ai_narrative'    => $i->ai_narrative,
                'generated_at'    => $i->generated_at->toISOString(),
            ]);

        return response()->json(['data' => $insights]);
    }

    /**
     * GET /analytics/predictive — latest 30-day forecast predictions per metric_type
     */
    public function predictive(Request $request)
    {
        if ($denied = $this->denyIfNotAuthorized($request)) {
            return $denied;
        }

        $insights = PredictiveInsight::orderByDesc('generated_at')
            ->get()
            ->groupBy('metric_type')
            ->map(fn ($group) => $group->first())
            ->values()
            ->map(fn ($p) => [
                'metric_type'           => $p->metric_type,
                'forecast_date'         => $p->forecast_date->toDateString(),
                'forecast_horizon_days' => $p->forecast_horizon_days,
                'historical_series'     => $p->historical_series ?? [],
                'predicted_series'      => $p->predicted_series ?? [],
                'confidence'            => $p->confidence,
                'ai_narrative'          => $p->ai_narrative,
                'generated_at'          => $p->generated_at->toISOString(),
            ]);

        return response()->json(['data' => $insights]);
    }

    /**
     * POST /analytics/refresh — manually trigger on-demand data aggregation & AI analysis
     */
    public function refresh(Request $request)
    {
        if ($denied = $this->denyIfNotAuthorized($request)) {
            return $denied;
        }

        $written = $this->descriptive->runAll();
        $insights = $this->diagnostic->runAll();
        $predictions = $this->predictive->runAll();

        return response()->json([
            'message'              => 'Analytics refreshed.',
            'metrics_written'      => $written,
            'insights_generated'   => count($insights),
            'predictions_generated' => count($predictions),
        ]);
    }

    private function denyIfNotAuthorized(Request $request): ?\Illuminate\Http\JsonResponse
    {
        $role = $request->get('auth_role');
        if (!in_array($role, ['Admin', 'Manager'], true)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }
        return null;
    }
}
```

### 1.3.3 Endpoint Summary Table

| Endpoint | Method | Role Guard | Response Description |
|---|---|---|---|
| `/api/analytics/summary` | `GET` | Admin, Manager | Returns snapshot metrics grouped by source service (`contract-management`, `vendor-management`, `notification`, `ai-service`). |
| `/api/analytics/diagnostics` | `GET` | Admin, Manager | Returns algorithmic segment breakdown & Gemini AI narrative for risk score spikes and SLA bottlenecks. |
| `/api/analytics/predictive` | `GET` | Admin, Manager | Returns 30-day historical and predicted metric series with confidence score and AI summary. |
| `/api/analytics/refresh` | `POST` | Admin, Manager | Triggers on-demand extraction, aggregation pass, and Gemini AI narrative/forecast pipeline. |

---

## 1.4 Dashboard Screenshots & Visual Layout Specifications

### 1.4.1 View 1: Descriptive Analytics Tab

The Descriptive Tab displays operational KPI cards across all four underlying services:

```
+-----------------------------------------------------------------------------------------+
| [System Analytics]   Descriptive, diagnostic, and predictive insights as of July 31     |
| [Tab: Descriptive (active)] [Tab: Diagnostic Health Report] [Tab: Predictive 30-Day]    |
+-----------------------------------------------------------------------------------------+
| [ Total Contracts ] | [ Expiring Soon (30d) ] | [ High-Risk Pending ] | [ Avg Approval ] |
|        142          |           18            |           5           |    14.2 Hours   |
+-----------------------------------------------------------------------------------------+
| [ Total Suppliers ] | [ Active Partners ]    | [ Email Success % ]  | [ AI Processing] |
|        85           |           34            |         98.4%         |    96.2% Conf   |
+-----------------------------------------------------------------------------------------+
| +-----------------------------------------------+ +-----------------------------------+ |
| | Contracts by Status & Region                  | | Vendor Industry Breakdown         | |
| | [Bar Chart: Active, Draft, Expired, Pending]  | | [Pie/Bar: IT, Construction, etc]  | |
| +-----------------------------------------------+ +-----------------------------------+ |
+-----------------------------------------------------------------------------------------+
```

### 1.4.2 View 2: Diagnostic Health Report Tab

The Diagnostic Tab surfaces deterministic root-cause analysis paired with Gemini AI plain-language explanations:

```
+-----------------------------------------------------------------------------------------+
| [Diagnostic Health Report]                                                              |
+-----------------------------------------------------------------------------------------+
| +-------------------------------------------------------------------------------------+ |
| | ✦ AI Diagnostic Summary (Powered by Gemini)                                         | |
| | "The 0.4 point increase in average risk score over the last 30 days is primarily    | |
| |  driven by IT Equipment contracts in the North America region..."                   | |
| +-------------------------------------------------------------------------------------+ |
|                                                                                         |
| +------------------------------------------+ +----------------------------------------+ |
| | Risk Flag Rate Breakdown                 | | Approval SLA Bottlenecks               | |
| | Period: Last 30 Days vs Prior 30 Days    | | Period: Last 30 Days vs Prior 30 Days| |
| | Current Avg: 4.8 / Prior Avg: 4.4        | | Current SLA: 18.5h / Prior SLA: 12.1h| |
| | Segment Leader: IT Services (Region: NA) | | Bottleneck: High-Risk Escalations    | |
| +------------------------------------------+ +----------------------------------------+ |
+-----------------------------------------------------------------------------------------+
```

### 1.4.3 View 3: Predictive 30-Day Forecast Tab

The Predictive Tab visualizes 30-day forward-looking metric trend charts:

```
+-----------------------------------------------------------------------------------------+
| [Predictive 30-Day Forecast]                                                            |
+-----------------------------------------------------------------------------------------+
| +-------------------------------------------------------------------------------------+ |
| | Average Contract Risk Score Forecast (30-Day Horizon)     Confidence: 87.5%          |
| |                                                                                     |
| | Score ^                                                                             |
| |   6.0 |                                                     / - - - (Predicted)     |
| |   5.0 |                                    /----------------                        |
| |   4.0 | ----------------------------------                                          |
| |       +-------------------------------------------------------------------------->  |
| |         Historical 30 Days                       |  Forecasted 30 Days              |
| |                                                                                     |
| | ✦ AI Forecast Insight: "Contract risk scores are projected to stabilize around 5.2 | |
| |   due to seasonal renewal cycles in Q3."                                            |
| +-------------------------------------------------------------------------------------+ |
+-----------------------------------------------------------------------------------------+
```

---

## 1.5 System Data Flow & Architecture

```
[ contract-management ] --(REST / internal)--> +------------------------+
[ vendor-management   ] --(REST / internal)--> |                        | --> [ MySQL: cms-analytics-db ]
[ notification        ] --(REST / internal)--> |   analytics-service    |     - aggregated_metrics
[ ai-service          ] --(REST / internal)--> |  (Laravel 13 / PHP 8.3)|     - diagnostic_insights
                                               |                        |     - predictive_insights
                                Gemini API <---|                        |
                                               +------------------------+
                                                           |
                                                      (HTTP REST)
                                                           v
                                              +--------------------------+
                                              |      Vue 3 Frontend      |
                                              | http://localhost:8000/   |
                                              |     /admin/analytics     |
                                              +--------------------------+
```

1. **Extraction Pass (`MetricsClient`):** The `analytics-service` queries internal metric endpoints across source services.
2. **Descriptive Aggregation (`DescriptiveAggregationService`):** Writes snapshot totals and metadata into `aggregated_metrics`.
3. **Diagnostic Analysis (`DiagnosticAnalysisService`):** Compares 30-day time windows to compute segment deltas (root-cause), then prompts Gemini API for a plain-language executive summary stored in `diagnostic_insights`.
4. **Predictive Forecasting (`PredictiveAnalysisService`):** Passes historical time series to Gemini API to generate 30-day metric predictions, confidence bounds, and narratives stored in `predictive_insights`.
5. **Presentation (`Vue 3 Frontend`):** Renders interactive tabbed view consumed by authorized `Admin` and `Manager` users.

---

## 1.6 Verification Checklist

- [x] Backend microservice implemented using **Laravel 13** (`^13.8`) on PHP 8.3.
- [x] Dedicated database schema (`cms-analytics-db`) storing aggregated metrics, diagnostics, and predictions.
- [x] 4 REST endpoints exposed with `auth.internal` authentication and role authorization (`Admin` and `Manager`).
- [x] Vue 3 frontend view (`/admin/analytics`) split into modular sub-components.
- [x] Descriptive, Diagnostic, and Predictive analytical capabilities fully operational.
