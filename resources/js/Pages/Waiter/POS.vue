<script setup>
import { ref } from 'vue';
import { useForm, router, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ShoppingCart } from 'lucide-vue-next';

// Komponenty cząstkowe
import ProductGrid from './Partials/ProductGrid.vue';
import OrderReceiptPanel from './Partials/OrderReceiptPanel.vue';
import ModifierModal from './Partials/ModifierModal.vue';
import StockAlertModal from './Partials/StockAlertModal.vue';

const props = defineProps({
    products: { type: Array, default: () => [] },
    ingredients: { type: Array, default: () => [] }
});

const cart = ref([]);
const orderType = ref('lokal');
const tableNumber = ref('');

// Stan okien modalnych
const isModifierModalOpen = ref(false);
const activeProduct = ref(null);
const activeVariant = ref(null);

const isStockAlertOpen = ref(false);
const missingIngredientName = ref('');

// Otwieranie konfiguratora modyfikatorów
const handleSelectVariant = ({ product, variant }) => {
    activeProduct.value = product;
    activeVariant.value = variant;
    isModifierModalOpen.value = true;
};

// Dodanie spersonalizowanego produktu do koszyka
const handleAddToCartFromModal = (customizedItem) => {
    cart.value.push(customizedItem);
    isModifierModalOpen.value = false;
};

const handleRemoveFromCart = (index) => {
    cart.value.splice(index, 1);
};

const form = useForm({
    type: 'lokal',
    payment_method: 'gotówka',
    table_number: null,
    items: []
});

// Wysyłka zamówienia
const submitOrder = () => {
    if (cart.value.length === 0) return;

    form.type = orderType.value;
    form.table_number = orderType.value === 'lokal' ? parseInt(tableNumber.value) : null;
    
    form.items = cart.value.map(item => ({
        product_variant_id: item.variantId,
        quantity: item.quantity,
        modifiers: item.modifiers.map(m => ({
            ingredient_id: m.ingredient_id,
            action: m.action
        }))
    }));

    form.post(route('order.store'), {
        preserveScroll: true,
        onSuccess: () => {
            cart.value = [];
            tableNumber.value = '';
            form.reset();
        },
        onError: (errors) => {
            if (errors.missing_ingredient) {
                missingIngredientName.value = errors.missing_ingredient;
                isStockAlertOpen.value = true;
            }
        }
    });
};

// Wymuszenie przyjęcia zamówienia przy braku surowca
const confirmOrderWithMissingStock = () => {
    isStockAlertOpen.value = false;
    
    router.post(route('order.store'), {
        ...form.data(),
        ignore_stock: true
    }, {
        preserveScroll: true,
        onSuccess: () => {
            cart.value = [];
            tableNumber.value = '';
            form.reset();
        }
    });
};

const cancelOrderWithMissingStock = () => {
    isStockAlertOpen.value = false;
    form.clearErrors('missing_ingredient');
};
</script>

<template>
    <Head title="Kasa POS - Savona Admin" />

    <AuthenticatedLayout>
        <div class="min-h-screen bg-[#0B0F19] text-slate-300 flex flex-col lg:flex-row font-sans">
            
            <!-- LEWA STRONA: MENU PRODUKTÓW -->
            <div class="flex-1 p-6 overflow-y-auto max-h-[calc(100vh-2rem)]">
                <header class="mb-6 border-b border-slate-800 pb-4 flex items-center space-x-3">
                    <ShoppingCart class="w-6 h-6 text-amber-500" />
                    <div>
                        <h1 class="text-xl font-black text-amber-500 uppercase tracking-wider">Kasa Kelnerska POS</h1>
                        <p class="text-xs text-slate-400">Wybierz wariant i skomponuj pozycję rachunku.</p>
                    </div>
                </header>

                <ProductGrid 
                    :products="products" 
                    @select-variant="handleSelectVariant" 
                />
            </div>

            <!-- PRAWA STRONA: RACHUNEK KELNERSKI -->
            <div class="w-full lg:w-96 bg-slate-900 border-l border-slate-800 p-6 flex flex-col justify-between max-h-screen shrink-0 shadow-2xl">
                <OrderReceiptPanel 
                    :cart="cart"
                    v-model:orderType="orderType"
                    v-model:tableNumber="tableNumber"
                    :processing="form.processing"
                    @remove-item="handleRemoveFromCart"
                    @submit-order="submitOrder"
                />
            </div>

            <!-- OKNO MODALNE: MODYFIKATORY DANIOWE -->
            <ModifierModal 
                :is-open="isModifierModalOpen"
                :product="activeProduct"
                :variant="activeVariant"
                :ingredients="ingredients"
                @close="isModifierModalOpen = false"
                @add-to-cart="handleAddToCartFromModal"
            />

            <!-- OKNO MODALNE: ALERT BRAKU SUROWCA -->
            <StockAlertModal 
                :is-open="isStockAlertOpen"
                :missing-ingredient-name="missingIngredientName"
                @confirm="confirmOrderWithMissingStock"
                @cancel="cancelOrderWithMissingStock"
            />

        </div>
    </AuthenticatedLayout>
</template>