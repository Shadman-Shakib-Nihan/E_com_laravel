<template>
  <div class="shop-page">
    <header class="shop-header">
      <div class="logo">GRAVITY<br />CREATION</div>

      <div class="header-actions">
        <button class="icon-btn" aria-label="Search">🔍</button>
        <button class="cart-toggle" @click="cartOpen = true">
          Cart <span class="cart-pill">{{ cartCount }}</span>
        </button>
      </div>
    </header>

    <section class="shop-hero">
      <h1>Shop</h1>

      <div class="filter-pills">
        <button
          v-for="cat in categoriesWithAll"
          :key="cat.id"
          class="pill"
          :class="{ 'pill-active': selectedCategory === String(cat.id) }"
          @click="selectCategory(cat.id)"
        >
          {{ cat.name }}
        </button>
      </div>

      <div v-if="genders.length" class="filter-pills filter-pills-secondary">
        <button
          class="pill pill-outline"
          :class="{ 'pill-active': selectedGender === 'all' }"
          @click="selectedGender = 'all'"
        >
          All Genders
        </button>
        <button
          v-for="g in genders"
          :key="g.id"
          class="pill pill-outline"
          :class="{ 'pill-active': selectedGender === String(g.id) }"
          @click="selectedGender = String(g.id)"
        >
          {{ g.name }}
        </button>
      </div>
    </section>

    <div class="toolbar">
      <span class="result-count">{{ filteredProducts.length }} products</span>
      <label class="sort-control">
        Sort by
        <select v-model="sortBy">
          <option value="default">Featured</option>
          <option value="price-asc">Price: Low to High</option>
          <option value="price-desc">Price: High to Low</option>
          <option value="newest">Newest</option>
          <option value="name-asc">Name: A–Z</option>
        </select>
      </label>
    </div>

    <div v-if="filteredProducts.length === 0" class="state-msg">
      No products match these filters.
    </div>

    <div v-else class="product-grid">
      <div v-for="(product, idx) in filteredProducts" :key="product.id" class="product-card">
        <div
          class="product-image"
          :style="{ background: idx % 2 === 0 ? '#faf6ec' : '#fbeeec' }"
        >
          <span v-if="product.gender" class="gender-badge">{{ product.gender }}</span>
          <img v-if="product.image" :src="product.image" :alt="product.name" loading="lazy" />
        </div>

        <div class="product-info">
          <h3 class="product-name">{{ product.name }}</h3>
          <p v-if="product.description" class="product-description">{{ product.description }}</p>
          <p class="product-price">
            <template v-if="product.discount && product.base_pricing !== product.price">
              <span class="original-price">$ {{ Number(product.base_pricing).toFixed(2) }}</span>
              <span class="discounted-price">$ {{ Number(product.price).toFixed(2) }} USD</span>
            </template>
            <template v-else>
              $ {{ Number(product.price).toFixed(2) }} USD
            </template>
          </p>

          <div v-if="product.sizes && product.sizes.length" class="size-row">
            <span class="size-label">Size</span>
            <div class="size-options">
              <button
                v-for="size in product.sizes"
                :key="size"
                class="size-btn"
                :class="{ 'size-selected': selectedSizes[product.id] === size }"
                @click="selectSize(product.id, size)"
              >
                {{ size }}
              </button>
            </div>
          </div>

          <div class="qty-cart-row">
            <div class="qty-stepper">
              <button class="qty-btn" @click="decrementQty(product.id)" aria-label="Decrease quantity">−</button>
              <span class="qty-value">{{ quantities[product.id] || 1 }}</span>
              <button class="qty-btn" @click="incrementQty(product.id)" aria-label="Increase quantity">+</button>
            </div>
            <button class="add-cart-btn" @click="addToCart(product)">Add to cart</button>
          </div>
        </div>
      </div>
    </div>

    <transition name="fade">
      <div v-if="cartOpen" class="cart-overlay" @click.self="cartOpen = false">
        <transition name="slide">
          <aside v-if="cartOpen" class="cart-drawer">
            <div class="cart-header">
              <h2>Your cart ({{ cartCount }})</h2>
              <button class="icon-btn" @click="cartOpen = false" aria-label="Close cart">×</button>
            </div>

            <div v-if="cart.length === 0" class="cart-empty">Your cart is empty.</div>

            <div v-else class="cart-items">
              <div v-for="item in cart" :key="item.key" class="cart-item">
                <img v-if="item.image" :src="item.image" :alt="item.name" class="cart-item-img" />
                <div class="cart-item-info">
                  <h4>{{ item.name }}</h4>
                  <p v-if="item.size" class="cart-item-size">Size: {{ item.size }}</p>
                  <p class="cart-item-price">$ {{ (item.price * item.qty).toFixed(2) }} USD</p>
                  <div class="qty-stepper qty-stepper-small">
                    <button class="qty-btn" @click="decrementCartQty(item.key)">−</button>
                    <span class="qty-value">{{ item.qty }}</span>
                    <button class="qty-btn" @click="incrementCartQty(item.key)">+</button>
                  </div>
                </div>
                <button class="remove-btn" @click="removeFromCart(item.key)" aria-label="Remove item">🗑</button>
              </div>
            </div>

            <div v-if="cart.length" class="cart-footer">
              <div class="subtotal-row">
                <span>Subtotal</span>
                <span>$ {{ cartSubtotal.toFixed(2) }} USD</span>
              </div>
              <button class="checkout-btn">Checkout</button>
            </div>
          </aside>
        </transition>
      </div>
    </transition>
  </div>
</template>

<script setup lang="ts">
import { computed, reactive, ref } from 'vue'

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

<style scoped>
* {
  box-sizing: border-box;
}

.shop-page {
  font-family: 'Helvetica Neue', Arial, sans-serif;
  color: #14151a;
  max-width: 1280px;
  margin: 0 auto;
  padding: 0 32px 64px;
}

/* ---------- Header ---------- */
.shop-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 28px 0;
}

.logo {
  font-family: 'Arial Black', Arial, sans-serif;
  font-weight: 900;
  font-size: 18px;
  line-height: 1.05;
  letter-spacing: -0.5px;
  text-transform: uppercase;
}

.main-nav {
  display: flex;
  gap: 28px;
}

.nav-link {
  font-size: 14px;
  font-weight: 600;
  letter-spacing: 0.5px;
  text-transform: uppercase;
  color: #14151a;
  text-decoration: none;
}

.nav-link.active {
  text-decoration: underline;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 18px;
}

.icon-btn {
  background: none;
  border: none;
  font-size: 16px;
  cursor: pointer;
}

.cart-toggle {
  display: flex;
  align-items: center;
  gap: 8px;
  background: none;
  border: none;
  font-weight: 600;
  font-size: 14px;
  cursor: pointer;
  color: #14151a;
}

.cart-pill {
  background: #14151a;
  color: #fff;
  border-radius: 50%;
  width: 20px;
  height: 20px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 11px;
}

/* ---------- Hero / filters ---------- */
.shop-hero {
  background: #f1f1f1;
  border-radius: 12px;
  padding: 40px 32px;
  text-align: center;
}

.shop-hero h1 {
  font-size: 40px;
  font-weight: 800;
  margin: 0 0 24px;
}

.filter-pills {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 10px;
}

.filter-pills-secondary {
  margin-top: 12px;
}

.pill {
  background: #fff;
  border: 1px solid #fff;
  border-radius: 999px;
  padding: 10px 20px;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  color: #14151a;
  transition: all 0.15s ease;
}

.pill-outline {
  background: transparent;
  border: 1px solid #ccc;
}

.pill:hover {
  border-color: #14151a;
}

.pill-active {
  background: #14151a;
  color: #fff;
  border-color: #14151a;
}

/* ---------- Toolbar ---------- */
.toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 24px 4px;
  font-size: 14px;
  color: #555;
}

.sort-control select {
  margin-left: 8px;
  padding: 6px 10px;
  border-radius: 6px;
  border: 1px solid #ddd;
  font-size: 14px;
}

.state-msg {
  text-align: center;
  padding: 60px 0;
  color: #777;
  font-size: 15px;
}

.state-error {
  color: #b3261e;
}

/* ---------- Product grid ---------- */
.product-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 24px;
}

@media (max-width: 1024px) {
  .product-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 560px) {
  .product-grid {
    grid-template-columns: 1fr;
  }
  .shop-header {
    flex-direction: column;
    gap: 16px;
  }
  .main-nav {
    flex-wrap: wrap;
    justify-content: center;
  }
}

.product-card {
  display: flex;
  flex-direction: column;
}

.product-image {
  position: relative;
  border-radius: 10px;
  aspect-ratio: 1 / 1;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}

.product-image img {
  max-width: 75%;
  max-height: 75%;
  object-fit: contain;
}

.gender-badge {
  position: absolute;
  top: 10px;
  left: 10px;
  background: rgba(20, 21, 26, 0.85);
  color: #fff;
  font-size: 11px;
  font-weight: 600;
  padding: 4px 9px;
  border-radius: 999px;
  text-transform: uppercase;
}

.product-info {
  padding-top: 12px;
}

.product-name {
  font-size: 15px;
  font-weight: 600;
  margin: 0 0 4px;
}

.product-price {
  font-size: 14px;
  color: #666;
  margin: 0 0 10px;
}

.product-description {
  font-size: 12px;
  color: #888;
  margin: 0 0 8px;
  line-height: 1.4;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.original-price {
  text-decoration: line-through;
  color: #aaa;
  margin-right: 6px;
}

.discounted-price {
  color: #b12704;
  font-weight: 600;
}

/* sizes */
.size-row {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 12px;
}

.size-label {
  font-size: 12px;
  color: #888;
  flex-shrink: 0;
}

.size-options {
  display: flex;
  gap: 6px;
}

.size-btn {
  width: 28px;
  height: 28px;
  border-radius: 6px;
  border: 1px solid #ddd;
  background: #fff;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
}

.size-btn:hover {
  border-color: #14151a;
}

.size-selected {
  background: #14151a;
  color: #fff;
  border-color: #14151a;
}

/* quantity + add to cart */
.qty-cart-row {
  display: flex;
  align-items: center;
  gap: 10px;
}

.qty-stepper {
  display: flex;
  align-items: center;
  border: 1px solid #ddd;
  border-radius: 8px;
  overflow: hidden;
}

.qty-btn {
  background: #f5f5f5;
  border: none;
  width: 28px;
  height: 32px;
  font-size: 15px;
  cursor: pointer;
  line-height: 1;
}

.qty-value {
  width: 28px;
  text-align: center;
  font-size: 13px;
  font-weight: 600;
}

.add-cart-btn {
  flex: 1;
  background: #14151a;
  color: #fff;
  border: none;
  border-radius: 8px;
  padding: 8px 14px;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
}

.add-cart-btn:hover {
  background: #2a2b32;
}

/* ---------- Cart drawer ---------- */
.cart-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.4);
  display: flex;
  justify-content: flex-end;
  z-index: 50;
}

.cart-drawer {
  width: 380px;
  max-width: 100%;
  background: #fff;
  height: 100%;
  display: flex;
  flex-direction: column;
  padding: 24px;
}

.cart-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 16px;
}

.cart-header h2 {
  font-size: 18px;
  margin: 0;
}

.cart-empty {
  color: #888;
  text-align: center;
  margin-top: 60px;
}

.cart-items {
  flex: 1;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.cart-item {
  display: flex;
  gap: 12px;
  align-items: flex-start;
  border-bottom: 1px solid #eee;
  padding-bottom: 16px;
}

.cart-item-img {
  width: 64px;
  height: 64px;
  object-fit: contain;
  background: #f5f5f5;
  border-radius: 8px;
}

.cart-item-info {
  flex: 1;
}

.cart-item-info h4 {
  font-size: 14px;
  margin: 0 0 4px;
}

.cart-item-size,
.cart-item-price {
  font-size: 12px;
  color: #777;
  margin: 0 0 6px;
}

.qty-stepper-small .qty-btn {
  width: 22px;
  height: 24px;
  font-size: 12px;
}

.qty-stepper-small .qty-value {
  width: 22px;
  font-size: 12px;
}

.remove-btn {
  background: none;
  border: none;
  cursor: pointer;
  font-size: 14px;
}

.cart-footer {
  border-top: 1px solid #eee;
  padding-top: 16px;
}

.subtotal-row {
  display: flex;
  justify-content: space-between;
  font-weight: 600;
  margin-bottom: 12px;
}

.checkout-btn {
  width: 100%;
  background: #14151a;
  color: #fff;
  border: none;
  border-radius: 8px;
  padding: 12px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
}

/* transitions */
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
