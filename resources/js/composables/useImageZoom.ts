import { ref, type Ref } from 'vue'

interface UseImageZoomReturn {
  scale: Ref<number>
  translateX: Ref<number>
  translateY: Ref<number>
  isZoomed: Ref<boolean>
  containerRef: Ref<HTMLElement | null>
  handleImageClick: () => void
  handleWheel: (e: WheelEvent) => void
  handleMouseDown: (e: MouseEvent) => void
  handleMouseMove: (e: MouseEvent) => void
  handleMouseUp: () => void
  handleDoubleClick: () => void
  handleTouchStart: (e: TouchEvent) => void
  handleTouchMove: (e: TouchEvent) => void
  handleTouchEnd: () => void
  resetZoom: () => void
}

export function useImageZoom(): UseImageZoomReturn {
  const scale = ref(1)
  const translateX = ref(0)
  const translateY = ref(0)
  const isZoomed = ref(false)
  const containerRef = ref<HTMLElement | null>(null)

  let isDragging = false
  let dragStartX = 0
  let dragStartY = 0
  let lastTranslateX = 0
  let lastTranslateY = 0
  let lastPinchDist = 0

  const MIN_SCALE = 1
  const MAX_SCALE = 5
  const ZOOM_STEP = 0.25
  const CLICK_ZOOM = 2.5

  function clamp(v: number, min: number, max: number): number {
    return Math.min(Math.max(v, min), max)
  }

  function handleImageClick(): void {
    if (isDragging) return
    if (scale.value === MIN_SCALE) {
      scale.value = CLICK_ZOOM
      isZoomed.value = true
    }
  }

  function handleWheel(e: WheelEvent): void {
    e.preventDefault()
    const delta = e.deltaY > 0 ? -ZOOM_STEP : ZOOM_STEP
    const newScale = clamp(scale.value + delta, MIN_SCALE, MAX_SCALE)
    scale.value = newScale
    isZoomed.value = newScale > MIN_SCALE
  }

  function handleMouseDown(e: MouseEvent): void {
    if (scale.value <= MIN_SCALE) return
    isDragging = true
    dragStartX = e.clientX
    dragStartY = e.clientY
    lastTranslateX = translateX.value
    lastTranslateY = translateY.value
  }

  function handleMouseMove(e: MouseEvent): void {
    if (!isDragging) return
    const dx = e.clientX - dragStartX
    const dy = e.clientY - dragStartY
    translateX.value = lastTranslateX + dx
    translateY.value = lastTranslateY + dy
  }

  function handleMouseUp(): void {
    isDragging = false
  }

  function handleDoubleClick(): void {
    resetZoom()
  }

  function handleTouchStart(e: TouchEvent): void {
    if (e.touches.length === 1 && scale.value > MIN_SCALE) {
      isDragging = true
      dragStartX = e.touches[0].clientX
      dragStartY = e.touches[0].clientY
      lastTranslateX = translateX.value
      lastTranslateY = translateY.value
    } else if (e.touches.length === 2) {
      isDragging = false
      const dx = e.touches[0].clientX - e.touches[1].clientX
      const dy = e.touches[0].clientY - e.touches[1].clientY
      lastPinchDist = Math.sqrt(dx * dx + dy * dy)
    }
  }

  function handleTouchMove(e: TouchEvent): void {
    if (e.touches.length === 2) {
      e.preventDefault()
      const dx = e.touches[0].clientX - e.touches[1].clientX
      const dy = e.touches[0].clientY - e.touches[1].clientY
      const dist = Math.sqrt(dx * dx + dy * dy)
      if (lastPinchDist > 0) {
        const newScale = clamp(scale.value * (dist / lastPinchDist), MIN_SCALE, MAX_SCALE)
        scale.value = newScale
        isZoomed.value = newScale > MIN_SCALE
      }
      lastPinchDist = dist
    } else if (e.touches.length === 1 && isDragging) {
      const dx = e.touches[0].clientX - dragStartX
      const dy = e.touches[0].clientY - dragStartY
      translateX.value = lastTranslateX + dx
      translateY.value = lastTranslateY + dy
    }
  }

  function handleTouchEnd(): void {
    isDragging = false
    lastPinchDist = 0
  }

  function resetZoom(): void {
    scale.value = 1
    translateX.value = 0
    translateY.value = 0
    isZoomed.value = false
    isDragging = false
  }

  return {
    scale,
    translateX,
    translateY,
    isZoomed,
    containerRef,
    handleImageClick,
    handleWheel,
    handleMouseDown,
    handleMouseMove,
    handleMouseUp,
    handleDoubleClick,
    handleTouchStart,
    handleTouchMove,
    handleTouchEnd,
    resetZoom,
  }
}
