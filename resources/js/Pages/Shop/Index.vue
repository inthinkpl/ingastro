<script setup>
import { ref, computed } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import axios from 'axios';
import { 
    Pizza, ShoppingBag, Flame, Sparkles, Phone, MapPin, Clock, 
    Car, Store, Smartphone, Zap, Banknote, Ticket, Info, AlertTriangle, 
    Send, ShieldCheck, X, Check, Loader2, Gift, Percent, Utensils, Menu as MenuIcon
} from 'lucide-vue-next';
import { useCart } from '@/Composables/useCart';

const props = defineProps({
    products: { type: Array, default: () => [] },
    ingredients: { type: Array, default: () => [] },
    minOrderAmount: { type: Number, default: 40.00 }
});

// WSPÓLNY KOSZYK Z COMPOSABLE
const { cart, addToCart, removeFromCart, clearCart, cartSubtotal, cartItemsCount } = useCart();

// Stan modali i nawigacji mobilnej
const isModifierModalOpen = ref(false);
const isMobileMenuOpen = ref(false);
const activeProduct = ref(null);
const activeVariant = ref(null);
const selectedModifiers = ref([]);
const activeCategoryFilter = ref('Wszystko');

// STAN KODU RABATOWEGO
const discountCodeInput = ref('');
const appliedDiscount = ref(null);
const discountError = ref(null);
const isValidatingCode = ref(false);

// Dynamiczne kategorie
const uniqueCategories = computed(() => ['Wszystko', ...new Set(props.products.map(p => p.category))]);

// Przypisanie dedykowanych ikon do kategorii
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

// Filtrowanie produktów
const filteredProducts = computed(() => {
    if (activeCategoryFilter.value === 'Wszystko') {
        return props.products;
    }
    return props.products.filter(p => p.category === activeCategoryFilter.value);
});

const filterProducts = (cat) => {
    activeCategoryFilter.value = cat;
    scrollToSection('products-grid');
};

// Modyfikacja składników dozwolona TYLKO dla kategorii Pizza
const handleVariantSelect = (product, variant) => {
    const isPizza = product.category?.toLowerCase() === 'pizza';
    const hasIngredients = variant.ingredients && variant.ingredients.length > 0;

    if (isPizza && hasIngredients) {
        // Otwórz modal modyfikacji tylko dla pizzy
        openModifierModal(product, variant);
    } else {
        // Wszystkie inne kategorie (napoje, sałatki, makarony, sosy itp.) -> od razu do koszyka
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

// Modyfikatory składników
const openModifierModal = (product, variant) => {
    activeProduct.value = product;
    activeVariant.value = variant;
    selectedModifiers.value = [];
    isModifierModalOpen.value = true;
};

const toggleModifier = (ingredient, action) => {
    const existingIdx = selectedModifiers.value.findIndex(m => m.ingredient_id === ingredient.id);
    if (existingIdx > -1) {
        if (selectedModifiers.value[existingIdx].action === action) {
            selectedModifiers.value.splice(existingIdx, 1);
            return;
        }
        selectedModifiers.value[existingIdx].action = action;
    } else {
        selectedModifiers.value.push({ ingredient_id: ingredient.id, name: ingredient.name, action: action });
    }
};

const getModifierAction = (ingredientId) => {
    const found = selectedModifiers.value.find(m => m.ingredient_id === ingredientId);
    return found ? found.action : null;
};

const addCustomizedToCart = () => {
    addToCart({
        variantId: activeVariant.value.id,
        name: activeProduct.value.name,
        size: activeVariant.value.size_name,
        price: parseFloat(activeVariant.value.price),
        quantity: 1,
        modifiers: [...selectedModifiers.value]
    });
    isModifierModalOpen.value = false;
};

// Formularz zamówienia Inertia
const form = useForm({
    type: 'dostawa',
    payment_method: 'blik',
    delivery_address: '',
    discount_code: '',
    items: []
});

const discountValue = computed(() => {
    if (!appliedDiscount.value) return 0.00;
    if (appliedDiscount.value.type === 'percent') {
        return Math.round((cartSubtotal.value * (appliedDiscount.value.value / 100)) * 100) / 100;
    }
    return Math.min(appliedDiscount.value.value, cartSubtotal.value);
});

const cartTotal = computed(() => {
    return Math.max(0, cartSubtotal.value - discountValue.value);
});

const minOrderWarning = computed(() => {
    if (form.type !== 'dostawa') return null;
    if (cartTotal.value < props.minOrderAmount) {
        const missing = (props.minOrderAmount - cartTotal.value).toFixed(2);
        return `Minimalna wartość zamówienia w dostawie wynosi ${props.minOrderAmount.toFixed(2)} zł. Dołóż do koszyka dania za jeszcze ${missing} zł.`;
    }
    return null;
});

const applyDiscountCode = async () => {
    if (!discountCodeInput.value) return;
    discountError.value = null;
    isValidatingCode.value = true;

    try {
        const response = await axios.post(route('discount.validate'), {
            code: discountCodeInput.value,
            subtotal: cartSubtotal.value
        });
        appliedDiscount.value = response.data;
        form.discount_code = response.data.code;
    } catch (error) {
        appliedDiscount.value = null;
        form.discount_code = '';
        discountError.value = error.response?.data?.message || 'Wystąpił błąd podczas weryfikacji kodu.';
    } finally {
        isValidatingCode.value = false;
    }
};

const removeDiscountCode = () => {
    appliedDiscount.value = null;
    discountCodeInput.value = '';
    form.discount_code = '';
    discountError.value = null;
};

const checkout = () => {
    if (cart.value.length === 0 || minOrderWarning.value) return;

    form.items = cart.value.map(item => ({
        product_variant_id: item.variantId,
        quantity: item.quantity,
        modifiers: item.modifiers.map(m => ({
            ingredient_id: m.ingredient_id,
            action: m.action
        }))
    }));

    form.post(route('order.store'), {
        onSuccess: () => {
            clearCart();
            form.reset('delivery_address', 'discount_code');
            appliedDiscount.value = null;
            discountCodeInput.value = '';
            alert('Grazie! Twoje zamówienie zostało przekazane bezpośrednio na monitor kuchenny naszej pizzerii.');
        }
    });
};

const scrollToSection = (id) => {
    isMobileMenuOpen.value = false;
    const el = document.getElementById(id);
    if (el) {
        el.scrollIntoView({ behavior: 'smooth' });
    }
};
</script>

<template>
    <div class="bg-[#0B0F19] text-slate-300 font-sans antialiased selection:bg-red-500 selection:text-white min-h-screen">

        <!-- NAWIGACJA GŁÓWNA -->
        <header class="sticky top-0 z-50 bg-[#0B0F19]/90 backdrop-blur-md border-b border-slate-900 shadow-xl">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
                <a @click.prevent="scrollToSection('hero-section')" href="#" class="flex items-center space-x-2 cursor-pointer">
                    <span class="text-2xl font-bold text-red-500 tracking-wider">SAVONA</span>
                    <span class="text-xs bg-amber-500 text-slate-950 px-2 py-0.5 rounded-full font-semibold">pizza</span>
                </a>
                
                <!-- MENU DESKTOP -->
                <nav class="hidden md:flex items-center space-x-8 font-medium text-sm">
                    <a @click.prevent="scrollToSection('o-nas')" href="#o-nas" class="text-slate-300 hover:text-red-500 transition cursor-pointer">O nas</a>
                    
                    <Link 
                        :href="route('shop.menu')" 
                        class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-full font-bold transition inline-flex items-center space-x-2 text-xs uppercase shadow-md"
                    >
                        <Utensils class="w-4 h-4" />
                        <span>Przejdź do Menu & Zamów</span>
                    </Link>

                    <a @click.prevent="scrollToSection('kontakt')" href="#kontakt" class="text-slate-300 hover:text-red-500 transition cursor-pointer">Kontakt</a>
                </nav>

                <div class="flex items-center space-x-3">
                    <a href="tel:+48785555455" class="border border-slate-700 hover:border-slate-500 hover:text-white text-slate-300 px-3 sm:px-5 py-2.5 rounded-full font-semibold transition inline-flex items-center space-x-2 shadow-sm text-sm sm:text-base">
                        <Phone class="w-4 h-4 text-amber-500" />
                        <span class="hidden sm:inline">785 555 455</span>
                    </a>
                    
                    <button @click="scrollToSection('menu')" class="bg-red-600 hover:bg-red-700 text-white px-4 sm:px-5 py-2.5 rounded-full font-semibold transition inline-flex items-center space-x-2 shadow-lg shadow-red-600/30 text-sm sm:text-base cursor-pointer">
                        <ShoppingBag class="w-4 h-4" />
                        <span class="hidden xs:inline">Koszyk</span>
                        <span class="bg-amber-500 text-slate-950 text-xs px-2 py-0.5 rounded-full font-bold ml-1">
                            {{ cartItemsCount }}
                        </span>
                    </button>

                    <!-- PRZYCISK MOBILE MENU -->
                    <button @click="isMobileMenuOpen = !isMobileMenuOpen" class="md:hidden p-2 text-slate-400 hover:text-white">
                        <MenuIcon class="w-6 h-6" />
                    </button>
                </div>
            </div>

            <!-- ROZWIJANE MENU MOBILNE -->
            <div v-if="isMobileMenuOpen" class="md:hidden bg-slate-900 border-b border-slate-800 px-4 py-4 space-y-3 text-sm">
                <a @click.prevent="scrollToSection('o-nas')" href="#o-nas" class="block text-slate-300 py-1">O nas</a>
                
                <Link 
                    :href="route('shop.menu')" 
                    class="block bg-red-600 text-white font-bold py-2.5 px-4 rounded-xl text-center text-xs uppercase tracking-wider flex items-center justify-center space-x-2"
                >
                    <Utensils class="w-4 h-4" />
                    <span>Przejdź do Menu & Zamów</span>
                </Link>

                <a @click.prevent="scrollToSection('kontakt')" href="#kontakt" class="block text-slate-300 py-1">Kontakt</a>
            </div>
        </header>

        <main>
            <!-- BANER GŁÓWNY (HERO) -->
            <section id="hero-section" class="relative bg-slate-950 text-white overflow-hidden py-24 lg:py-36">
                <div class="absolute inset-0 opacity-20">
                    <img src="https://images.unsplash.com/photo-1513104890138-7c749659a591?q=80&w=1920&auto=format&fit=crop" alt="Pyszna, świeża pizza w pizzerii Savona" class="w-full h-full object-cover">
                </div>
                <div class="absolute inset-0 bg-gradient-to-t from-[#0B0F19] via-transparent to-transparent"></div>
                
                <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 z-10">
                    <div class="grid lg:grid-cols-12 gap-12 items-center">
                        
                        <div class="lg:col-span-7 text-center lg:text-left space-y-6">
                            <span class="text-amber-400 font-semibold tracking-widest uppercase text-sm block">Tradycja smaku od lat w Białymstoku</span>
                            <h1 class="text-4xl md:text-6xl font-bold leading-tight text-white">
                                Pizzeria Savona – Pyszna Pizza w Białymstoku
                            </h1>
                            <p class="text-lg text-slate-300 max-w-xl mx-auto lg:mx-0">
                                Odkryj menu pełne chrupiącej pizzy, legendarnych sałatek i kultowych makaronów. Wypiekane z pasją, serwowane z miłością w samym centrum Białegostoku.
                            </p>
                            <div class="pt-4 flex flex-col sm:flex-row justify-center lg:justify-start gap-4">
                                <Link :href="route('shop.menu')" class="bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold px-8 py-4 rounded-xl transition text-center shadow-lg shadow-amber-500/20 cursor-pointer">
                                    Zobacz wybrane menu
                                </Link>
                                <button @click="scrollToSection('menu')" class="border-2 border-slate-700 hover:border-slate-500 text-white font-semibold px-8 py-4 rounded-xl transition text-center backdrop-blur-sm bg-slate-900/40 cursor-pointer">
                                    Zamów przez Internet
                                </button>
                            </div>
                        </div>
                        
                        <!-- PROMOCJE -->
                        <div class="lg:col-span-5 space-y-4 max-w-md mx-auto lg:mx-0 w-full">
                            <div class="text-center lg:text-left mb-1">
                                <span class="inline-flex items-center space-x-1.5 bg-red-600/90 text-white text-xs px-3 py-1 rounded-full font-bold uppercase tracking-wider shadow-md shadow-red-600/10">
                                    <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span>
                                    <span>Aktualne Promocje</span>
                                </span>
                            </div>
                            
                            <div class="bg-slate-900/70 border-2 border-white/20 backdrop-blur-md p-5 rounded-2xl shadow-xl flex items-start space-x-4 hover:border-white/40 transition duration-300">
                                <div class="bg-red-500/10 p-3 rounded-xl border border-red-500/20 text-red-500 flex-shrink-0">
                                    <Gift class="w-6 h-6" />
                                </div>
                                <div class="space-y-1">
                                    <h3 class="text-amber-400 font-bold tracking-wide uppercase text-xs">Zestaw z gratisem</h3>
                                    <p class="text-white font-medium text-sm md:text-base leading-snug">
                                        Kup <span class="text-amber-400 font-bold">2 dowolne pizze Maxi</span> i odbierz Coca-Colę 0,85l <span class="text-green-400 font-bold">GRATIS!</span>
                                    </p>
                                </div>
                            </div>
                            
                            <div class="bg-slate-900/70 border-2 border-white/20 backdrop-blur-md p-5 rounded-2xl shadow-xl flex items-start space-x-4 hover:border-white/40 transition duration-300">
                                <div class="bg-amber-500/10 p-3 rounded-xl border border-amber-500/20 text-amber-500 flex-shrink-0">
                                    <Percent class="w-6 h-6" />
                                </div>
                                <div class="space-y-1">
                                    <h3 class="text-amber-400 font-bold tracking-wide uppercase text-xs">Uczta dla paczki</h3>
                                    <p class="text-white font-medium text-sm md:text-base leading-snug">
                                        Kup <span class="text-amber-400 font-bold">trzy dowolne pizze Maxi</span>, a najtańszą dostaniesz aż <span class="text-red-400 font-bold">50% TANIEJ!</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                    </div>
                </div>
            </section>

            <!-- ADRESY LOKALI -->
            <section class="relative z-20 -mt-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid md:grid-cols-2 gap-4">
                    <div class="bg-slate-900/90 border border-slate-800 rounded-2xl p-5 shadow-2xl backdrop-blur-md flex items-center space-x-4 hover:border-red-500/40 transition duration-300">
                        <div class="bg-red-500/10 p-3.5 rounded-xl text-red-500 flex-shrink-0">
                            <MapPin class="w-6 h-6" />
                        </div>
                        <div>
                            <h3 class="text-white font-bold text-base md:text-lg">Pizzeria Savona Legionowa</h3>
                            <p class="text-slate-400 text-xs md:text-sm mt-0.5">ul. Legionowa 9/1, 15-369 Białystok</p>
                        </div>
                    </div>
                    <div class="bg-slate-900/90 border border-slate-800 rounded-2xl p-5 shadow-2xl backdrop-blur-md flex items-center space-x-4 hover:border-amber-500/40 transition duration-300">
                        <div class="bg-amber-500/10 p-3.5 rounded-xl text-amber-500 flex-shrink-0">
                            <MapPin class="w-6 h-6" />
                        </div>
                        <div>
                            <h3 class="text-white font-bold text-base md:text-lg">Pizzeria Primo Savona</h3>
                            <p class="text-slate-400 text-xs md:text-sm mt-0.5">Rynek Kościuszki 8/1, 15-426 Białystok</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- O NAS -->
            <section id="o-nas" class="py-16 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 scroll-mt-20">
                <div class="grid md:grid-cols-2 gap-12 items-center">
                    <div class="space-y-4">
                        <h2 class="text-3xl font-bold text-white">Kultowa Pizzeria w Białymstoku</h2>
                        <p class="text-slate-400 leading-relaxed">
                            Pizzeria Savona przy <strong class="text-amber-400">ul. Legionowej 9/1 oraz ul. Rynek Kościuszki 8 lok 1</strong> to miejsce, które na stałe wpisało się w kulinarną mapę Białegostoku. Naszą specjalnością jest nie tylko idealnie wypieczona pizza, ale również unikalne kompozycje smakowe, które pokochali białostoczanie.
                        </p>
                        <p class="text-slate-400 leading-relaxed">
                            Używamy wyłącznie świeżych składników, dbając o oryginalne receptury. Niezależnie od tego, czy odwiedzasz nas osobiście, czy zamawiasz na dowóz – gwarantujemy najwyższą jakość i wyjątkowy, tradycyjny smak.
                        </p>
                    </div>
                    
                    <div class="bg-slate-900 p-4 sm:p-6 rounded-2xl shadow-2xl border border-slate-800 grid grid-cols-2 gap-3 sm:gap-4 text-center">
                        <div class="p-3 bg-[#0B0F19] rounded-xl border border-slate-800/50 flex flex-col justify-center min-h-[90px]">
                            <span class="block text-2xl sm:text-3xl font-bold text-red-500">100%</span>
                            <span class="text-[11px] sm:text-xs text-slate-400 font-medium uppercase tracking-wider mt-1 block leading-tight">Świeże Składniki</span>
                        </div>
                        <div class="p-3 bg-[#0B0F19] rounded-xl border border-slate-800/50 flex flex-col justify-center min-h-[90px]">
                            <span class="block text-2xl sm:text-3xl font-bold text-red-500">Savona</span>
                            <span class="text-[11px] sm:text-xs text-slate-400 font-medium uppercase tracking-wider mt-1 block leading-tight">Serce Białegostoku</span>
                        </div>
                        <div class="p-3 bg-[#0B0F19] rounded-xl border border-slate-800/50 flex flex-col justify-center min-h-[90px]">
                            <span class="block text-2xl sm:text-3xl font-bold text-red-500">Gorąca</span>
                            <span class="text-[11px] sm:text-xs text-slate-400 font-medium uppercase tracking-wider mt-1 block leading-tight">Szybka Dostawa</span>
                        </div>
                        <div class="p-3 bg-[#0B0F19] rounded-xl border border-slate-800/50 flex flex-col justify-center min-h-[90px]">
                            <span class="block text-2xl sm:text-3xl font-bold text-red-500">Kultowa</span>
                            <span class="text-[11px] sm:text-xs text-slate-400 font-medium uppercase tracking-wider mt-1 block leading-tight">Sałatka Paryska</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- KARTA DAŃ I KAFELKI MENU -->
            <section id="menu" class="py-16 bg-slate-950/40 border-t border-b border-slate-900 scroll-mt-20">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    
                    <div class="text-center max-w-3xl mx-auto mb-10 space-y-3">
                        <span class="text-red-500 font-bold uppercase tracking-wider text-xs">Odkryj Nasze Smaki</span>
                        <h2 class="text-3xl md:text-4xl font-bold text-white">Karta Dań & Menu Pizzerii</h2>
                        <p class="text-slate-400 text-sm">
                            Kliknij kafel kategorii, aby odfiltrować wybrane specjały i dostosować składniki do swojego zamówienia.
                        </p>
                    </div>

                    <!-- KAFELKI KATEGORII DAŃ -->
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3.5 mb-12">
                        <button 
                            v-for="cat in uniqueCategories" 
                            :key="cat"
                            @click="filterProducts(cat)"
                            :class="activeCategoryFilter === cat ? 'bg-gradient-to-b from-red-600 to-red-700 text-white border-red-500 shadow-xl shadow-red-600/20 scale-[1.02]' : 'bg-slate-900/90 text-slate-300 border-slate-800 hover:border-slate-700 hover:bg-slate-800/80'"
                            class="p-4 rounded-2xl border transition-all duration-300 flex flex-col items-center justify-center space-y-2 cursor-pointer group text-center"
                        >
                            <div 
                                :class="activeCategoryFilter === cat ? 'bg-white/20 text-white' : 'bg-[#0B0F19] text-amber-500 group-hover:text-red-400'"
                                class="p-3 rounded-xl border border-slate-800 transition"
                            >
                                <component :is="getCategoryIcon(cat)" class="w-6 h-6" />
                            </div>
                            <span class="font-bold text-xs uppercase tracking-wider">{{ cat }}</span>
                        </button>
                    </div>

                    <!-- KARTY PRODUKTÓW ORAZ KOSZYK -->
                    <div id="products-grid" class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start scroll-mt-24">
                        
                        <!-- LISTA DAŃ -->
                        <div class="lg:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div v-for="product in filteredProducts" :key="product.id" class="bg-slate-900 rounded-2xl overflow-hidden shadow-xl border border-slate-800 hover:border-slate-700 transition group flex flex-col justify-between">
                                
                                <div>
                                    <!-- Zdjęcie -->
                                    <div class="h-52 overflow-hidden relative bg-slate-950">
                                        <img 
                                            v-if="product.image_path" 
                                            :src="'/storage/' + product.image_path" 
                                            :alt="product.name" 
                                            class="w-full h-full object-cover group-hover:scale-105 transition duration-500 opacity-90 group-hover:opacity-100"
                                        />
                                        <div v-else class="h-full w-full flex flex-col items-center justify-center text-slate-600 bg-slate-950">
                                            <Pizza class="w-12 h-12 text-slate-700" />
                                            <span class="text-[9px] uppercase font-bold text-slate-600 mt-2">Wypiek Rzemieślniczy</span>
                                        </div>
                                        <span class="absolute top-3 right-3 bg-slate-950/80 backdrop-blur-md px-3 py-1 border border-slate-800 rounded-full uppercase text-[10px] font-bold tracking-wider text-amber-400">
                                            {{ product.category }}
                                        </span>
                                    </div>

                                    <!-- Treść -->
                                    <div class="p-6 space-y-3">
                                        <h4 class="text-xl font-bold text-white group-hover:text-amber-400 transition">{{ product.name }}</h4>
                                        <p class="text-xs text-slate-400 leading-relaxed line-clamp-3">
                                            {{ product.description || 'Kompozycja autorskich, świeżych składników dobrana według tradycyjnej receptury.' }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Warianty rozmiarów i przyciski -->
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

                        <!-- KOSZYK INTERAKTYWNY -->
                        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 sticky top-24 shadow-2xl space-y-4">
                            <h3 class="text-sm font-bold text-slate-300 uppercase tracking-wider border-b border-slate-800 pb-3 flex items-center justify-between">
                                <span>Twoje Zamówienie</span>
                                <ShoppingBag class="w-4 h-4 text-amber-500" />
                            </h3>

                            <div class="space-y-3 max-h-[35vh] overflow-y-auto pr-1">
                                <div v-for="(item, idx) in cart" :key="idx" class="bg-[#0B0F19] p-3 rounded-xl border border-slate-800 text-xs space-y-1.5">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <div class="font-bold text-white uppercase">{{ item.name }}</div>
                                            <div class="text-slate-400 text-[11px]">{{ item.size }} — {{ item.quantity }} szt.</div>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <span class="font-mono font-bold text-emerald-400">{{ (item.price * item.quantity).toFixed(2) }} zł</span>
                                            <button @click="removeFromCart(idx)" class="text-slate-500 hover:text-red-400 transition cursor-pointer">
                                                <X class="w-4 h-4" />
                                            </button>
                                        </div>
                                    </div>

                                    <div v-if="item.modifiers.length > 0" class="flex flex-wrap gap-1 mt-1 pt-1 border-t border-slate-800/60">
                                        <span 
                                            v-for="mod in item.modifiers" 
                                            :key="mod.ingredient_id"
                                            :class="mod.action === 'ADD' ? 'bg-emerald-950/60 text-emerald-400 border-emerald-900' : 'bg-red-950/60 text-red-400 border-red-900'"
                                            class="text-[9px] font-bold px-1.5 py-0.5 rounded border uppercase"
                                        >
                                            {{ mod.action === 'ADD' ? 'Ekstra' : 'Bez' }} {{ mod.name }}
                                        </span>
                                    </div>
                                </div>

                                <div v-if="cart.length === 0" class="text-center py-8 text-xs text-slate-500 italic">
                                    Koszyk jest pusty. Wybierz pozycję z menu.
                                </div>
                            </div>

                            <div v-if="cart.length > 0" class="space-y-4 pt-3 border-t border-slate-800">
                                <!-- TYP REALIZACJI -->
                                <div class="grid grid-cols-2 gap-2">
                                    <button 
                                        @click="form.type = 'dostawa'" 
                                        :class="form.type === 'dostawa' ? 'bg-red-600 text-white border-red-500' : 'bg-[#0B0F19] text-slate-400 border-slate-800'"
                                        class="py-2 rounded-xl text-xs font-bold border transition flex items-center justify-center space-x-1.5 cursor-pointer"
                                    >
                                        <Car class="w-3.5 h-3.5" />
                                        <span>Dostawa</span>
                                    </button>
                                    <button 
                                        @click="form.type = 'wynos'" 
                                        :class="form.type === 'wynos' ? 'bg-red-600 text-white border-red-500' : 'bg-[#0B0F19] text-slate-400 border-slate-800'"
                                        class="py-2 rounded-xl text-xs font-bold border transition flex items-center justify-center space-x-1.5 cursor-pointer"
                                    >
                                        <Store class="w-3.5 h-3.5" />
                                        <span>Odbiór</span>
                                    </button>
                                </div>

                                <div v-if="form.type === 'dostawa'" class="space-y-1">
                                    <input 
                                        v-model="form.delivery_address" 
                                        type="text" 
                                        placeholder="Adres dostawy (np. ul. Lipowa 10 m. 5)" 
                                        class="w-full bg-[#0B0F19] border border-slate-800 focus:border-red-500 rounded-xl p-2.5 text-xs text-white placeholder:text-slate-600"
                                        :required="form.type === 'dostawa'"
                                    />
                                </div>

                                <p v-if="minOrderWarning" class="text-[10px] text-red-400 bg-red-950/40 p-2.5 rounded-xl border border-red-900/50 font-bold leading-relaxed flex items-center space-x-1">
                                    <AlertTriangle class="w-3.5 h-3.5 text-red-400 shrink-0" />
                                    <span>{{ minOrderWarning }}</span>
                                </p>

                                <!-- PŁATNOŚĆ -->
                                <div class="grid grid-cols-3 gap-1.5">
                                    <button 
                                        @click="form.payment_method = 'blik'" 
                                        :class="form.payment_method === 'blik' ? 'bg-amber-500/20 text-amber-400 border-amber-500' : 'bg-[#0B0F19] text-slate-400 border-slate-800'"
                                        class="py-2 rounded-xl text-[10px] font-bold border transition text-center cursor-pointer"
                                    >
                                        BLIK
                                    </button>
                                    <button 
                                        @click="form.payment_method = 'payu'" 
                                        :class="form.payment_method === 'payu' ? 'bg-emerald-500/20 text-emerald-400 border-emerald-500' : 'bg-[#0B0F19] text-slate-400 border-slate-800'"
                                        class="py-2 rounded-xl text-[10px] font-bold border transition text-center cursor-pointer"
                                    >
                                        PayU
                                    </button>
                                    <button 
                                        @click="form.payment_method = 'gotówka'" 
                                        :class="form.payment_method === 'gotówka' ? 'bg-blue-500/20 text-blue-400 border-blue-500' : 'bg-[#0B0F19] text-slate-400 border-slate-800'"
                                        class="py-2 rounded-xl text-[10px] font-bold border transition text-center cursor-pointer"
                                    >
                                        Gotówka
                                    </button>
                                </div>

                                <!-- KOD RABATOWY -->
                                <div class="bg-[#0B0F19] p-2.5 rounded-xl border border-slate-800 space-y-2">
                                    <div v-if="!appliedDiscount" class="flex space-x-2">
                                        <input 
                                            v-model="discountCodeInput" 
                                            type="text" 
                                            placeholder="Kod rabatowy" 
                                            class="w-2/3 bg-slate-900 border border-slate-800 focus:border-red-500 rounded-xl px-3 py-1.5 text-xs text-white uppercase font-mono"
                                        />
                                        <button 
                                            type="button"
                                            @click="applyDiscountCode"
                                            :disabled="!discountCodeInput || isValidatingCode"
                                            class="w-1/3 bg-slate-800 hover:bg-red-600 disabled:opacity-50 text-white font-bold rounded-xl text-xs uppercase cursor-pointer flex items-center justify-center"
                                        >
                                            <Loader2 v-if="isValidatingCode" class="w-3.5 h-3.5 animate-spin" />
                                            <span v-else>Użyj</span>
                                        </button>
                                    </div>

                                    <div v-if="appliedDiscount" class="flex justify-between items-center text-xs">
                                        <span class="font-mono font-bold text-emerald-400 uppercase">{{ appliedDiscount.code }}</span>
                                        <button type="button" @click="removeDiscountCode" class="text-slate-500 hover:text-red-400 font-bold cursor-pointer">✕</button>
                                    </div>
                                </div>

                                <!-- RAZEM -->
                                <div class="flex justify-between items-center pt-2 border-t border-slate-800">
                                    <span class="text-xs text-slate-300 uppercase font-bold">Do zapłaty:</span>
                                    <span class="text-2xl font-black font-mono text-emerald-400">
                                        {{ cartTotal.toFixed(2) }} zł
                                    </span>
                                </div>

                                <button 
                                    @click="checkout"
                                    :disabled="(form.type === 'dostawa' && (!form.delivery_address || minOrderWarning)) || form.processing"
                                    class="w-full bg-red-600 hover:bg-red-700 disabled:bg-slate-800 disabled:text-slate-600 text-white font-bold py-3.5 rounded-xl text-xs uppercase tracking-wider transition shadow-lg flex items-center justify-center space-x-2 cursor-pointer"
                                >
                                    <Send class="w-4 h-4" />
                                    <span>{{ form.processing ? 'Wysyłanie...' : 'Wyślij zamówienie' }}</span>
                                </button>
                            </div>
                        </div>

                    </div>

                </div>
            </section>

            <!-- KONTAKT -->
            <section id="kontakt" class="py-16 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 scroll-mt-20">
                <div class="bg-slate-900 rounded-3xl overflow-hidden text-white shadow-2xl border border-slate-800 grid md:grid-cols-2">
                    <div class="p-8 md:p-12 space-y-8 flex flex-col justify-center">
                        <div class="space-y-3">
                            <span class="text-amber-400 font-semibold tracking-wider uppercase text-sm">Odwiedź nas lub zadzwoń</span>
                            <h2 class="text-3xl font-bold">Zapraszamy do Savony!</h2>
                            <p class="text-slate-400 text-sm">
                                Znajdziesz nas w dogodnej lokalizacji w Białymstoku. Dbamy o to, by zamówienia na dowóz docierały gorące!
                            </p>
                        </div>

                        <div class="space-y-4 font-light text-slate-300">
                            <div class="flex items-start space-x-3">
                                <MapPin class="w-5 h-5 text-amber-400 mt-1 shrink-0" />
                                <div>
                                    <strong class="font-semibold text-white block">Adresy lokali:</strong>
                                    <span class="text-white font-medium">Pizzeria Savona Legionowa</span><br>ul. Legionowa 9/1, 15-369 Białystok<br><br>
                                    <span class="text-white font-medium">Pizzeria Primo Savona</span><br>Rynek Kościuszki 8/1, 15-426 Białystok
                                </div>
                            </div>
                            <div class="flex items-center space-x-3">
                                <Phone class="w-5 h-5 text-amber-400 shrink-0" />
                                <div>
                                    <strong class="font-semibold text-white">Telefon: </strong>
                                    <a href="tel:+48785555455" class="hover:text-amber-400 transition text-amber-400 font-medium">+48 785 555 455</a>
                                </div>
                            </div>
                            <div class="flex items-start space-x-3">
                                <Clock class="w-5 h-5 text-amber-400 mt-1 shrink-0" />
                                <div>
                                    <strong class="font-semibold text-white block">Godziny otwarcia:</strong>
                                    Pon - Czw: 11:00 - 22:00<br>
                                    Pt - Sob: 11:00 - 23:00<br>
                                    Niedziela: 12:00 - 22:00
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="h-96 md:h-auto bg-[#0B0F19] relative border-t md:border-t-0 md:border-l border-slate-800 overflow-hidden">
                        <iframe src="https://www.google.com/maps/d/embed?mid=1jZkPl1rLXnNmAGChU_ttv8vYePFnGdc&ehbc=2E312F&noprof=1" class="w-full h-full border-0"></iframe>
                    </div>
                </div>
            </section>
        </main>

        <!-- STOPKA -->
        <footer class="bg-slate-950 text-slate-500 text-xs py-8 border-t border-slate-900">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                &copy; 2026 Pizzeria Savona Białystok. Wszelkie prawa zastrzeżone.
            </div>
        </footer>

        <!-- MODAL MODYFIKACJI SKŁADNIKÓW -->
        <div v-if="isModifierModalOpen" class="fixed inset-0 bg-black/80 flex items-center justify-center p-4 backdrop-blur-sm z-50">
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 w-full max-w-md shadow-2xl flex flex-col max-h-[85vh]">
                <div class="border-b border-slate-800 pb-3 mb-4 flex justify-between items-start">
                    <div>
                        <h3 class="text-base font-bold text-amber-400 uppercase tracking-wide">Komponujesz własną pizzę</h3>
                        <p class="text-xs text-slate-400 font-medium">{{ activeProduct?.name }} ({{ activeVariant?.size_name }})</p>
                    </div>
                    <button @click="isModifierModalOpen = false" class="text-slate-500 hover:text-white transition cursor-pointer">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <div class="overflow-y-auto space-y-2 pr-1 flex-1">
                    <div v-for="ing in activeVariant?.ingredients" :key="ing.id" class="bg-[#0B0F19] p-3 rounded-xl border border-slate-800 flex justify-between items-center text-xs">
                        <span class="font-bold text-slate-200 uppercase tracking-wide text-[11px]">{{ ing.name }}</span>
                        <div class="flex space-x-2">
                            <button 
                                @click="toggleModifier(ing, 'REMOVE')"
                                :class="getModifierAction(ing.id) === 'REMOVE' ? 'bg-red-600 text-white border-red-500' : 'bg-slate-900 text-red-400 border-slate-800 hover:border-red-500'"
                                class="px-3 py-1.5 text-[10px] font-bold rounded-lg border uppercase transition cursor-pointer"
                            >
                                Bez
                            </button>
                            <button 
                                @click="toggleModifier(ing, 'ADD')"
                                :class="getModifierAction(ing.id) === 'ADD' ? 'bg-emerald-600 text-white border-emerald-500' : 'bg-slate-900 text-emerald-400 border-slate-800 hover:border-emerald-500'"
                                class="px-3 py-1.5 text-[10px] font-bold rounded-lg border uppercase transition cursor-pointer"
                            >
                                + Dodaj Extra
                            </button>
                        </div>
                    </div>
                </div>

                <div class="flex space-x-3 pt-4 border-t border-slate-800 mt-4">
                    <button @click="isModifierModalOpen = false" class="w-1/3 bg-slate-800 hover:bg-slate-700 py-3 rounded-xl text-xs font-bold uppercase tracking-wider text-slate-300 transition cursor-pointer">Anuluj</button>
                    <button @click="addCustomizedToCart" class="w-2/3 bg-red-600 hover:bg-red-700 text-white font-bold py-3 rounded-xl text-xs uppercase tracking-wider transition shadow-md cursor-pointer">
                        Zatwierdź składniki
                    </button>
                </div>
            </div>
        </div>

    </div>
</template>