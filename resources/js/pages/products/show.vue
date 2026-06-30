<script setup lang="ts">
import { computed, reactive, ref } from 'vue'
import ProductQuickViewModal from '@/components/ProductQuickViewModal.vue'

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

interface CategoryItem {
  id: number
  name: string
}

interface GenderItem {
  id: number
  name: string
}

const props = defineProps<{
  products: ProductItem[]
  categories: CategoryItem[]
  genders: GenderItem[]
}>()

const selectedCategory = ref('all')
const selectedGender = ref('all')
const sortBy = ref('default')

const selectedSizes = reactive<Record<number, string>>({})
const quantities = reactive<Record<number, number>>({})

const cart = ref<Array<{
  key: string
  id: number
  name: string
  price: number
  image: string | null
  size: string | null
  qty: number
}>>([])
const cartOpen = ref(false)

const quickViewProduct = ref<ProductItem | null>(null)
const quickViewOpen = ref(false)

function openQuickView(product: ProductItem) {
  quickViewProduct.value = product
  quickViewOpen.value = true
}

function closeQuickView() {
  quickViewOpen.value = false
}

const quickViewSelectedSize = computed(() => {
  if (!quickViewProduct.value) return null
  return selectedSizes[quickViewProduct.value.id] ?? null
})

const quickViewQuantity = computed(() => {
  if (!quickViewProduct.value) return 1
  return quantities[quickViewProduct.value.id] || 1
})

const categoriesWithAll = computed(() => [
  { id: 'all', name: 'All Products' },
  ...props.categories.map((category) => ({ id: category.id, name: category.name })),
])

const filteredProducts = computed(() => {
  let list = [...props.products]

  if (selectedCategory.value !== 'all') {
    list = list.filter((product) => product.categoryId === Number(selectedCategory.value) || product.category === selectedCategory.value)
  }

  if (selectedGender.value !== 'all') {
    list = list.filter((product) => product.gender === selectedGender.value)
  }

  switch (sortBy.value) {
    case 'price-asc':
      list.sort((a, b) => a.price - b.price)
      break
    case 'price-desc':
      list.sort((a, b) => b.price - a.price)
      break
    case 'newest':
      list.sort((a, b) => new Date(b.createdAt ?? 0).getTime() - new Date(a.createdAt ?? 0).getTime())
      break
    case 'name-asc':
      list.sort((a, b) => a.name.localeCompare(b.name))
      break
  }

  return list
})

function selectCategory(id: string | number) {
  selectedCategory.value = String(id)
}

function selectSize(productId: number, size: string) {
  selectedSizes[productId] = size
}

function incrementQty(id: number) {
  quantities[id] = (quantities[id] || 1) + 1
}

function decrementQty(id: number) {
  quantities[id] = Math.max(1, (quantities[id] || 1) - 1)
}

function addToCart(product: ProductItem) {
  const size = selectedSizes[product.id] || null
  const qty = quantities[product.id] || 1
  const key = `${product.id}-${size || 'nosize'}`

  const existing = cart.value.find((item) => item.key === key)
  if (existing) {
    existing.qty += qty
  } else {
    cart.value.push({
      key,
      id: product.id,
      name: product.name,
      price: Number(product.price),
      image: product.image,
      size,
      qty,
    })
  }

  quantities[product.id] = 1
  cartOpen.value = true
}

function incrementCartQty(key: string) {
  const item = cart.value.find((entry) => entry.key === key)
  if (item) item.qty++
}

function decrementCartQty(key: string) {
  const item = cart.value.find((entry) => entry.key === key)
  if (!item) return
  item.qty--
  if (item.qty <= 0) removeFromCart(key)
}

function removeFromCart(key: string) {
  cart.value = cart.value.filter((entry) => entry.key !== key)
}

const cartCount = computed(() => cart.value.reduce((sum, item) => sum + item.qty, 0))
const cartSubtotal = computed(() => cart.value.reduce((sum, item) => sum + item.price * item.qty, 0))
</script>

<template>
  <div class="mx-auto max-w-[1280px] px-8 pb-16 font-sans text-[#14151a]">
    <!-- ============ Header ============ -->
    <header class="flex items-center justify-between py-7">
      <div class="text-lg font-black uppercase leading-[1.05] tracking-tight">NIHAN<br />CREATION</div>

      <div class="flex items-center gap-4">
        <button class="text-gray-700 hover:text-gray-900" >
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0Z" />
          </svg>
        </button>
        <button class="flex items-center gap-2 text-sm font-semibold" @click="cartOpen = true">
          Cart
          <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-gray-900 text-[11px] text-white">{{ cartCount }}</span>
        </button>
      </div>
    </header>

    <!-- ============ Hero / Filters ============ -->
    <section class="rounded-xl bg-gray-100 p-10 text-center">
      <h1 class="mb-6 text-4xl font-extrabold">Shop</h1>

      <div class="flex flex-wrap justify-center gap-2.5">
        <button
          v-for="cat in categoriesWithAll"
          :key="cat.id"
          class="rounded-full border px-5 py-2.5 text-[13px] font-semibold transition"
          :class="
            selectedCategory === String(cat.id)
              ? 'border-gray-900 bg-gray-900 text-white'
              : 'border-white bg-white text-[#14151a] hover:border-gray-900'
          "
          @click="selectCategory(cat.id)"
        >
          {{ cat.name }}
        </button>
      </div>

      <div v-if="genders.length" class="mt-3 flex flex-wrap justify-center gap-2.5">
        <button
          class="rounded-full border px-5 py-2.5 text-[13px] font-semibold transition"
          :class="
            selectedGender === 'all'
              ? 'border-gray-900 bg-gray-900 text-white'
              : 'border-gray-300 bg-transparent text-[#14151a] hover:border-gray-900'
          "
          @click="selectedGender = 'all'"
        >
          All Genders
        </button>
        <button
          v-for="g in genders"
          :key="g.id"
          class="rounded-full border px-5 py-2.5 text-[13px] font-semibold transition"
          :class="
            selectedGender === String(g.id)
              ? 'border-gray-900 bg-gray-900 text-white'
              : 'border-gray-300 bg-transparent text-[#14151a] hover:border-gray-900'
          "
          @click="selectedGender = String(g.id)"
        >
          {{ g.name }}
        </button>
      </div>
    </section>

    <!-- ============ Toolbar ============ -->
    <div class="flex items-center justify-between py-6 text-sm text-gray-500">
      <span>{{ filteredProducts.length }} products</span>
      <label class="flex items-center gap-2">
        Sort by
        <select v-model="sortBy" class="rounded-md border border-gray-200 px-2.5 py-1.5 text-sm text-gray-700">
          <option value="default">Featured</option>
          <option value="price-asc">Price: Low to High</option>
          <option value="price-desc">Price: High to Low</option>
          <option value="newest">Newest</option>
          <option value="name-asc">Name: A–Z</option>
        </select>
      </label>
    </div>

    <!-- ============ Empty state ============ -->
    <div v-if="filteredProducts.length === 0" class="py-16 text-center text-[15px] text-gray-500">
      No products match these filters.
    </div>

    <!-- ============ Product grid ============ -->
    <div v-else class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
      <div v-for="(product, idx) in filteredProducts" :key="product.id" class="flex flex-col">
        <div
          class="group relative flex aspect-square cursor-pointer items-center justify-center overflow-hidden rounded-[5px]"
          :class="idx % 2 === 0 ? 'bg-[#faf6ec]' : 'bg-[#fbeeec]'"
          @click="openQuickView(product)"
        >
          <span v-if="product.gender" class="absolute left-2.5 top-2.5 z-10 rounded-full bg-gray-900/85 px-2.5 py-1 text-[11px] font-semibold uppercase text-white">
            {{ product.gender }}
          </span>
          <img v-if="product.image" :src="product.image" :alt="product.name" loading="lazy" class="max-h-[75%] max-w-[75%] object-contain transition duration-300 group-hover:scale-105" />
          <div class="absolute inset-0 flex items-end justify-center pb-4 opacity-0 transition-opacity duration-200 group-hover:opacity-100">
            <span class="rounded-full bg-white/90 px-4 py-1.5 text-[11px] font-semibold uppercase tracking-wider text-gray-800 shadow-sm backdrop-blur-sm">
              Quick View
            </span>
          </div>
        </div>

        <div class="pt-3">
          <h3 class="mb-1 text-[15px] font-semibold">{{ product.name }}</h3>
          <p v-if="product.description" class="mb-2 line-clamp-2 text-xs leading-snug text-gray-500">{{ product.description }}</p>
          <p class="mb-2.5 text-sm text-gray-600">
            <template v-if="product.discount && product.base_pricing !== product.price">
              <span class="mr-1.5 text-gray-400 line-through">$ {{ Number(product.base_pricing).toFixed(2) }}</span>
              <span class="font-semibold text-[#b12704]">$ {{ Number(product.price).toFixed(2) }} USD</span>
            </template>
            <template v-else> $ {{ Number(product.price).toFixed(2) }} USD </template>
          </p>

          <div v-if="product.sizes && product.sizes.length" class="mb-3 flex items-center gap-2.5">
            <span class="shrink-0 text-xs text-gray-500">Size</span>
            <div class="flex gap-1.5">
              <button
                v-for="size in product.sizes"
                :key="size"
                class="h-7 w-7 rounded-md border text-xs font-semibold transition"
                :class="
                  selectedSizes[product.id] === size
                    ? 'border-gray-900 bg-gray-900 text-white'
                    : 'border-gray-200 bg-white text-gray-700 hover:border-gray-900'
                "
                @click="selectSize(product.id, size)"
              >
                {{ size }}
              </button>
            </div>
          </div>

          <div class="flex items-center gap-2.5">
            <div class="flex items-center overflow-hidden rounded-lg border border-gray-200">
              <button class="h-8 w-7 bg-gray-100 text-[15px] hover:bg-gray-200" aria-label="Decrease quantity" @click="decrementQty(product.id)">−</button>
              <span class="w-7 text-center text-[13px] font-semibold">{{ quantities[product.id] || 1 }}</span>
              <button class="h-8 w-7 bg-gray-100 text-[15px] hover:bg-gray-200" aria-label="Increase quantity" @click="incrementQty(product.id)">+</button>
            </div>
            <button class="flex-1 rounded-lg bg-gray-900 px-3.5 py-2 text-[13px] font-semibold text-white hover:bg-gray-800" @click="addToCart(product)">
              Add to cart
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ============ Cart drawer ============ -->
    <transition name="fade">
      <div v-if="cartOpen" class="fixed inset-0 z-50 flex justify-end bg-black/40" @click.self="cartOpen = false">
        <transition name="slide">
          <aside v-if="cartOpen" class="flex h-full w-[380px] max-w-full flex-col bg-white p-6">
            <div class="mb-4 flex items-center justify-between">
              <h2 class="text-lg font-semibold">Your cart ({{ cartCount }})</h2>
              <button class="text-xl text-gray-500 hover:text-gray-900" aria-label="Close cart" @click="cartOpen = false">×</button>
            </div>

            <div v-if="cart.length === 0" class="mt-16 text-center text-gray-500">Your cart is empty.</div>

            <div v-else class="flex-1 space-y-4 overflow-y-auto">
              <div v-for="item in cart" :key="item.key" class="flex items-start gap-3 border-b border-gray-100 pb-4">
                <img v-if="item.image" :src="item.image" :alt="item.name" class="h-16 w-16 rounded-lg bg-gray-100 object-contain" />
                <div class="flex-1">
                  <h4 class="mb-1 text-sm">{{ item.name }}</h4>
                  <p v-if="item.size" class="mb-1.5 text-xs text-gray-500">Size: {{ item.size }}</p>
                  <p class="mb-1.5 text-xs text-gray-500">$ {{ (item.price * item.qty).toFixed(2) }} USD</p>
                  <div class="flex items-center overflow-hidden rounded-lg border border-gray-200">
                    <button class="h-6 w-[22px] text-xs hover:bg-gray-50" @click="decrementCartQty(item.key)">−</button>
                    <span class="w-[22px] text-center text-xs font-semibold">{{ item.qty }}</span>
                    <button class="h-6 w-[22px] text-xs hover:bg-gray-50" @click="incrementCartQty(item.key)">+</button>
                  </div>
                </div>
                <button class="text-sm" aria-label="Remove item" @click="removeFromCart(item.key)">🗑</button>
              </div>
            </div>

            <div v-if="cart.length" class="mt-4 border-t border-gray-100 pt-4">
              <div class="mb-3 flex justify-between font-semibold">
                <span>Subtotal</span>
                <span>$ {{ cartSubtotal.toFixed(2) }} USD</span>
              </div>
              <button class="w-full rounded-lg bg-gray-900 py-3 text-sm font-semibold text-white hover:bg-gray-800">Checkout</button>
            </div>
          </aside>
        </transition>
      </div>
    </transition>

    <!-- ============ Quick View Modal ============ -->
    <ProductQuickViewModal
      :product="quickViewProduct"
      :open="quickViewOpen"
      :selected-size="quickViewSelectedSize"
      :quantity="quickViewQuantity"
      @close="closeQuickView"
      @select-size="(size: string) => quickViewProduct && selectSize(quickViewProduct.id, size)"
      @increment-qty="() => quickViewProduct && incrementQty(quickViewProduct.id)"
      @decrement-qty="() => quickViewProduct && decrementQty(quickViewProduct.id)"
      @add-to-cart="(product: ProductItem) => addToCart(product)"
    />
  </div>
</template>

<style scoped>
/* Vue's <transition> needs real CSS classes for enter/leave — these two
   small rules are the only thing Tailwind utility classes can't express. */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.slide-enter-active,
.slide-leave-active {
  transition: transform 0.25s ease;
}
.slide-enter-from,
.slide-leave-to {
  transform: translateX(100%);
}
</style>
