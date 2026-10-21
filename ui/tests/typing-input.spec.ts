import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { computed, effectScope, ref } from 'vue'
import { readFileSync } from 'node:fs'
import { createRequire } from 'node:module'
import { parse, compileScript } from '@vue/compiler-sfc'
import ts from 'typescript'
import { useTypingGame } from '../composables/useTypingGame'

vi.mock('../composables/useTypingApi', () => ({
  useTypingApi: () => ({ fetchWords: vi.fn(), fetchSentences: vi.fn(), submitSession: vi.fn() }),
}))

// Run the actual SFC input handlers, wired to the actual game composable.
// This reproduces the input -> update -> submit path without a browser dependency.
function createRound(submitOn: 'both' | 'enter' = 'both', words = ['cat', 'dog']) {
  const scope = effectScope()
  const game = scope.run(() => useTypingGame({ mode: submitOn === 'enter' ? 'sentence_typing' : 'word_typing', lang: 'en', difficulty: 'easy' }))!
  const filename = new URL('../components/games/typing/ui/TypingInput.vue', import.meta.url)
  const { descriptor } = parse(readFileSync(filename, 'utf8'))
  const compiled = compileScript(descriptor, { id: 'typing-input-test', genDefaultAs: 'component' })
  const js = ts.transpileModule(compiled.content, { compilerOptions: { module: ts.ModuleKind.CommonJS, target: ts.ScriptTarget.ES2022 } }).outputText
  const component = new Function('require', 'exports', `${js}; return component`)(createRequire(import.meta.url), {})
  const handlers = component.setup({ modelValue: '', submitOn, autofocus: false }, {
    emit: (event: string, value: string) => {
      if (event === 'update:modelValue') game.onInput(value)
      if (event === 'submit') game.submitWord()
    }, expose: () => {},
  })
  async function start() { await game.init(words); game.startPlaying() }
  function type(text: string) {
    for (let index = 1; index <= text.length; index++) handlers.onInput({ target: { value: text.slice(0, index) } })
  }
  function key(key: string, composing = false) {
    const preventDefault = vi.fn()
    handlers.onKeydown({ key, isComposing: composing, keyCode: composing ? 229 : 0, preventDefault })
    return preventDefault
  }
  return { game, start, type, key, stop: () => { game.reset(); scope.stop() } }
}

beforeEach(() => {
  vi.useFakeTimers()
  vi.stubGlobal('uuid', () => 'test-session')
  vi.stubGlobal('ref', ref)
  vi.stubGlobal('computed', computed)
  vi.stubGlobal('onMounted', () => {})
  vi.stubGlobal('useI18n', () => ({ t: (key: string) => key }))
  vi.stubGlobal('useKeyboardAwareInput', () => {})
})
afterEach(() => { vi.clearAllTimers(); vi.useRealTimers(); vi.unstubAllGlobals() })

describe('typing input and game integration', () => {
  it('scores the typed word before clearing the answer and counts each character once', async () => {
    const round = createRound()
    await round.start()
    round.type('cat')
    vi.advanceTimersByTime(1000)
    expect(round.key(' ')).toHaveBeenCalledOnce()
    expect(round.game.buildResult()).toMatchObject({ correct_chars: 3, total_chars: 3, correct_words: 1, mistakes: 0, max_combo: 1 })
    expect(round.game.wpm.value).toBe(36)
    expect(round.game.currentInput.value).toBe('')
    expect(round.game.currentWord.value).toBe('dog')
    round.stop()
  })

  it('allows spaces inside sentences and submits only with Enter', async () => {
    const round = createRound('enter', ['hello world', 'next sentence'])
    await round.start()
    round.type('hello')
    expect(round.key(' ')).not.toHaveBeenCalled()
    round.type('hello world')
    expect(round.game.currentIndex.value).toBe(0)
    round.key('Enter')
    expect(round.game.buildResult().correct_words).toBe(1)
    expect(round.game.currentIndex.value).toBe(1)
    round.stop()
  })

  it('does not submit an Enter used to confirm composed text', async () => {
    const round = createRound()
    await round.start()
    round.type('cat')
    expect(round.key('Enter', true)).not.toHaveBeenCalled()
    expect(round.game.currentIndex.value).toBe(0)
    round.key('Enter')
    expect(round.game.currentIndex.value).toBe(1)
    round.stop()
  })

  it('builds a correct-answer combo and resets it on a wrong character', async () => {
    const round = createRound('both', ['cat', 'dog', 'sun'])
    await round.start()
    round.type('cat'); round.key(' ')
    round.type('dog'); round.key('Enter')
    expect(round.game.combo.value).toBe(2)
    round.type('x')
    expect(round.game.combo.value).toBe(0)
    expect(round.game.buildResult()).toMatchObject({ correct_words: 2, max_combo: 2, mistakes: 1 })
    round.stop()
  })
})
