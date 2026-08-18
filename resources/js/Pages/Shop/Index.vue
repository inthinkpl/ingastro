<script setup>
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import { Pizza, Flame, Utensils, Sparkles } from 'lucide-vue-next';
import { useCart } from '@/Composables/useCart';

// Komponenty sklepowe z resources/js/Components/Shop/
import Header from '@/Components/Shop/Header.vue';
import HeroSection from '@/Components/Shop/HeroSection.vue';
import HalfHalfBanner from '@/Components/Shop/HalfHalfBanner.vue';
import HalfHalfModal from '@/Components/Shop/HalfHalfModal.vue';
import ModifierModal from '@/Components/Shop/ModifierModal.vue';
import CartSidebar from '@/Components/Shop/CartSidebar.vue';
import ContactSection from '@/Components/Shop/ContactSection.vue';

const props = defineProps({
    products: { type: Array, default: () => [] },
    minOrderAmount: { type: Number, default: 40.00 },
    freeDeliverySettings: { type: Object, default: () => ({ enabled: true, minAmount: 60.00 }) },
    upsellSettings: { type: Object, default: () => ({ enabled: true }) },
    halfHalfSettings: { type: Object, default: () => ({ enabled: true }) }
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

const getCategoryIcon = (cat) => {
    switch (cat?.toLowerCase()) {
        case 'pizza': return Pizza;
        case 'sałatki':
        case 'salatki': return Utensils;
        case 'makarony':
        case 'pasta': return Flame;
        case 'napoje': return Sparkles;
        default: return Pizza;
    }
};

const filteredProducts = computed(() => {
    if (activeCategoryFilter.value === 'Wszystko') return props.products;
    return props.products.filter(p => p.category === activeCategoryFilter.value);
});

const filterProducts = (cat) => {
    activeCategoryFilter.value = cat;
    scrollToSection('products-grid');
};

const handleVariantSelect = (product, variant) => {
    const isPizza = product.category?.toLowerCase() === 'pizza';
    const hasIngredients = variant.ingredients && variant.ingredients.length > 0;

    if (isPizza && hasIngredients) {
        activeProduct.value = product;
        activeVariant.value = variant;
        isModifierModalOpen.value = true;
    } else {
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
    <Head title="Pizzeria Savona Białystok - Zamów Online" />

    <div class="bg-[#0B0F19] text-slate-300 font-sans min-h-screen antialiased">
        <!-- HEADER -->
        <Header :cart-count="cartItemsCount" @open-cart="scrollToSection('menu')" />

        <main>
            <!-- HERO -->
            <HeroSection @scroll-to-menu="scrollToSection('menu')" />

            <!-- LOKALE BADGE -->
            <section class="relative z-20 -mt-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid md:grid-cols-2 gap-4">
                    <div class="bg-slate-900/90 border border-slate-800 rounded-2xl p-5 shadow-2xl backdrop-blur-md flex items-center space-x-4">
                        <div class="bg-red-500/10 p-3.5 rounded-xl text-red-500 flex-shrink-0">📍</div>
                        <div>
                            <h3 class="text-white font-bold text-base md:text-lg">Pizzeria Savona Legionowa</h3>
                            <p class="text-slate-400 text-xs md:text-sm mt-0.5">ul. Legionowa 9/1, 15-369 Białystok</p>
                        </div>
                    </div>
                    <div class="bg-slate-900/90 border border-slate-800 rounded-2xl p-5 shadow-2xl backdrop-blur-md flex items-center space-x-4">
                        <div class="bg-amber-500/10 p-3.5 rounded-xl text-amber-500 flex-shrink-0">📍</div>
                        <div>
                            <h3 class="text-white font-bold text-base md:text-lg">Pizzeria Primo Savona</h3>
                            <p class="text-slate-400 text-xs md:text-sm mt-0.5">Rynek Kościuszki 8/1, 15-426 Białystok</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- KARTA DAŃ & MENU -->
            <section id="menu" class="py-16 bg-slate-950/40 border-t border-b border-slate-900 scroll-mt-20">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    
                    <div class="text-center max-w-3xl mx-auto mb-10 space-y-3">
                        <span class="text-red-500 font-bold uppercase tracking-wider text-xs">Odkryj Nasze Smaki</span>
                        <h2 class="text-3xl md:text-4xl font-bold text-white">Karta Dań & Menu Pizzerii</h2>
                        <p class="text-slate-400 text-sm">Kliknij kafel kategorii, aby odfiltrować wybrane specjały.</p>
                    </div>

                    <!-- KAFELKI KATEGORII -->
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3.5 mb-8">
                        <button 
                            v-for="cat in uniqueCategories" 
                            :key="cat"
                            @click="filterProducts(cat)"
                            :class="activeCategoryFilter === cat ? 'bg-gradient-to-b from-red-600 to-red-700 text-white border-red-500 shadow-xl scale-[1.02]' : 'bg-slate-900/90 text-slate-300 border-slate-800 hover:border-slate-700'"
                            class="p-4 rounded-2xl border transition-all flex flex-col items-center justify-center space-y-2 cursor-pointer text-center"
                        >
                            <div :class="activeCategoryFilter === cat ? 'bg-white/20 text-white' : 'bg-[#0B0F19] text-amber-500'" class="p-3 rounded-xl border border-slate-800">
                                <component :is="getCategoryIcon(cat)" class="w-6 h-6" />
                            </div>
                            <span class="font-bold text-xs uppercase tracking-wider">{{ cat }}</span>
                        </button>
                    </div>

                    <!-- BANER PÓŁ NA PÓŁ -->
                    <HalfHalfBanner 
                        v-if="halfHalfSettings?.enabled" 
                        @open-modal="isHalfHalfModalOpen = true" 
                    />

                    <!-- SIATKA PRODUKTÓW I KOSZYK -->
                    <div id="products-grid" class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start scroll-mt-24">
                        
                        <!-- PRODUKTY -->
                        <div class="lg:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div v-for="product in filteredProducts" :key="product.id" class="bg-slate-900 rounded-2xl overflow-hidden shadow-xl border border-slate-800 flex flex-col justify-between">
                                <div>
                                    <div class="h-52 overflow-hidden relative bg-slate-950">
                                        <img v-if="product.image_path" :src="'/storage/' + product.image_path" :alt="product.name" class="w-full h-full object-cover opacity-90" />
                                        <div v-else class="h-full w-full flex flex-col items-center justify-center text-slate-600">
                                            <Pizza class="w-12 h-12 text-slate-700" />
                                        </div>
                                        <span class="absolute top-3 right-3 bg-slate-950/80 px-3 py-1 border border-slate-800 rounded-full text-[10px] font-bold text-amber-400 uppercase">
                                            {{ product.category }}
                                        </span>
                                    </div>

                                    <div class="p-6 space-y-3">
                                        <h4 class="text-xl font-bold text-white">{{ product.name }}</h4>
                                        <p class="text-xs text-slate-400 line-clamp-3">{{ product.description }}</p>
                                    </div>
                                </div>

                                <div class="p-6 pt-0 space-y-2">
                                    <button 
                                        v-for="variant in product.variants" 
                                        :key="variant.id"
                                        @click="handleVariantSelect(product, variant)"
                                        class="w-full bg-slate-950 hover:bg-red-600 border border-slate-800 hover:border-red-500 text-xs py-2.5 px-3 rounded-xl transition flex justify-between items-center group/btn cursor-pointer"
                                    >
                                        <span class="text-slate-300 group-hover/btn:text-white font-medium">{{ variant.size_name }}</span>
                                        <span class="font-mono font-bold text-amber-400 group-hover/btn:text-white">{{ variant.price }} zł</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- BOCZNY KOSZYK -->
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
            </section>

            <!-- KONTAKT -->
            <ContactSection />
        </main>

        <footer class="bg-slate-950 text-slate-500 text-xs py-8 border-t border-slate-900 text-center">
            &copy; 2026 Pizzeria Savona Białystok. Wszelkie prawa zastrzeżone.
        </footer>

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