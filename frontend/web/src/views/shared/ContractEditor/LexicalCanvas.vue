<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'

const props = defineProps<{
  zoom?: number
}>()

const emit = defineEmits<{
  'editor-mount': [element: HTMLElement]
  'editor-unmount': []
}>()

const editorContentRef = ref<HTMLDivElement | null>(null)

onMounted(() => {
  if (editorContentRef.value) {
    emit('editor-mount', editorContentRef.value)
  }
})

onUnmounted(() => {
  emit('editor-unmount')
})
</script>

<template>
  <div class="w-full flex-1 overflow-y-auto bg-[#F1F3F5] py-8 px-4 flex justify-center">
    <!-- Paper Sheet (US Letter / A4 Proportions) -->
    <div
      class="w-full max-w-[820px] min-h-[1080px] bg-white rounded-[2px] shadow-[0_1px_4px_rgba(0,0,0,0.08),0_4px_16px_rgba(0,0,0,0.06)] border border-black/[0.06] p-14 md:p-16 relative flex flex-col font-poppins transition-transform origin-top"
      :style="{ transform: props.zoom ? `scale(${props.zoom})` : 'none' }"
    >
      <!-- ContentEditable Lexical Container -->
      <div
        ref="editorContentRef"
        contenteditable="true"
        spellcheck="true"
        class="flex-1 outline-none focus:outline-none min-h-[850px] text-black/90 selection:bg-brand-blue/25 selection:text-brand-navy"
      />
    </div>
  </div>
</template>

<style>
/* Scoped custom typography for the generated contract */
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
</style>
