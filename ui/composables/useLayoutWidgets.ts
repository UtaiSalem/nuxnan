export function useLayoutWidgets() {
  return useState('layoutWidgets', () => ({
    hasLeftWidgets: false,
    hasRightWidgets: false,
  }))
}
