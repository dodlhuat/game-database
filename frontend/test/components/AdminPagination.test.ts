import { describe, it, expect } from 'vitest'
import { mountSuspended } from '@nuxt/test-utils/runtime'
import AdminPagination from '~/components/admin/Pagination.vue'

describe('AdminPagination', () => {
  it('stays hidden while everything fits on one page', async () => {
    const wrapper = await mountSuspended(AdminPagination, { props: { page: 1, lastPage: 1 } })
    expect(wrapper.find('.admin-pagination').exists()).toBe(false)
  })

  it('shows where we are and how many entries there are', async () => {
    const wrapper = await mountSuspended(AdminPagination, {
      props: { page: 2, lastPage: 3, total: 61 },
    })
    expect(wrapper.find('.admin-pagination__info').text()).toContain('Seite 2 von 3')
    expect(wrapper.find('.admin-pagination__info').text()).toContain('61 Einträge')
  })

  it('emits the neighbouring page and blocks stepping past the ends', async () => {
    const first = await mountSuspended(AdminPagination, { props: { page: 1, lastPage: 2 } })
    const [prev, next] = first.findAll('button')
    expect(prev!.attributes('disabled')).toBeDefined()
    await next!.trigger('click')
    expect(first.emitted('change')).toEqual([[2]])

    const last = await mountSuspended(AdminPagination, { props: { page: 2, lastPage: 2 } })
    expect(last.findAll('button')[1]!.attributes('disabled')).toBeDefined()
  })
})
