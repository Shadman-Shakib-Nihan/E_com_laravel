<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { dashboard } from '@/routes';
import { create as productCreate, edit as productEdit } from '@/routes/product';
import { toast } from 'vue-sonner';

defineProps<{
    products: {
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
}>();

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

            <div v-if="products.length === 0" class="py-12 text-center">
                <p class="text-muted-foreground">No products yet. Create your first product!</p>
            </div>

            <div v-else class="overflow-x-auto">
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
                        <tr v-for="product in products" :key="product.id" class="border-b border-gray-100 transition hover:bg-gray-50/50">
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
        </div>
    </div>
</template>
