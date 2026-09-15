import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mockNuxtImport, mountSuspended } from '@nuxt/test-utils/runtime'
import { flushPromises } from '@vue/test-utils'
import { useAuthStore } from '~/stores/auth'
import AccountPage from '~/pages/account.vue'

const patchMock = vi.fn()
mockNuxtImport('useApi', () => {
  return () => ({
    get: vi.fn(),
    post: vi.fn(),
    put: vi.fn(),
    patch: patchMock,
    delete: vi.fn(),
    download: vi.fn(),
  })
})

function setUser(overrides: Record<string, unknown> = {}) {
  const auth = useAuthStore()
  auth.token = 'test-token'
  auth.user = {
    id: 1,
    name: 'Test User',
    email: 'test@example.com',
    address: null,
    street: '',
    postal_code: '',
    city: '',
    date_of_birth: '',
    role: 'MEMBER',
    status: 'ACTIVE',
    newsletter_opt_in: false,
    tokens: 10,
    tokens_blocked: 0,
    membership_expires_at: new Date(Date.now() + 86_400_000).toISOString(),
    is_member: true,
    email_verified_at: new Date().toISOString(),
    ...overrides,
  } as Parameters<typeof auth.setUser>[0]

  return auth
}

async function mountAccountPage() {
  const wrapper = await mountSuspended(AccountPage, {
    global: { stubs: { UiDatePicker: true } },
  })
  await flushPromises()
  return wrapper
}

function profileSection(wrapper: Awaited<ReturnType<typeof mountAccountPage>>) {
  return wrapper.findAll('.account-section')[0]!
}

function passwordSection(wrapper: Awaited<ReturnType<typeof mountAccountPage>>) {
  return wrapper.findAll('.account-section')[2]!
}

describe('account.vue — profile', () => {
  beforeEach(() => {
    patchMock.mockReset()
  })

  it('does not send address/DOB fields for a user who cannot borrow', async () => {
    setUser({ role: 'USER', membership_expires_at: null })
    patchMock.mockResolvedValue({ message: 'ok' })

    const wrapper = await mountAccountPage()
    await profileSection(wrapper).find('button').trigger('click')
    await flushPromises()

    expect(patchMock).toHaveBeenCalledWith('/account', {
      name: 'Test User',
      email: 'test@example.com',
    })
  })

  it('includes address/DOB fields for a member', async () => {
    setUser({
      street: 'Teststraße 1',
      postal_code: '1010',
      city: 'Wien',
      date_of_birth: '2000-01-01',
    })
    patchMock.mockResolvedValue({ message: 'ok' })

    const wrapper = await mountAccountPage()
    await profileSection(wrapper).find('button').trigger('click')
    await flushPromises()

    expect(patchMock).toHaveBeenCalledWith('/account', {
      name: 'Test User',
      email: 'test@example.com',
      street: 'Teststraße 1',
      postal_code: '1010',
      city: 'Wien',
      date_of_birth: '2000-01-01',
    })
  })

  it('shows a success message and updates the store on success', async () => {
    const auth = setUser({})
    patchMock.mockResolvedValue({ message: 'ok', user: { ...auth.user, name: 'Neuer Name' } })

    const wrapper = await mountAccountPage()
    await profileSection(wrapper).find('button').trigger('click')
    await flushPromises()

    expect(wrapper.find('.alert-success').exists()).toBe(true)
    expect(auth.user?.name).toBe('Neuer Name')
  })

  it('shows field-level validation errors from the API', async () => {
    setUser({})
    patchMock.mockRejectedValue({ errors: { email: ['E-Mail ist bereits vergeben.'] } })

    const wrapper = await mountAccountPage()
    await profileSection(wrapper).find('button').trigger('click')
    await flushPromises()

    expect(profileSection(wrapper).text()).toContain('E-Mail ist bereits vergeben.')
  })

  it('shows a generic error message when the failure has no field errors', async () => {
    setUser({})
    patchMock.mockRejectedValue({ message: 'Serverfehler.' })

    const wrapper = await mountAccountPage()
    await profileSection(wrapper).find('button').trigger('click')
    await flushPromises()

    expect(wrapper.find('.alert-error').text()).toBe('Serverfehler.')
  })
})

describe('account.vue — newsletter', () => {
  beforeEach(() => {
    patchMock.mockReset()
  })

  it('reverts the toggle when saving the newsletter preference fails', async () => {
    setUser({ newsletter_opt_in: false })
    patchMock.mockRejectedValue(new Error('down'))

    const wrapper = await mountAccountPage()
    const toggle = wrapper.findComponent({ name: 'UiSwitch' })
    await toggle.vm.$emit('update:modelValue', true)
    await toggle.vm.$emit('change')
    await flushPromises()

    expect(toggle.props('modelValue')).toBe(false)
  })
})

describe('account.vue — password', () => {
  beforeEach(() => {
    patchMock.mockReset()
  })

  it('does not submit when the new password confirmation does not match', async () => {
    setUser({})
    const wrapper = await mountAccountPage()
    const inputs = passwordSection(wrapper).findAll('input')
    await inputs[0]!.setValue('current-pw')
    await inputs[1]!.setValue('new-pw')
    await inputs[2]!.setValue('does-not-match')

    await passwordSection(wrapper).find('button').trigger('click')
    await flushPromises()

    expect(patchMock).not.toHaveBeenCalled()
    expect(passwordSection(wrapper).text()).toContain(wrapper.vm.$t('account.password_mismatch'))
  })

  it('does not submit when required fields are empty', async () => {
    setUser({})
    const wrapper = await mountAccountPage()

    await passwordSection(wrapper).find('button').trigger('click')
    await flushPromises()

    expect(patchMock).not.toHaveBeenCalled()
  })

  it('changes the password and clears the form on success', async () => {
    setUser({})
    patchMock.mockResolvedValue({})

    const wrapper = await mountAccountPage()
    const inputs = passwordSection(wrapper).findAll('input')
    await inputs[0]!.setValue('current-pw')
    await inputs[1]!.setValue('new-pw')
    await inputs[2]!.setValue('new-pw')

    await passwordSection(wrapper).find('button').trigger('click')
    await flushPromises()

    expect(patchMock).toHaveBeenCalledWith('/account', {
      current_password: 'current-pw',
      new_password: 'new-pw',
      new_password_confirmation: 'new-pw',
    })
    expect(passwordSection(wrapper).find('.alert-success').exists()).toBe(true)
    expect((inputs[0]!.element as HTMLInputElement).value).toBe('')
  })
})
