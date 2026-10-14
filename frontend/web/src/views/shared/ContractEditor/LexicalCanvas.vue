<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from 'vue'
import type { PaperSize, PageOrientation } from '@/composables/useLexicalEditor'
import type { HeaderFooterConfig } from '@/composables/useCreateContractDraft'

const props = withDefaults(
  defineProps<{
    zoom?: number
    paperSize?: PaperSize
    orientation?: PageOrientation
    headerFooter: HeaderFooterConfig
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
  'update:headerFooter': [config: HeaderFooterConfig]
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

// Calculate number of pages based on explicit page breaks and scroll height
function recalculatePages() {
  if (!editorContentRef.value) return
  const pageBreaks = editorContentRef.value.querySelectorAll('.crms-page-break, [data-page]').length
  const scrollH = editorContentRef.value.scrollHeight + 140
  const pageH = dimensions.value.height
  const calculated = Math.max(pageBreaks + 1, Math.ceil(scrollH / pageH))
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
  <div class="w-full flex-1 overflow-y-auto bg-[#ECEEF1] py-8 px-4 flex flex-col items-center select-text">
    <!-- Outer Scalable Container -->
    <div
      class="relative flex flex-col items-center transition-all origin-top mb-12"
      :style="{
        width: `${dimensions.width}px`,
        transform: props.zoom ? `scale(${props.zoom})` : 'none',
      }"
    >
      <!-- Continuous Solid Paper Sheet with Visual Page Separation -->
      <div
        class="w-full bg-white rounded-[2px] shadow-[0_2px_8px_rgba(0,0,0,0.08),0_12px_24px_rgba(0,0,0,0.04)] border border-black/[0.08] flex flex-col transition-all duration-200"
        :style="{
          minHeight: `${dimensions.height}px`,
        }"
      >
        <!-- Page Header (Top Margin) - Editable & Repeated across pages -->
        <div class="group relative px-12 sm:px-16 pt-7 pb-4 border-b border-black/[0.08] flex justify-between items-center text-[11px] text-black/50 font-poppins tracking-wider select-none bg-slate-50/50 hover:bg-slate-100/70 transition-colors rounded-t-[2px]">
          <input
            :value="props.headerFooter.headerLeft"
            @input="(e) => emit('update:headerFooter', { ...props.headerFooter, headerLeft: (e.target as HTMLInputElement).value })"
            placeholder="Header Left (e.g. Confidential Agreement)"
            class="bg-transparent border border-transparent hover:border-black/15 focus:border-brand-blue rounded px-1.5 py-0.5 text-[11px] font-medium text-black/70 focus:text-black focus:bg-white outline-none transition max-w-[42%]"
            title="Click to edit header left (repeated on all pages)"
          />

          <span class="text-[9px] uppercase tracking-widest text-brand-navy/60 font-semibold px-2 py-0.5 rounded bg-brand-blue/10 opacity-0 group-hover:opacity-100 transition-opacity">
            Repeatable Header
          </span>

          <input
            :value="props.headerFooter.headerRight"
            @input="(e) => emit('update:headerFooter', { ...props.headerFooter, headerRight: (e.target as HTMLInputElement).value })"
            placeholder="Header Right (e.g. SBSI CRMS)"
            class="bg-transparent border border-transparent hover:border-black/15 focus:border-brand-blue rounded px-1.5 py-0.5 text-[11px] font-medium text-black/70 focus:text-black focus:bg-white outline-none transition max-w-[42%] text-right"
            title="Click to edit header right (repeated on all pages)"
          />
        </div>

        <!-- Editable Lexical Canvas -->
        <div class="px-12 sm:px-16 py-8 text-black/90 font-poppins outline-none focus:outline-none flex-1">
          <div
            ref="editorContentRef"
            contenteditable="true"
            spellcheck="true"
            class="outline-none focus:outline-none min-h-[850px] leading-relaxed selection:bg-brand-blue/25 selection:text-brand-navy"
            @input="recalculatePages"
            @keyup="recalculatePages"
          />
        </div>

        <!-- Page Footer (Bottom Margin) - Editable & Repeated across pages -->
        <div class="group relative px-12 sm:px-16 py-5 border-t border-black/[0.08] flex justify-between items-center text-[11px] text-black/50 font-poppins select-none bg-slate-50/50 hover:bg-slate-100/70 transition-colors rounded-b-[2px]">
          <input
            :value="props.headerFooter.footerLeft"
            @input="(e) => emit('update:headerFooter', { ...props.headerFooter, footerLeft: (e.target as HTMLInputElement).value })"
            placeholder="Footer Left (e.g. Authorized Draft)"
            class="bg-transparent border border-transparent hover:border-black/15 focus:border-brand-blue rounded px-1.5 py-0.5 text-[11px] font-medium text-black/70 focus:text-black focus:bg-white outline-none transition max-w-[45%]"
            title="Click to edit footer left (repeated on all pages)"
          />

          <span class="text-[9px] uppercase tracking-widest text-brand-navy/60 font-semibold px-2 py-0.5 rounded bg-brand-blue/10 opacity-0 group-hover:opacity-100 transition-opacity">
            Repeatable Footer
          </span>

          <div v-if="props.headerFooter.showPageNumber" class="text-[11px] font-medium text-black/60 px-1.5 py-0.5 select-none">
            Page {{ pageCount }} of {{ pageCount }} ({{ props.paperSize?.toUpperCase() || 'LETTER' }})
          </div>
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

/* ── Clear Visual Page Break Divider ── */
.crms-page-break,
div[data-page] {
  display: block;
  position: relative;
  /* Break out horizontally so the gray canvas gap cuts all the way across */
  margin-top: 4rem;
  margin-bottom: 4rem;
  margin-left: -3rem;
  margin-right: -3rem;
  padding: 0;
  cursor: default;
  user-select: none;
}

@media (min-width: 640px) {
  .crms-page-break,
  div[data-page] {
    margin-left: -4rem;
    margin-right: -4rem;
  }
}

.crms-page-break::before,
div[data-page]::before {
  content: '';
  display: block;
  height: 28px;
  background-color: #ECEEF1;
  border-top: 1px solid rgba(0, 0, 0, 0.08);
  border-bottom: 1px solid rgba(0, 0, 0, 0.08);
  box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.05), inset 0 -2px 4px rgba(0, 0, 0, 0.05);
}

.crms-page-break::after,
div[data-page]::after {
  content: 'PAGE BREAK';
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  background: #ffffff;
  color: #475569;
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.1em;
  padding: 3px 14px;
  border-radius: 9999px;
  border: 1px solid rgba(0, 0, 0, 0.12);
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
  text-transform: uppercase;
  font-family: 'Poppins', sans-serif;
}

@media print {
  .crms-page-break,
  div[data-page] {
    page-break-before: always !important;
    break-before: page !important;
    height: 0 !important;
    margin: 0 !important;
    padding: 0 !important;
    visibility: hidden !important;
  }
}
</style>
