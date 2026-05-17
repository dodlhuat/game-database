import type { components, operations } from './api.generated'

// ─── Schemas (response shapes) ────────────────────────────────────
export type ApiEvent        = components['schemas']['EventResource']
export type ApiGame         = components['schemas']['GameResource']
export type ApiCategory     = components['schemas']['CategoryResource']
export type ApiTag          = components['schemas']['TagResource']
export type ApiCopy         = components['schemas']['CopyResource']
export type ApiLoan         = components['schemas']['LoanResource']
export type ApiUser         = components['schemas']['UserResource']
export type ApiReservation  = components['schemas']['ReservationResource']
export type ApiReview       = components['schemas']['ReviewResource']

// ─── Request bodies ───────────────────────────────────────────────
export type EventStoreBody  = operations['events.store']['requestBody'] extends { content: { 'application/json': infer B } } ? B : never
export type EventUpdateBody = operations['events.update']['requestBody'] extends { content: { 'application/json': infer B } } ? B : never
