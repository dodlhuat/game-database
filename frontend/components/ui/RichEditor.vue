<template>
  <div ref="editorRoot" class="editor">
    <div class="editor-toolbar" role="toolbar" aria-label="Editor tools">
      <div class="toolbar-group">
        <button type="button" data-cmd="bold" title="Bold (Ctrl+B)">
          <span class="icon icon-format_bold" aria-hidden="true" />
        </button>
        <button type="button" data-cmd="italic" title="Italic (Ctrl+I)">
          <span class="icon icon-format_italic" aria-hidden="true" />
        </button>
        <button type="button" data-cmd="underline" title="Underline (Ctrl+U)">
          <span class="icon icon-format_underlined" aria-hidden="true" />
        </button>
        <button type="button" data-cmd="strikeThrough" title="Strikethrough">
          <span class="icon icon-strikethrough" aria-hidden="true" />
        </button>
      </div>

      <div class="toolbar-separator" />

      <div class="toolbar-group">
        <button type="button" data-cmd="formatBlock" data-value="h1" title="Heading 1">H1</button>
        <button type="button" data-cmd="formatBlock" data-value="h2" title="Heading 2">H2</button>
        <button type="button" data-cmd="formatBlock" data-value="p" title="Paragraph">
          <strong>P</strong>
        </button>
      </div>

      <div class="toolbar-separator" />

      <div class="toolbar-group">
        <button type="button" data-cmd="insertUnorderedList" title="Bullet list">
          <span class="icon icon-format_list_bulleted" aria-hidden="true" />
        </button>
        <button type="button" data-cmd="insertOrderedList" title="Numbered list">
          <span class="icon icon-format_list_numbered" aria-hidden="true" />
        </button>
      </div>

      <div class="toolbar-separator" />

      <div class="toolbar-group">
        <button type="button" data-editor-action="link" title="Insert link (Ctrl+K)">
          <span class="icon icon-add" aria-hidden="true" />
        </button>
        <button type="button" data-editor-action="image" title="Insert image" style="display: none">
          <span class="icon icon-add_photo_alternate" aria-hidden="true" />
        </button>
        <input type="file" data-editor="image-file" accept="image/*" hidden />
      </div>
    </div>

    <div class="editor-body">
      <div class="editor-main">
        <div
          data-editor="editable"
          class="editable"
          contenteditable="true"
          spellcheck="true"
          aria-label="Editing area"
        />
      </div>

      <!-- Side panel required by Editor class (hidden by default) -->
      <div data-editor="side-panel" class="editor-side">
        <div class="side-tabs">
          <button class="side-tab active" type="button" data-tab="code-panel">HTML</button>
          <button class="side-tab" type="button" data-tab="preview-panel">Vorschau</button>
        </div>
        <div class="side-panels">
          <div data-editor="code-panel" class="side-panel active">
            <textarea data-editor="code" />
            <div class="code-actions">
              <button type="button" data-editor-action="apply-code">Übernehmen</button>
              <button type="button" data-editor-action="sanitize-code">Bereinigen</button>
              <button type="button" data-editor-action="minify-code">Minify</button>
            </div>
          </div>
          <div data-editor="preview-panel" class="side-panel">
            <div data-editor="preview" class="preview-content" />
          </div>
        </div>
      </div>
    </div>

    <div class="editor-footer">
      <span data-editor="wordcount" class="editor-wordcount">0 words</span>
      <span class="editor-shortcuts">
        <kbd>Ctrl+B</kbd> Bold <kbd>Ctrl+K</kbd> Link <kbd>Ctrl+S</kbd> Save
      </span>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, watch } from 'vue'
import { Editor } from '@dodlhuat/basix/js/editor'

const props = defineProps<{ modelValue: string }>()
const emit = defineEmits<{ 'update:modelValue': [value: string] }>()

const editorRoot = ref<HTMLElement | null>(null)
let observer: MutationObserver | null = null
let ignoreNext = false
let editor: InstanceType<typeof Editor> | null = null

onMounted(() => {
  if (!editorRoot.value) return
  const editableEl = editorRoot.value.querySelector<HTMLElement>('[data-editor="editable"]')
  if (!editableEl) return

  if (props.modelValue) {
    editableEl.innerHTML = props.modelValue
  }

  editor = new Editor({ root: editorRoot.value })

  observer = new MutationObserver(() => {
    if (ignoreNext) {
      ignoreNext = false
      return
    }
    emit('update:modelValue', editableEl.innerHTML)
  })
  observer.observe(editableEl, { childList: true, subtree: true, characterData: true })
})

watch(
  () => props.modelValue,
  (val) => {
    const editableEl = editorRoot.value?.querySelector<HTMLElement>('[data-editor="editable"]')
    if (!editableEl || editableEl.innerHTML === val) return
    ignoreNext = true
    editableEl.innerHTML = val ?? ''
  }
)

onUnmounted(() => {
  observer?.disconnect()
  editor?.destroy()
  editor = null
})
</script>
