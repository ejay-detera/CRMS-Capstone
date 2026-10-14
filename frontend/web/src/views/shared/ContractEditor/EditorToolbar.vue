<script setup lang="ts">
import { ref } from 'vue'
import {
  Undo2,
  Redo2,
  Bold,
  Italic,
  Underline,
  Strikethrough,
  AlignLeft,
  AlignCenter,
  AlignRight,
  AlignJustify,
  List,
  ListOrdered,
  Highlighter,
  Baseline,
  ChevronDown,
  Table as TableIcon,
  FileText,
  RotateCw,
  PanelTop,
  SeparatorHorizontal,
} from 'lucide-vue-next'
import type { EditorToolbarState, PaperSize, PageOrientation } from '@/composables/useLexicalEditor'
import type { HeaderFooterConfig } from '@/composables/useCreateContractDraft'

const props = defineProps<{
  state: EditorToolbarState
  headerFooter: HeaderFooterConfig
}>()

const emit = defineEmits<{
  'format-text': [format: 'bold' | 'italic' | 'underline' | 'strikethrough']
  'format-align': [align: 'left' | 'center' | 'right' | 'justify']
  'format-block': [type: 'paragraph' | 'h1' | 'h2' | 'h3']
  'format-list': [type: 'bullet' | 'number']
  'insert-table': [rows: number, cols: number]
  'set-paper-size': [size: PaperSize]
  'set-orientation': [orientation: PageOrientation]
  'set-font-family': [family: string]
  'set-font-size': [size: string]
  'set-font-color': [color: string]
  'set-highlight': [color: string]
  'undo': []
  'redo': []
  'update:headerFooter': [config: HeaderFooterConfig]
  'insert-page-break': []
}>()

const showColorPicker = ref(false)
const showHighlightPicker = ref(false)
const showTablePicker = ref(false)
const showHeaderFooterModal = ref(false)
const tableRows = ref(3)
const tableCols = ref(3)

const fontFamilies = [
  { name: 'Poppins', label: 'Poppins (Brand)' },
  { name: 'Montserrat', label: 'Montserrat' },
  { name: 'Times New Roman', label: 'Times New Roman' },
  { name: 'Arial', label: 'Arial' },
  { name: 'Courier New', label: 'Courier New' },
]

const fontSizes = ['9pt', '10pt', '11pt', '12pt', '14pt', '16pt', '18pt', '24pt', '30pt']

const brandColors = [
  { label: 'Black', hex: '#000000' },
  { label: 'Navy', hex: '#252578' },
  { label: 'Blue', hex: '#2E85D8' },
  { label: 'Dark Navy', hex: '#2F2F73' },
  { label: 'Muted Gray', hex: '#64748B' },
  { label: 'Charcoal', hex: '#334155' },
  { label: 'Crimson', hex: '#DC2626' },
]

const highlightColors = [
  { label: 'None', hex: 'transparent' },
  { label: 'Yellow', hex: '#FEF08A' },
  { label: 'Brand Light Blue', hex: '#E0F2FE' },
  { label: 'Soft Green', hex: '#DCFCE7' },
  { label: 'Soft Amber', hex: '#FEF3C7' },
  { label: 'Soft Rose', hex: '#FFE4E6' },
]

function stepFontSize(delta: number) {
  const currentIdx = fontSizes.indexOf(props.state.fontSize)
  let nextIdx = currentIdx !== -1 ? currentIdx + delta : 2 + delta
  if (nextIdx < 0) nextIdx = 0
  if (nextIdx >= fontSizes.length) nextIdx = fontSizes.length - 1
  emit('set-font-size', fontSizes[nextIdx])
}

function handleInsertTable(r: number, c: number) {
  emit('insert-table', r, c)
  showTablePicker.value = false
}
</script>

<template>
  <div class="min-h-10 bg-white border-b border-black/8 px-2 sm:px-3 py-1 flex flex-wrap items-center gap-1 select-none font-poppins shrink-0">
    <!-- Undo / Redo -->
    <div class="flex items-center">
      <button
        type="button"
        title="Undo (Ctrl+Z)"
        :disabled="!props.state.canUndo"
        @click="emit('undo')"
        class="w-6.5 h-6.5 flex items-center justify-center rounded hover:bg-black/5 disabled:opacity-30 disabled:hover:bg-transparent text-black/70 transition"
      >
        <Undo2 class="w-3.5 h-3.5" />
      </button>
      <button
        type="button"
        title="Redo (Ctrl+Y)"
        :disabled="!props.state.canRedo"
        @click="emit('redo')"
        class="w-6.5 h-6.5 flex items-center justify-center rounded hover:bg-black/5 disabled:opacity-30 disabled:hover:bg-transparent text-black/70 transition"
      >
        <Redo2 class="w-3.5 h-3.5" />
      </button>
    </div>

    <div class="h-4 w-px bg-black/10 mx-0.5" />

    <!-- Style / Heading Dropdown -->
    <div class="relative">
      <select
        :value="props.state.blockType"
        @change="(e) => emit('format-block', (e.target as HTMLSelectElement).value as any)"
        class="h-6.5 pl-1.5 pr-5 text-xs font-medium rounded border border-transparent hover:border-black/10 hover:bg-black/[0.03] bg-transparent text-black/80 focus:outline-none cursor-pointer appearance-none max-w-[95px] truncate"
      >
        <option value="paragraph">Normal</option>
        <option value="h1">Heading 1</option>
        <option value="h2">Heading 2</option>
        <option value="h3">Heading 3</option>
      </select>
      <ChevronDown class="w-3 h-3 text-black/40 pointer-events-none absolute right-1 top-2" />
    </div>

    <!-- Font Family Dropdown -->
    <div class="relative">
      <select
        :value="props.state.fontFamily"
        @change="(e) => emit('set-font-family', (e.target as HTMLSelectElement).value)"
        class="h-6.5 pl-1.5 pr-5 text-xs font-medium rounded border border-transparent hover:border-black/10 hover:bg-black/[0.03] bg-transparent text-black/80 focus:outline-none cursor-pointer appearance-none max-w-[105px] truncate"
      >
        <option v-for="font in fontFamilies" :key="font.name" :value="font.name">
          {{ font.name }}
        </option>
      </select>
      <ChevronDown class="w-3 h-3 text-black/40 pointer-events-none absolute right-1 top-2" />
    </div>

    <div class="h-4 w-px bg-black/10 mx-0.5" />

    <!-- Font Size Controls -->
    <div class="flex items-center gap-0.5">
      <button
        type="button"
        title="Decrease font size"
        @click="stepFontSize(-1)"
        class="w-5.5 h-6.5 flex items-center justify-center rounded hover:bg-black/5 text-xs font-bold text-black/70"
      >
        -
      </button>
      <span class="w-7 text-center text-xs font-medium text-black/80 select-none">
        {{ props.state.fontSize.replace('pt', '') }}
      </span>
      <button
        type="button"
        title="Increase font size"
        @click="stepFontSize(1)"
        class="w-5.5 h-6.5 flex items-center justify-center rounded hover:bg-black/5 text-xs font-bold text-black/70"
      >
        +
      </button>
    </div>

    <div class="h-4 w-px bg-black/10 mx-0.5" />

    <!-- Inline text formats -->
    <div class="flex items-center gap-0.5">
      <button
        type="button"
        title="Bold (Ctrl+B)"
        :class="props.state.isBold ? 'bg-brand-blue/15 text-brand-navy font-bold' : 'hover:bg-black/5 text-black/70'"
        @click="emit('format-text', 'bold')"
        class="w-6.5 h-6.5 flex items-center justify-center rounded transition"
      >
        <Bold class="w-3.5 h-3.5" />
      </button>

      <button
        type="button"
        title="Italic (Ctrl+I)"
        :class="props.state.isItalic ? 'bg-brand-blue/15 text-brand-navy' : 'hover:bg-black/5 text-black/70'"
        @click="emit('format-text', 'italic')"
        class="w-6.5 h-6.5 flex items-center justify-center rounded transition"
      >
        <Italic class="w-3.5 h-3.5" />
      </button>

      <button
        type="button"
        title="Underline (Ctrl+U)"
        :class="props.state.isUnderline ? 'bg-brand-blue/15 text-brand-navy' : 'hover:bg-black/5 text-black/70'"
        @click="emit('format-text', 'underline')"
        class="w-6.5 h-6.5 flex items-center justify-center rounded transition"
      >
        <Underline class="w-3.5 h-3.5" />
      </button>

      <button
        type="button"
        title="Strikethrough"
        :class="props.state.isStrikethrough ? 'bg-brand-blue/15 text-brand-navy' : 'hover:bg-black/5 text-black/70'"
        @click="emit('format-text', 'strikethrough')"
        class="w-6.5 h-6.5 flex items-center justify-center rounded transition"
      >
        <Strikethrough class="w-3.5 h-3.5" />
      </button>
    </div>

    <div class="h-4 w-px bg-black/10 mx-0.5" />

    <!-- Text Color & Highlight -->
    <div class="flex items-center gap-0.5 relative">
      <!-- Text Color Button -->
      <div class="relative">
        <button
          type="button"
          title="Text color"
          @click="showColorPicker = !showColorPicker; showHighlightPicker = false; showTablePicker = false"
          class="h-6.5 px-1.5 flex items-center gap-1 rounded hover:bg-black/5 text-black/70 transition"
        >
          <Baseline class="w-3.5 h-3.5" />
          <span class="w-3 h-1 rounded-sm" :style="{ backgroundColor: props.state.fontColor || '#000000' }"></span>
        </button>

        <div
          v-if="showColorPicker"
          class="absolute left-0 top-full mt-1 bg-white rounded-lg shadow-lg border border-black/10 p-2 z-50 flex gap-1.5"
        >
          <button
            v-for="c in brandColors"
            :key="c.hex"
            :title="c.label"
            type="button"
            @click="emit('set-font-color', c.hex); showColorPicker = false"
            class="w-5 h-5 rounded-full border border-black/15 transition transform hover:scale-110"
            :style="{ backgroundColor: c.hex }"
          />
        </div>
      </div>

      <!-- Highlight Color Button -->
      <div class="relative">
        <button
          type="button"
          title="Highlight color"
          @click="showHighlightPicker = !showHighlightPicker; showColorPicker = false; showTablePicker = false"
          class="h-6.5 px-1.5 flex items-center gap-1 rounded hover:bg-black/5 text-black/70 transition"
        >
          <Highlighter class="w-3.5 h-3.5" />
          <span
            class="w-3 h-1 rounded-sm border border-black/10"
            :style="{ backgroundColor: props.state.highlightColor !== 'transparent' ? props.state.highlightColor : '#FEF08A' }"
          ></span>
        </button>

        <div
          v-if="showHighlightPicker"
          class="absolute left-0 top-full mt-1 bg-white rounded-lg shadow-lg border border-black/10 p-2 z-50 flex gap-1.5"
        >
          <button
            v-for="h in highlightColors"
            :key="h.hex"
            :title="h.label"
            type="button"
            @click="emit('set-highlight', h.hex); showHighlightPicker = false"
            class="w-5 h-5 rounded-full border border-black/15 transition transform hover:scale-110 flex items-center justify-center text-[10px]"
            :style="{ backgroundColor: h.hex === 'transparent' ? '#ffffff' : h.hex }"
          >
            <span v-if="h.hex === 'transparent'" class="text-red-500 font-bold">/</span>
          </button>
        </div>
      </div>
    </div>

    <div class="h-4 w-px bg-black/10 mx-0.5" />

    <!-- Alignments -->
    <div class="flex items-center gap-0.5">
      <button
        type="button"
        title="Align left"
        :class="props.state.align === 'left' ? 'bg-brand-blue/15 text-brand-navy' : 'hover:bg-black/5 text-black/70'"
        @click="emit('format-align', 'left')"
        class="w-6.5 h-6.5 flex items-center justify-center rounded transition"
      >
        <AlignLeft class="w-3.5 h-3.5" />
      </button>

      <button
        type="button"
        title="Align center"
        :class="props.state.align === 'center' ? 'bg-brand-blue/15 text-brand-navy' : 'hover:bg-black/5 text-black/70'"
        @click="emit('format-align', 'center')"
        class="w-6.5 h-6.5 flex items-center justify-center rounded transition"
      >
        <AlignCenter class="w-3.5 h-3.5" />
      </button>

      <button
        type="button"
        title="Align right"
        :class="props.state.align === 'right' ? 'bg-brand-blue/15 text-brand-navy' : 'hover:bg-black/5 text-black/70'"
        @click="emit('format-align', 'right')"
        class="w-6.5 h-6.5 flex items-center justify-center rounded transition"
      >
        <AlignRight class="w-3.5 h-3.5" />
      </button>

      <button
        type="button"
        title="Justify"
        :class="props.state.align === 'justify' ? 'bg-brand-blue/15 text-brand-navy' : 'hover:bg-black/5 text-black/70'"
        @click="emit('format-align', 'justify')"
        class="w-6.5 h-6.5 flex items-center justify-center rounded transition"
      >
        <AlignJustify class="w-3.5 h-3.5" />
      </button>
    </div>

    <div class="h-4 w-px bg-black/10 mx-0.5" />

    <!-- Lists -->
    <div class="flex items-center gap-0.5">
      <button
        type="button"
        title="Bulleted list"
        :class="props.state.blockType === 'bullet' ? 'bg-brand-blue/15 text-brand-navy' : 'hover:bg-black/5 text-black/70'"
        @click="emit('format-list', 'bullet')"
        class="w-6.5 h-6.5 flex items-center justify-center rounded transition"
      >
        <List class="w-3.5 h-3.5" />
      </button>

      <button
        type="button"
        title="Numbered list"
        :class="props.state.blockType === 'number' ? 'bg-brand-blue/15 text-brand-navy' : 'hover:bg-black/5 text-black/70'"
        @click="emit('format-list', 'number')"
        class="w-6.5 h-6.5 flex items-center justify-center rounded transition"
      >
        <ListOrdered class="w-3.5 h-3.5" />
      </button>
    </div>

    <div class="h-4 w-px bg-black/10 mx-0.5" />

    <!-- Insert Table Popover -->
    <div class="relative">
      <button
        type="button"
        title="Insert Table"
        @click="showTablePicker = !showTablePicker; showColorPicker = false; showHighlightPicker = false"
        class="h-6.5 px-1.5 flex items-center gap-1 rounded hover:bg-black/5 text-xs font-medium text-black/70 transition"
      >
        <TableIcon class="w-3.5 h-3.5" />
        <span>Table</span>
        <ChevronDown class="w-3 h-3 text-black/40" />
      </button>

      <div
        v-if="showTablePicker"
        class="absolute left-0 top-full mt-1 bg-white rounded-lg shadow-xl border border-black/10 p-3 z-50 w-52 flex flex-col gap-2.5 font-poppins text-xs"
      >
        <div class="font-semibold text-black/70">Insert Table</div>
        <div class="grid grid-cols-2 gap-1.5">
          <button
            type="button"
            @click="handleInsertTable(2, 2)"
            class="px-2 py-1.5 border border-black/10 rounded hover:bg-brand-blue/10 hover:border-brand-blue text-left transition font-medium"
          >
            2 × 2 Grid
          </button>
          <button
            type="button"
            @click="handleInsertTable(3, 3)"
            class="px-2 py-1.5 border border-black/10 rounded hover:bg-brand-blue/10 hover:border-brand-blue text-left transition font-medium"
          >
            3 × 3 Grid
          </button>
          <button
            type="button"
            @click="handleInsertTable(4, 3)"
            class="px-2 py-1.5 border border-black/10 rounded hover:bg-brand-blue/10 hover:border-brand-blue text-left transition font-medium"
          >
            4 × 3 Table
          </button>
          <button
            type="button"
            @click="handleInsertTable(2, 4)"
            class="px-2 py-1.5 border border-black/10 rounded hover:bg-brand-blue/10 hover:border-brand-blue text-left transition font-medium"
          >
            2 × 4 Table
          </button>
        </div>

        <div class="border-t border-black/8 pt-2 flex items-center justify-between gap-1">
          <div class="flex items-center gap-1 text-[11px] text-black/60">
            <input v-model.number="tableRows" type="number" min="1" max="10" class="w-10 h-6 border rounded px-1 text-center" />
            <span>×</span>
            <input v-model.number="tableCols" type="number" min="1" max="10" class="w-10 h-6 border rounded px-1 text-center" />
          </div>
          <button
            type="button"
            @click="handleInsertTable(tableRows || 3, tableCols || 3)"
            class="px-2 py-1 bg-brand-navy hover:bg-brand-dark text-white rounded font-medium text-[11px]"
          >
            Insert
          </button>
        </div>
      </div>
    </div>

    <div class="h-4 w-px bg-black/10 mx-0.5" />

    <!-- Insert Page Break -->
    <button
      type="button"
      title="Insert Page Break (Ctrl+Enter)"
      @click="emit('insert-page-break')"
      class="h-6.5 px-1.5 flex items-center gap-1 rounded hover:bg-black/5 text-xs font-medium text-black/70 transition"
    >
      <SeparatorHorizontal class="w-3.5 h-3.5 text-black/60" />
      <span>Page Break</span>
    </button>

    <div class="h-4 w-px bg-black/10 mx-0.5" />

    <!-- Header & Footer Settings Popover -->
    <div class="relative">
      <button
        type="button"
        title="Customize Header & Footer across all pages"
        @click="showHeaderFooterModal = !showHeaderFooterModal; showTablePicker = false; showColorPicker = false; showHighlightPicker = false"
        class="h-6.5 px-1.5 flex items-center gap-1 rounded hover:bg-black/5 text-xs font-medium text-black/70 transition border border-transparent"
        :class="{ 'bg-brand-blue/15 text-brand-navy border-brand-blue/30': showHeaderFooterModal }"
      >
        <PanelTop class="w-3.5 h-3.5 text-brand-navy" />
        <span>Header &amp; Footer</span>
      </button>

      <!-- Dropdown Panel -->
      <div
        v-if="showHeaderFooterModal"
        class="absolute right-0 sm:left-0 top-full mt-1 bg-white rounded-xl shadow-2xl border border-black/10 p-3.5 z-50 w-72 flex flex-col gap-2.5 font-poppins text-xs"
      >
        <div class="flex items-center justify-between pb-1.5 border-b border-black/8 font-semibold text-black/80">
          <div class="flex items-center gap-1.5">
            <PanelTop class="w-4 h-4 text-brand-navy" />
            <span>Page Header &amp; Footer</span>
          </div>
          <button @click="showHeaderFooterModal = false" class="text-black/40 hover:text-black text-xs font-bold">✕</button>
        </div>

        <div class="space-y-2">
          <div>
            <label class="block text-[11px] font-semibold text-black/60 mb-0.5">Header Left (Top)</label>
            <input
              :value="props.headerFooter.headerLeft"
              @input="(e) => emit('update:headerFooter', { ...props.headerFooter, headerLeft: (e.target as HTMLInputElement).value })"
              type="text"
              placeholder="e.g. Confidential Agreement"
              class="w-full h-6.5 px-2 border rounded text-xs focus:outline-none focus:ring-1 focus:ring-brand-blue"
            />
          </div>

          <div>
            <label class="block text-[11px] font-semibold text-black/60 mb-0.5">Header Right (Top)</label>
            <input
              :value="props.headerFooter.headerRight"
              @input="(e) => emit('update:headerFooter', { ...props.headerFooter, headerRight: (e.target as HTMLInputElement).value })"
              type="text"
              placeholder="e.g. SBSI CRMS"
              class="w-full h-6.5 px-2 border rounded text-xs focus:outline-none focus:ring-1 focus:ring-brand-blue"
            />
          </div>

          <div class="pt-1 border-t border-black/6">
            <label class="block text-[11px] font-semibold text-black/60 mb-0.5">Footer Left (Bottom)</label>
            <input
              :value="props.headerFooter.footerLeft"
              @input="(e) => emit('update:headerFooter', { ...props.headerFooter, footerLeft: (e.target as HTMLInputElement).value })"
              type="text"
              placeholder="e.g. Authorized Legal Draft"
              class="w-full h-6.5 px-2 border rounded text-xs focus:outline-none focus:ring-1 focus:ring-brand-blue"
            />
          </div>

          <div class="flex items-center justify-between pt-1">
            <label class="text-[11px] font-semibold text-black/60">Show Page Numbers</label>
            <input
              type="checkbox"
              :checked="props.headerFooter.showPageNumber"
              @change="(e) => emit('update:headerFooter', { ...props.headerFooter, showPageNumber: (e.target as HTMLInputElement).checked })"
              class="rounded text-brand-navy focus:ring-brand-blue w-3.5 h-3.5 cursor-pointer"
            />
          </div>

          <div class="pt-1.5 flex justify-end">
            <button
              type="button"
              @click="showHeaderFooterModal = false"
              class="px-2.5 py-1 bg-brand-navy hover:bg-brand-dark text-white rounded font-medium text-[11px] transition"
            >
              Done
            </button>
          </div>
        </div>
      </div>
    </div>

    <div class="h-4 w-px bg-black/10 mx-0.5" />

    <!-- Paper Size & Orientation Selector -->
    <div class="flex items-center gap-1">
      <div class="relative flex items-center">
        <FileText class="w-3.5 h-3.5 text-black/40 mr-1" />
        <select
          :value="props.state.paperSize"
          @change="(e) => emit('set-paper-size', (e.target as HTMLSelectElement).value as any)"
          class="h-6.5 pl-1 pr-4 text-xs font-medium rounded border border-transparent hover:border-black/10 hover:bg-black/[0.03] bg-transparent text-black/80 focus:outline-none cursor-pointer appearance-none"
        >
          <option value="letter">Letter</option>
          <option value="a4">A4</option>
          <option value="legal">Legal</option>
        </select>
        <ChevronDown class="w-3 h-3 text-black/40 pointer-events-none absolute right-0.5 top-2" />
      </div>

      <button
        type="button"
        :title="props.state.orientation === 'portrait' ? 'Switch to Landscape' : 'Switch to Portrait'"
        @click="emit('set-orientation', props.state.orientation === 'portrait' ? 'landscape' : 'portrait')"
        class="h-6.5 px-1.5 flex items-center gap-1 text-xs font-medium rounded hover:bg-black/5 text-black/70 border border-transparent hover:border-black/10 transition"
      >
        <RotateCw class="w-3 h-3 text-black/50" />
        <span class="capitalize text-[11px]">{{ props.state.orientation }}</span>
      </button>
    </div>
  </div>
</template>
