<script setup lang="ts">
import { computed, watch, ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3';
import { dashboard } from '@/routes';
import { create as productCreate, edit as productEdit } from '@/routes/product';
import { toast } from 'vue-sonner';

interface PaginatedProducts {
    data: {
        id: number;
        name: string;
        description: string;
        base_pricing: number;
        stock: number;
        discount: number | null;
        discount_type: string | null;
        category: string | null;
        sizes: string[];
        gender: string | null;
        image: string | null;
    }[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
}

interface CategoryItem {
    id: number;
    name: string;
}

interface GenderItem {
    id: string;
    name: string;
}

const props = defineProps<{
    products: PaginatedProducts;
    categories?: CategoryItem[];
    genders?: GenderItem[];
    sizes?: string[];
    filters?: {
        search: string | null;
        category: string | null;
        gender: string | null;
        size: string | null;
        min_price: string | null;
        max_price: string | null;
    };
}>();

const selectedMinPrice = ref(props.filters?.min_price ?? '');
const selectedMaxPrice = ref(props.filters?.max_price ?? '');

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Product',
                href: dashboard(),
            },
        ],
    },
});

const search = ref(props.filters?.search ?? '')
const selectedCategory = ref(props.filters?.category ?? '')
const selectedGender = ref(props.filters?.gender ?? '')
const selectedSize = ref(props.filters?.size ?? '')

let searchTimeout: ReturnType<typeof setTimeout> | null = null

function onSearchInput() {
    if (searchTimeout) clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => applyFilters(), 300)
}

function applyFilters() {
    const params: Record<string, string> = {}
    if (search.value) params.search = search.value
    if (selectedCategory.value) params.category = selectedCategory.value
    if (selectedGender.value) params.gender = selectedGender.value
    if (selectedSize.value) params.size = selectedSize.value
    if (selectedMinPrice.value) params.min_price = selectedMinPrice.value
    if (selectedMaxPrice.value) params.max_price = selectedMaxPrice.value

    router.get(window.location.pathname, params, {
        preserveState: true,
        preserveScroll: true,
    })
}

function goToPage(page: number) {
    const params: Record<string, string> = {}
    if (search.value) params.search = search.value
    if (selectedCategory.value) params.category = selectedCategory.value
    if (selectedGender.value) params.gender = selectedGender.value
    if (selectedSize.value) params.size = selectedSize.value
    if (selectedMinPrice.value) params.min_price = selectedMinPrice.value
    if (selectedMaxPrice.value) params.max_price = selectedMaxPrice.value

    router.get(window.location.pathname, { ...params, page }, {
        preserveState: true,
        preserveScroll: true,
    })
}

const pageRange = computed(() => {
    const current = props.products.current_page
    const last = props.products.last_page
    const range: (number | 'ellipsis')[] = []
    if (last <= 7) {
        for (let i = 1; i <= last; i++) range.push(i)
        return range
    }
    range.push(1)
    if (current > 3) range.push('ellipsis')
    const start = Math.max(2, current - 1)
    const end = Math.min(last - 1, current + 1)
    for (let i = start; i <= end; i++) range.push(i)
    if (current < last - 2) range.push('ellipsis')
    range.push(last)
    return range
})

function deleteProduct(id: number, name: string) {
    if (!confirm(`Are you sure you want to delete "${name}"?`)) return;

    router.delete(`/product/${id}`, {
        onSuccess: () => {
            toast.success('Product deleted successfully.');
        },
        onError: () => {
            toast.error('Failed to delete product.');
        },
    });
}
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <Head title="Products" />

        <div class="rounded-xl border border-sidebar-border/70 bg-card p-6 shadow-sm dark:border-sidebar-border">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-semibold tracking-normal">Products</h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Manage your product inventory
                    </p>
                </div>
                <Link
                    :href="productCreate()"
                    class="inline-flex items-center gap-2 rounded-full bg-[#b5e2a0] px-5 py-2 text-sm font-semibold text-gray-800 transition hover:bg-[#a3d48e]"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Product
                </Link>
            </div>

            <!-- Filter bar -->
            <div class="mb-4 flex flex-wrap items-center gap-3">
                <div class="relative min-w-[200px]">
                    <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0Z" />
                    </svg>
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search by name..."
                        class="h-9 w-full rounded-lg border border-gray-200 bg-white pl-9 pr-3 text-sm outline-none transition focus:border-gray-400"
                        @input="onSearchInput"
                    />
                </div>

                <select
                    v-model="selectedCategory"
                    class="h-9 rounded-lg border border-gray-200 bg-white px-3 text-sm outline-none transition focus:border-gray-400"
                    @change="applyFilters"
                >
                    <option value="">All Categories</option>
                    <option v-for="cat in categories" :key="cat.id" :value="String(cat.id)">{{ cat.name }}</option>
                </select>

                <select
                    v-model="selectedGender"
                    class="h-9 rounded-lg border border-gray-200 bg-white px-3 text-sm outline-none transition focus:border-gray-400"
                    @change="applyFilters"
                >
                    <option value="">All Genders</option>
                    <option v-for="g in genders" :key="g.id" :value="g.id">{{ g.name }}</option>
                </select>

                <select
                    v-model="selectedSize"
                    class="h-9 rounded-lg border border-gray-200 bg-white px-3 text-sm outline-none transition focus:border-gray-400"
                    @change="applyFilters"
                >
                    <option value="">All Sizes</option>
                    <option v-for="s in sizes" :key="s" :value="s">{{ s }}</option>
                </select>

                <div class="flex items-center gap-2">
                    <input
                        v-model="selectedMinPrice"
                        type="number"
                        placeholder="Min Price"
                        class="h-9 w-full rounded-lg border border-gray-200 bg-white px-3 text-sm outline-none transition focus:border-gray-400"
                        @input="onSearchInput"
                    />
                    <span class="text-muted-foreground">-</span>
                    <input
                        v-model="selectedMaxPrice"
                        type="number"
                        placeholder="Max Price"
                        class="h-9 w-full rounded-lg border border-gray-200 bg-white px-3 text-sm outline-none transition focus:border-gray-400"
                        @input="onSearchInput"
                    />
                </div>
            </div>

            <div v-if="products.data.length === 0" class="py-12 text-center">
                <p class="text-muted-foreground">No products match your filters.</p>
            </div>

            <div v-else>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                                <th class="px-4 py-3">Image</th>
                                <th class="px-4 py-3">Name</th>
                                <th class="px-4 py-3">Category</th>
                                <th class="px-4 py-3">Price</th>
                                <th class="px-4 py-3">Stock</th>
                                <th class="px-4 py-3">Sizes</th>
                                <th class="px-4 py-3">Gender</th>
                                <th class="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="product in products.data" :key="product.id" class="border-b border-gray-100 transition hover:bg-gray-50/50">
                                <td class="px-4 py-3">
                                    <img
                                        v-if="product.image"
                                        :src="product.image"
                                        :alt="product.name"
                                        class="h-12 w-12 rounded-lg object-cover"
                                    />
                                    <div v-else class="flex h-12 w-12 items-center justify-center rounded-lg bg-gray-100 text-xs text-muted-foreground">
                                        No img
                                    </div>
                                </td>
                                <td class="px-4 py-3 font-medium">{{ product.name }}</td>
                                <td class="px-4 py-3 text-muted-foreground">{{ product.category ?? '-' }}</td>
                                <td class="px-4 py-3">${{ Number(product.base_pricing).toFixed(2) }}</td>
                                <td class="px-4 py-3">
                                    <span :class="product.stock > 0 ? 'text-green-600' : 'text-red-500'">
                                        {{ product.stock }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-1">
                                        <span
                                            v-for="size in product.sizes"
                                            :key="size"
                                            class="inline-flex items-center rounded-md bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700"
                                        >
                                            {{ size }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-muted-foreground">{{ product.gender ?? '-' }}</td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <Link
                                            :href="productEdit({ product: product.id }).url"
                                            class="inline-flex items-center gap-1 rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-gray-50 hover:border-gray-300"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                            Edit
                                        </Link>
                                        <button
                                            @click="deleteProduct(product.id, product.name)"
                                            class="inline-flex items-center gap-1 rounded-lg border border-red-200 px-3 py-1.5 text-xs font-medium text-red-600 transition hover:bg-red-50 hover:border-red-300"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="products.last_page > 1" class="mt-6 flex items-center justify-between border-t border-gray-100 pt-4">
                    <p class="text-xs text-muted-foreground">
                        Showing {{ products.from }}–{{ products.to }} of {{ products.total }} products
                    </p>
                    <div class="flex items-center gap-1">
                        <button
                            :disabled="products.current_page <= 1"
                            class="flex h-8 w-8 items-center justify-center rounded-lg text-sm transition hover:bg-gray-100 disabled:opacity-30 disabled:cursor-not-allowed"
                            @click="goToPage(products.current_page - 1)"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                        </button>

                        <template v-for="item in pageRange" :key="item">
                            <span v-if="item === 'ellipsis'" class="px-1 text-xs text-muted-foreground">…</span>
                            <button
                                v-else
                                class="flex h-8 min-w-[32px] items-center justify-center rounded-lg px-2 text-sm font-medium transition"
                                :class="
                                    item === products.current_page
                                        ? 'bg-gray-900 text-white'
                                        : 'hover:bg-gray-100'
                                "
                                @click="goToPage(item)"
                            >
                                {{ item }}
                            </button>
                        </template>

                        <button
                            :disabled="products.current_page >= products.last_page"
                            class="flex h-8 w-8 items-center justify-center rounded-lg text-sm transition hover:bg-gray-100 disabled:opacity-30 disabled:cursor-not-allowed"
                            @click="goToPage(products.current_page + 1)"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
