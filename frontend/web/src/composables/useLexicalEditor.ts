import { ref, reactive, shallowRef } from 'vue'
import {
  createEditor,
  $getRoot,
  $getSelection,
  $isRangeSelection,
  $createParagraphNode,
  $isElementNode,
  $isTextNode,
  FORMAT_TEXT_COMMAND,
  FORMAT_ELEMENT_COMMAND,
  UNDO_COMMAND,
  REDO_COMMAND,
  COMMAND_PRIORITY_CRITICAL,
  CAN_UNDO_COMMAND,
  CAN_REDO_COMMAND,
  SELECTION_CHANGE_COMMAND,
  type LexicalEditor,
  type ElementFormatType,
  type TextFormatType,
} from 'lexical'
import {
  registerRichText,
  HeadingNode,
  QuoteNode,
  $createHeadingNode,
  $isHeadingNode,
} from '@lexical/rich-text'
import {
  registerHistory,
  createEmptyHistoryState,
} from '@lexical/history'
import {
  registerList,
  ListNode,
  ListItemNode,
  INSERT_UNORDERED_LIST_COMMAND,
  INSERT_ORDERED_LIST_COMMAND,
  REMOVE_LIST_COMMAND,
  $isListNode,
} from '@lexical/list'
import {
  $patchStyleText,
  $getSelectionStyleValueForProperty,
  $setBlocksType,
} from '@lexical/selection'
import {
  $generateHtmlFromNodes,
  $generateNodesFromDOM,
} from '@lexical/html'

export interface EditorToolbarState {
  isBold: boolean
  isItalic: boolean
  isUnderline: boolean
  isStrikethrough: boolean
  canUndo: boolean
  canRedo: boolean
  fontFamily: string
  fontSize: string
  fontColor: string
  highlightColor: string
  blockType: 'paragraph' | 'h1' | 'h2' | 'h3' | 'bullet' | 'number'
  align: ElementFormatType
}

export function useLexicalEditor() {
  const editorRef = shallowRef<LexicalEditor | null>(null)
  const isReady = ref(false)

  const state = reactive<EditorToolbarState>({
    isBold: false,
    isItalic: false,
    isUnderline: false,
    isStrikethrough: false,
    canUndo: false,
    canRedo: false,
    fontFamily: 'Poppins',
    fontSize: '11pt',
    fontColor: '#000000',
    highlightColor: 'transparent',
    blockType: 'paragraph',
    align: 'left',
  })

  let cleanups: (() => void)[] = []

  function initEditor() {
    const editor = createEditor({
      namespace: 'CRMSContractEditor',
      theme: {
        paragraph: 'editor-p mb-3 text-[14px] leading-relaxed text-black/90 font-normal',
        heading: {
          h1: 'editor-h1 text-2xl font-bold text-black mb-3 mt-4 text-center tracking-tight',
          h2: 'editor-h2 text-lg font-bold text-black mb-2 mt-5 border-b border-black/10 pb-1',
          h3: 'editor-h3 text-base font-semibold text-black mb-2 mt-4',
        },
        list: {
          ul: 'editor-ul list-disc list-inside mb-4 pl-4 space-y-1 text-[14px] text-black/90',
          ol: 'editor-ol list-decimal list-inside mb-4 pl-4 space-y-1 text-[14px] text-black/90',
          listitem: 'editor-li leading-relaxed',
        },
        quote: 'editor-quote border-l-4 border-brand-blue pl-4 py-1 italic my-3 text-black/75 bg-blue-50/30 rounded-r',
        text: {
          bold: 'font-bold',
          italic: 'italic',
          underline: 'underline underline-offset-2',
          strikethrough: 'line-through',
          underlineStrikethrough: 'underline line-through',
        },
      },
      nodes: [
        HeadingNode,
        QuoteNode,
        ListNode,
        ListItemNode,
      ],
      onError: (err) => console.error('Lexical Error:', err),
    })

    editorRef.value = editor
    return editor
  }

  function updateToolbarFromSelection() {
    const editor = editorRef.value
    if (!editor) return

    editor.getEditorState().read(() => {
      const selection = $getSelection()
      if (!$isRangeSelection(selection)) return

      // Text formats
      state.isBold = selection.hasFormat('bold')
      state.isItalic = selection.hasFormat('italic')
      state.isUnderline = selection.hasFormat('underline')
      state.isStrikethrough = selection.hasFormat('strikethrough')

      // Inline styles
      const fontFam = $getSelectionStyleValueForProperty(selection, 'font-family', 'Poppins')
      state.fontFamily = fontFam.replace(/['"]/g, '') || 'Poppins'

      const fontSz = $getSelectionStyleValueForProperty(selection, 'font-size', '11pt')
      state.fontSize = fontSz || '11pt'

      state.fontColor = $getSelectionStyleValueForProperty(selection, 'color', '#000000') || '#000000'
      state.highlightColor = $getSelectionStyleValueForProperty(selection, 'background-color', 'transparent') || 'transparent'

      // Block formatting & alignment
      const anchorNode = selection.anchor.getNode()
      const element =
        anchorNode.getKey() === 'root'
          ? anchorNode
          : anchorNode.getTopLevelElementOrThrow()

      if ($isHeadingNode(element)) {
        const tag = element.getTag()
        state.blockType = tag as 'h1' | 'h2' | 'h3'
      } else if ($isListNode(element)) {
        const listType = element.getListType()
        state.blockType = listType === 'number' ? 'number' : 'bullet'
      } else {
        state.blockType = 'paragraph'
      }

      state.align = (element as any).getFormatType?.() || 'left'
    })
  }

  function mount(rootElement: HTMLElement) {
    if (!editorRef.value) {
      initEditor()
    }
    const editor = editorRef.value!
    editor.setRootElement(rootElement)

    const unregisterRichText = registerRichText(editor)
    const historyState = createEmptyHistoryState()
    const unregisterHistory = registerHistory(editor, historyState, 300)
    const unregisterList = registerList(editor)

    const unregisterUpdateListener = editor.registerUpdateListener(({ editorState }) => {
      editorState.read(() => {
        updateToolbarFromSelection()
      })
    })

    const unregisterSelectionListener = editor.registerCommand(
      SELECTION_CHANGE_COMMAND,
      () => {
        updateToolbarFromSelection()
        return false
      },
      COMMAND_PRIORITY_CRITICAL
    )

    const unregisterCanUndo = editor.registerCommand(
      CAN_UNDO_COMMAND,
      (payload: boolean) => {
        state.canUndo = payload
        return false
      },
      COMMAND_PRIORITY_CRITICAL
    )

    const unregisterCanRedo = editor.registerCommand(
      CAN_REDO_COMMAND,
      (payload: boolean) => {
        state.canRedo = payload
        return false
      },
      COMMAND_PRIORITY_CRITICAL
    )

    cleanups = [
      unregisterRichText,
      unregisterHistory,
      unregisterList,
      unregisterUpdateListener,
      unregisterSelectionListener,
      unregisterCanUndo,
      unregisterCanRedo,
    ]

    isReady.value = true
  }

  function unmount() {
    cleanups.forEach((fn) => fn())
    cleanups = []
    if (editorRef.value) {
      editorRef.value.setRootElement(null)
    }
    isReady.value = false
  }

  function formatText(format: TextFormatType) {
    editorRef.value?.dispatchCommand(FORMAT_TEXT_COMMAND, format)
  }

  function formatAlign(align: ElementFormatType) {
    editorRef.value?.dispatchCommand(FORMAT_ELEMENT_COMMAND, align)
    state.align = align
  }

  function formatBlock(type: 'paragraph' | 'h1' | 'h2' | 'h3') {
    const editor = editorRef.value
    if (!editor) return

    editor.update(() => {
      const selection = $getSelection()
      if ($isRangeSelection(selection)) {
        if (type === 'paragraph') {
          $setBlocksType(selection, () => $createParagraphNode())
        } else {
          $setBlocksType(selection, () => $createHeadingNode(type))
        }
      }
    })
    state.blockType = type
  }

  function formatList(type: 'bullet' | 'number') {
    const editor = editorRef.value
    if (!editor) return

    if (state.blockType === type) {
      editor.dispatchCommand(REMOVE_LIST_COMMAND, undefined)
      state.blockType = 'paragraph'
    } else if (type === 'bullet') {
      editor.dispatchCommand(INSERT_UNORDERED_LIST_COMMAND, undefined)
      state.blockType = 'bullet'
    } else {
      editor.dispatchCommand(INSERT_ORDERED_LIST_COMMAND, undefined)
      state.blockType = 'number'
    }
  }

  function setFontFamily(font: string) {
    const editor = editorRef.value
    if (!editor) return

    editor.update(() => {
      const selection = $getSelection()
      if ($isRangeSelection(selection)) {
        $patchStyleText(selection, { 'font-family': font })
      }
    })
    state.fontFamily = font
  }

  function setFontSize(size: string) {
    const editor = editorRef.value
    if (!editor) return

    editor.update(() => {
      const selection = $getSelection()
      if ($isRangeSelection(selection)) {
        $patchStyleText(selection, { 'font-size': size })
      }
    })
    state.fontSize = size
  }

  function setFontColor(color: string) {
    const editor = editorRef.value
    if (!editor) return

    editor.update(() => {
      const selection = $getSelection()
      if ($isRangeSelection(selection)) {
        $patchStyleText(selection, { color })
      }
    })
    state.fontColor = color
  }

  function setHighlight(color: string) {
    const editor = editorRef.value
    if (!editor) return

    editor.update(() => {
      const selection = $getSelection()
      if ($isRangeSelection(selection)) {
        $patchStyleText(selection, { 'background-color': color })
      }
    })
    state.highlightColor = color
  }

  function undo() {
    editorRef.value?.dispatchCommand(UNDO_COMMAND, undefined)
  }

  function redo() {
    editorRef.value?.dispatchCommand(REDO_COMMAND, undefined)
  }

  function getHtml(): string {
    const editor = editorRef.value
    if (!editor) return ''
    let html = ''
    editor.getEditorState().read(() => {
      html = $generateHtmlFromNodes(editor, null)
    })
    return html
  }

  function setHtml(html: string) {
    const editor = editorRef.value
    if (!editor) return

    editor.update(() => {
      const parser = new DOMParser()
      const dom = parser.parseFromString(html, 'text/html')
      const nodes = $generateNodesFromDOM(editor, dom)
      const root = $getRoot()
      root.clear()

      for (const node of nodes) {
        if ($isElementNode(node)) {
          root.append(node)
        } else if ($isTextNode(node)) {
          const text = node.getTextContent().trim()
          if (text) {
            const p = $createParagraphNode()
            p.append(node)
            root.append(p)
          }
        }
      }

      if (root.getChildrenSize() === 0) {
        root.append($createParagraphNode())
      }
    })
  }

  return {
    editor: editorRef,
    isReady,
    state,
    mount,
    unmount,
    formatText,
    formatAlign,
    formatBlock,
    formatList,
    setFontFamily,
    setFontSize,
    setFontColor,
    setHighlight,
    undo,
    redo,
    getHtml,
    setHtml,
  }
}
