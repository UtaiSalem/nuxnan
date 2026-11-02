import { describe, expect, it, vi, beforeEach } from 'vitest'
import { createRouter, createMemoryHistory } from 'vue-router'
import { setActivePinia, createPinia } from 'pinia'
import { useTypingStore } from '../stores/typing'

describe('Route Resolution and Legacy Redirects', () => {
  let router: any

  beforeEach(() => {
    setActivePinia(createPinia())
    router = createRouter({
      history: createMemoryHistory(),
      routes: [
        {
          name: 'courses-slug',
          path: '/courses/:slug()',
          component: { template: '<div>Course Detail</div>' },
        },
        {
          name: 'courses-legacy-redirect',
          path: '/courses/:path(.*)*',
          component: { template: '<div>Course Legacy Redirect</div>' },
        },
        {
          name: 'typing-curriculum-redirect',
          path: '/play/games/typing/curriculum',
          alias: ['/curriculum'],
          component: { template: '<div>Curriculum Redirect</div>' },
        },
        {
          name: 'typing-curriculum-stage-redirect',
          path: '/play/games/typing/curriculum/:stageId',
          alias: ['/curriculum/:stageId'],
          component: { template: '<div>Stage Redirect</div>' },
        },
        {
          name: 'typing-play',
          path: '/play/games/typing/play',
          component: { template: '<div>Typing Play</div>' },
        },
      ],
    })
  })

  describe('Course routes distinction', () => {
    it('resolves single slug course URL to courses-slug', () => {
      const match = router.resolve('/courses/math-101')
      expect(match.name).toBe('courses-slug')
      expect(match.params.slug).toBe('math-101')
    })

    it('resolves multi-level course URL to courses-legacy-redirect', () => {
      const match = router.resolve('/courses/math-101/lessons/5')
      expect(match.name).toBe('courses-legacy-redirect')
      expect(match.params.path).toEqual(['math-101', 'lessons', '5'])
    })

    it('correctly constructs targetPath for redirect', () => {
      const match = router.resolve('/courses/24/groups/1')
      const param = match.params.path
      const targetPath = Array.isArray(param) ? param.join('/') : param || ''
      expect(targetPath).toBe('24/groups/1')
      expect(`/Learn/Courses/${targetPath}`).toBe('/Learn/Courses/24/groups/1')
    })
  })

  describe('Typing curriculum legacy URLs', () => {
    it('resolves /play/games/typing/curriculum to typing-curriculum-redirect', () => {
      const match = router.resolve('/play/games/typing/curriculum')
      expect(match.name).toBe('typing-curriculum-redirect')
    })

    it('resolves /curriculum via alias to typing-curriculum-redirect', () => {
      const match = router.resolve('/curriculum')
      expect(match.name).toBe('typing-curriculum-redirect')
    })

    it('resolves /play/games/typing/curriculum/15 to stage redirect with stageId param', () => {
      const match = router.resolve('/play/games/typing/curriculum/15')
      expect(match.name).toBe('typing-curriculum-stage-redirect')
      expect(match.params.stageId).toBe('15')
    })

    it('resolves /curriculum/15 via alias to stage redirect with stageId param', () => {
      const match = router.resolve('/curriculum/15')
      expect(match.name).toBe('typing-curriculum-stage-redirect')
      expect(match.params.stageId).toBe('15')
    })
  })

  describe('Play page mode selection logic', () => {
    const VALID_MODES = [
      'key_training',
      'word_typing',
      'time_attack',
      'sentence_typing',
      'monster_battle',
      'falling_words',
      'letter_runner',
    ]

    function syncModeFromQuery(store: any, queryMode?: string) {
      if (queryMode && VALID_MODES.includes(queryMode)) {
        store.selectedMode = queryMode
      }
    }

    it('defaults to word_typing when no query parameter is provided', () => {
      const store = useTypingStore()
      expect(store.selectedMode).toBe('word_typing')
      syncModeFromQuery(store, undefined)
      expect(store.selectedMode).toBe('word_typing')
    })

    it('updates store.selectedMode when mode=key_training query is present', () => {
      const store = useTypingStore()
      syncModeFromQuery(store, 'key_training')
      expect(store.selectedMode).toBe('key_training')
    })

    it('updates store.selectedMode for other valid modes', () => {
      const store = useTypingStore()
      syncModeFromQuery(store, 'time_attack')
      expect(store.selectedMode).toBe('time_attack')
    })

    it('ignores invalid mode strings and preserves store.selectedMode', () => {
      const store = useTypingStore()
      store.selectedMode = 'key_training'
      syncModeFromQuery(store, 'non_existent_mode')
      expect(store.selectedMode).toBe('key_training')
    })
  })
})
