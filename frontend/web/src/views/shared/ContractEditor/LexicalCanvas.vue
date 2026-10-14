<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from 'vue'
import type { PaperSize, PageOrientation } from '@/composables/useLexicalEditor'

const props = withDefaults(
  defineProps<{
    zoom?: number
    paperSize?: PaperSize
    orientation?: PageOrientation
  }>(),
  {
    zoom: 1,
    paperSize: 'letter',
    orientation: 'portrait',
  }
)

const emit = defineEmits<{
  'editor-mount': [element: HTMLElement]
  'editor-unmount': []
}>()

const editorContentRef = ref<HTMLDivElement | null>(null)
const pageCount = ref(1)

// Standard paper dimensions at 96 DPI
const dimensions = computed(() => {
  const size = props.paperSize || 'letter'
  const isLandscape = props.orientation === 'landscape'

  let w = 816
  let h = 1056

  if (size === 'a4') {
    w = 794
    h = 1123
  } else if (size === 'legal') {
    w = 816
    h = 1344
  }

  return {
    width: isLandscape ? h : w,
    height: isLandscape ? w : h,
  }
})

// Calculate number of pages based on actual scroll/content height
function recalculatePages() {
  if (!editorContentRef.value) return
  const scrollH = editorContentRef.value.scrollHeight
  const pageH = dimensions.value.height
  const calculated = Math.max(1, Math.ceil(scrollH / (pageH - 40)))
  pageCount.value = calculated
}

let resizeObserver: ResizeObserver | null = null

onMounted(() => {
  if (editorContentRef.value) {
    emit('editor-mount', editorContentRef.value)

    resizeObserver = new ResizeObserver(() => {
      recalculatePages()
    })
    resizeObserver.observe(editorContentRef.value)
  }
  nextTick(() => recalculatePages())
})

watch(() => [props.paperSize, props.orientation], () => {
  nextTick(() => recalculatePages())
})

onUnmounted(() => {
  if (resizeObserver) {
    resizeObserver.disconnect()
    resizeObserver = null
  }
  emit('editor-unmount')
})
</script>

<template>
  <div class="w-full flex-1 overflow-y-auto bg-[#ECEEF1] py-10 px-4 flex flex-col items-center select-text">
    <!-- Outer Scalable Container -->
    <div
      class="relative flex flex-col items-center transition-all origin-top"
      :style="{
        width: `${dimensions.width}px`,
        transform: props.zoom ? `scale(${props.zoom})` : 'none',
      }"
    >
      <!-- Page Sheets Wrapper -->
      <div class="relative w-full">
        <!-- Visual Multi-Page Sheets Background -->
        <div class="absolute inset-0 pointer-events-none flex flex-col gap-6 z-0">
          <div
            v-for="pageIndex in pageCount"
            :key="pageIndex"
            class="w-full bg-white rounded-[2px] shadow-[0_1px_3px_rgba(0,0,0,0.1),0_4px_16px_rgba(0,0,0,0.06)] border border-black/[0.08] relative"
            :style="{ height: `${dimensions.height}px` }"
          >
            <!-- Page Header (Top Margin) -->
            <div class="absolute top-4 left-14 right-14 flex justify-between text-[10px] text-black/25 font-poppins uppercase tracking-wider select-none">
              <span>Confidential Agreement</span>
              <span>SBSI CRMS</span>
            </div>

            <!-- Page Number (Bottom Footer) -->
            <div class="absolute bottom-4 left-14 right-14 flex justify-between text-[11px] text-black/35 font-poppins select-none">
              <span>Authorized Draft</span>
              <span>Page {{ pageIndex }} of {{ pageCount }}</span>
            </div>
          </div>
        </div>

        <!-- Single ContentEditable Lexical Container with dynamic page breaks overlay -->
        <div
          class="relative z-10 p-14 md:p-16 text-black/90 font-poppins outline-none focus:outline-none"
          :style="{
            minHeight: `${dimensions.height * pageCount + (pageCount - 1) * 24}px`,
          }"
        >
          <div
            ref="editorContentRef"
            contenteditable="true"
            spellcheck="true"
            class="outline-none focus:outline-none min-h-[850px] leading-relaxed selection:bg-brand-blue/25 selection:text-brand-navy"
            @input="recalculatePages"
            @keyup="recalculatePages"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<style>
/* Scoped typography & structure for the contract sheet */
.editor-h1 {
  font-family: 'Poppins', sans-serif;
  font-size: 1.5rem;
  font-weight: 700;
  text-align: center;
  margin-top: 1rem;
  margin-bottom: 0.75rem;
  letter-spacing: -0.02em;
  color: #111827;
}

.editor-h2 {
  font-family: 'Poppins', sans-serif;
  font-size: 1.125rem;
  font-weight: 700;
  margin-top: 1.5rem;
  margin-bottom: 0.5rem;
  letter-spacing: -0.01em;
  color: #111827;
  border-bottom: 1px solid rgba(0, 0, 0, 0.08);
  padding-bottom: 0.25rem;
}

.editor-h3 {
  font-family: 'Poppins', sans-serif;
  font-size: 1rem;
  font-weight: 600;
  margin-top: 1rem;
  margin-bottom: 0.5rem;
  color: #111827;
}

.editor-p {
  line-height: 1.75;
  margin-bottom: 0.75rem;
  font-size: 0.9375rem;
  color: #1f2937;
}

.editor-ul {
  list-style-type: disc;
  padding-left: 1.5rem;
  margin-bottom: 0.75rem;
}

.editor-ol {
  list-style-type: decimal;
  padding-left: 1.5rem;
  margin-bottom: 0.75rem;
}

.editor-li {
  margin-bottom: 0.25rem;
  line-height: 1.6;
}

/* Lexical Table Styling */
.editor-table {
  width: 100%;
  border-collapse: collapse;
  margin: 1.25rem 0;
  font-size: 0.8125rem;
  border: 1px solid #cbd5e1;
}

.editor-table-row {
  border-bottom: 1px solid #e2e8f0;
}

.editor-table-cell {
  border: 1px solid #cbd5e1;
  padding: 0.5rem 0.75rem;
  vertical-align: top;
  min-width: 50px;
}

.editor-table-cell-header {
  background-color: #f8fafc;
  font-weight: 600;
  color: #0f172a;
  border: 1px solid #cbd5e1;
  padding: 0.5rem 0.75rem;
}
</style>
