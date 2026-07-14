<template>
  <div class="donations-page">
    <!-- ── Page Header ─────────────────────────────────────────── -->
    <section class="page-hero">
      <div class="page-hero__backdrop" aria-hidden="true">
        <div class="page-hero__glow" />
        <div class="page-hero__dots" />
      </div>
      <div class="page-hero__body">
        <p class="page-hero__eyebrow">{{ $t('pages.donations.eyebrow') }}</p>
        <h1 class="page-hero__title">{{ $t('pages.donations.title') }}</h1>
        <p class="page-hero__sub">{{ $t('pages.donations.subtitle') }}</p>
      </div>
    </section>

    <!-- ── Content ─────────────────────────────────────────────── -->
    <section class="donations-content">
      <div class="donations-content__inner">
        <div class="rules-card">
          <span class="icon icon-info rules-card__icon" aria-hidden="true" />
          <div>
            <h2 class="rules-card__title">{{ $t('pages.donations.rules_title') }}</h2>
            <p class="rules-card__text">{{ $t('pages.donations.rules_text') }}</p>
          </div>
        </div>

        <div v-if="success" class="alert alert-success">
          <p>{{ success }}</p>
        </div>

        <form v-else class="donation-form" @submit.prevent="submit">
          <UiChipInput
            v-model="form.games"
            :label="$t('pages.donations.form_games_label')"
            :placeholder="$t('pages.donations.form_games_placeholder')"
            :hint="$t('pages.donations.form_games_hint')"
            :error="errors.games"
            required
          />

          <div class="donation-form__section">
            <input
              id="confirmed-complete"
              v-model="form.confirmed_complete"
              type="checkbox"
              class="styled-checkbox"
            />
            <label for="confirmed-complete">{{ $t('pages.donations.form_confirm_label') }}</label>
            <p v-if="errors.confirmed_complete" class="error-text" role="alert">
              {{ errors.confirmed_complete }}
            </p>
          </div>

          <div class="donation-form__section">
            <label class="form-label">{{ $t('pages.donations.form_images_label') }}</label>
            <div class="file-uploader">
              <div
                class="drop-zone"
                :class="{ 'drag-over': isDragging }"
                @dragover.prevent="isDragging = true"
                @dragleave.prevent="isDragging = false"
                @drop.prevent="onDrop"
                @click="imageInputRef?.click()"
              >
                <input
                  ref="imageInputRef"
                  type="file"
                  accept="image/*"
                  multiple
                  style="display: none"
                  @change="onFileChange"
                />
                <div class="drop-zone-content">
                  <div class="icon-container">
                    <span class="icon icon-add_photo_alternate" />
                  </div>
                  <span class="primary-text">{{ $t('admin.form.image_hint') }}</span>
                  <span class="secondary-text">{{ $t('pages.donations.form_images_hint') }}</span>
                </div>
              </div>

              <div v-if="form.images.length" class="file-list">
                <div v-for="(file, i) in form.images" :key="i" class="file-item">
                  <div class="file-item-header">
                    <div class="file-info">
                      <img
                        :src="previewUrls[i]"
                        :alt="$t('admin.form.preview')"
                        style="
                          width: 40px;
                          height: 40px;
                          object-fit: cover;
                          border-radius: 4px;
                          flex-shrink: 0;
                        "
                      />
                      <div class="file-details">
                        <span class="file-name">{{ file.name }}</span>
                        <span class="file-size">{{ formatFileSize(file.size) }}</span>
                      </div>
                    </div>
                    <button type="button" class="remove-btn" @click.stop="removeImage(i)">
                      <span class="icon icon-close" />
                    </button>
                  </div>
                </div>
              </div>
            </div>
            <p v-if="errors.images" class="error-text" role="alert">{{ errors.images }}</p>
          </div>

          <!-- Honeypot: hidden from real users, bots will fill this -->
          <div class="hp-field" aria-hidden="true">
            <label for="website">Website</label>
            <input
              id="website"
              v-model="form.website"
              type="text"
              name="website"
              tabindex="-1"
              autocomplete="off"
            />
          </div>

          <div v-if="serverError" class="alert alert-error" role="alert">{{ serverError }}</div>

          <UiButton type="submit" :loading="loading">{{
            $t('pages.donations.form_submit')
          }}</UiButton>
        </form>
      </div>
    </section>

    <AppFooter />
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onBeforeUnmount } from 'vue'

definePageMeta({ middleware: 'auth' })

const { submitDonation } = useDonations()
const { t } = useI18n()

const loading = ref(false)
const success = ref('')
const serverError = ref('')
const isDragging = ref(false)
const imageInputRef = ref<HTMLInputElement | null>(null)
const previewUrls = ref<string[]>([])

const MAX_IMAGES = 3

const formLoadedAt = Date.now()

const form = reactive({
  games: [] as string[],
  confirmed_complete: false,
  images: [] as File[],
  website: '',
  form_loaded_at: formLoadedAt,
})

const errors = reactive({
  games: '',
  confirmed_complete: '',
  images: '',
})

function addFiles(files: FileList | File[]) {
  for (const file of Array.from(files)) {
    if (form.images.length >= MAX_IMAGES) break
    if (!file.type.startsWith('image/')) continue
    form.images.push(file)
    previewUrls.value.push(URL.createObjectURL(file))
  }
}

function onFileChange(e: Event) {
  const files = (e.target as HTMLInputElement).files
  if (files) addFiles(files)
  ;(e.target as HTMLInputElement).value = ''
}

function onDrop(e: DragEvent) {
  isDragging.value = false
  if (e.dataTransfer?.files) addFiles(e.dataTransfer.files)
}

function removeImage(i: number) {
  URL.revokeObjectURL(previewUrls.value[i]!)
  form.images.splice(i, 1)
  previewUrls.value.splice(i, 1)
}

function formatFileSize(bytes: number) {
  return bytes < 1024 * 1024
    ? `${(bytes / 1024).toFixed(1)} KB`
    : `${(bytes / 1024 / 1024).toFixed(1)} MB`
}

onBeforeUnmount(() => {
  previewUrls.value.forEach((url) => URL.revokeObjectURL(url))
})

function buildFormData() {
  const fd = new FormData()
  form.games.forEach((g) => fd.append('games[]', g))
  fd.append('confirmed_complete', form.confirmed_complete ? '1' : '0')
  form.images.forEach((file) => fd.append('images[]', file))
  fd.append('website', form.website)
  fd.append('form_loaded_at', String(form.form_loaded_at))
  return fd
}

async function submit() {
  Object.keys(errors).forEach((k) => ((errors as Record<string, string>)[k] = ''))
  serverError.value = ''
  loading.value = true

  try {
    const data = await submitDonation(buildFormData())
    success.value = data.message
  } catch (err: unknown) {
    const e = err as { errors?: Record<string, string[]>; message?: string }

    if (e.errors) {
      Object.entries(e.errors).forEach(([key, msgs]) => {
        const field = key.replace(/\.\d+$/, '').replace('[]', '')
        if (field in errors) (errors as Record<string, string>)[field] = msgs[0] ?? ''
      })
      if (e.errors.form) serverError.value = e.errors.form[0] ?? ''
    } else {
      serverError.value = e.message ?? t('common.error.generic')
    }
  } finally {
    loading.value = false
  }
}

useHead(() => ({ title: `${t('pages.donations.title')} — AUA` }))
</script>

<style lang="scss" scoped>
$hero-bg: #0f0e0c;
$nav-height: 64px;

$amber-08: rgba(212, 146, 30, 0.08);
$amber-25: rgba(212, 146, 30, 0.25);
$amber-glow: rgba(212, 146, 30, 0.18);

$hero-text: #eee8df;
$hero-muted: rgba(238, 232, 223, 0.72);

.donations-page {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background: var(--background);
}

// ─── Hero ─────────────────────────────────────────────────────────
.page-hero {
  position: relative;
  background: $hero-bg;
  padding: calc(#{$nav-height} + 3.5rem) 1.5rem 3.5rem;
  overflow: hidden;

  &__backdrop {
    position: absolute;
    inset: 0;
    pointer-events: none;
  }

  &__glow {
    position: absolute;
    width: 500px;
    height: 500px;
    top: -150px;
    right: -100px;
    border-radius: 50%;
    filter: blur(100px);
    background: $amber-glow;
  }

  &__dots {
    position: absolute;
    inset: 0;
    background-image: radial-gradient(circle, rgba(255, 255, 255, 0.04) 1px, transparent 1px);
    background-size: 24px 24px;
    mask-image: radial-gradient(ellipse 80% 100% at 70% 50%, black 20%, transparent 100%);
  }

  &__body {
    position: relative;
    z-index: 1;
    max-width: 700px;
    margin: 0 auto;
  }

  &__eyebrow {
    display: inline-block;
    font-size: 0.72rem;
    font-weight: 600;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    color: $amber;
    margin-bottom: 0.75rem;
    padding: 0.25rem 0.65rem;
    border: 1px solid $amber-25;
    border-radius: 999px;
    background: $amber-08;
  }

  &__title {
    font-size: clamp(2rem, 5vw, 3rem);
    font-weight: 800;
    letter-spacing: -0.04em;
    color: $hero-text;
    margin: 0 0 0.5rem;
  }

  &__sub {
    font-size: 1rem;
    line-height: 1.6;
    color: $hero-muted;
    padding-bottom: 0;
  }
}

// ─── Content ──────────────────────────────────────────────────────
.donations-content {
  flex: 1;
  padding: 2.5rem 1.5rem 4rem;

  &__inner {
    max-width: 700px;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
  }
}

.rules-card {
  display: flex;
  gap: 0.9rem;
  padding: 1.25rem;
  background: var(--secondary-background);
  border: 1px solid var(--divider);
  border-radius: 14px;

  &__icon {
    font-size: 1.25rem;
    color: var(--accent-color);
    flex-shrink: 0;
    margin-top: 0.15rem;
  }

  &__title {
    font-size: 1rem;
    font-weight: 700;
    color: var(--primary-text);
    margin: 0 0 0.35rem;
  }

  &__text {
    font-size: 0.9rem;
    line-height: 1.6;
    color: var(--secondary-text);
    padding-bottom: 0;
  }
}

.donation-form {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;

  &__section {
    display: flex;
    flex-direction: column;
    gap: 0.4rem;
  }
}

.form-label {
  display: block;
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--secondary-text);
  margin-bottom: 0.4rem;
  letter-spacing: 0.03em;
}

.donation-form__section {
  :deep(.styled-checkbox + label) {
    font-size: 0.9rem;
    color: var(--primary-text);
  }
}

.error-text {
  color: var(--error);
  font-size: 0.875rem;
  margin: 0;
  padding-bottom: 0;
}

.hp-field {
  position: absolute;
  left: -9999px;
  width: 1px;
  height: 1px;
  overflow: hidden;
  opacity: 0;
  pointer-events: none;
}
</style>
