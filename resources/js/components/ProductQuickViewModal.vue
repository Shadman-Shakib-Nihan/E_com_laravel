<script setup lang="ts">
import { computed, watch, ref, onMounted, onUnmounted } from 'vue'
import { useImageZoom } from '@/composables/useImageZoom'

interface ProductImageItem {
  id: number
  image: string
}

interface ProductItem {
  id: number
  name: string
  description: string | null
  base_pricing: number
  price: number
  discount: number | null
  discount_type: string | null
  stock: number
  image: string | null
  images: ProductImageItem[]
  categoryId: number | null
  category: string | null
  sizes: string[]
  gender: string | null
  createdAt: string | null
}

const props = defineProps<{
  product: ProductItem | null
  open: boolean
  selectedSize: string | null
  quantity: number
}>()

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'select-size', size: string): void
  (e: 'increment-qty'): void
  (e: 'decrement-qty'): void
  (e: 'add-to-cart', product: ProductItem): void
}>()

const currentIndex = ref(0)
const imageLoaded = ref(false)
const showZoomIcon = ref(false)

const {
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
} = useImageZoom()

const gallery = computed<ProductImageItem[]>(() => {
  if (!props.product) return []
  if (props.product.images && props.product.images.length > 0) {
    return props.product.images
  }
  if (props.product.image) {
    return [{ id: 0, image: props.product.image }]
  }
  return []
})

const currentImage = computed(() => {
  return gallery.value[currentIndex.value]?.image ?? null
})

const hasMultipleImages = computed(() => gallery.value.length > 1)

const discountPercent = computed(() => {
  if (!props.product?.discount || !props.product?.base_pricing) return null
  if (props.product.base_pricing === props.product.price) return null
  return Math.round(props.product.discount)
})

const inStock = computed(() => {
  return (props.product?.stock ?? 0) > 0
})

function selectImage(index: number): void {
  if (index === currentIndex.value) return
  resetZoom()
  imageLoaded.value = false
  currentIndex.value = index
}

function prevImage(): void {
  if (gallery.value.length <= 1) return
  resetZoom()
  imageLoaded.value = false
  currentIndex.value = (currentIndex.value - 1 + gallery.value.length) % gallery.value.length
}

function nextImage(): void {
  if (gallery.value.length <= 1) return
  resetZoom()
  imageLoaded.value = false
  currentIndex.value = (currentIndex.value + 1) % gallery.value.length
}

function handleKeydown(e: KeyboardEvent): void {
  if (!props.open) return
  if (e.key === 'Escape') {
    emit('close')
  } else if (e.key === 'ArrowLeft') {
    prevImage()
  } else if (e.key === 'ArrowRight') {
    nextImage()
  }
}

function handleOverlayClick(e: MouseEvent): void {
  if ((e.target as HTMLElement).classList.contains('quick-view-overlay')) {
    emit('close')
  }
}

function handleAddToCart(): void {
  if (!props.product || !inStock.value) return
  emit('add-to-cart', props.product)
}

watch(
  () => props.open,
  (val) => {
    if (val) {
      currentIndex.value = 0
      imageLoaded.value = false
      resetZoom()
      document.body.style.overflow = 'hidden'
    } else {
      document.body.style.overflow = ''
      resetZoom()
    }
  },
)

watch(
  () => props.product,
  () => {
    currentIndex.value = 0
    imageLoaded.value = false
    resetZoom()
  },
)

onMounted(() => {
  document.addEventListener('keydown', handleKeydown)
})

onUnmounted(() => {
  document.removeEventListener('keydown', handleKeydown)
  document.body.style.overflow = ''
})
</script>

<template>
  <Teleport to="body">
    <Transition name="qv-fade">
      <div
        v-if="open && product"
        class="quick-view-overlay fixed inset-0 z-[100] flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
        @click="handleOverlayClick"
      >
        <Transition name="qv-scale" appear>
          <div
            v-if="open"
            class="relative flex max-h-[90vh] w-full max-w-5xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl md:flex-row"
            @click.stop
          >
            <!-- Close button -->
            <button
              class="absolute right-3 top-3 z-10 flex h-8 w-8 items-center justify-center rounded-full bg-white/90 text-gray-700 shadow-md transition hover:bg-white hover:text-gray-900"
              aria-label="Close quick view"
              @click="emit('close')"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>

            <!-- ============ LEFT: Image Gallery ============ -->
            <div class="relative flex w-full flex-col bg-gray-50 md:w-3/5">
              <!-- Main image area -->
              <div
                ref="containerRef"
                class="relative flex flex-1 cursor-grab items-center justify-center overflow-hidden p-6"
                :class="{ 'cursor-zoom-in': !isZoomed, 'cursor-grabbing': isZoomed }"
                @click="handleImageClick"
                @wheel.prevent="handleWheel"
                @mousedown="handleMouseDown"
                @mousemove="handleMouseMove"
                @mouseup="handleMouseUp"
                @mouseleave="handleMouseUp; showZoomIcon = false"
                @dblclick="handleDoubleClick"
                @touchstart.passive="handleTouchStart"
                @touchmove.passive="handleTouchMove"
                @touchend="handleTouchEnd"
                @mouseenter="showZoomIcon = true"
              >
                <!-- Loading -->
                <div
                  v-if="!imageLoaded"
                  class="absolute inset-0 flex items-center justify-center"
                >
                  <div class="h-8 w-8 animate-spin rounded-full border-2 border-gray-300 border-t-gray-900" />
                </div>

                <!-- Image -->
                <img
                  v-show="currentImage"
                  :src="currentImage ?? ''"
                  :alt="product.name"
                  class="max-h-full max-w-full select-none object-contain transition-opacity duration-300"
                  :class="{ 'opacity-0': !imageLoaded, 'opacity-100': imageLoaded }"
                  :style="{
                    transform: `scale(${scale}) translate(${translateX}px, ${translateY}px)`,
                  }"
                  draggable="false"
                  @load="imageLoaded = true"
                />

                <!-- Zoom icon -->
                <div
                  v-show="showZoomIcon && !isZoomed"
                  class="pointer-events-none absolute bottom-4 right-4 rounded-full bg-white/80 p-2 shadow"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0Z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 8v6m-3-3h6" />
                  </svg>
                </div>

                <!-- Prev / Next arrows -->
                <button
                  v-if="hasMultipleImages"
                  class="absolute left-3 top-1/2 -translate-y-1/2 flex h-8 w-8 items-center justify-center rounded-full bg-white/80 text-gray-700 shadow transition hover:bg-white hover:text-gray-900"
                  aria-label="Previous image"
                  @click.stop="prevImage"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                  </svg>
                </button>
                <button
                  v-if="hasMultipleImages"
                  class="absolute right-3 top-1/2 -translate-y-1/2 flex h-8 w-8 items-center justify-center rounded-full bg-white/80 text-gray-700 shadow transition hover:bg-white hover:text-gray-900"
                  aria-label="Next image"
                  @click.stop="nextImage"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                  </svg>
                </button>
              </div>

              <!-- Thumbnails -->
              <div
                v-if="hasMultipleImages"
                class="flex items-center justify-center gap-2 border-t border-gray-200 px-4 py-3"
              >
                <button
                  v-for="(img, idx) in gallery"
                  :key="img.id"
                  class="h-14 w-14 flex-shrink-0 overflow-hidden rounded-lg border-2 transition"
                  :class="
                    idx === currentIndex
                      ? 'border-gray-900'
                      : 'border-transparent opacity-60 hover:opacity-100'
                  "
                  @click="selectImage(idx)"
                >
                  <img
                    :src="img.image"
                    :alt="`${product.name} ${idx + 1}`"
                    class="h-full w-full object-cover"
                  />
                </button>
              </div>
            </div>

            <!-- ============ RIGHT: Product Details ============ -->
            <div class="flex w-full flex-col overflow-y-auto p-6 md:w-2/5 md:p-8">
              <!-- Category & Gender badges -->
              <div class="mb-3 flex flex-wrap items-center gap-2">
                <span
                  v-if="product.category"
                  class="rounded-full bg-gray-100 px-3 py-1 text-[11px] font-semibold uppercase tracking-wider text-gray-600"
                >
                  {{ product.category }}
                </span>
                <span
                  v-if="product.gender"
                  class="rounded-full bg-gray-100 px-3 py-1 text-[11px] font-semibold uppercase tracking-wider text-gray-600"
                >
                  {{ product.gender }}
                </span>
              </div>

              <!-- Name -->
              <h2 class="text-xl font-bold text-gray-900 md:text-2xl">
                {{ product.name }}
              </h2>

              <!-- Description -->
              <p
                v-if="product.description"
                class="mt-3 text-sm leading-relaxed text-gray-500"
              >
                {{ product.description }}
              </p>

              <!-- Stock -->
              <p class="mt-3 text-xs font-medium" :class="inStock ? 'text-green-600' : 'text-red-500'">
                {{ inStock ? `In Stock (${product.stock} available)` : 'Out of Stock' }}
              </p>

              <!-- Price -->
              <div class="mt-4">
                <template v-if="product.discount && product.base_pricing !== product.price">
                  <span class="text-lg text-gray-400 line-through">${{ Number(product.base_pricing).toFixed(2) }}</span>
                  <span class="ml-2 text-2xl font-bold text-[#b12704]">${{ Number(product.price).toFixed(2) }}</span>
                  <span
                    v-if="discountPercent"
                    class="ml-2 rounded bg-red-100 px-2 py-0.5 text-xs font-bold text-red-600"
                  >
                    -{{ discountPercent }}%
                  </span>
                </template>
                <template v-else>
                  <span class="text-2xl font-bold text-gray-900">${{ Number(product.price).toFixed(2) }}</span>
                </template>
              </div>

              <!-- Size selection -->
              <div v-if="product.sizes && product.sizes.length" class="mt-6">
                <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-gray-500">
                  Size
                </p>
                <div class="flex flex-wrap gap-2">
                  <button
                    v-for="size in product.sizes"
                    :key="size"
                    class="flex h-9 w-9 items-center justify-center rounded-md border text-xs font-semibold transition"
                    :class="
                      selectedSize === size
                        ? 'border-gray-900 bg-gray-900 text-white'
                        : 'border-gray-200 bg-white text-gray-700 hover:border-gray-900'
                    "
                    @click="emit('select-size', size)"
                  >
                    {{ size }}
                  </button>
                </div>
              </div>

              <!-- Quantity + Add to Cart -->
              <div class="mt-6 flex items-center gap-3">
                <div class="flex items-center overflow-hidden rounded-lg border border-gray-200">
                  <button
                    class="flex h-9 w-8 items-center justify-center bg-gray-100 text-[15px] hover:bg-gray-200"
                    aria-label="Decrease quantity"
                    @click="emit('decrement-qty')"
                  >
                    −
                  </button>
                  <span class="flex h-9 w-8 items-center justify-center text-[13px] font-semibold">
                    {{ quantity }}
                  </span>
                  <button
                    class="flex h-9 w-8 items-center justify-center bg-gray-100 text-[15px] hover:bg-gray-200"
                    aria-label="Increase quantity"
                    @click="emit('increment-qty')"
                  >
                    +
                  </button>
                </div>

                <button
                  class="flex flex-1 items-center justify-center gap-2 rounded-lg bg-gray-900 px-5 py-2.5 text-[13px] font-semibold text-white transition hover:bg-gray-800 disabled:cursor-not-allowed disabled:opacity-50"
                  :disabled="!inStock"
                  @click="handleAddToCart"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z" />
                  </svg>
                  Add to Cart
                </button>
              </div>
            </div>
          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.qv-fade-enter-active,
.qv-fade-leave-active {
  transition: opacity 0.2s ease;
}
.qv-fade-enter-from,
.qv-fade-leave-to {
  opacity: 0;
}

.qv-scale-enter-active {
  transition: transform 0.2s ease, opacity 0.2s ease;
}
.qv-scale-leave-active {
  transition: transform 0.15s ease, opacity 0.15s ease;
}
.qv-scale-enter-from {
  transform: scale(0.9);
  opacity: 0;
}
.qv-scale-leave-to {
  transform: scale(0.9);
  opacity: 0;
}
</style>
