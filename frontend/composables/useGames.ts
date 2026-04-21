export interface Game {
  id: number
  title: string
  slug: string
  description: string | null
  short_description: string | null
  category: { id: number; name: string; slug: string } | null
  tags: { id: number; name: string; slug: string }[]
  min_players: number | null
  max_players: number | null
  min_age: number | null
  duration_min: number | null
  duration_max: number | null
  difficulty: 'EASY' | 'MEDIUM' | 'HARD' | 'EXPERT' | null
  languages: { id: number; name: string }[]
  year: number | null
  instagram_url: string | null
  deposit_tokens: number
  cover_image_url: string | null
  available_copies_count: number
  copies_count: number
  reviews_count: number
  is_favorited?: boolean
  already_borrowed?: boolean
  copies?: { id: number; condition: string; is_available: boolean }[]
  images?: { id: number; url: string }[]
  earliest_available_at?: string | null
}

export interface GameFilters {
  search?: string
  category?: string
  tag?: string
  difficulty?: string
  players?: number | string
  duration?: 'short' | 'medium' | 'long' | ''
  language?: number | string
  min_age?: number | string
  available?: boolean
  sort?: string
  page?: number
}

export interface Package {
  id: number
  name: string
  slug: string
  description: string | null
  type: 'CATEGORY' | 'CURATED'
  category: { id: number; name: string; slug: string } | null
  is_active: boolean
  games?: { id: number; title: string; slug: string; available_copies_count?: number }[]
  games_count?: number
  available?: boolean
}

export interface PaginatedGames {
  data: Game[]
  meta: {
    current_page: number
    last_page: number
    per_page: number
    total: number
  }
}

export function useGames() {
  const api = useApi()

  function fetchGames(filters: GameFilters = {}) {
    const params = Object.fromEntries(
      Object.entries(filters).filter(([, v]) => v !== undefined && v !== '' && v !== false),
    ) as Record<string, string | number | boolean>

    return api.get<PaginatedGames>('/games', { params })
  }

  function fetchGame(slug: string) {
    return api.get<{ data: Game }>(`/games/${slug}`)
  }

  function fetchCategories() {
    return api.get<{
      data: {
        id: number
        name: string
        slug: string
        games_count: number
        children: { id: number; name: string; slug: string; games_count: number }[]
      }[]
    }>('/categories')
  }

  function fetchPackages() {
    return api.get<{ data: Package[] }>('/packages')
  }

  function fetchPackage(slug: string) {
    return api.get<{ data: Package }>(`/packages/${slug}`)
  }

  function fetchLanguages() {
    return api.get<{ id: number; name: string }[]>('/languages')
  }

  return { fetchGames, fetchGame, fetchCategories, fetchLanguages, fetchPackages, fetchPackage }
}
