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
} from 'lucide-vue-next'
import type { EditorToolbarState } from '@/composables/useLexicalEditor'

const props = defineProps<{
  state: EditorToolbarState
}>()

const emit = defineEmits<{
  'format-text': [format: 'bold' | 'italic' | 'underline' | 'strikethrough']
  'format-align': [align: 'left' | 'center' | 'right' | 'justify']
  'format-block': [type: 'paragraph' | 'h1' | 'h2' | 'h3']
  'format-list': [type: 'bullet' | 'number']
  'set-font-family': [family: string]
  'set-font-size': [size: string]
  'set-font-color': [color: string]
  'set-highlight': [color: string]
  'undo': []
  'redo': []
}>()

const showColorPicker = ref(false)
const showHighlightPicker = ref(false)

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
</script>

<template>
  <div class="h-11 bg-white border-b border-black/8 px-4 flex items-center gap-1.5 overflow-x-auto select-none font-poppins shrink-0">
    <!-- Undo / Redo -->
    <div class="flex items-center">
      <button
        type="button"
        title="Undo (Ctrl+Z)"
        :disabled="!props.state.canUndo"
        @click="emit('undo')"
        class="w-7 h-7 flex items-center justify-center rounded hover:bg-black/5 disabled:opacity-30 disabled:hover:bg-transparent text-black/70 transition"
      >
        <Undo2 class="w-4 h-4" />
      </button>
      <button
        type="button"
        title="Redo (Ctrl+Y)"
        :disabled="!props.state.canRedo"
        @click="emit('redo')"
        class="w-7 h-7 flex items-center justify-center rounded hover:bg-black/5 disabled:opacity-30 disabled:hover:bg-transparent text-black/70 transition"
      >
        <Redo2 class="w-4 h-4" />
      </button>
    </div>

    <div class="h-4 w-px bg-black/10 mx-1" />

    <!-- Style / Heading Dropdown -->
    <div class="relative">
      <select
        :value="props.state.blockType"
        @change="(e) => emit('format-block', (e.target as HTMLSelectElement).value as any)"
        class="h-7 pl-2 pr-6 text-xs font-medium rounded border border-transparent hover:border-black/10 hover:bg-black/[0.03] bg-transparent text-black/80 focus:outline-none cursor-pointer appearance-none"
      >
        <option value="paragraph">Normal text</option>
        <option value="h1">Title / Heading 1</option>
        <option value="h2">Heading 2</option>
        <option value="h3">Heading 3</option>
      </select>
      <ChevronDown class="w-3 h-3 text-black/40 pointer-events-none absolute right-1.5 top-2" />
    </div>

    <!-- Font Family Dropdown -->
    <div class="relative">
      <select
        :value="props.state.fontFamily"
        @change="(e) => emit('set-font-family', (e.target as HTMLSelectElement).value)"
        class="h-7 pl-2 pr-6 text-xs font-medium rounded border border-transparent hover:border-black/10 hover:bg-black/[0.03] bg-transparent text-black/80 focus:outline-none cursor-pointer appearance-none max-w-[130px] truncate"
      >
        <option v-for="font in fontFamilies" :key="font.name" :value="font.name">
          {{ font.label }}
        </option>
      </select>
      <ChevronDown class="w-3 h-3 text-black/40 pointer-events-none absolute right-1.5 top-2" />
    </div>

    <div class="h-4 w-px bg-black/10 mx-1" />

    <!-- Font Size Controls -->
    <div class="flex items-center gap-0.5">
      <button
        type="button"
        title="Decrease font size"
        @click="stepFontSize(-1)"
        class="w-6 h-7 flex items-center justify-center rounded hover:bg-black/5 text-xs font-bold text-black/70"
      >
        -
      </button>
      <span class="w-9 text-center text-xs font-medium text-black/80 select-none">
        {{ props.state.fontSize.replace('pt', '') }}
      </span>
      <button
        type="button"
        title="Increase font size"
        @click="stepFontSize(1)"
        class="w-6 h-7 flex items-center justify-center rounded hover:bg-black/5 text-xs font-bold text-black/70"
      >
        +
      </button>
    </div>

    <div class="h-4 w-px bg-black/10 mx-1" />

    <!-- Inline text formats -->
    <div class="flex items-center gap-0.5">
      <button
        type="button"
        title="Bold (Ctrl+B)"
        :class="props.state.isBold ? 'bg-brand-blue/15 text-brand-navy font-bold' : 'hover:bg-black/5 text-black/70'"
        @click="emit('format-text', 'bold')"
        class="w-7 h-7 flex items-center justify-center rounded transition"
      >
        <Bold class="w-4 h-4" />
      </button>

      <button
        type="button"
        title="Italic (Ctrl+I)"
        :class="props.state.isItalic ? 'bg-brand-blue/15 text-brand-navy' : 'hover:bg-black/5 text-black/70'"
        @click="emit('format-text', 'italic')"
        class="w-7 h-7 flex items-center justify-center rounded transition"
      >
        <Italic class="w-4 h-4" />
      </button>

      <button
        type="button"
        title="Underline (Ctrl+U)"
        :class="props.state.isUnderline ? 'bg-brand-blue/15 text-brand-navy' : 'hover:bg-black/5 text-black/70'"
        @click="emit('format-text', 'underline')"
        class="w-7 h-7 flex items-center justify-center rounded transition"
      >
        <Underline class="w-4 h-4" />
      </button>

      <button
        type="button"
        title="Strikethrough"
        :class="props.state.isStrikethrough ? 'bg-brand-blue/15 text-brand-navy' : 'hover:bg-black/5 text-black/70'"
        @click="emit('format-text', 'strikethrough')"
        class="w-7 h-7 flex items-center justify-center rounded transition"
      >
        <Strikethrough class="w-4 h-4" />
      </button>
    </div>

    <div class="h-4 w-px bg-black/10 mx-1" />

    <!-- Text Color & Highlight -->
    <div class="flex items-center gap-1 relative">
      <!-- Text Color Button -->
      <div class="relative">
        <button
          type="button"
          title="Text color"
          @click="showColorPicker = !showColorPicker; showHighlightPicker = false"
          class="h-7 px-1.5 flex items-center gap-1 rounded hover:bg-black/5 text-black/70 transition"
        >
          <Baseline class="w-4 h-4" />
          <span class="w-3.5 h-1 rounded-sm" :style="{ backgroundColor: props.state.fontColor || '#000000' }"></span>
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
          @click="showHighlightPicker = !showHighlightPicker; showColorPicker = false"
          class="h-7 px-1.5 flex items-center gap-1 rounded hover:bg-black/5 text-black/70 transition"
        >
          <Highlighter class="w-4 h-4" />
          <span
            class="w-3.5 h-1 rounded-sm border border-black/10"
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

    <div class="h-4 w-px bg-black/10 mx-1" />

    <!-- Alignments -->
    <div class="flex items-center gap-0.5">
      <button
        type="button"
        title="Align left"
        :class="props.state.align === 'left' ? 'bg-brand-blue/15 text-brand-navy' : 'hover:bg-black/5 text-black/70'"
        @click="emit('format-align', 'left')"
        class="w-7 h-7 flex items-center justify-center rounded transition"
      >
        <AlignLeft class="w-4 h-4" />
      </button>

      <button
        type="button"
        title="Align center"
        :class="props.state.align === 'center' ? 'bg-brand-blue/15 text-brand-navy' : 'hover:bg-black/5 text-black/70'"
        @click="emit('format-align', 'center')"
        class="w-7 h-7 flex items-center justify-center rounded transition"
      >
        <AlignCenter class="w-4 h-4" />
      </button>

      <button
        type="button"
        title="Align right"
        :class="props.state.align === 'right' ? 'bg-brand-blue/15 text-brand-navy' : 'hover:bg-black/5 text-black/70'"
        @click="emit('format-align', 'right')"
        class="w-7 h-7 flex items-center justify-center rounded transition"
      >
        <AlignRight class="w-4 h-4" />
      </button>

      <button
        type="button"
        title="Justify"
        :class="props.state.align === 'justify' ? 'bg-brand-blue/15 text-brand-navy' : 'hover:bg-black/5 text-black/70'"
        @click="emit('format-align', 'justify')"
        class="w-7 h-7 flex items-center justify-center rounded transition"
      >
        <AlignJustify class="w-4 h-4" />
      </button>
    </div>

    <div class="h-4 w-px bg-black/10 mx-1" />

    <!-- Lists -->
    <div class="flex items-center gap-0.5">
      <button
        type="button"
        title="Bulleted list"
        :class="props.state.blockType === 'bullet' ? 'bg-brand-blue/15 text-brand-navy' : 'hover:bg-black/5 text-black/70'"
        @click="emit('format-list', 'bullet')"
        class="w-7 h-7 flex items-center justify-center rounded transition"
      >
        <List class="w-4 h-4" />
      </button>

      <button
        type="button"
        title="Numbered list"
        :class="props.state.blockType === 'number' ? 'bg-brand-blue/15 text-brand-navy' : 'hover:bg-black/5 text-black/70'"
        @click="emit('format-list', 'number')"
        class="w-7 h-7 flex items-center justify-center rounded transition"
      >
        <ListOrdered class="w-4 h-4" />
      </button>
    </div>
  </div>
</template>
