<script setup lang="ts">
import { Head, useForm, setLayoutProps } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import InputError from '@/components/InputError.vue';
import { toast } from 'vue-sonner';
import { dashboard } from '@/routes';

const props = defineProps<{
    product: {
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
        images: { id: number; url: string; is_primary: boolean }[];
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Product', href: dashboard() },
            { title: 'Edit Product', href: '#' },
        ],
    },
});

setLayoutProps({
    breadcrumbs: [
        { title: 'Product', href: dashboard() },
        { title: 'Edit Product', href: `/product/${props.product.id}/edit` },
    ],
});

// ─── Existing images state ─────────────────────────────────
// Track which existing images to delete (by id)
const deletedImageIds = ref<number[]>([]);
// Track which existing image id is set as primary (-1 = none changed)
const primaryImageId = ref<number>(
    props.product.images.find((img) => img.is_primary)?.id ?? -1,
);

const existingImages = computed(() =>
    props.product.images.filter((img) => !deletedImageIds.value.includes(img.id)),
);

function deleteExistingImage(id: number) {
    deletedImageIds.value.push(id);
    // If deleted image was the active preview, fall back to first remaining
    if (primaryImageId.value === id) {
        primaryImageId.value = existingImages.value[0]?.id ?? -1;
    }
    if (activePreview.value === 'existing-' + id) {
        const first = existingImages.value[0];
        activePreview.value = first ? 'existing-' + first.id : null;
    }
}

function setPrimary(id: number) {
    primaryImageId.value = id;
}

// ─── New images state ──────────────────────────────────────
const newImageFiles = ref<File[]>([]);
const newImagePreviews = ref<string[]>([]);

function handleImageUpload(event: Event) {
    const input = event.target as HTMLInputElement;
    if (!input.files) return;
    Array.from(input.files).forEach((file) => {
        newImageFiles.value.push(file);
        const reader = new FileReader();
        reader.onload = (e) => {
            newImagePreviews.value.push(e.target?.result as string);
        };
        reader.readAsDataURL(file);
    });
    input.value = '';
}

function removeNewImage(index: number) {
    newImageFiles.value.splice(index, 1);
    newImagePreviews.value.splice(index, 1);
    if (activePreview.value === 'new-' + index) {
        const first = existingImages.value[0];
        activePreview.value = first ? 'existing-' + first.id : null;
    }
}

// ─── Active preview ────────────────────────────────────────
// Can be 'existing-{id}' or 'new-{index}' or null
const activePreview = ref<string | null>(
    props.product.images[0] ? 'existing-' + props.product.images[0].id : null,
);

const activePreviewSrc = computed(() => {
    if (!activePreview.value) return null;
    if (activePreview.value.startsWith('existing-')) {
        const id = Number(activePreview.value.replace('existing-', ''));
        return props.product.images.find((img) => img.id === id)?.url ?? null;
    }
    if (activePreview.value.startsWith('new-')) {
        const idx = Number(activePreview.value.replace('new-', ''));
        return newImagePreviews.value[idx] ?? null;
    }
    return null;
});

const totalImageCount = computed(
    () => existingImages.value.length + newImagePreviews.value.length,
);

// ─── Form ──────────────────────────────────────────────────
const submitted = ref(false);

const form = useForm({
    name: props.product.name,
    description: props.product.description,
    sizes: [...props.product.sizes],
    gender: props.product.gender ?? '',
    basePricing: String(props.product.base_pricing),
    stock: String(props.product.stock),
    discount: props.product.discount !== null ? String(props.product.discount) : '',
    discountType: props.product.discount_type ?? '',
    category: props.product.category ?? '',
    // sent as JSON strings / arrays to backend
    deletedImageIds: [] as number[],
    primaryImageId: primaryImageId.value,
    newImages: [] as File[],
});

const availableSizes = ['XS', 'S', 'M', 'XL', 'XXL'];
const genderOptions = ['Men', 'Woman', 'Unisex'];

function toggleSize(size: string) {
    const idx = form.sizes.indexOf(size);
    if (idx === -1) form.sizes.push(size);
    else form.sizes.splice(idx, 1);
}

function updateProduct() {
    submitted.value = true;

    if (
        !form.name.trim() ||
        !form.basePricing ||
        !form.stock
    ) {
        toast.error('Please fill in all required fields.');
        return;
    }

    // Sync reactive state into form before submitting
    form.deletedImageIds = deletedImageIds.value;
    form.primaryImageId = primaryImageId.value;
    form.newImages = newImageFiles.value;

    toast.success('Updating product...', {
        description: 'Please wait while we save your changes.',
    });

    form.post(`/product/${props.product.id}`, {
        // Laravel needs _method spoofing for PUT with files
        headers: { 'X-HTTP-Method-Override': 'PUT' },
    });
}
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <Head title="Edit Product" />

        <!-- Header -->
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2 text-base font-semibold">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                Edit Product
            </div>
            <button
                type="button"
                @click="updateProduct"
                class="flex items-center gap-2 rounded-full bg-[#b5e2a0] px-5 py-2 text-sm font-semibold text-gray-800 transition hover:bg-[#a3d48e]"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                Update Product
            </button>
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
                        <p v-if="submitted && !form.name.trim()" class="mt-1 text-xs text-red-500">Name is required</p>
                    </div>

                    <!-- Description -->
                    <div class="mb-5">
                        <label class="mb-1.5 block text-sm font-medium text-gray-700">Description Product</label>
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
                            <label class="mb-0.5 block text-sm font-medium text-gray-700">Size</label>
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
                            <label class="mb-0.5 block text-sm font-medium text-gray-700">Gender</label>
                            <p class="mb-2 text-xs text-gray-400">Pick Available Gender</p>
                            <div class="flex flex-wrap gap-4">
                                <label
                                    v-for="g in genderOptions"
                                    :key="g"
                                    class="flex cursor-pointer items-center gap-1.5 text-sm text-gray-700"
                                >
                                    <input type="radio" :value="g" v-model="form.gender" class="h-4 w-4 accent-[#7ac75a]" />
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
                            <p v-if="submitted && !form.basePricing" class="mt-1 text-xs text-red-500">Base pricing is required</p>
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
                            <p v-if="submitted && !form.stock" class="mt-1 text-xs text-red-500">Stock is required</p>
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

                <!-- Image Manager -->
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <h2 class="mb-3 text-base font-bold text-gray-900">Images</h2>

                    <!-- Main preview -->
                    <div class="relative mb-3 flex h-56 items-center justify-center overflow-hidden rounded-xl bg-gray-100">
                        <img
                            v-if="activePreviewSrc"
                            :src="activePreviewSrc"
                            class="h-full w-full object-contain"
                            alt="Preview"
                        />
                        <span v-else class="text-sm text-gray-400">No image selected</span>

                        <!-- Delete active image button -->
                        <button
                            v-if="activePreviewSrc && activePreview"
                            type="button"
                            @click="
                                activePreview.startsWith('existing-')
                                    ? deleteExistingImage(Number(activePreview.replace('existing-', '')))
                                    : removeNewImage(Number(activePreview.replace('new-', '')))
                            "
                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded-full bg-red-500 text-white shadow transition hover:bg-red-600"
                            title="Remove image"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>

                        <!-- Set as primary button (only for existing images not yet primary) -->
                        <button
                            v-if="activePreview && activePreview.startsWith('existing-') && primaryImageId !== Number(activePreview.replace('existing-', ''))"
                            type="button"
                            @click="setPrimary(Number(activePreview.replace('existing-', '')))"
                            class="absolute bottom-2 right-2 rounded-full bg-white/90 px-2.5 py-1 text-xs font-semibold text-gray-700 shadow transition hover:bg-white"
                        >
                            Set as primary
                        </button>

                        <!-- Counter badge -->
                        <span
                            v-if="totalImageCount > 0"
                            class="absolute bottom-2 left-2 rounded-full bg-black/40 px-2 py-0.5 text-xs font-medium text-white"
                        >
                            {{ totalImageCount }} image{{ totalImageCount > 1 ? 's' : '' }}
                        </span>
                    </div>

                    <!-- Thumbnails row -->
                    <div class="flex flex-wrap items-center gap-2">

                        <!-- Existing image thumbnails -->
                        <div
                            v-for="image in existingImages"
                            :key="'existing-' + image.id"
                            class="relative"
                        >
                            <button
                                type="button"
                                @click="activePreview = 'existing-' + image.id"
                                :class="[
                                    'h-16 w-16 overflow-hidden rounded-lg border-2 transition',
                                    activePreview === 'existing-' + image.id ? 'border-gray-700' : 'border-transparent',
                                ]"
                            >
                                <img :src="image.url" class="h-full w-full object-cover" alt="thumb" />
                            </button>

                            <!-- Primary badge -->
                            <span
                                v-if="primaryImageId === image.id"
                                class="absolute top-0 left-0 rounded-br-md rounded-tl-lg bg-green-500 px-1 py-0.5 text-[9px] font-bold leading-none text-white"
                            >
                                Primary
                            </span>

                            <!-- Per-thumbnail delete -->
                            <button
                                type="button"
                                @click="deleteExistingImage(image.id)"
                                class="absolute -top-1.5 -right-1.5 flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-white shadow transition hover:bg-red-600"
                                title="Remove image"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- New image thumbnails -->
                        <div
                            v-for="(src, i) in newImagePreviews"
                            :key="'new-' + i"
                            class="relative"
                        >
                            <button
                                type="button"
                                @click="activePreview = 'new-' + i"
                                :class="[
                                    'h-16 w-16 overflow-hidden rounded-lg border-2 transition',
                                    activePreview === 'new-' + i ? 'border-gray-700' : 'border-transparent',
                                ]"
                            >
                                <img :src="src" class="h-full w-full object-cover" alt="new thumb" />
                            </button>

                            <!-- "New" badge -->
                            <span class="absolute top-0 left-0 rounded-br-md rounded-tl-lg bg-blue-500 px-1 py-0.5 text-[9px] font-bold leading-none text-white">
                                New
                            </span>

                            <!-- Per-thumbnail delete -->
                            <button
                                type="button"
                                @click="removeNewImage(i)"
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
                            <input type="file" accept="image/*" multiple class="hidden" @change="handleImageUpload" />
                        </label>
                    </div>

                    <InputError :message="form.errors.newImages" class="mt-2" />

                    <!-- Hint -->
                    <p v-if="totalImageCount === 0" class="mt-2 text-xs text-gray-400">No images. Click + to upload.</p>
                    <p v-else class="mt-2 text-xs text-gray-400">
                        {{ existingImages.length }} existing · {{ newImagePreviews.length }} new
                        <span v-if="deletedImageIds.length > 0" class="text-red-400">· {{ deletedImageIds.length }} to be deleted</span>
                    </p>
                </div>

                <!-- Category -->
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <h2 class="mb-4 text-base font-bold text-gray-900">Category</h2>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700">Product Category</label>
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
                </div>

            </div>
        </div>
    </div>
</template>
