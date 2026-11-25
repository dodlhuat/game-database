import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { mockNuxtImport, mountSuspended } from '@nuxt/test-utils/runtime'
import type { VueWrapper } from '@vue/test-utils'
import { ref } from 'vue'
import HomePage from '~/pages/index.vue'
import { useAuthStore } from '~/stores/auth'

const maintenance = vi.hoisted(() => ({ on: false }))
mockNuxtImport('useMaintenance', () => () => ref(maintenance.on))

let wrapper: VueWrapper | null = null

function login(role: string | null) {
  const auth = useAuthStore()
  auth.token = role ? 'test-token' : null
  auth.user = role
    ? ({ id: 1, name: 'Mia', role, status: 'ACTIVE' } as unknown as typeof auth.user)
    : null
}

const links = () => wrapper!.findAll('a').map((a) => a.attributes('href'))

beforeEach(() => {
  maintenance.on = false
  // happy-dom has no IntersectionObserver; the feature reveal only needs one to exist
  globalThis.IntersectionObserver ??= class {
    observe() {}
    disconnect() {}
  } as unknown as typeof IntersectionObserver
})

afterEach(() => {
  wrapper?.unmount()
  wrapper = null
  login(null)
})

describe('/ (home)', () => {
  it('invites guests to register or log in', async () => {
    login(null)
    wrapper = await mountSuspended(HomePage)

    expect(links()).toEqual(expect.arrayContaining(['/games', '/register', '/login']))
    expect(links()).not.toContain('/dashboard')
    expect(wrapper.find('.cta-strip').exists()).toBe(true)
  })

  it('leads members to their dashboard and donations, not to sign-up', async () => {
    login('MEMBER')
    wrapper = await mountSuspended(HomePage)

    expect(links()).toEqual(expect.arrayContaining(['/dashboard', '/donations']))
    expect(links()).not.toContain('/register')
    expect(links()).not.toContain('/admin')
  })

  it('shows admins the way to the admin area', async () => {
    login('ADMIN')
    wrapper = await mountSuspended(HomePage)

    expect(links()).toContain('/admin')
  })

  it('shows only the maintenance notice with an admin login during maintenance', async () => {
    maintenance.on = true
    wrapper = await mountSuspended(HomePage)

    expect(wrapper.find('.landing').exists()).toBe(false)
    expect(links()).toEqual(['/impressum', '/login'])
  })
})
