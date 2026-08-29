<script setup>
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import { Pizza } from 'lucide-vue-next';
import { useCart } from '@/Composables/useCart';

// Komponenty sklepowe z resources/js/Components/Shop/
import Header from '@/Components/Shop/Header.vue';
import CategoryFilter from '@/Components/Shop/CategoryFilter.vue';
import HalfHalfBanner from '@/Components/Shop/HalfHalfBanner.vue';
import HalfHalfModal from '@/Components/Shop/HalfHalfModal.vue';
import ModifierModal from '@/Components/Shop/ModifierModal.vue';
import CartSidebar from '@/Components/Shop/CartSidebar.vue';

const props = defineProps({
    products: { type: Array, default: () => [] },
    ingredients: { type: Array, default: () => [] },
    minOrderAmount: { type: Number, default: 40.00 },
    freeDeliverySettings: { 
        type: Object, 
        default: () => ({ enabled: true, minAmount: 60.00 }) 
    },
    upsellSettings: {
        type: Object,
        default: () => ({ enabled: true })
    },
    halfHalfSettings: {
        type: Object,
        default: () => ({ enabled: true })
    }
});

// Composable Koszyka
const { cart, addToCart, removeFromCart, clearCart, cartItemsCount } = useCart();

// Stan modali i filtrowania
const isHalfHalfModalOpen = ref(false);
const isModifierModalOpen = ref(false);
const activeProduct = ref(null);
const activeVariant = ref(null);
const activeCategoryFilter = ref('Wszystko');

const uniqueCategories = computed(() => ['Wszystko', ...new Set(props.products.map(p => p.category))]);

const filteredProducts = computed(() => {
    if (activeCategoryFilter.value === 'Wszystko') return props.products;
    return props.products.filter(p => p.category === activeCategoryFilter.value);
});

const filterProducts = (cat) => {
    activeCategoryFilter.value = cat;
};

// OBSŁUGA WYBORU WARIANTU – MODAL MODYFIKATORÓW TYLKO DLA KATEGORII PIZZA
const handleVariantSelect = (product, variant) => {
    const isPizza = (product.category || '').toLowerCase() === 'pizza';
    const hasIngredients = variant.ingredients && variant.ingredients.length > 0;

    // Tylko kategoria Pizza otwiera ModifierModal (składniki BEZ / EKSTRA)
    if (isPizza && hasIngredients) {
        activeProduct.value = product;
        activeVariant.value = variant;
        isModifierModalOpen.value = true;
    } else {
        // Wszystkie pozostałe potrawy (Makarony, Sałatki, Burgery, Napoje itp.) dodają się bezpośrednio do koszyka
        addToCart({
            variantId: variant.id,
            name: product.name,
            size: variant.size_name,
            price: parseFloat(variant.price),
            quantity: 1,
            modifiers: []
        });
    }
};

const scrollToSection = (id) => {
    const el = document.getElementById(id);
    if (el) el.scrollIntoView({ behavior: 'smooth' });
};
</script>

<template>
    <Head title="Zamów Online - Pizzeria Savona" />

    <div class="bg-[#0B0F19] text-slate-300 font-sans antialiased selection:bg-red-500 selection:text-white min-h-screen">

        <!-- HEADER -->
        <Header :cart-count="cartItemsCount" @open-cart="scrollToSection('cart-section')" />

        <!-- SEKCJA GŁÓWNA -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
            
            <div class="space-y-4">
                <div class="flex justify-between items-end border-b border-slate-900 pb-4">
                    <div>
                        <h1 class="text-2xl sm:text-3xl font-black text-white uppercase tracking-wide">Zamów Online</h1>
                        <p class="text-xs text-slate-400 mt-1">Wybierz danie, dopasuj składniki i wyślij zamówienie bezpośrednio do pizzerii.</p>
                    </div>
                </div>

                <!-- KAFELKI KATEGORII -->
                <CategoryFilter 
                    :categories="uniqueCategories" 
                    :active-category="activeCategoryFilter" 
                    @select-category="filterProducts" 
                />
            </div>

            <!-- BANER PIZZY PÓŁ NA PÓŁ -->
            <HalfHalfBanner 
                v-if="halfHalfSettings?.enabled" 
                @open-modal="isHalfHalfModalOpen = true" 
            />

            <!-- SIATKA DAŃ ORAZ KOSZYK BOCZNY -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
                
                <!-- LISTA PRODUKTÓW -->
                <div class="lg:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div 
                        v-for="product in filteredProducts" 
                        :key="product.id" 
                        class="bg-slate-900 rounded-2xl overflow-hidden shadow-xl border border-slate-800 hover:border-slate-700 transition flex flex-col justify-between"
                    >
                        <div>
                            <div class="h-48 overflow-hidden relative bg-slate-950">
                                <img 
                                    v-if="product.image_path" 
                                    :src="'/storage/' + product.image_path" 
                                    :alt="product.name" 
                                    class="w-full h-full object-cover opacity-90" 
                                />
                                <div v-else class="h-full w-full flex flex-col items-center justify-center text-slate-600 bg-slate-950">
                                    <Pizza class="w-10 h-10 text-slate-700" />
                                </div>
                                <span class="absolute top-3 right-3 bg-slate-950/80 backdrop-blur-md px-2.5 py-1 border border-slate-800 rounded-full uppercase text-[9px] font-bold tracking-wider text-amber-400">
                                    {{ product.category }}
                                </span>
                            </div>

                            <div class="p-5 space-y-2">
                                <h4 class="text-lg font-bold text-white">{{ product.name }}</h4>
                                <p class="text-xs text-slate-400 leading-relaxed line-clamp-2">
                                    {{ product.description || 'Świeże składniki i tradycyjna receptura.' }}
                                </p>
                            </div>
                        </div>

                        <!-- WARIANTY ROZMIARÓW -->
                        <div class="p-5 pt-0 space-y-2">
                            <button 
                                v-for="variant in product.variants" 
                                :key="variant.id"
                                @click="handleVariantSelect(product, variant)"
                                class="w-full bg-slate-950 hover:bg-red-600 border border-slate-800 hover:border-red-500 text-xs py-2 px-3 rounded-xl transition flex justify-between items-center cursor-pointer group"
                            >
                                <span class="text-slate-300 group-hover:text-white font-medium">{{ variant.size_name }}</span>
                                <span class="font-mono font-bold text-amber-400 group-hover:text-white">{{ variant.price }} zł</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- BOCZNY KOSZYK -->
                <div id="cart-section">
                    <CartSidebar 
                        :cart="cart"
                        :products="products"
                        :min-order-amount="minOrderAmount"
                        :free-delivery-settings="freeDeliverySettings"
                        :upsell-settings="upsellSettings"
                        @remove-item="removeFromCart"
                        @add-to-cart="addToCart"
                        @clear-cart="clearCart"
                    />
                </div>

            </div>

        </main>

        <!-- MODALE -->
        <HalfHalfModal 
            v-if="halfHalfSettings?.enabled"
            :is-open="isHalfHalfModalOpen" 
            :products="products" 
            @close="isHalfHalfModalOpen = false" 
            @add-to-cart="addToCart" 
        />

        <ModifierModal 
            :is-open="isModifierModalOpen" 
            :product="activeProduct" 
            :variant="activeVariant" 
            @close="isModifierModalOpen = false" 
            @add-to-cart="addToCart" 
        />

    </div>
</template>