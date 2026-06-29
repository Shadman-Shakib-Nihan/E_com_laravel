<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import InputError from '@/components/InputError.vue';
import { toast } from 'vue-sonner';
import { dashboard } from '@/routes';
import {
    create as productCreate,
    store as productStore,
} from '@/routes/product';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Product', href: dashboard() },
            { title: 'Add Product', href: productCreate() },
        ],
    },
});

const submitted = ref(false);

const isFormValid = computed(() => {
    return (
        form.name.trim() !== '' &&
        form.basePricing !== '' &&
        form.stock !== '' &&
        form.description !== '' &&
        form.gender !== '' &&
        form.sizes.length > 0 &&
        form.category !== ''
    );
});

const form = useForm({
    name: '',
    description: '',
    sizes: [] as string[],
    gender: '',
    basePricing: '',
    stock: '',
    discount: '',
    discountType: '',
    category: '',
    images: [] as File[],
});

const availableSizes = ['XS', 'S', 'M', 'XL', 'XXL'];
const genderOptions = ['Men', 'Woman', 'Unisex'];

const imagePreviews = ref<string[]>([]);
const activePreview = ref(0);

function toggleSize(size: string) {
    const idx = form.sizes.indexOf(size);
    if (idx === -1) form.sizes.push(size);
    else form.sizes.splice(idx, 1);
}

function handleImageUpload(event: Event) {
    const input = event.target as HTMLInputElement;
    if (!input.files) return;
    Array.from(input.files).forEach((file) => {
        form.images.push(file);
        const reader = new FileReader();
        reader.onload = (e) => {
            imagePreviews.value.push(e.target?.result as string);
        };
        reader.readAsDataURL(file);
    });
    // reset input so same file can be re-added after deletion
    input.value = '';
}

function removeImage(index: number) {
    form.images.splice(index, 1);
    imagePreviews.value.splice(index, 1);
    // keep activePreview in bounds
    if (activePreview.value >= imagePreviews.value.length) {
        activePreview.value = Math.max(0, imagePreviews.value.length - 1);
    }
}

function addProduct() {
    submitted.value = true;

    if (!isFormValid.value) {
        toast.error('Please fill in all required fields.');
        return;
    }

    toast.success('Creating product...', {
        description: 'Please wait while we save your product.',
    });
    form.post(productStore().url);
}
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <Head title="Add Product" />

        <!-- Header -->
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2 text-base font-semibold">
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="h-5 w-5"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M9 17v-2a4 4 0 014-4h4M9 17H5a2 2 0 01-2-2V7a2 2 0 012-2h4l2 2h6a2 2 0 012 2v4"
                    />
                </svg>
                Add New Product
            </div>
            <div class="flex gap-3">
                <button
                    type="button"
                    @click="addProduct"
                    class="flex items-center gap-2 rounded-full bg-[#b5e2a0] px-5 py-2 text-sm font-semibold text-gray-800 transition hover:bg-[#a3d48e]"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-4 w-4"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M5 13l4 4L19 7"
                        />
                    </svg>
                    Add Product
                </button>
            </div>
        </div>

        <!-- Body Grid -->
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-[1fr_380px]">
            <!-- Left Column -->
            <div class="flex flex-col gap-4">

                <!-- General Information -->
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h2 class="mb-5 text-base font-bold text-gray-900">General Information</h2>

                    <!-- Name -->
                    <div class="mb-4">
                        <label class="mb-1.5 block text-sm font-medium text-gray-700">
                            Name Product <span class="text-red-400">*</span>
                        </label>
                        <input
                            v-model="form.name"
                            type="text"
                            placeholder="Product name"
                            :class="[
                                'w-full rounded-lg border px-4 py-2.5 text-sm text-gray-800 transition outline-none',
                                submitted && !form.name.trim()
                                    ? 'border-red-300 bg-red-50 focus:border-red-400 focus:ring-1 focus:ring-red-200'
                                    : 'border-gray-200 bg-gray-50 focus:border-green-400 focus:ring-1 focus:ring-green-300',
                            ]"
                        />
                        <InputError :message="form.errors.name" class="mt-1" />
                        <p v-if="submitted && !form.name.trim()" class="mt-1 text-xs text-red-500">
                            Name is required
                        </p>
                    </div>

                    <!-- Description -->
                    <div class="mb-5">
                        <label class="mb-1.5 block text-sm font-medium text-gray-700">
                            Description Product <span class="text-red-400">*</span>
                        </label>
                        <textarea
                            v-model="form.description"
                            rows="5"
                            placeholder="Product description"
                            class="w-full resize-none rounded-lg border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-800 transition outline-none focus:border-green-400 focus:ring-1 focus:ring-green-300"
                        />
                        <InputError :message="form.errors.description" class="mt-1" />
                    </div>

                    <div class="grid grid-cols-2 gap-6">
                        <!-- Size -->
                        <div>
                            <label class="mb-0.5 block text-sm font-medium text-gray-700">Size <span class="text-red-400">*</span></label>
                            <p class="mb-2 text-xs text-gray-400">Pick Available Size</p>
                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-for="size in availableSizes"
                                    :key="size"
                                    type="button"
                                    @click="toggleSize(size)"
                                    :class="[
                                        'rounded-lg border px-4 py-1.5 text-sm font-medium transition',
                                        form.sizes.includes(size)
                                            ? 'border-[#b5e2a0] bg-[#b5e2a0] text-gray-800'
                                            : 'border-gray-200 bg-white text-gray-600 hover:border-green-300',
                                    ]"
                                >
                                    {{ size }}
                                </button>
                            </div>
                            <InputError :message="form.errors.sizes" class="mt-1" />
                        </div>

                        <!-- Gender -->
                        <div>
                            <label class="mb-0.5 block text-sm font-medium text-gray-700">Gender <span class="text-red-400">*</span></label>
                            <p class="mb-2 text-xs text-gray-400">Pick Available Gender</p>
                            <div class="flex flex-wrap gap-4">
                                <label
                                    v-for="g in genderOptions"
                                    :key="g"
                                    class="flex cursor-pointer items-center gap-1.5 text-sm text-gray-700"
                                >
                                    <input
                                        type="radio"
                                        :value="g"
                                        v-model="form.gender"
                                        class="h-4 w-4 accent-[#7ac75a]"
                                    />
                                    {{ g }}
                                </label>
                            </div>
                            <InputError :message="form.errors.gender" class="mt-1" />
                        </div>
                    </div>
                </div>

                <!-- Pricing And Stock -->
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h2 class="mb-5 text-base font-bold text-gray-900">Pricing And Stock</h2>
                    <div class="grid grid-cols-2 gap-4">

                        <!-- Base Pricing -->
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700">
                                Base Pricing <span class="text-red-400">*</span>
                            </label>
                            <input
                                v-model="form.basePricing"
                                type="text"
                                placeholder="$0.00"
                                :class="[
                                    'w-full rounded-lg border px-4 py-2.5 text-sm text-gray-800 transition outline-none',
                                    submitted && !form.basePricing
                                        ? 'border-red-300 bg-red-50 focus:border-red-400 focus:ring-1 focus:ring-red-200'
                                        : 'border-gray-200 bg-gray-50 focus:border-green-400 focus:ring-1 focus:ring-green-300',
                                ]"
                            />
                            <InputError :message="form.errors.basePricing" class="mt-1" />
                            <p v-if="submitted && !form.basePricing" class="mt-1 text-xs text-red-500">
                                Base pricing is required
                            </p>
                        </div>

                        <!-- Stock -->
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700">
                                Stock <span class="text-red-400">*</span>
                            </label>
                            <input
                                v-model="form.stock"
                                type="number"
                                placeholder="0"
                                :class="[
                                    'w-full rounded-lg border px-4 py-2.5 text-sm text-gray-800 transition outline-none',
                                    submitted && !form.stock
                                        ? 'border-red-300 bg-red-50 focus:border-red-400 focus:ring-1 focus:ring-red-200'
                                        : 'border-gray-200 bg-gray-50 focus:border-green-400 focus:ring-1 focus:ring-green-300',
                                ]"
                            />
                            <InputError :message="form.errors.stock" class="mt-1" />
                            <p v-if="submitted && !form.stock" class="mt-1 text-xs text-red-500">
                                Stock is required
                            </p>
                        </div>

                        <!-- Discount -->
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700">Discount</label>
                            <input
                                v-model="form.discount"
                                type="text"
                                placeholder="0%"
                                class="w-full rounded-lg border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-800 transition outline-none focus:border-green-400 focus:ring-1 focus:ring-green-300"
                            />
                            <InputError :message="form.errors.discount" class="mt-1" />
                        </div>

                        <!-- Discount Type -->
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700">Discount Type</label>
                            <div class="relative">
                                <select
                                    v-model="form.discountType"
                                    class="w-full appearance-none rounded-lg border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-800 transition outline-none focus:border-green-400 focus:ring-1 focus:ring-green-300"
                                >
                                    <option value="" disabled>Select type</option>
                                    <option value="fixed">Fixed amount</option>
                                    <option value="percentage">Percentage</option>
                                </select>
                                <span class="pointer-events-none absolute top-1/2 right-3 -translate-y-1/2 text-gray-500">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </span>
                            </div>
                            <InputError :message="form.errors.discountType" class="mt-1" />
                        </div>

                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="flex flex-col gap-4">

                <!-- Upload Image -->
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <h2 class="mb-3 text-base font-bold text-gray-900">Upload Images</h2>

                    <!-- Main preview -->
                    <div class="relative mb-3 flex h-56 items-center justify-center overflow-hidden rounded-xl bg-gray-100">
                        <img
                            v-if="imagePreviews[activePreview]"
                            :src="imagePreviews[activePreview]"
                            class="h-full w-full object-contain"
                            alt="Preview"
                        />
                        <span v-else class="text-sm text-gray-400">No image selected</span>

                        <!-- Delete button on main preview -->
                        <button
                            v-if="imagePreviews[activePreview]"
                            type="button"
                            @click="removeImage(activePreview)"
                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded-full bg-red-500 text-white shadow transition hover:bg-red-600"
                            title="Remove image"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>

                        <!-- Image counter badge -->
                        <span
                            v-if="imagePreviews.length > 0"
                            class="absolute bottom-2 left-2 rounded-full bg-black/40 px-2 py-0.5 text-xs font-medium text-white"
                        >
                            {{ activePreview + 1 }} / {{ imagePreviews.length }}
                        </span>
                    </div>

                    <!-- Thumbnails -->
                    <div class="flex flex-wrap items-center gap-2">
                        <div
                            v-for="(src, i) in imagePreviews"
                            :key="i"
                            class="relative"
                        >
                            <!-- Thumbnail -->
                            <button
                                type="button"
                                @click="activePreview = i"
                                :class="[
                                    'h-16 w-16 overflow-hidden rounded-lg border-2 transition',
                                    activePreview === i ? 'border-gray-700' : 'border-transparent',
                                ]"
                            >
                                <img :src="src" class="h-full w-full object-cover" alt="thumb" />
                            </button>

                            <!-- Per-thumbnail delete button -->
                            <button
                                type="button"
                                @click="removeImage(i)"
                                class="absolute -top-1.5 -right-1.5 flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-white shadow transition hover:bg-red-600"
                                title="Remove image"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Add image button -->
                        <label class="flex h-16 w-16 cursor-pointer items-center justify-center rounded-lg border-2 border-dashed border-green-300 bg-green-50 text-green-500 transition hover:bg-green-100">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            <input
                                type="file"
                                accept="image/*"
                                multiple
                                class="hidden"
                                @change="handleImageUpload"
                            />
                        </label>
                    </div>

                    <InputError :message="form.errors.images" class="mt-2" />

                    <!-- Image count hint -->
                    <p v-if="imagePreviews.length === 0" class="mt-2 text-xs text-gray-400">
                        No images added yet. Click + to upload.
                    </p>
                    <p v-else class="mt-2 text-xs text-gray-400">
                        {{ imagePreviews.length }} image{{ imagePreviews.length > 1 ? 's' : '' }} added
                    </p>
                </div>

                <!-- Category -->
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <h2 class="mb-4 text-base font-bold text-gray-900">Category</h2>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700">Product Category <span class="text-red-400">*</span></label>
                    <div class="relative mb-4">
                        <select
                            v-model="form.category"
                            class="w-full appearance-none rounded-lg border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-800 transition outline-none focus:border-green-400 focus:ring-1 focus:ring-green-300"
                        >
                            <option value="" disabled>Select category</option>
                            <option>Jacket</option>
                            <option>T-Shirt</option>
                            <option>Pants</option>
                            <option>Shoes</option>
                            <option>Accessories</option>
                        </select>
                        <span class="pointer-events-none absolute top-1/2 right-3 -translate-y-1/2 text-gray-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </span>
                    </div>
                    <InputError :message="form.errors.category" class="mb-4" />
                    <button
                        type="button"
                        class="w-full rounded-full bg-[#b5e2a0] py-2.5 text-sm font-semibold text-gray-800 transition hover:bg-[#a3d48e]"
                    >
                        Add Category
                    </button>
                </div>

            </div>
        </div>
    </div>
</template>
