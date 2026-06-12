<template>
  <div class="events-page">
    <!-- ── Hero ───────────────────────────────────────────────────── -->
    <section class="page-hero">
      <div class="page-hero__backdrop" aria-hidden="true">
        <div class="page-hero__glow" />
        <div class="page-hero__dots" />
      </div>
      <div class="page-hero__body">
        <p class="page-hero__eyebrow">{{ $t('events.eyebrow') }}</p>
        <h1 class="page-hero__title">{{ $t('events.title') }}</h1>
      </div>
    </section>

    <!-- ── Calendar ───────────────────────────────────────────────── -->
    <div class="events-content">
      <div class="events-content__inner">
        <div v-if="loading" class="events-state">
          <div class="spinner" />
        </div>
        <div v-else ref="calendarEl" class="calendar-wrap" />
      </div>
    </div>

    <!-- ── Event Detail Modal ─────────────────────────────────────── -->
    <Transition name="modal">
      <div v-if="selected" class="modal-overlay" @click.self="selected = null">
        <div class="dialog dialog--event">
          <div class="dialog__header">
            <h3 class="dialog__title">{{ selected.title }}</h3>
            <button class="dialog__close" :aria-label="$t('btn.close')" @click="selected = null">
              <span class="icon icon-close" aria-hidden="true" />
            </button>
          </div>

          <div class="dialog__body">
            <div v-if="selected.image_url" class="event-modal__image">
              <img :src="selected.image_url" :alt="selected.title" />
            </div>

            <div class="event-modal__meta">
              <span class="event-modal__date">
                <span class="icon icon-calendar_today" aria-hidden="true" />
                {{ formatDate(selected.date) }}
              </span>
              <span class="event-modal__time">
                <span class="icon icon-schedule" aria-hidden="true" />
                {{ selected.is_all_day ? $t('events.all_day') : formatTime(selected.time) }}
              </span>
            </div>

            <!-- eslint-disable vue/no-v-html -->
            <div
              v-if="selected.description"
              class="event-modal__description rich-content"
              v-html="selected.description"
            />
            <!-- eslint-enable vue/no-v-html -->
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, watch } from 'vue'
import type { ApiEvent } from '~/composables/useEvents'
import type { Calendar } from '@dodlhuat/basix/js/calendar'

definePageMeta({ middleware: 'auth' })

const { fetchEvents } = useEvents()
const calendarEl = ref<HTMLElement | null>(null)
const loading = ref(true)
const selected = ref<ApiEvent | null>(null)
let cal: InstanceType<typeof Calendar> | null = null
let events: ApiEvent[] = []

function toCalendarEvent(e: ApiEvent) {
  const dateStr = e.date
  let start: Date
  let end: Date

  if (e.is_all_day || !e.time) {
    start = new Date(`${dateStr}T00:00:00`)
    end = new Date(`${dateStr}T23:59:59`)
  } else {
    const [h, m] = e.time.split(':').map(Number)
    start = new Date(`${dateStr}T00:00:00`)
    start.setHours(h, m, 0, 0)
    end = new Date(start)
    end.setHours(h + 1, m, 0, 0)
  }

  return {
    id: String(e.id),
    title: e.title,
    start,
    end,
    allDay: e.is_all_day,
  }
}

function formatDate(iso: string) {
  const [y, m, d] = iso.split('-')
  return `${d}.${m}.${y}`
}

function formatTime(time: string | null) {
  if (!time) return ''
  const [h, m] = time.split(':')
  return `${h}:${m} Uhr`
}

onMounted(async () => {
  try {
    events = await fetchEvents()
  } finally {
    loading.value = false
  }
})

watch([loading, calendarEl], async ([isLoading, el]) => {
  if (isLoading || !el) return

  const { Calendar } = await import('@dodlhuat/basix/js/calendar')

  cal = new Calendar({
    container: el,
    events: events.map(toCalendarEvent),
    view: 'month',
    iconBasePath: '/svg-icons/',
    onEventClick: (event) => {
      const original = events.find((e) => String(e.id) === event.id)
      if (original) selected.value = original
    },
  })
})

onBeforeUnmount(() => {
  cal?.destroy()
  cal = null
})
</script>

<style lang="scss" scoped>
$hero-bg: #0f0e0c;
$nav-height: 64px;
$amber-glow: rgba(212, 146, 30, 0.16);
$hero-text: #eee8df;
$hero-muted: rgba(238, 232, 223, 0.55);

.events-page {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background: var(--background);
}

// ─── Hero ─────────────────────────────────────────────────────────
.page-hero {
  position: relative;
  background: $hero-bg;
  padding: calc(#{$nav-height} + 1.5rem) 1.25rem 1.5rem;
  overflow: hidden;

  &__backdrop {
    position: absolute;
    inset: 0;
    pointer-events: none;
  }

  &__glow {
    position: absolute;
    width: 360px;
    height: 360px;
    top: -100px;
    right: -60px;
    border-radius: 50%;
    filter: blur(80px);
    background: $amber-glow;
  }

  &__dots {
    position: absolute;
    inset: 0;
    background-image: radial-gradient(circle, rgba(255, 255, 255, 0.035) 1px, transparent 1px);
    background-size: 22px 22px;
    mask-image: radial-gradient(ellipse 80% 100% at 70% 50%, black 20%, transparent 100%);
  }

  &__body {
    position: relative;
    z-index: 1;
    max-width: 900px;
    margin: 0 auto;
  }

  &__eyebrow {
    display: inline-block;
    font-size: 0.7rem;
    font-weight: 600;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    color: $amber;
    margin-bottom: 0.45rem;
    padding: 0.18rem 0.55rem;
    border: 1px solid rgba(212, 146, 30, 0.3);
    border-radius: 999px;
    background: rgba(212, 146, 30, 0.08);
  }

  &__title {
    font-size: clamp(1.5rem, 5vw, 2rem);
    font-weight: 800;
    letter-spacing: -0.04em;
    color: $hero-text;
    margin: 0;
  }
}

// ─── Content ──────────────────────────────────────────────────────
.events-content {
  flex: 1;
  padding: 1.5rem 1.25rem 4rem;
  &__inner {
    max-width: 900px;
    margin: 0 auto;
  }
}

.events-state {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 300px;
}

.calendar-wrap {
  width: 100%;
}

// ─── Modal Base ───────────────────────────────────────────────────
.modal-overlay {
  position: fixed;
  inset: 0;
  z-index: 200;
  background: rgba(0, 0, 0, 0.65);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.5rem;
  overflow-y: auto;
}

.dialog {
  background: var(--secondary-background);
  border: 1px solid var(--divider);
  border-radius: 16px;
  padding: 1.75rem;
  width: 100%;
  max-width: 480px;
  box-shadow: 0 25px 60px rgba(0, 0, 0, 0.45);

  &__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: 1.25rem;
  }

  &__title {
    font-size: 1.1rem;
    font-weight: 700;
    letter-spacing: -0.02em;
    color: var(--primary-text);
  }

  &__close {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    flex-shrink: 0;
    background: transparent;
    border: none;
    border-radius: 6px;
    color: var(--secondary-text);
    cursor: pointer;
    transition:
      background 0.15s,
      color 0.15s;
    .icon {
      font-size: 1.125rem;
    }
    &:hover {
      background: var(--background);
      color: var(--primary-text);
    }
  }

  &__body {
    overflow-y: auto;
  }
}

.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.2s ease;
  .dialog {
    transition:
      opacity 0.2s ease,
      transform 0.2s ease;
  }
}
.modal-enter-from,
.modal-leave-to {
  opacity: 0;
  .dialog {
    opacity: 0;
    transform: translateY(8px) scale(0.98);
  }
}

// ─── Event Modal ──────────────────────────────────────────────────
.event-modal {
  &__image {
    margin: -1.25rem -1.25rem 1.25rem;
    img {
      width: 100%;
      max-height: 280px;
      object-fit: cover;
      border-radius: 16px 16px 0 0;
      display: block;
    }
  }

  &__meta {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem 1.5rem;
    margin-bottom: 1rem;
  }

  &__date,
  &__time {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    font-size: 0.85rem;
    font-weight: 500;
    color: var(--secondary-text);
    .icon {
      font-size: 1rem;
      color: $amber;
    }
  }

  &__description {
    font-size: 0.9rem;
    line-height: 1.65;
    color: var(--primary-text);
  }
}

// ─── Dialog override for events ───────────────────────────────────
.dialog--event {
  max-width: 540px;
  .dialog__body {
    overflow-y: auto;
    max-height: 70vh;
  }
}

// ─── Rich content reset ───────────────────────────────────────────
.rich-content :deep(h1),
.rich-content :deep(h2),
.rich-content :deep(h3) {
  margin: 0.75rem 0 0.35rem;
  font-weight: 700;
}
.rich-content :deep(p) {
  margin: 0 0 0.6rem;
}
.rich-content :deep(ul),
.rich-content :deep(ol) {
  margin: 0 0 0.6rem 1.25rem;
}
.rich-content :deep(a) {
  color: $amber;
}
</style>
