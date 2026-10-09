<template>
  <article class="legal-doc">
    <nav v-if="sections.length >= 4" class="legal-doc__toc" :aria-label="$t('legal.toc')">
      <a v-for="s in sections" :key="s.id" :href="`#${s.id}`" class="legal-doc__toc-link">
        <span class="legal-doc__toc-no">{{ s.no }}</span>
        {{ s.text }}
      </a>
    </nav>

    <template v-for="(block, i) in blocks" :key="i">
      <p v-if="block.type === 'meta'" class="legal-doc__meta">{{ block.text }}</p>

      <h2
        v-else-if="block.type === 'h2'"
        :id="block.id"
        class="legal-doc__h2"
        :style="{ '--i': Math.min(i, 8) }"
      >
        <span class="legal-doc__no">{{ block.no }}</span>
        <span class="legal-doc__h2-text">{{ block.text }}</span>
      </h2>

      <h3 v-else-if="block.type === 'h3'" class="legal-doc__h3">
        <span class="legal-doc__letter" aria-hidden="true">{{ block.label }}</span>
        {{ block.text }}
      </h3>

      <p v-else-if="block.type === 'p'" class="legal-doc__p">
        <template v-for="(l, j) in block.lines" :key="j"> <br v-if="j > 0" />{{ l }} </template>
      </p>

      <ul v-else-if="block.type === 'list'" class="legal-doc__list">
        <li v-for="(item, j) in block.items" :key="j">{{ item }}</li>
      </ul>

      <p v-else-if="block.type === 'basis'" class="legal-doc__basis">
        <span class="legal-doc__basis-label">{{ $t('legal.basis') }}</span>
        {{ block.text }}
      </p>

      <p v-else class="legal-doc__footer">{{ block.text }}</p>
    </template>
  </article>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { parseLegalDocument, legalSections } from '~/composables/useLegalDocument'

const props = defineProps<{ content: string }>()

const blocks = computed(() => parseLegalDocument(props.content))
const sections = computed(() => legalSections(blocks.value))
</script>

<style lang="scss" scoped>
$line: var(--divider);
$muted: var(--secondary-text);

.legal-doc {
  color: var(--primary-text);
  font-size: 0.92rem;
  line-height: 1.75;

  &__meta {
    margin: 0 0 1.5rem;
    color: $muted;
    font-size: 0.82rem;
  }

  // ─── Table of contents ──────────────────────────────────────────
  &__toc {
    display: flex;
    flex-wrap: wrap;
    gap: 0.4rem;
    margin: 0 0 2.5rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid $line;
  }

  &__toc-link {
    display: inline-flex;
    align-items: baseline;
    gap: 0.4rem;
    padding: 0.3rem 0.7rem;
    border: 1px solid $line;
    border-radius: 999px;
    font-size: 0.75rem;
    line-height: 1.4;
    color: $muted;
    text-decoration: none;
    transition:
      border-color 0.15s,
      color 0.15s,
      background 0.15s;

    &:hover,
    &:focus-visible {
      border-color: rgba(212, 146, 30, 0.45);
      background: rgba(212, 146, 30, 0.08);
      color: var(--primary-text);
    }
  }

  &__toc-no {
    font-weight: 700;
    font-variant-numeric: tabular-nums;
    color: $amber;
  }

  // ─── Headings ───────────────────────────────────────────────────
  &__h2 {
    position: relative;
    display: flex;
    align-items: baseline;
    gap: 0.75rem;
    margin: 2.75rem 0 1rem;
    padding-top: 1.5rem;
    border-top: 1px solid $line;
    font-size: 1.05rem;
    font-weight: 700;
    letter-spacing: 0.01em;
    line-height: 1.3;
    scroll-margin-top: calc(#{$nav-height} + 1rem);
    animation: legal-rise 0.5s cubic-bezier(0.22, 1, 0.36, 1) both;
    animation-delay: calc(var(--i, 0) * 45ms);
  }

  &__no {
    flex: none;
    min-width: 2.2rem;
    font-size: 1.7rem;
    font-weight: 800;
    letter-spacing: -0.04em;
    font-variant-numeric: tabular-nums;
    line-height: 1;
    color: $amber;
  }

  &__h3 {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    margin: 1.6rem 0 0.4rem;
    font-size: 0.95rem;
    font-weight: 600;
    line-height: 1.4;
  }

  &__letter {
    flex: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1.5rem;
    height: 1.5rem;
    border-radius: 0.4rem;
    background: rgba(212, 146, 30, 0.12);
    border: 1px solid rgba(212, 146, 30, 0.28);
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    color: $amber;
  }

  // ─── Text ───────────────────────────────────────────────────────
  &__p {
    margin: 0 0 0.9rem;
    color: color-mix(in srgb, var(--primary-text) 88%, transparent);
  }

  &__list {
    list-style: none;
    margin: 0 0 1rem;
    padding: 0;

    li {
      position: relative;
      // basix sets list-style-type: disc on `ul li`, so `none` on the ul is not enough
      list-style: none;
      padding: 0.15rem 0 0.15rem 1.35rem;
      color: color-mix(in srgb, var(--primary-text) 88%, transparent);

      &::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0.95em;
        width: 0.7rem;
        height: 2px;
        border-radius: 2px;
        background: $amber;
      }
    }
  }

  &__basis {
    margin: 0.4rem 0 1.1rem;
    padding: 0.6rem 0.9rem;
    border-left: 3px solid rgba(212, 146, 30, 0.5);
    border-radius: 0 0.6rem 0.6rem 0;
    background: rgba(212, 146, 30, 0.06);
    font-size: 0.82rem;
    line-height: 1.6;
    color: $muted;
  }

  &__basis-label {
    display: block;
    margin-bottom: 0.1rem;
    font-size: 0.64rem;
    font-weight: 700;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: $amber;
  }

  &__footer {
    margin: 3rem 0 0;
    padding-top: 1.25rem;
    border-top: 1px solid $line;
    font-size: 0.8rem;
    color: $muted;
  }
}

@keyframes legal-rise {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: none;
  }
}

@media (prefers-reduced-motion: reduce) {
  .legal-doc__h2 {
    animation: none;
  }
}
</style>
