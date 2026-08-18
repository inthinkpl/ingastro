<script setup>
import { ref, computed } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import axios from 'axios';
import { 
    Pizza, ShoppingBag, Phone, MapPin, Car, Store, 
    AlertTriangle, Send, X, Loader2, Utensils, Flame, Sparkles, 
    Menu as MenuIcon
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

// Kod rabatowy
const discountCodeInput = ref('');
const appliedDiscount = ref(null);
const discountError = ref(null);
const isValidatingCode = ref(false);

// Dynamiczne kategorie
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

// Filtrowanie produktów
const filteredProducts = computed(() => {
    if (activeCategoryFilter.value === 'Wszystko') return props.products;
    return props.products.filter(p => p.category === activeCategoryFilter.value);
});

const filterProducts = (cat) => {
    activeCategoryFilter.value = cat;
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

// Formularz zamówienia
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

const cartTotal = computed(() => Math.max(0, cartSubtotal.value - discountValue.value));

const minOrderWarning = computed(() => {
    if (form.type !== 'dostawa') return null;
    if (cartTotal.value < props.minOrderAmount) {
        const missing = (props.minOrderAmount - cartTotal.value).toFixed(2);
        return `Minimum w dostawie wynosi ${props.minOrderAmount.toFixed(2)} zł. Brakuje ${missing} zł.`;
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
        discountError.value = error.response?.data?.message || 'Błąd kodu.';
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

        <!-- HEADER (IDENTYCZNY JAK NA STRONIE GŁÓWNEJ) -->
        <header class="sticky top-0 z-50 bg-[#0B0F19]/90 backdrop-blur-md border-b border-slate-900 shadow-xl">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
                
                <!-- LOGO STRONY -->
                <Link :href="route('shop.index')" class="flex items-center space-x-2 cursor-pointer">
                    <span class="text-2xl font-bold text-red-500 tracking-wider">SAVONA</span>
                    <span class="text-xs bg-amber-500 text-slate-950 px-2 py-0.5 rounded-full font-semibold">pizza</span>
                </Link>
                
                <!-- MENU DESKTOP -->
                <nav class="hidden md:flex items-center space-x-8 font-medium text-sm">
                    <Link :href="route('shop.index') + '#o-nas'" class="text-slate-300 hover:text-red-500 transition cursor-pointer">O nas</Link>
                    
                    <Link 
                        :href="route('shop.menu')" 
                        class="bg-red-600/10 border border-red-500/30 text-red-400 hover:bg-red-600 hover:text-white px-3.5 py-1.5 rounded-full transition flex items-center space-x-1.5 cursor-pointer font-bold uppercase tracking-wide text-xs"
                    >
                        <Utensils class="w-3.5 h-3.5" />
                        <span>Karta Dań (Menu)</span>
                    </Link>

                    <Link :href="route('shop.index') + '#kontakt'" class="text-slate-300 hover:text-red-500 transition cursor-pointer">Kontakt</Link>
                </nav>

                <!-- AKCJE PO PRAWEJ STRONIE HEADER-A -->
                <div class="flex items-center space-x-3">
                    <a href="tel:+48785555455" class="border border-slate-700 hover:border-slate-500 hover:text-white text-slate-300 px-3 sm:px-5 py-2.5 rounded-full font-semibold transition inline-flex items-center space-x-2 shadow-sm text-sm sm:text-base">
                        <Phone class="w-4 h-4 text-amber-500" />
                        <span class="hidden sm:inline">785 555 455</span>
                    </a>
                    
                    <button @click="scrollToSection('cart-section')" class="bg-red-600 hover:bg-red-700 text-white px-4 sm:px-5 py-2.5 rounded-full font-semibold transition inline-flex items-center space-x-2 shadow-lg shadow-red-600/30 text-sm sm:text-base cursor-pointer">
                        <ShoppingBag class="w-4 h-4" />
                        <span class="hidden xs:inline">Koszyk</span>
                        <span class="bg-amber-500 text-slate-950 text-xs px-2 py-0.5 rounded-full font-bold ml-1">
                            {{ cartItemsCount }}
                        </span>
                    </button>

                    <!-- HAMBURGER MENU DLA SMARTFONÓW -->
                    <button @click="isMobileMenuOpen = !isMobileMenuOpen" class="md:hidden p-2 text-slate-400 hover:text-white">
                        <MenuIcon class="w-6 h-6" />
                    </button>
                </div>
            </div>

            <!-- ROZWIJANE MENU MOBILNE -->
            <div v-if="isMobileMenuOpen" class="md:hidden bg-slate-900 border-b border-slate-800 px-4 py-4 space-y-3 text-sm">
                <Link :href="route('shop.index') + '#o-nas'" class="block text-slate-300 py-1">O nas</Link>
                <Link :href="route('shop.menu')" class="block text-amber-400 font-bold py-1 flex items-center space-x-2">
                    <Utensils class="w-4 h-4" />
                    <span>Karta Dań (Menu)</span>
                </Link>
                <Link :href="route('shop.index') + '#kontakt'" class="block text-slate-300 py-1">Kontakt</Link>
            </div>
        </header>

        <!-- SEKCJA GŁÓWNA: KAFELKI KATEGORII + LISTA DAŃ + KOSZYK -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
            
            <!-- TYTUŁ I KAFELKI KATEGORII -->
            <div class="space-y-4">
                <div class="flex justify-between items-end border-b border-slate-900 pb-4">
                    <div>
                        <h1 class="text-2xl sm:text-3xl font-black text-white uppercase tracking-wide">Zamów Online</h1>
                        <p class="text-xs text-slate-400 mt-1">Wybierz danie, dopasuj składniki i wyślij zamówienie bezpośrednio do pizzerii.</p>
                    </div>
                </div>

                <!-- KAFELKI KATEGORII -->
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
                    <button 
                        v-for="cat in uniqueCategories" 
                        :key="cat"
                        @click="filterProducts(cat)"
                        :class="activeCategoryFilter === cat ? 'bg-gradient-to-b from-red-600 to-red-700 text-white border-red-500 shadow-xl shadow-red-600/20 scale-[1.02]' : 'bg-slate-900 text-slate-300 border-slate-800 hover:border-slate-700 hover:bg-slate-800/80'"
                        class="p-3.5 rounded-2xl border transition-all duration-300 flex flex-col items-center justify-center space-y-2 cursor-pointer text-center"
                    >
                        <div :class="activeCategoryFilter === cat ? 'bg-white/20 text-white' : 'bg-[#0B0F19] text-amber-500'" class="p-2.5 rounded-xl border border-slate-800">
                            <component :is="getCategoryIcon(cat)" class="w-5 h-5" />
                        </div>
                        <span class="font-bold text-xs uppercase tracking-wider">{{ cat }}</span>
                    </button>
                </div>
            </div>

            <!-- SIATKA DAŃ ORAZ KOSZYK BOCZNY -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
                
                <!-- LISTA PRODUKTÓW -->
                <div class="lg:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div v-for="product in filteredProducts" :key="product.id" class="bg-slate-900 rounded-2xl overflow-hidden shadow-xl border border-slate-800 hover:border-slate-700 transition flex flex-col justify-between">
                        <div>
                            <div class="h-48 overflow-hidden relative bg-slate-950">
                                <img v-if="product.image_path" :src="'/storage/' + product.image_path" :alt="product.name" class="w-full h-full object-cover opacity-90" />
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
                <div id="cart-section" class="bg-slate-900 border border-slate-800 rounded-2xl p-6 sticky top-24 shadow-2xl space-y-4">
                    <h3 class="text-xs font-bold text-slate-300 uppercase tracking-wider border-b border-slate-800 pb-3 flex items-center justify-between">
                        <span>Podsumowanie Zamówienia</span>
                        <ShoppingBag class="w-4 h-4 text-amber-500" />
                    </h3>

                    <div class="space-y-2.5 max-h-[40vh] overflow-y-auto pr-1">
                        <div v-for="(item, idx) in cart" :key="idx" class="bg-[#0B0F19] p-3 rounded-xl border border-slate-800 text-xs space-y-1">
                            <div class="flex justify-between items-start">
                                <div>
                                    <div class="font-bold text-white uppercase">{{ item.name }}</div>
                                    <div class="text-slate-400 text-[10px]">{{ item.size }} — {{ item.quantity }} szt.</div>
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
                                    {{ mod.action === 'ADD' ? 'Extra' : 'Bez' }} {{ mod.name }}
                                </span>
                            </div>
                        </div>

                        <div v-if="cart.length === 0" class="text-center py-8 text-xs text-slate-500 italic">
                            Koszyk jest pusty. Wybierz danie z listy.
                        </div>
                    </div>

                    <div v-if="cart.length > 0" class="space-y-3 pt-3 border-t border-slate-800">
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
                                placeholder="Adres dostawy (ulica, numer)" 
                                class="w-full bg-[#0B0F19] border border-slate-800 focus:border-red-500 rounded-xl p-2.5 text-xs text-white"
                                :required="form.type === 'dostawa'"
                            />
                        </div>

                        <p v-if="minOrderWarning" class="text-[10px] text-red-400 bg-red-950/40 p-2 rounded-xl border border-red-900/50 font-bold flex items-center space-x-1">
                            <AlertTriangle class="w-3.5 h-3.5 text-red-400 shrink-0" />
                            <span>{{ minOrderWarning }}</span>
                        </p>

                        <div class="grid grid-cols-3 gap-1">
                            <button @click="form.payment_method = 'blik'" :class="form.payment_method === 'blik' ? 'bg-amber-500/20 text-amber-400 border-amber-500' : 'bg-[#0B0F19] text-slate-400 border-slate-800'" class="py-1.5 rounded-xl text-[10px] font-bold border transition text-center cursor-pointer">BLIK</button>
                            <button @click="form.payment_method = 'payu'" :class="form.payment_method === 'payu' ? 'bg-emerald-500/20 text-emerald-400 border-emerald-500' : 'bg-[#0B0F19] text-slate-400 border-slate-800'" class="py-1.5 rounded-xl text-[10px] font-bold border transition text-center cursor-pointer">PayU</button>
                            <button @click="form.payment_method = 'gotówka'" :class="form.payment_method === 'gotówka' ? 'bg-blue-500/20 text-blue-400 border-blue-500' : 'bg-[#0B0F19] text-slate-400 border-slate-800'" class="py-1.5 rounded-xl text-[10px] font-bold border transition text-center cursor-pointer">Gotówka</button>
                        </div>

                        <div class="flex justify-between items-center pt-2 border-t border-slate-800">
                            <span class="text-xs text-slate-300 uppercase font-bold">Razem:</span>
                            <span class="text-2xl font-black font-mono text-emerald-400">{{ cartTotal.toFixed(2) }} zł</span>
                        </div>

                        <button 
                            @click="checkout"
                            :disabled="(form.type === 'dostawa' && (!form.delivery_address || minOrderWarning)) || form.processing"
                            class="w-full bg-red-600 hover:bg-red-700 disabled:bg-slate-800 disabled:text-slate-600 text-white font-bold py-3 rounded-xl text-xs uppercase tracking-wider transition flex items-center justify-center space-x-2 cursor-pointer shadow-lg"
                        >
                            <Send class="w-4 h-4" />
                            <span>{{ form.processing ? 'Wysyłanie...' : 'Złóż Zamówienie' }}</span>
                        </button>
                    </div>
                </div>

            </div>

        </main>

        <!-- MODAL MODYFIKACJI SKŁADNIKÓW -->
        <div v-if="isModifierModalOpen" class="fixed inset-0 bg-black/80 flex items-center justify-center p-4 backdrop-blur-sm z-50">
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 w-full max-w-md shadow-2xl flex flex-col max-h-[85vh]">
                <div class="border-b border-slate-800 pb-3 mb-4 flex justify-between items-start">
                    <div>
                        <h3 class="text-sm font-bold text-amber-400 uppercase tracking-wide">Modyfikacja składników</h3>
                        <p class="text-xs text-slate-400 font-medium">{{ activeProduct?.name }} ({{ activeVariant?.size_name }})</p>
                    </div>
                    <button @click="isModifierModalOpen = false" class="text-slate-500 hover:text-white transition cursor-pointer">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <div class="overflow-y-auto space-y-2 pr-1 flex-1">
                    <div v-for="ing in activeVariant?.ingredients" :key="ing.id" class="bg-[#0B0F19] p-2.5 rounded-xl border border-slate-800 flex justify-between items-center text-xs">
                        <span class="font-bold text-slate-200 uppercase tracking-wide text-[11px]">{{ ing.name }}</span>
                        <div class="flex space-x-2">
                            <button @click="toggleModifier(ing, 'REMOVE')" :class="getModifierAction(ing.id) === 'REMOVE' ? 'bg-red-600 text-white border-red-500' : 'bg-slate-900 text-red-400 border-slate-800'" class="px-2.5 py-1 text-[10px] font-bold rounded-lg border uppercase cursor-pointer">Bez</button>
                            <button @click="toggleModifier(ing, 'ADD')" :class="getModifierAction(ing.id) === 'ADD' ? 'bg-emerald-600 text-white border-emerald-500' : 'bg-slate-900 text-emerald-400 border-slate-800'" class="px-2.5 py-1 text-[10px] font-bold rounded-lg border uppercase cursor-pointer">+ Extra</button>
                        </div>
                    </div>
                </div>

                <div class="flex space-x-3 pt-4 border-t border-slate-800 mt-4">
                    <button @click="isModifierModalOpen = false" class="w-1/3 bg-slate-800 hover:bg-slate-700 py-2.5 rounded-xl text-xs font-bold uppercase text-slate-300 transition cursor-pointer">Anuluj</button>
                    <button @click="addCustomizedToCart" class="w-2/3 bg-red-600 hover:bg-red-700 text-white font-bold py-2.5 rounded-xl text-xs uppercase transition shadow-md cursor-pointer">Dodaj do koszyka</button>
                </div>
            </div>
        </div>

    </div>
</template>