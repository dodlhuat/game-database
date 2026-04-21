<template>
  <div class="admin-page">
    <AppNav />

    <!-- ── Page Hero ────────────────────────────────────────────── -->
    <section class="page-hero">
      <div class="page-hero__backdrop" aria-hidden="true">
        <div class="page-hero__glow" />
        <div class="page-hero__dots" />
      </div>
      <div class="page-hero__body">
        <AdminBreadcrumb :label="$t('admin.breadcrumb.games')" />
        <div class="page-hero__row">
          <h1 class="page-hero__title">{{ $t('admin.games.title') }}</h1>
          <div class="hero-actions">
            <button class="hero-btn hero-btn--secondary" :disabled="exporting" @click="doExport">
              <span class="icon icon-download-outline" aria-hidden="true" />
              {{ exporting ? $t('btn.exporting') : $t('btn.export') }}
            </button>
            <label class="hero-btn hero-btn--secondary" :class="{ 'hero-btn--loading': importing }">
              <span class="icon icon-cloud" aria-hidden="true" />
              {{ importing ? $t('btn.importing') : $t('btn.import') }}
              <input type="file" accept=".xlsx,.xls,.csv" class="hero-file-input" :disabled="importing" @change="doImport" />
            </label>
            <button class="hero-btn" @click="openCreate">
              <span class="icon icon-add" aria-hidden="true" />
              {{ $t('admin.actions.add_game') }}
            </button>
          </div>
        </div>
      </div>
    </section>

    <!-- ── Content ──────────────────────────────────────────────── -->
    <div class="admin-content">
      <div class="admin-content__inner">

        <div v-if="loading" class="admin-state">
          <div class="spinner" />
        </div>

        <section v-else class="dash-section">
          <header class="dash-section__header">
            <h2 class="dash-section__title">{{ $t('admin.games.all') }}</h2>
            <span class="dash-section__count">{{ games.length }}</span>
          </header>

          <div v-if="!games.length" class="dash-empty">
            <span class="icon icon-article dash-empty__icon" aria-hidden="true" />
            <p class="dash-empty__text">{{ $t('admin.empty.games') }}</p>
          </div>

          <div v-else class="table-wrap">
            <table class="dash-table">
              <thead>
                <tr>
                  <th>{{ $t('admin.table.title') }}</th>
                  <th>{{ $t('admin.table.category') }}</th>
                  <th>{{ $t('admin.table.copies') }}</th>
                  <th>{{ $t('admin.table.status') }}</th>
                  <th>{{ $t('admin.table.actions') }}</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="game in games" :key="game.id">
                  <td class="dash-table__primary">{{ game.title }}</td>
                  <td>{{ game.category?.name ?? '—' }}</td>
                  <td>{{ game.copies_count }}</td>
                  <td>
                    <span class="badge" :class="game.is_active ? 'badge-success' : ''">
                      {{ game.is_active ? $t('common.badge.active') : $t('common.badge.inactive') }}
                    </span>
                  </td>
                  <td>
                    <div class="action-row">
                      <button class="action-btn" @click="openEdit(game)">{{ $t('admin.actions.edit') }}</button>
                      <button class="action-btn" @click="openCopies(game)">
                        <span class="icon icon-layers" aria-hidden="true" />
                        {{ $t('admin.actions.copies_manage') }}
                        <span class="action-btn__badge">{{ game.copies_count }}</span>
                      </button>
                      <button class="action-btn action-btn--danger" @click="remove(game.id)">{{ $t('admin.actions.delete') }}</button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>

      </div>
    </div>

    <!-- ── Spiel-Formular Modal ──────────────────────────────────── -->
    <Transition name="modal">
      <div v-if="form.open" class="modal-overlay" @click.self="closeForm">
        <div class="dialog dialog--wide">
          <div class="dialog__header">
            <h3 class="dialog__title">{{ form.id ? $t('admin.games.edit_game') : $t('admin.games.add_game') }}</h3>
            <button class="dialog__close" :aria-label="$t('admin.form.close')" @click="closeForm">
              <span class="icon icon-close" aria-hidden="true" />
            </button>
          </div>

          <div class="dialog__body">
            <div class="form-grid">
              <div class="form-grid__full"><UiInput v-model="form.title" :label="$t('admin.form.title')" required /></div>
              <div class="form-grid__full"><UiInput v-model="form.slug" :label="$t('admin.form.slug')" required /></div>
              <div class="form-grid__full"><UiInput v-model="form.short_description" :label="$t('admin.form.short_desc')" /></div>
              <div class="form-grid__full">
                <label class="form-label">{{ $t('admin.form.description') }}</label>
                <UiRichEditor v-model="form.description" />
              </div>

              <div class="form-grid__full">
                <label class="form-label">{{ $t('admin.table.category') }}</label>
                <select v-model="form.category_id" class="form-select">
                  <option :value="null">{{ $t('admin.form.no_category') }}</option>
                  <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                </select>
              </div>

              <div><UiInput v-model="form.min_players" :label="$t('admin.form.min_players')" type="number" /></div>
              <div><UiInput v-model="form.max_players" :label="$t('admin.form.max_players')" type="number" /></div>
              <div><UiInput v-model="form.min_age" :label="$t('admin.form.min_age')" type="number" /></div>

              <div><UiInput v-model="form.duration_min" :label="$t('admin.form.duration_min')" type="number" /></div>
              <div><UiInput v-model="form.duration_max" :label="$t('admin.form.duration_max')" type="number" /></div>
              <div><UiInput v-model="form.year" :label="$t('admin.form.year')" type="number" /></div>

              <div>
                <label class="form-label">{{ $t('admin.form.difficulty') }}</label>
                <select v-model="form.difficulty" class="form-select">
                  <option value="">{{ $t('admin.form.no_category') }}</option>
                  <option value="EASY">{{ $t('admin.form.difficulty_easy') }}</option>
                  <option value="MEDIUM">{{ $t('admin.form.difficulty_medium') }}</option>
                  <option value="HARD">{{ $t('admin.form.difficulty_hard') }}</option>
                  <option value="EXPERT">{{ $t('admin.form.difficulty_expert') }}</option>
                </select>
              </div>

              <div class="form-grid__full">
                <label class="form-label">{{ $t('admin.form.language') }}</label>
                <div class="tag-picker">
                  <label
                    v-for="lang in allLanguages"
                    :key="lang.id"
                    class="tag-chip"
                    :class="{ 'tag-chip--selected': form.language_ids.includes(lang.id) }"
                  >
                    <input type="checkbox" :value="lang.id" v-model="form.language_ids" class="tag-chip__input" />
                    {{ lang.name }}
                  </label>
                </div>
              </div>

              <div class="form-grid__full">
                <UiInput
                  v-model="form.instagram_url"
                  :label="$t('admin.form.instagram')"
                  placeholder="https://www.instagram.com/p/…"
                  type="url"
                />
              </div>

              <div>
                <UiInput
                  v-model="form.deposit_tokens"
                  :label="$t('admin.form.deposit_tokens')"
                  type="number"
                  :min="0"
                />
              </div>

              <div class="form-grid__full">
                <label class="form-label">{{ $t('admin.form.tags') }}</label>
                <div class="tag-picker">
                  <label
                    v-for="tag in allTags"
                    :key="tag.id"
                    class="tag-chip"
                    :class="{ 'tag-chip--selected': form.tag_ids.includes(tag.id) }"
                  >
                    <input type="checkbox" :value="tag.id" v-model="form.tag_ids" class="tag-chip__input" />
                    {{ tag.name }}
                  </label>
                </div>
                <div class="tag-add">
                  <UiInput v-model="newTagName" :placeholder="$t('admin.form.new_tag')" style="flex:1" />
                  <button class="action-btn" :disabled="!newTagName.trim()" @click="addTag">{{ $t('btn.add') }}</button>
                </div>
              </div>

              <div class="form-grid__full">
                <label class="form-label">{{ $t('admin.form.cover_image') }}</label>
                <div class="file-uploader">
                  <div
                    class="drop-zone"
                    :class="{ 'drag-over': isDragging }"
                    @dragover.prevent="isDragging = true"
                    @dragleave.prevent="isDragging = false"
                    @drop.prevent="onDrop"
                    @click="coverInputRef?.click()"
                  >
                    <input
                      ref="coverInputRef"
                      type="file"
                      accept="image/*"
                      style="display:none"
                      @change="onFileChange"
                    />
                    <div class="drop-zone-content">
                      <div class="icon-container">
                        <span class="icon icon-add_photo_alternate" />
                      </div>
                      <span class="primary-text">{{ $t('admin.form.image_hint') }}</span>
                      <span class="secondary-text">{{ $t('admin.form.image_formats') }}</span>
                    </div>
                  </div>

                  <div v-if="form.coverFile || form.existingCoverUrl" class="file-list">
                    <div class="file-item">
                      <div class="file-item-header">
                        <div class="file-info">
                          <img
                            :src="form.coverFile ? coverPreviewUrl! : form.existingCoverUrl!"
                            :alt="$t('admin.form.preview')"
                            style="width:40px;height:40px;object-fit:cover;border-radius:4px;flex-shrink:0"
                          />
                          <div class="file-details">
                            <span class="file-name">{{ form.coverFile ? form.coverFile.name : $t('admin.form.cover_current') }}</span>
                            <span class="file-size">{{ form.coverFile ? formatFileSize(form.coverFile.size) : '' }}</span>
                          </div>
                        </div>
                        <button type="button" class="remove-btn" @click.stop="removeCover">
                          <span class="icon icon-close" />
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div v-if="form.id" class="form-grid__full">
                <label class="form-label">{{ $t('admin.form.other_images') }}</label>
                <div class="game-images">
                  <div v-if="form.existingImages.length" class="game-images__grid">
                    <div v-for="img in form.existingImages" :key="img.id" class="game-images__item">
                      <img :src="img.url" alt="Spielbild" class="game-images__thumb" />
                      <button type="button" class="game-images__remove" @click="removeGameImage(img)" title="Bild löschen">
                        <span class="icon icon-close" />
                      </button>
                    </div>
                  </div>
                  <label class="game-images__add" :class="{ 'game-images__add--loading': imageUploading }">
                    <input ref="imageInputRef" type="file" accept="image/*" multiple style="display:none" :disabled="imageUploading" @change="onImagesChange" />
                    <span class="icon icon-add_photo_alternate" />
                    <span>{{ imageUploading ? $t('btn.importing') : $t('admin.form.add_images') }}</span>
                  </label>
                </div>
              </div>

              <div class="form-grid__full">
                <label class="form-check">
                  <input v-model="form.is_active" type="checkbox" />
                  <span>{{ $t('admin.form.active_game') }}</span>
                </label>
              </div>
            </div>

            <div v-if="formError" class="form-error">{{ formError }}</div>
          </div>

          <div class="dialog__actions">
            <UiButton :loading="saving" @click="save">{{ $t('admin.form.save') }}</UiButton>
            <button class="action-btn" @click="closeForm">{{ $t('admin.form.cancel') }}</button>
          </div>
        </div>
      </div>
    </Transition>

    <!-- ── Kopien Modal ──────────────────────────────────────────── -->
    <Transition name="modal">
      <div v-if="copiesPanel.open" class="modal-overlay" @click.self="closeCopies">
        <div class="dialog dialog--wide">
          <div class="dialog__header">
            <div>
              <div class="dialog__eyebrow">{{ $t('admin.games.copies_title') }}</div>
              <h3 class="dialog__title">{{ copiesPanel.gameTitle }}</h3>
            </div>
            <button class="dialog__close" :aria-label="$t('admin.form.close')" @click="closeCopies">
              <span class="icon icon-close" aria-hidden="true" />
            </button>
          </div>

          <div class="dialog__body">
            <div v-if="copiesPanel.loading" class="modal-loading">
              <div class="spinner" />
            </div>

            <template v-else>
              <div class="dialog__toolbar">
                <span class="dialog__count">{{ copiesPanel.copies.length }} Kopie{{ copiesPanel.copies.length !== 1 ? 'n' : '' }}</span>
                <button class="action-btn" @click="openCopyCreate">
                  <span class="icon icon-add" aria-hidden="true" />
                  {{ $t('admin.games.add_copy') }}
                </button>
              </div>

              <div v-if="!copiesPanel.copies.length" class="dash-empty">
                <span class="icon icon-layers dash-empty__icon" aria-hidden="true" />
                <p class="dash-empty__text">{{ $t('admin.empty.copies') }}</p>
              </div>

              <div v-else class="copies-table-wrap">
                <table class="dash-table">
                  <thead>
                    <tr>
                      <th>{{ $t('admin.form.condition') }}</th>
                      <th>{{ $t('admin.form.qr_code') }}</th>
                      <th>{{ $t('admin.table.status') }}</th>
                      <th>{{ $t('admin.table.actions') }}</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="copy in copiesPanel.copies" :key="copy.id">
                      <td>
                        <span class="badge" :class="conditionClass(copy.condition)">
                          {{ conditionLabel(copy.condition) }}
                        </span>
                      </td>
                      <td class="text-mono">{{ copy.qr_code ?? '—' }}</td>
                      <td>
                        <span class="badge" :class="copy.is_available ? 'badge-success' : 'badge-error'">
                          {{ copy.is_available ? $t('common.badge.available') : $t('common.badge.loaned') }}
                        </span>
                      </td>
                      <td>
                        <div class="action-row">
                          <template v-if="copy.condition === 'REVIEW'">
                            <button class="action-btn action-btn--success" @click="approveCopy(copy.id)">{{ $t('admin.actions.approve_copy') }}</button>
                            <button class="action-btn action-btn--danger" @click="openMarkDamaged(copy.id)">{{ $t('admin.actions.mark_damaged') }}</button>
                          </template>
                          <template v-else>
                            <button class="action-btn" @click="openCopyEdit(copy)">{{ $t('admin.actions.edit') }}</button>
                            <button class="action-btn action-btn--danger" @click="removeCopy(copy.id)">{{ $t('admin.actions.delete') }}</button>
                          </template>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </template>
          </div>

          <div class="dialog__actions">
            <button class="action-btn" @click="closeCopies">{{ $t('admin.form.close') }}</button>
          </div>
        </div>
      </div>
    </Transition>

    <!-- ── Kopie-Formular Modal ──────────────────────────────────── -->
    <Transition name="modal">
      <div v-if="copyForm.open" class="modal-overlay modal-overlay--top" @click.self="copyForm.open = false">
        <div class="dialog">
          <div class="dialog__header">
            <h3 class="dialog__title">{{ copyForm.id ? $t('admin.games.edit_copy') : $t('admin.games.add_copy') }}</h3>
            <button class="dialog__close" :aria-label="$t('admin.form.close')" @click="copyForm.open = false">
              <span class="icon icon-close" aria-hidden="true" />
            </button>
          </div>

          <div class="dialog__body">
            <div class="form-field">
              <label class="form-label">{{ $t('admin.form.condition') }}</label>
              <select v-model="copyForm.condition" class="form-select">
                <option value="NEW">{{ $t('admin.form.condition_new') }}</option>
                <option value="VERY_GOOD">{{ $t('admin.form.condition_very_good') }}</option>
                <option value="GOOD">{{ $t('admin.form.condition_good') }}</option>
                <option value="DAMAGED">{{ $t('admin.form.condition_damaged') }}</option>
                <option value="LOCKED">{{ $t('admin.form.condition_locked') }}</option>
              </select>
            </div>
            <UiInput v-model="copyForm.qr_code" :label="$t('admin.form.qr_code')" />
            <UiInput v-model="copyForm.notes" :label="$t('admin.form.notes')" />
            <div v-if="copyForm.error" class="form-error">{{ copyForm.error }}</div>
          </div>

          <div class="dialog__actions">
            <UiButton :loading="copyForm.saving" @click="saveCopy">{{ $t('admin.form.save') }}</UiButton>
            <button class="action-btn" @click="copyForm.open = false">{{ $t('admin.form.cancel') }}</button>
          </div>
        </div>
      </div>
    </Transition>

    <!-- ── Beschädigt-Markieren Modal ──────────────────────────── -->
    <Transition name="modal">
      <div v-if="damagedForm.open" class="modal-overlay modal-overlay--top" @click.self="damagedForm.open = false">
        <div class="dialog">
          <div class="dialog__header">
            <h3 class="dialog__title">{{ $t('admin.games.mark_damaged_title') }}</h3>
            <button class="dialog__close" :aria-label="$t('admin.form.close')" @click="damagedForm.open = false">
              <span class="icon icon-close" aria-hidden="true" />
            </button>
          </div>
          <div class="dialog__body">
            <p class="form-hint">{{ $t('admin.games.mark_damaged_hint') }}</p>
            <UiInput v-model="damagedForm.notes" :label="$t('admin.form.notes')" />
            <div v-if="damagedForm.error" class="form-error">{{ damagedForm.error }}</div>
          </div>
          <div class="dialog__actions">
            <UiButton variant="danger" :loading="damagedForm.saving" @click="saveMarkDamaged">{{ $t('admin.actions.mark_damaged') }}</UiButton>
            <button class="action-btn" @click="damagedForm.open = false">{{ $t('admin.form.cancel') }}</button>
          </div>
        </div>
      </div>
    </Transition>

    <!-- ── Import Ergebnis Modal ────────────────────────────────── -->
    <Transition name="modal">
      <div v-if="importResult" class="modal-overlay" @click.self="importResult = null">
        <div class="dialog">
          <div class="dialog__header">
            <h3 class="dialog__title">{{ $t('admin.import.title') }}</h3>
            <button class="dialog__close" :aria-label="$t('admin.form.close')" @click="importResult = null">
              <span class="icon icon-close" aria-hidden="true" />
            </button>
          </div>
          <div class="dialog__body">
            <div class="import-result">
              <div class="import-result__row">
                <span class="import-result__label">{{ $t('admin.import.new_games') }}</span>
                <span class="import-result__value import-result__value--new">{{ importResult.new }}</span>
              </div>
              <div class="import-result__row">
                <span class="import-result__label">{{ $t('admin.import.updated_games') }}</span>
                <span class="import-result__value import-result__value--updated">{{ importResult.updated }}</span>
              </div>
              <div class="import-result__divider" />
              <div class="import-result__row">
                <span class="import-result__label">{{ $t('admin.import.total') }}</span>
                <span class="import-result__value">{{ importResult.total }}</span>
              </div>
            </div>
            <div v-if="importError" class="form-error">{{ importError }}</div>
          </div>
          <div class="dialog__actions">
            <button class="action-btn" @click="importResult = null">{{ $t('admin.form.close') }}</button>
          </div>
        </div>
      </div>
    </Transition>

    <!-- ── Footer ──────────────────────────────────────────────── -->
    <footer class="l-footer">
      <div class="l-footer__inner">
        <div class="l-footer__brand">
          <span class="l-footer__hex" aria-hidden="true">⬡</span>
          <span class="l-footer__name">AUA</span>
        </div>
        <p class="l-footer__copy">{{ $t('common.copyright_short', { year }) }}</p>
      </div>
    </footer>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'

definePageMeta({ middleware: ['auth', 'admin'] })

const { t } = useI18n()
const { fetchAdminGames, createGame, updateGame, deleteGame, fetchAdminCategories, fetchAdminTags, createTag, importGames, exportGames, fetchCopies, createCopy, updateCopy, deleteCopy, uploadGameImages, deleteGameImage } = useAdmin()

interface Game {
  id: number; title: string; slug: string; description: string | null; short_description: string | null
  category: { id: number; name: string } | null; copies_count: number; is_active: boolean
  min_players: number | null; max_players: number | null; min_age: number | null
  duration_min: number | null; duration_max: number | null; difficulty: string | null
  languages: { id: number; name: string }[]; year: number | null; tags: { id: number; name: string }[]
  deposit_tokens: number; cover_image_url: string | null
  images: { id: number; url: string }[]
}

interface GameImage { id: number; url: string }

interface Copy {
  id: number; condition: string; borrow_count: number; qr_code: string | null; notes: string | null; is_available: boolean
}

const year = new Date().getFullYear()
const loading = ref(true)
const importing = ref(false)
const exporting = ref(false)
const importResult = ref<{ new: number; updated: number; total: number } | null>(null)
const importError = ref('')
const coverPreviewUrl = computed(() => form.coverFile ? URL.createObjectURL(form.coverFile) : null)
const coverInputRef = ref<HTMLInputElement | null>(null)
const isDragging = ref(false)
const saving = ref(false)
const formError = ref('')
const imageInputRef = ref<HTMLInputElement | null>(null)
const imageUploading = ref(false)
const games = ref<Game[]>([])
interface CategoryItem { id: number; name: string; is_active: boolean; children: CategoryItem[] }
const rawCategories = ref<CategoryItem[]>([])
const categories = computed(() => {
  const flat: { id: number; name: string }[] = []
  for (const cat of rawCategories.value) {
    if (cat.is_active) flat.push({ id: cat.id, name: cat.name })
    for (const child of cat.children ?? []) {
      if (child.is_active) flat.push({ id: child.id, name: `${cat.name} › ${child.name}` })
    }
  }
  return flat
})
const allTags = ref<{ id: number; name: string; slug: string }[]>([])
const allLanguages = ref<{ id: number; name: string }[]>([])
const newTagName = ref('')

// ── Spiel-Formular ────────────────────────────────────────────────
const form = reactive({
  open: false, id: null as number | null,
  title: '', slug: '', description: '', short_description: '',
  category_id: null as number | null,
  min_players: '', max_players: '', min_age: '',
  duration_min: '', duration_max: '',
  difficulty: '', year: '', instagram_url: '', deposit_tokens: '',
  is_active: true, tag_ids: [] as number[], language_ids: [] as number[],
  coverFile: null as File | null,
  existingCoverUrl: null as string | null,
  existingImages: [] as GameImage[],
})

// ── Kopien Panel ──────────────────────────────────────────────────
const copiesPanel = reactive({
  open: false,
  gameId: null as number | null,
  gameTitle: '',
  copies: [] as Copy[],
  loading: false,
})

// ── Kopie-Formular ────────────────────────────────────────────────
const copyForm = reactive({
  open: false,
  id: null as number | null,
  condition: 'NEW',
  qr_code: '',
  notes: '',
  saving: false,
  error: '',
})

// ── Beschädigt-Markieren Dialog ───────────────────────────────────
const damagedForm = reactive({
  open: false,
  copyId: null as number | null,
  notes: '',
  saving: false,
  error: '',
})

onMounted(async () => {
  await load()
  const { fetchLanguages } = useGames()
  const [catData, tagData, langData] = await Promise.all([fetchAdminCategories(), fetchAdminTags(), fetchLanguages()])
  rawCategories.value = catData.data as CategoryItem[]
  allTags.value = tagData.data
  allLanguages.value = langData as { id: number; name: string }[]
})

async function load() {
  loading.value = true
  try { const data = await fetchAdminGames(); games.value = data.data as Game[] }
  finally { loading.value = false }
}

function openCreate() {
  Object.assign(form, { open: true, id: null, title: '', slug: '', description: '', short_description: '', category_id: null, min_players: '', max_players: '', min_age: '', duration_min: '', duration_max: '', difficulty: '', year: '', instagram_url: '', deposit_tokens: '', is_active: true, tag_ids: [], language_ids: [], coverFile: null, existingCoverUrl: null, existingImages: [] })
  formError.value = ''
}

async function openEdit(game: Game) {
  Object.assign(form, { open: true, id: game.id, title: game.title, slug: game.slug, description: game.description ?? '', short_description: game.short_description ?? '', category_id: game.category?.id ?? null, min_players: game.min_players ?? '', max_players: game.max_players ?? '', min_age: game.min_age ?? '', duration_min: game.duration_min ?? '', duration_max: game.duration_max ?? '', difficulty: game.difficulty ?? '', year: game.year ?? '', instagram_url: game.instagram_url ?? '', deposit_tokens: game.deposit_tokens ?? '', is_active: game.is_active, tag_ids: game.tags?.map(t => t.id) ?? [], language_ids: game.languages?.map(l => l.id) ?? [], coverFile: null, existingCoverUrl: game.cover_image_url ?? null, existingImages: [] })
  formError.value = ''
  // Load game detail to get images
  try {
    const detail = await useApi().get<{ data: Game }>(`/admin/games/${game.id}`)
    form.existingImages = (detail.data as any).images ?? []
  } catch {}
}

function closeForm() { form.open = false }
function onFileChange(e: Event) { form.coverFile = (e.target as HTMLInputElement).files?.[0] ?? null }
function onDrop(e: DragEvent) {
  isDragging.value = false
  const file = e.dataTransfer?.files?.[0]
  if (file && file.type.startsWith('image/')) form.coverFile = file
}
function removeCover() { form.coverFile = null; form.existingCoverUrl = null; if (coverInputRef.value) coverInputRef.value.value = '' }
function formatFileSize(bytes: number) { if (bytes < 1024) return `${bytes} B`; if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`; return `${(bytes / (1024 * 1024)).toFixed(1)} MB` }

async function addTag() {
  const name = newTagName.value.trim()
  if (!name) return
  try { const res = await createTag(name); allTags.value.push(res.data); form.tag_ids.push(res.data.id); newTagName.value = '' } catch {}
}

async function save() {
  saving.value = true; formError.value = ''
  try {
    const fd = new FormData()
    const fields = ['title', 'slug', 'description', 'short_description', 'category_id', 'min_players', 'max_players', 'min_age', 'duration_min', 'duration_max', 'difficulty', 'year', 'instagram_url', 'deposit_tokens'] as const
    fields.forEach((f) => { if (form[f] !== '' && form[f] !== null) fd.append(f, String(form[f])) })
    fd.append('is_active', form.is_active ? '1' : '0')
    form.tag_ids.forEach(id => fd.append('tag_ids[]', String(id)))
    form.language_ids.forEach(id => fd.append('language_ids[]', String(id)))
    if (form.coverFile) fd.append('cover_image', form.coverFile)
    form.id ? await updateGame(form.id, fd) : await createGame(fd)
    await load(); closeForm()
  } catch (err: unknown) {
    formError.value = (err as { message?: string }).message ?? t('common.error.save')
  } finally { saving.value = false }
}

async function remove(id: number) { await deleteGame(id); await load() }

// ── Kopien ────────────────────────────────────────────────────────
async function openCopies(game: Game) {
  copiesPanel.gameId = game.id
  copiesPanel.gameTitle = game.title
  copiesPanel.open = true
  copiesPanel.loading = true
  try {
    const data = await fetchCopies({ game_id: game.id })
    copiesPanel.copies = data.data as Copy[]
  } finally {
    copiesPanel.loading = false
  }
}

function closeCopies() { copiesPanel.open = false }

function openCopyCreate() {
  Object.assign(copyForm, { open: true, id: null, condition: 'NEW', qr_code: '', notes: '', error: '' })
}

function openCopyEdit(copy: Copy) {
  Object.assign(copyForm, { open: true, id: copy.id, condition: copy.condition, qr_code: copy.qr_code ?? '', notes: copy.notes ?? '', error: '' })
}

async function saveCopy() {
  copyForm.saving = true; copyForm.error = ''
  try {
    const payload = { game_id: copiesPanel.gameId, condition: copyForm.condition, qr_code: copyForm.qr_code || undefined, notes: copyForm.notes || undefined }
    copyForm.id ? await updateCopy(copyForm.id, payload) : await createCopy(payload)
    const data = await fetchCopies({ game_id: copiesPanel.gameId! })
    copiesPanel.copies = data.data as Copy[]
    const game = games.value.find(g => g.id === copiesPanel.gameId)
    if (game) game.copies_count = copiesPanel.copies.length
    copyForm.open = false
  } catch (err: unknown) {
    copyForm.error = (err as { message?: string }).message ?? t('common.error.save')
  } finally { copyForm.saving = false }
}

async function removeCopy(id: number) {
  try {
    await deleteCopy(id)
    copiesPanel.copies = copiesPanel.copies.filter(c => c.id !== id)
    const game = games.value.find(g => g.id === copiesPanel.gameId)
    if (game) game.copies_count = copiesPanel.copies.length
  } catch (err: unknown) { alert((err as { message?: string }).message ?? t('common.error.generic')) }
}

async function approveCopy(id: number) {
  try {
    await useApi().post(`/admin/copies/${id}/approve`, {})
    const data = await fetchCopies({ game_id: copiesPanel.gameId! })
    copiesPanel.copies = data.data as Copy[]
  } catch (err: unknown) { alert((err as { message?: string }).message ?? t('common.error.generic')) }
}

function openMarkDamaged(id: number) {
  Object.assign(damagedForm, { open: true, copyId: id, notes: '', error: '' })
}

async function saveMarkDamaged() {
  damagedForm.saving = true; damagedForm.error = ''
  try {
    await useApi().post(`/admin/copies/${damagedForm.copyId}/mark-damaged`, { notes: damagedForm.notes || undefined })
    damagedForm.open = false
    const data = await fetchCopies({ game_id: copiesPanel.gameId! })
    copiesPanel.copies = data.data as Copy[]
  } catch (err: unknown) {
    damagedForm.error = (err as { message?: string }).message ?? t('common.error.save')
  } finally { damagedForm.saving = false }
}

// ── Bilder ────────────────────────────────────────────────────────
async function onImagesChange(e: Event) {
  if (!form.id) return
  const files = Array.from((e.target as HTMLInputElement).files ?? [])
  if (!files.length) return
  imageUploading.value = true
  try {
    const res = await uploadGameImages(form.id, files)
    form.existingImages.push(...res.images)
  } catch (err: unknown) {
    formError.value = (err as { message?: string }).message ?? t('common.error.save')
  } finally {
    imageUploading.value = false
    if (imageInputRef.value) imageInputRef.value.value = ''
  }
}

async function removeGameImage(image: GameImage) {
  if (!form.id) return
  try {
    await deleteGameImage(form.id, image.id)
    form.existingImages = form.existingImages.filter(i => i.id !== image.id)
  } catch (err: unknown) {
    formError.value = (err as { message?: string }).message ?? t('common.error.generic')
  }
}

function conditionLabel(c: string) {
  const m: Record<string, string> = {
    NEW: 'common.badge.condition_new', VERY_GOOD: 'common.badge.condition_very_good',
    GOOD: 'common.badge.condition_good', REVIEW: 'common.badge.condition_review',
    DAMAGED: 'common.badge.condition_damaged', LOCKED: 'common.badge.condition_locked',
  }
  return m[c] ? t(m[c]) : c
}
function conditionClass(c: string) {
  const m: Record<string, string> = {
    NEW: 'badge-success', VERY_GOOD: 'badge-success', GOOD: 'badge-warning',
    REVIEW: 'badge-info', DAMAGED: 'badge-error', LOCKED: '',
  }
  return m[c] ?? ''
}

// ── Import / Export ───────────────────────────────────────────────
async function doImport(e: Event) {
  const file = (e.target as HTMLInputElement).files?.[0]
  if (!file) return
  importing.value = true
  importError.value = ''
  try {
    const result = await importGames(file)
    importResult.value = result
    await load()
  } catch (err: unknown) {
    importError.value = (err as { message?: string }).message ?? t('common.error.import_failed')
    importResult.value = { new: 0, updated: 0, total: 0 }
  } finally {
    importing.value = false
    ;(e.target as HTMLInputElement).value = ''
  }
}

async function doExport() {
  exporting.value = true
  try { await exportGames() }
  catch (err: unknown) { alert((err as { message?: string }).message ?? t('common.error.export_failed')) }
  finally { exporting.value = false }
}
</script>

<style lang="scss" scoped>
$hero-bg:     #0F0E0C;
$nav-height:  64px;
$amber-08:    rgba(212, 146, 30, 0.08);
$amber-14:    rgba(212, 146, 30, 0.14);
$amber-25:    rgba(212, 146, 30, 0.25);
$amber-glow:  rgba(212, 146, 30, 0.16);
$hero-text:   #EEE8DF;
$hero-muted:  rgba(238, 232, 223, 0.72);
$hero-muted-50: rgba(238, 232, 223, 0.65);
$hero-divider:  rgba(238, 232, 223, 0.10);

.admin-page { min-height: 100vh; display: flex; flex-direction: column; background: var(--background); }

.page-hero {
  position: relative; background: $hero-bg;
  padding: calc(#{$nav-height} + 1.75rem) 1.5rem 1.75rem; overflow: hidden;
  &__backdrop { position: absolute; inset: 0; pointer-events: none; }
  &__glow { position: absolute; width: 400px; height: 400px; top: -120px; right: -60px; border-radius: 50%; filter: blur(90px); background: $amber-glow; }
  &__dots { position: absolute; inset: 0; background-image: radial-gradient(circle, rgba(255,255,255,0.04) 1px, transparent 1px); background-size: 24px 24px; mask-image: radial-gradient(ellipse 80% 100% at 70% 50%, black 20%, transparent 100%); }
  &__body { position: relative; z-index: 1; max-width: 1100px; margin: 0 auto; }
  &__row { display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap; }
  &__title { font-size: clamp(1.5rem, 3vw, 2.25rem); font-weight: 800; letter-spacing: -0.04em; color: $hero-text; margin: 0; }
}

.hero-actions { display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; }

.hero-btn {
  display: inline-flex; align-items: center; gap: 0.4rem;
  padding: 0.5rem 1rem; font-size: 0.875rem; font-weight: 600; font-family: inherit;
  background: $amber; color: #0F0E0C; border: none; border-radius: 8px; cursor: pointer;
  transition: opacity 0.2s;
  .icon { font-size: 1rem; }
  &:hover { opacity: 0.88; }
  &:disabled { opacity: 0.5; cursor: not-allowed; }
  &--secondary {
    background: transparent; color: $hero-text;
    border: 1px solid rgba(238,232,223,0.2);
    &:hover { background: rgba(238,232,223,0.06); opacity: 1; }
  }
  &--loading { opacity: 0.6; cursor: not-allowed; pointer-events: none; }
}

.hero-file-input { display: none; }

.admin-content { flex: 1; padding: 2rem 1.5rem 4rem; &__inner { max-width: 1100px; margin: 0 auto; display: flex; flex-direction: column; gap: 1.5rem; } }
.admin-state { display: flex; justify-content: center; align-items: center; min-height: 200px; }

.dash-section { background: var(--secondary-background); border: 1px solid var(--divider); border-radius: 14px; overflow: hidden; }
.dash-section__header { display: flex; align-items: center; gap: 0.75rem; padding: 1.1rem 1.5rem; border-bottom: 1px solid var(--divider); }
.dash-section__title { font-size: 0.95rem; font-weight: 700; color: var(--primary-text); margin: 0; letter-spacing: -0.02em; }
.dash-section__count { display: inline-flex; align-items: center; justify-content: center; min-width: 22px; height: 22px; padding: 0 6px; font-size: 0.75rem; font-weight: 700; color: $amber; background: $amber-08; border: 1px solid $amber-25; border-radius: 999px; }

.dash-empty { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 0.5rem; padding: 3rem 1.5rem; color: var(--secondary-text); &__icon { width: 2rem; height: 2rem; opacity: 0.35; } &__text { font-size: 0.9rem; padding-bottom: 0; } }

.table-wrap { overflow-x: auto; }
.copies-table-wrap { overflow-x: auto; border-radius: 8px; border: 1px solid var(--divider); margin-top: 0.75rem; }

.dash-table {
  width: 100%; border-collapse: collapse; font-size: 0.875rem;
  th { padding: 0.7rem 1.5rem; text-align: left; font-size: 0.72rem; font-weight: 600; letter-spacing: 0.08em; text-transform: uppercase; color: var(--secondary-text); background: var(--background); border-bottom: 1px solid var(--divider); white-space: nowrap; }
  td { padding: 0.9rem 1.5rem; color: var(--primary-text); border-bottom: 1px solid var(--divider); vertical-align: middle; }
  tbody tr:last-child td { border-bottom: none; }
  tbody tr { transition: background 0.15s; &:hover { background: var(--background); } }
  &__primary { font-weight: 600; }
}

.text-mono { font-family: monospace; font-size: 0.8rem; color: var(--secondary-text); }


.action-row { display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; }
.action-btn {
  display: inline-flex; align-items: center; gap: 0.35rem;
  padding: 0.35rem 0.75rem; font-size: 0.8rem; font-weight: 600; font-family: inherit;
  color: var(--primary-text); background: var(--background); border: 1px solid var(--divider); border-radius: 7px; cursor: pointer; transition: border-color 0.2s, color 0.2s; white-space: nowrap;
  .icon { font-size: 0.875rem; }
  &:hover { border-color: var(--accent-color); color: var(--accent-text); }
  &--danger { color: #f87171; border-color: rgba(239,68,68,0.25); background: rgba(239,68,68,0.05); &:hover { border-color: rgba(239,68,68,0.5); color: #fca5a5; } }
  &:disabled { opacity: 0.4; cursor: not-allowed; }
  &__badge { display: inline-flex; align-items: center; justify-content: center; min-width: 18px; height: 18px; padding: 0 5px; font-size: 0.7rem; font-weight: 700; background: $amber-08; color: $amber; border: 1px solid $amber-25; border-radius: 999px; }
}

// ─── Modal ────────────────────────────────────────────────────────
.modal-overlay {
  position: fixed; inset: 0; z-index: 200; background: rgba(0,0,0,0.6); backdrop-filter: blur(4px);
  display: flex; align-items: center; justify-content: center; padding: 1.5rem; overflow-y: auto;
  &--top { z-index: 210; background: rgba(0,0,0,0.4); backdrop-filter: none; }
}
.dialog {
  background: var(--secondary-background); border: 1px solid var(--divider); border-radius: 16px; padding: 1.75rem; width: 100%; max-width: 480px; box-shadow: 0 25px 60px rgba(0,0,0,0.4);
  &--wide { max-width: 700px; }
  &__header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 1.5rem; }
  &__eyebrow { font-size: 0.7rem; font-weight: 600; letter-spacing: 0.08em; text-transform: uppercase; color: $amber; margin-bottom: 0.2rem; }
  &__title { font-size: 1.05rem; font-weight: 700; letter-spacing: -0.02em; color: var(--primary-text); }
  &__close { display: flex; align-items: center; justify-content: center; width: 28px; height: 28px; background: transparent; border: none; border-radius: 6px; color: var(--secondary-text); cursor: pointer; transition: background 0.15s, color 0.15s; .icon { font-size: 1.125rem; } &:hover { background: var(--background); color: var(--primary-text); } }
  &__body { margin-bottom: 1.5rem; max-height: 65vh; overflow-y: auto; }
  &__toolbar { display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem; }
  &__count { font-size: 0.8rem; font-weight: 600; color: var(--secondary-text); }
  &__actions { display: flex; gap: 0.75rem; }
}

.modal-loading { display: flex; justify-content: center; align-items: center; padding: 2rem; }

.modal-enter-active, .modal-leave-active { transition: opacity 0.2s ease; .dialog { transition: opacity 0.2s ease, transform 0.2s ease; } }
.modal-enter-from, .modal-leave-to { opacity: 0; .dialog { opacity: 0; transform: translateY(8px) scale(0.98); } }

// ─── Form Elements ────────────────────────────────────────────────
.form-grid {
  display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.75rem 1rem;
  @media (max-width: 600px) { grid-template-columns: 1fr; }
  &__full { grid-column: 1 / -1; }
}

.form-field { display: flex; flex-direction: column; gap: 0.4rem; margin-bottom: 0.75rem; }
.form-label { display: block; font-size: 0.8rem; font-weight: 600; color: var(--secondary-text); margin-bottom: 0.4rem; letter-spacing: 0.03em; }

.form-select { display: block; width: 100%; height: 40px; padding: 0 0.75rem; border: 1px solid var(--divider); border-radius: 8px; background: var(--background); color: var(--primary-text); font-size: 0.875rem; font-family: inherit; cursor: pointer; transition: border-color 0.2s; &:focus { outline: none; border-color: var(--accent-color); } }


.form-check { display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem; color: var(--primary-text); cursor: pointer; user-select: none; input { accent-color: var(--accent-color); width: 15px; height: 15px; cursor: pointer; } }

.form-error { margin-top: 0.75rem; padding: 0.75rem 1rem; background: rgba(239,68,68,0.08); border: 1px solid rgba(239,68,68,0.25); border-radius: 8px; color: #f87171; font-size: 0.875rem; }

.tag-picker { display: flex; flex-wrap: wrap; gap: 0.4rem; margin-bottom: 0.5rem; }
.tag-chip { display: inline-flex; align-items: center; gap: 0.3rem; padding: 0.3rem 0.65rem; border: 1px solid var(--divider); border-radius: 999px; font-size: 0.8rem; cursor: pointer; user-select: none; transition: border-color 0.15s, background 0.15s; &--selected { border-color: var(--accent-color); background: var(--accent-color-muted); color: var(--accent-text); } &__input { display: none; } }
.tag-add { display: flex; gap: 0.5rem; align-items: center; margin-top: 0.5rem; }

// ─── Import Result ────────────────────────────────────────────────
.import-result {
  display: flex; flex-direction: column; gap: 0; padding: 0.25rem 0;
  &__row { display: flex; align-items: center; justify-content: space-between; padding: 0.65rem 0; }
  &__label { font-size: 0.9rem; color: var(--secondary-text); }
  &__value { font-size: 1rem; font-weight: 700; color: var(--primary-text); }
  &__value--new { color: #4ade80; }
  &__value--updated { color: $amber; }
  &__divider { height: 1px; background: var(--divider); margin: 0.25rem 0; }
}

// ─── Game Images ──────────────────────────────────────────────────
.game-images {
  display: flex; flex-direction: column; gap: 0.75rem;
  &__grid { display: flex; flex-wrap: wrap; gap: 0.5rem; }
  &__item { position: relative; width: 80px; height: 80px; border-radius: 8px; overflow: visible; }
  &__thumb { width: 80px; height: 80px; object-fit: cover; border-radius: 8px; border: 1px solid var(--divider); display: block; }
  &__remove {
    position: absolute; top: -6px; right: -6px; z-index: 1;
    width: 20px; height: 20px; border-radius: 50%; border: 1px solid var(--divider);
    background: var(--secondary-background); color: var(--primary-text);
    display: flex; align-items: center; justify-content: center; cursor: pointer; padding: 0;
    .icon { font-size: 0.75rem; }
    &:hover { background: rgba(239,68,68,0.15); color: #f87171; border-color: rgba(239,68,68,0.4); }
  }
  &__add {
    display: inline-flex; align-items: center; gap: 0.4rem;
    padding: 0.45rem 0.85rem; font-size: 0.8rem; font-weight: 600;
    color: var(--primary-text); background: var(--background); border: 1px solid var(--divider);
    border-radius: 8px; cursor: pointer; transition: border-color 0.2s;
    .icon { font-size: 1rem; }
    &:hover { border-color: var(--accent-color); }
    &--loading { opacity: 0.5; pointer-events: none; }
  }
}

// ─── Footer ───────────────────────────────────────────────────────
.l-footer { background: $hero-bg; border-top: 1px solid $hero-divider; padding: 1.75rem 1.5rem; &__inner { max-width: 1100px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; } &__brand { display: flex; align-items: center; gap: 0.4rem; } &__hex { font-size: 1.1rem; color: $amber; } &__name { font-size: 0.9rem; font-weight: 700; color: $hero-text; letter-spacing: -0.02em; } &__copy { font-size: 0.8rem; color: $hero-muted-50; padding-bottom: 0; } }
</style>
