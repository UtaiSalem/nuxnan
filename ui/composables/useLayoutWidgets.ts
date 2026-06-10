import { onBeforeUnmount, toValue, watch, type MaybeRefOrGetter } from 'vue'

type LayoutWidgetsState = {
  hasLeftWidgets: boolean
  hasRightWidgets: boolean
  isLeftPanelOpen: boolean
  isRightPanelOpen: boolean
  isTableLayout: boolean
  activeOwnerId: string | null
}

let layoutWidgetOwnerSequence = 0

export function useLayoutWidgets() {
  return useState<LayoutWidgetsState>('layoutWidgets', () => ({
    hasLeftWidgets: false,
    hasRightWidgets: false,
    isLeftPanelOpen: false,
    isRightPanelOpen: false,
    isTableLayout: false,
    activeOwnerId: null,
  }))
}

export function usePageLayoutWidgets(options: {
  left?: MaybeRefOrGetter<boolean>
  right?: MaybeRefOrGetter<boolean>
  tableLayout?: MaybeRefOrGetter<boolean>
}) {
  const layoutWidgets = useLayoutWidgets()

  // SSR-safety: do NOT mutate the shared layout state on the server.
  // main.vue (the layout) renders BEFORE this page's setup runs, so any flag we
  // set here would be serialized into the hydration payload as `true` while the
  // layout HTML was already rendered with the previous value (`false`).
  // That divergence is exactly what triggers the "Hydration class/children
  // mismatch" warnings on the grid columns. Applying the flags on the client
  // only keeps the server HTML and the hydration payload in agreement; the
  // widget columns then appear via a normal post-hydration update.
  if (import.meta.server) return layoutWidgets

  const ownerId = `layout-widgets-${++layoutWidgetOwnerSequence}`

  watch(
    () => [
      Boolean(toValue(options.left ?? false)),
      Boolean(toValue(options.right ?? false)),
      Boolean(toValue(options.tableLayout ?? false)),
    ] as const,
    ([hasLeftWidgets, hasRightWidgets, isTableLayout]) => {
      const state = layoutWidgets.value

      state.activeOwnerId = ownerId
      state.hasLeftWidgets = hasLeftWidgets
      state.hasRightWidgets = hasRightWidgets
      state.isTableLayout = isTableLayout

      if (!hasLeftWidgets) state.isLeftPanelOpen = false
      if (!hasRightWidgets) state.isRightPanelOpen = false
    },
    { immediate: true }
  )

  onBeforeUnmount(() => {
    const state = layoutWidgets.value

    if (state.activeOwnerId !== ownerId) return

    state.activeOwnerId = null
    state.hasLeftWidgets = false
    state.hasRightWidgets = false
    state.isLeftPanelOpen = false
    state.isRightPanelOpen = false
    state.isTableLayout = false
  })

  return layoutWidgets
}
