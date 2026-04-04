<template>
  <div class="editor">
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
        <button id="linkBtn" type="button" title="Insert link (Ctrl+K)">
          <span class="icon icon-add" aria-hidden="true" />
        </button>
        <button id="imageBtn" type="button" title="Insert image" style="display: none;">
          <span class="icon icon-add_photo_alternate" aria-hidden="true" />
        </button>
        <input type="file" id="imageFile" accept="image/*" hidden />
      </div>
    </div>

    <div class="editor-body">
      <div class="editor-main">
        <div
          id="editable"
          class="editable"
          contenteditable="true"
          spellcheck="true"
          aria-label="Editing area"
        />
      </div>

      <!-- Side panel required by Editor class (hidden by default) -->
      <div id="sidePanel" class="editor-side">
        <div class="side-tabs">
          <button class="side-tab active" type="button" data-tab="codePanel">HTML</button>
          <button class="side-tab" type="button" data-tab="previewPanel">Vorschau</button>
        </div>
        <div class="side-panels">
          <div id="codePanel" class="side-panel active">
            <textarea id="code" />
            <div class="code-actions">
              <button type="button">Übernehmen</button>
              <button type="button">Bereinigen</button>
              <button type="button">Minify</button>
            </div>
          </div>
          <div id="previewPanel" class="side-panel">
            <div id="preview" class="preview-content" />
          </div>
        </div>
      </div>
    </div>

    <div class="editor-footer">
      <span id="wordCount" class="editor-wordcount">0 words</span>
      <span class="editor-shortcuts">
        <kbd>Ctrl+B</kbd> Bold
        <kbd>Ctrl+K</kbd> Link
        <kbd>Ctrl+S</kbd> Save
      </span>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, onUnmounted, watch } from 'vue'
import { Editor } from '@dodlhuat/basix/js/editor'

const props = defineProps<{ modelValue: string }>()
const emit = defineEmits<{ 'update:modelValue': [value: string] }>()

let observer: MutationObserver | null = null
let ignoreNext = false

onMounted(() => {
  const editableEl = document.getElementById('editable') as HTMLElement | null
  if (!editableEl) return

  if (props.modelValue) {
    editableEl.innerHTML = props.modelValue
  }

  new Editor()

  observer = new MutationObserver(() => {
    if (ignoreNext) { ignoreNext = false; return }
    emit('update:modelValue', editableEl.innerHTML)
  })
  observer.observe(editableEl, { childList: true, subtree: true, characterData: true })
})

watch(() => props.modelValue, (val) => {
  const editableEl = document.getElementById('editable') as HTMLElement | null
  if (!editableEl || editableEl.innerHTML === val) return
  ignoreNext = true
  editableEl.innerHTML = val ?? ''
})

onUnmounted(() => {
  observer?.disconnect()
})
</script>
