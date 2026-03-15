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
  language: string | null
  year: number | null
  cover_image_url: string | null
  available_copies_count: number
  copies_count: number
  reviews_count: number
  is_favorited?: boolean
  copies?: { id: number; condition: string; is_available: boolean }[]
}

export interface GameFilters {
  search?: string
  category?: string
  tag?: string
  difficulty?: string
  available?: boolean
  sort?: string
  page?: number
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
    return api.get<{ data: { id: number; name: string; slug: string; games_count: number }[] }>('/categories')
  }

  return { fetchGames, fetchGame, fetchCategories }
}
