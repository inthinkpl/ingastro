<script setup>
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({
    products: { type: Array, default: () => [] },
    ingredients: { type: Array, default: () => [] },
    minOrderAmount: { type: Number, default: 40.00 } // Globalny próg min. zamówienia w dostawie z Ustawień
});

// Stan koszyka i modali
const cart = ref([]);
const isModifierModalOpen = ref(false);
const activeProduct = ref(null);
const activeVariant = ref(null);
const selectedModifiers = ref([]);
const activeCategoryFilter = ref('Wszystko');

// 🔥 STAN KODU RABATOWEGO
const discountCodeInput = ref('');
const appliedDiscount = ref(null);
const discountError = ref(null);
const isValidatingCode = ref(false);

// Dynamiczne pobieranie unikalnych kategorii z listy produktów
const uniqueCategories = computed(() => ['Wszystko', ...new Set(props.products.map(p => p.category))]);

// Filtrowanie produktów wg wybranej kategorii
const filteredProducts = computed(() => {
    if (activeCategoryFilter.value === 'Wszystko') {
        return props.products;
    }
    return props.products.filter(p => p.category === activeCategoryFilter.value);
});

const filterProducts = (cat) => {
    activeCategoryFilter.value = cat;
};

// Otwieranie okna personalizacji składników (modyfikatorów)
const openModifierModal = (product, variant) => {
    activeProduct.value = product;
    activeVariant.value = variant;
    selectedModifiers.value = [];
    isModifierModalOpen.value = true;
};

// Dodawanie/usuwanie składników w modalu
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

// Zapis skonfigurowanej pizzy do koszyka
const addCustomizedToCart = () => {
    cart.value.push({
        variantId: activeVariant.value.id,
        name: activeProduct.value.name,
        size: activeVariant.value.size_name,
        price: parseFloat(activeVariant.value.price),
        quantity: 1,
        modifiers: [...selectedModifiers.value]
    });
    isModifierModalOpen.value = false;
};

const removeFromCart = (index) => {
    cart.value.splice(index, 1);
};

// Formularz zamówienia Inertia
const form = useForm({
    type: 'dostawa',
    payment_method: 'blik',
    delivery_address: '',
    discount_code: '', // 🔥 Pole z kodem rabatowym
    items: []
});

// Wartość częściowa koszyka (przed rabatem)
const cartSubtotal = computed(() => {
    return cart.value.reduce((sum, i) => sum + (i.price * i.quantity), 0);
});

// Wyliczona kwota zniżki
const discountValue = computed(() => {
    if (!appliedDiscount.value) return 0.00;
    
    if (appliedDiscount.value.type === 'percent') {
        return Math.round((cartSubtotal.value * (appliedDiscount.value.value / 100)) * 100) / 100;
    }
    return Math.min(appliedDiscount.value.value, cartSubtotal.value);
});

// Ostateczna kwota do zapłaty po rabacie
const cartTotal = computed(() => {
    return Math.max(0, cartSubtotal.value - discountValue.value);
});

// Liczba pozycji dla indykatora w menu
const cartItemsCount = computed(() => cart.value.reduce((sum, item) => sum + item.quantity, 0));

// Ostrzeżenie o braku minimalnej kwoty zamówienia w dostawie
const minOrderWarning = computed(() => {
    if (form.type !== 'dostawa') return null;
    
    if (cartTotal.value < props.minOrderAmount) {
        const missing = (props.minOrderAmount - cartTotal.value).toFixed(2);
        return `Minimalna wartość zamówienia w dostawie wynosi ${props.minOrderAmount.toFixed(2)} zł. Dołóż do koszyka dania za jeszcze ${missing} zł.`;
    }
    return null;
});

// 🔥 Funkcja sprawdzająca kod rabatowy
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

// Wysyłka zamówienia
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
            cart.value = [];
            form.reset('delivery_address', 'discount_code');
            appliedDiscount.value = null;
            discountCodeInput.value = '';
            alert('Grazie! Twoje zamówienie zostało przekazane bezpośrednio na monitor kuchenny naszej pizzerii.');
        }
    });
};

// Płynne przewijanie do sekcji podstron
const scrollToSection = (id) => {
    const el = document.getElementById(id);
    if (el) {
        el.scrollIntoView({ behavior: 'smooth' });
    }
};
</script>

<template>
    <div class="min-h-screen bg-slate-950 text-white font-sans selection:bg-orange-600 selection:text-white">
        
        <!-- 1. GÓRNE MENU NAWIGACYJNE (STICKY NAVBAR) -->
        <nav class="sticky top-0 z-50 bg-slate-950/90 backdrop-blur-md border-b border-slate-900/80 px-6 py-4 transition-all">
            <div class="max-w-6xl mx-auto flex justify-between items-center">
                <!-- LOGO -->
                <div @click="scrollToSection('hero-section')" class="flex items-center space-x-2 cursor-pointer">
                    <span class="text-2xl">🍕</span>
                    <span class="text-lg font-black tracking-widest text-orange-500 uppercase">SAVONA</span>
                </div>

                <!-- LINKI DO PODSTRON -->
                <div class="hidden md:flex items-center space-x-8 text-xs font-black uppercase tracking-wider text-slate-400">
                    <button @click="scrollToSection('hero-section')" class="hover:text-orange-500 transition-colors">Strona Główna</button>
                    <button @click="scrollToSection('about-section')" class="hover:text-orange-500 transition-colors">O Nas</button>
                    <button @click="scrollToSection('menu-section')" class="hover:text-orange-500 transition-colors">Karta Dań</button>
                    <button @click="scrollToSection('contact-section')" class="hover:text-orange-500 transition-colors">Kontakt</button>
                </div>

                <!-- MINI WSKAŹNIK KOSZYKA -->
                <button 
                    @click="scrollToSection('menu-section')" 
                    class="bg-slate-900 border border-slate-800 px-3 py-1.5 rounded-xl text-xs font-bold flex items-center space-x-2 text-slate-200 hover:border-orange-500 transition-colors"
                >
                    <span>🛒 Koszyk:</span>
                    <span class="font-mono text-orange-400 font-black bg-slate-950 px-2 py-0.5 rounded-md">
                        {{ cartItemsCount }}
                    </span>
                </button>
            </div>
        </nav>

        <!-- 2. SEKCJA HERO (BANER POWITALNY) -->
        <section id="hero-section" class="relative bg-slate-950 py-32 md:py-48 flex items-center justify-center border-b border-slate-900/50 overflow-hidden">
            <div class="absolute inset-0 z-0">
                <img 
                    src="https://inthink.pl/hero.jpg" 
                    alt="Tradycyjny piec do pizzy" 
                    class="h-full w-full object-cover object-center brightness-[0.50] contrast-[1.15]"
                />
            </div>
            <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/40 to-slate-950/20 z-10"></div>
            <div class="absolute inset-0 bg-black/30 z-10"></div>
            
            <div class="relative z-20 max-w-4xl mx-auto text-center px-6 space-y-6">
                <span class="text-xs font-black uppercase tracking-widest text-orange-500 bg-orange-950/60 backdrop-blur-md border border-orange-900/60 px-4 py-1.5 rounded-full inline-block">
                    🇮🇹 Prawdziwa włoska receptura w Twoim mieścieee
                </span>
                <h1 class="text-4xl sm:text-6xl font-black uppercase tracking-tight text-white leading-none drop-shadow-md">
                    Tradycja Wypiekana <br class="hidden sm:block" />
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-orange-500 to-red-600">Żywym Ogniem</span>
                </h1>
                <p class="text-slate-300 max-w-xl mx-auto text-sm sm:text-base font-medium drop-shadow">
                    Nasze ciasto dojrzewa minimum 48 godzin, a sos powstaje wyłącznie z oryginalnych pomidorów San Marzano. Spróbuj różnicy.
                </p>
                <div class="pt-4">
                    <button 
                        @click="scrollToSection('menu-section')"
                        class="bg-gradient-to-r from-orange-600 to-red-600 hover:from-orange-500 hover:to-red-500 font-black px-8 py-4 rounded-xl text-xs uppercase tracking-wider transition-all shadow-xl hover:scale-105 active:scale-95 border border-orange-500/20"
                    >
                        🔥 Zobacz Menu i Zamów
                    </button>
                </div>
            </div>
        </section>

        <!-- 3. PODSTRONA: O NAS -->
        <section id="about-section" class="py-24 max-w-5xl mx-auto px-6 space-y-16 scroll-mt-20">
            <div class="text-center space-y-3">
                <h2 class="text-xs font-black uppercase tracking-widest text-orange-500">Nasza Filozofia</h2>
                <p class="text-2xl sm:text-3xl font-black uppercase tracking-wider text-slate-100">Dlaczego Savona smakuje inaczej?</p>
                <div class="h-1 w-12 bg-orange-600 mx-auto rounded-full mt-2"></div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
                <div class="space-y-6 text-sm text-slate-400 leading-relaxed font-medium">
                    <h3 class="text-lg font-black text-slate-200 uppercase tracking-wide">Wszystko zaczyna się od cierpliwości...</h3>
                    <p>
                        Nie idziemy na skróty. W pizzerii Savona sercem lokalu jest tradycyjny piec szamotowy rozgrzewany do temperatury bliskiej 450 stopni Celsjusza. Nasze ciasto wyrabiane jest z włoskiej mąki <span class="text-orange-400 font-bold">Caputo Tipo 00</span>, czystej wody i minimalnej ilości drożdży.
                    </p>
                    <p>
                        Zamiast przyspieszać procesy chemiczne, dajemy ciastu odpocząć przez pełne dwie doby. To właśnie powolna fermentacja sprawia, że placki są niezwykle lekkie, puszyste wewnątrz, a brzegi zdobią charakterystyczne dla pizzy neapolitańskiej tygrysie cętki przypieczenia.
                    </p>
                </div>
                <!-- Karty cech -->
                <div class="grid grid-cols-1 gap-4">
                    <div class="bg-slate-900 border border-slate-850 p-5 rounded-2xl flex items-start space-x-4">
                        <span class="text-2xl bg-orange-950/60 p-2.5 border border-orange-900/30 rounded-xl text-orange-400">🌾</span>
                        <div>
                            <h4 class="font-bold text-xs uppercase text-slate-200 tracking-wide">Importowane Składniki</h4>
                            <p class="text-xs text-slate-400 mt-1">Oryginalna Mozzarella di Bufala Campana, Prosciutto di Parma oraz oliwy Extra Vergine.</p>
                        </div>
                    </div>
                    <div class="bg-slate-900 border border-slate-850 p-5 rounded-2xl flex items-start space-x-4">
                        <span class="text-2xl bg-orange-950/60 p-2.5 border border-orange-900/30 rounded-xl text-orange-400">🪵</span>
                        <div>
                            <h4 class="font-bold text-xs uppercase text-slate-200 tracking-wide">Prawdziwy Szamot</h4>
                            <p class="text-xs text-slate-400 mt-1">Wypiek trwa zaledwie 60-90 sekund w potężnym żarze, co blokuje wilgoć wewnątrz składników.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 4. PODSTRONA: KARTA DAŃ I SKLEP -->
        <section id="menu-section" class="max-w-6xl mx-auto p-6 grid grid-cols-1 lg:grid-cols-3 gap-8 scroll-mt-20 border-t border-slate-900 pt-20">
            
            <!-- LEWA STRONA: LISTA POTRAW -->
            <div class="lg:col-span-2 space-y-6">
                <div class="border-b border-slate-900 pb-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <h2 class="text-xl font-black tracking-wider text-orange-400 uppercase">Karta Dań i Sklep</h2>
                        <p class="text-[11px] text-slate-500 font-medium">Kliknij wybrany wariant, by dostosować dodatki lub wykluczyć składniki.</p>
                    </div>
                    
                    <!-- Filtry kategorii -->
                    <div class="flex flex-wrap gap-1.5">
                        <button 
                            v-for="cat in uniqueCategories" 
                            :key="cat"
                            @click="filterProducts(cat)"
                            :class="activeCategoryFilter === cat ? 'bg-orange-600 text-white font-bold border-orange-500' : 'bg-slate-900 text-slate-400 border-slate-800'"
                            class="px-3 py-1.5 rounded-lg text-[10px] uppercase font-black tracking-wider border transition-all"
                        >
                            {{ cat }}
                        </button>
                    </div>
                </div>
                
                <!-- Siatka produktów z opisami i zdjęciami -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div v-for="product in filteredProducts" :key="product.id" class="bg-slate-900 border border-slate-850/60 rounded-2xl overflow-hidden flex flex-col justify-between hover:border-slate-800 transition-all shadow-md group">
                        
                        <!-- Miniaturka z bazy danych -->
                        <div class="h-44 w-full bg-slate-950 relative overflow-hidden shrink-0 border-b border-slate-850">
                            <img 
                                v-if="product.image_path" 
                                :src="'/storage/' + product.image_path" 
                                :alt="product.name" 
                                class="h-full w-full object-cover object-center group-hover:scale-105 transition-transform duration-500"
                            />
                            <div v-else class="h-full w-full flex flex-col items-center justify-center text-slate-700 bg-gradient-to-b from-slate-900 to-slate-950">
                                <span class="text-4xl">🍕</span>
                                <span class="text-[9px] uppercase font-bold text-slate-600 mt-2">Wypiek Rzemieślniczy</span>
                            </div>
                            <span class="absolute top-3 right-3 bg-slate-950/80 backdrop-blur-md px-2 py-0.5 border border-slate-800 rounded-md uppercase text-[9px] font-black tracking-wider text-orange-400 z-20">
                                {{ product.category }}
                            </span>
                        </div>

                        <!-- Opis i Teksty -->
                        <div class="p-5 flex-1 flex flex-col justify-between space-y-4">
                            <div>
                                <h3 class="font-black text-slate-100 text-base uppercase tracking-wide">{{ product.name }}</h3>
                                <p class="text-xs text-slate-400 mt-1 leading-relaxed min-h-[36px]">
                                    {{ product.description || 'Kompozycja autorskich, świeżych składników dobrana według tradycyjnej, włoskiej sztuki kulinarnej.' }}
                                </p>
                            </div>

                            <!-- Przyciski rozmiarów z cenami -->
                            <div class="space-y-1.5 pt-2 border-t border-slate-950">
                                <button 
                                    v-for="variant in product.variants" 
                                    :key="variant.id"
                                    @click="openModifierModal(product, variant)"
                                    class="w-full bg-slate-950 hover:bg-orange-600 border border-slate-850 hover:border-orange-500 text-xs py-2 px-3 rounded-xl transition-all flex justify-between items-center group/btn"
                                >
                                    <span class="text-slate-400 group-hover/btn:text-white font-medium">{{ variant.size_name }}</span>
                                    <span class="font-mono font-bold text-orange-400 group-hover/btn:text-white">{{ variant.price }} zł</span>
                                </button>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- PRAWA STRONA: MODUŁ KOSZYKA ZAKUPOWEGO -->
            <div class="bg-slate-900 border border-slate-850 rounded-2xl p-6 h-max sticky top-24 shadow-xl flex flex-col">
                <h2 class="text-sm font-black text-slate-400 uppercase tracking-widest mb-4 border-b border-slate-800 pb-2">Twoje Zamówienie</h2>
                
                <!-- LISTA ELEMENTÓW W KOSZYKU -->
                <div class="space-y-3 mb-4 max-h-[35vh] overflow-y-auto pr-1">
                    <div v-for="(item, idx) in cart" :key="idx" class="bg-slate-950 p-3 rounded-xl border border-slate-850 text-xs flex flex-col space-y-1.5">
                        <div class="flex justify-between items-start">
                            <div>
                                <div class="font-bold text-slate-200 uppercase tracking-wide">{{ item.name }}</div>
                                <div class="text-slate-500 font-medium">{{ item.size }} — {{ item.quantity }} szt.</div>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="font-mono font-bold text-emerald-400">{{ (item.price * item.quantity).toFixed(2) }} zł</span>
                                <button @click="removeFromCart(idx)" class="text-slate-600 hover:text-red-400 font-bold transition-colors">✕</button>
                            </div>
                        </div>

                        <!-- Modyfikatory w koszyku -->
                        <div v-if="item.modifiers.length > 0" class="flex flex-wrap gap-1 mt-1 pt-1.5 border-t border-slate-900">
                            <span 
                                v-for="mod in item.modifiers" 
                                :key="mod.ingredient_id"
                                :class="mod.action === 'ADD' ? 'bg-emerald-950/60 text-emerald-400 border-emerald-900' : 'bg-red-950/60 text-red-400 border-red-900'"
                                class="text-[9px] font-black px-1.5 py-0.5 rounded border uppercase"
                            >
                                {{ mod.action === 'ADD' ? 'Ekstra' : 'Bez' }} {{ mod.name }}
                            </span>
                        </div>
                    </div>
                    
                    <div v-if="cart.length === 0" class="text-center py-10 text-xs text-slate-600 italic">
                        Twój koszyk jest pusty. Wybierz pozycję z karty dań.
                    </div>
                </div>

                <!-- FORMULARZ FINALIZACJI -->
                <div v-if="cart.length > 0" class="space-y-5 pt-4 border-t border-slate-800">

                    <!-- 🛵 WYBÓR TYPU REALIZACJI -->
                    <div class="space-y-2">
                        <label class="block text-[11px] font-black text-slate-400 uppercase tracking-wider pl-1">
                            🛵 Sposób realizacji zamówienia
                        </label>
                        
                        <div class="flex flex-col space-y-2">
                            <!-- Opcja: Dostawa kurierem -->
                            <label 
                                :class="form.type === 'dostawa' ? 'border-orange-500 bg-orange-950/20 text-white shadow-lg' : 'border-slate-850 bg-slate-950 text-slate-400 hover:bg-slate-900/60'"
                                class="flex items-center justify-between p-3.5 border rounded-xl cursor-pointer transition-all select-none group"
                            >
                                <div class="flex items-center space-x-3">
                                    <span class="text-lg bg-slate-900 p-1.5 rounded-lg border border-slate-800">🚗</span>
                                    <div>
                                        <span class="block text-xs font-black tracking-wide uppercase">Dostawa kurierem</span>
                                        <span class="block text-[10px] text-slate-500 font-medium mt-0.5">Gorący wypiek pod Twoje drzwi</span>
                                    </div>
                                </div>
                                <input type="radio" v-model="form.type" value="dostawa" class="hidden" />
                                <div :class="form.type === 'dostawa' ? 'bg-orange-500 scale-100' : 'bg-transparent border border-slate-700 scale-75'" class="h-2.5 w-2.5 rounded-full transition-all duration-300"></div>
                            </label>

                            <!-- Opcja: Odbiór osobisty -->
                            <label 
                                :class="form.type === 'wynos' ? 'border-orange-500 bg-orange-950/20 text-white shadow-lg' : 'border-slate-850 bg-slate-950 text-slate-400 hover:bg-slate-900/60'"
                                class="flex items-center justify-between p-3.5 border rounded-xl cursor-pointer transition-all select-none group"
                            >
                                <div class="flex items-center space-x-3">
                                    <span class="text-lg bg-slate-900 p-1.5 rounded-lg border border-slate-800">🏬</span>
                                    <div>
                                        <span class="block text-xs font-black tracking-wide uppercase">Odbiór w pizzerii</span>
                                        <span class="block text-[10px] text-slate-500 font-medium mt-0.5">Osobisty odbiór w lokalu</span>
                                    </div>
                                </div>
                                <input type="radio" v-model="form.type" value="wynos" class="hidden" />
                                <div :class="form.type === 'wynos' ? 'bg-orange-500 scale-100' : 'bg-transparent border border-slate-700 scale-75'" class="h-2.5 w-2.5 rounded-full transition-all duration-300"></div>
                            </label>
                        </div>
                    </div>

                    <!-- 📍 ADRES DOSTAWY (Tylko dla dostawy) -->
                    <div v-if="form.type === 'dostawa'" class="p-3.5 bg-slate-950 border border-slate-850 rounded-xl space-y-1.5">
                        <label class="block text-[10px] uppercase font-bold text-slate-400 tracking-wider pl-0.5">
                            Adres dostawy kurierskiej
                        </label>
                        <input 
                            v-model="form.delivery_address" 
                            type="text" 
                            placeholder="np. ul. Lipowa 10 m. 5, Białystok" 
                            class="w-full bg-slate-900 border border-slate-800 focus:border-orange-500 rounded-xl p-3 text-xs text-white placeholder:text-slate-600 font-medium"
                            :required="form.type === 'dostawa'"
                        />
                    </div>

                    <!-- ℹ️ INFORMACJA O MINIMALNYM ZAMÓWIENIU -->
                    <div v-if="form.type === 'dostawa' && !minOrderWarning" class="text-[10px] text-slate-400 bg-slate-950 p-3 rounded-xl border border-slate-850 flex items-center space-x-2">
                        <span>ℹ️ Minimalna kwota zamówienia w dostawie: <strong class="text-orange-400 font-mono">{{ minOrderAmount.toFixed(2) }} zł</strong></span>
                    </div>

                    <!-- ⚠️ OSTRZEŻENIE O BRAKU KWOTY MINIMALNEJ -->
                    <p v-if="minOrderWarning" class="text-[10px] text-red-400 bg-red-950/40 p-3 rounded-xl border border-red-900/50 font-bold leading-relaxed">
                        ⚠️ {{ minOrderWarning }}
                    </p>

                    <!-- 💳 SEKCJA WYBORU PŁATNOŚCI -->
                    <div class="space-y-2">
                        <label class="block text-[11px] font-black text-slate-400 uppercase tracking-wider mb-2 pl-1">
                            💳 Wybierz metodę płatności
                        </label>
                        
                        <div class="flex flex-col space-y-2">
                            <!-- Opcja 1: BLIK -->
                            <label 
                                :class="form.payment_method === 'blik' ? 'border-orange-500 bg-orange-950/20 text-white shadow-lg' : 'border-slate-850 bg-slate-950 text-slate-400 hover:bg-slate-900/60'"
                                class="flex items-center justify-between p-3.5 border rounded-xl cursor-pointer transition-all select-none group"
                            >
                                <div class="flex items-center space-x-3">
                                    <span class="text-lg bg-slate-900 p-1.5 rounded-lg border border-slate-800">📱</span>
                                    <span class="text-xs font-black tracking-wide uppercase">BLIK (Błyskawiczny kod)</span>
                                </div>
                                <input type="radio" v-model="form.payment_method" value="blik" class="hidden" />
                                <div :class="form.payment_method === 'blik' ? 'bg-orange-500 scale-100' : 'bg-transparent border border-slate-700 scale-75'" class="h-2.5 w-2.5 rounded-full transition-all duration-300"></div>
                            </label>

                            <!-- Opcja 2: PayU -->
                            <label 
                                :class="form.payment_method === 'payu' ? 'border-emerald-500 bg-emerald-950/20 text-white shadow-lg' : 'border-slate-850 bg-slate-950 text-slate-400 hover:bg-slate-900/60'"
                                class="flex items-center justify-between p-3.5 border rounded-xl cursor-pointer transition-all select-none group"
                            >
                                <div class="flex items-center space-x-3">
                                    <span class="text-lg bg-slate-900 p-1.5 rounded-lg border border-slate-800">⚡</span>
                                    <span class="text-xs font-black tracking-wide uppercase">PayU / Szybki Przelew</span>
                                </div>
                                <input type="radio" v-model="form.payment_method" value="payu" class="hidden" />
                                <div :class="form.payment_method === 'payu' ? 'bg-emerald-500 scale-100' : 'bg-transparent border border-slate-700 scale-75'" class="h-2.5 w-2.5 rounded-full transition-all duration-300"></div>
                            </label>

                            <!-- Opcja 3: Gotówka przy odbiorze -->
                            <label 
                                :class="form.payment_method === 'gotówka' ? 'border-blue-500 bg-blue-950/20 text-white shadow-lg' : 'border-slate-850 bg-slate-950 text-slate-400 hover:bg-slate-900/60'"
                                class="flex items-center justify-between p-3.5 border rounded-xl cursor-pointer transition-all select-none group"
                            >
                                <div class="flex items-center space-x-3">
                                    <span class="text-lg bg-slate-900 p-1.5 rounded-lg border border-slate-800">💵</span>
                                    <span class="text-xs font-black tracking-wide uppercase">Gotówka przy odbiorze</span>
                                </div>
                                <input type="radio" v-model="form.payment_method" value="gotówka" class="hidden" />
                                <div :class="form.payment_method === 'gotówka' ? 'bg-blue-500 scale-100' : 'bg-transparent border border-slate-700 scale-75'" class="h-2.5 w-2.5 rounded-full transition-all duration-300"></div>
                            </label>
                        </div>
                    </div>

                    <!-- 🎟️ WIDGET KODU RABATOWEGO -->
                    <div class="bg-slate-950 p-3.5 rounded-xl border border-slate-850 space-y-2">
                        <label class="block text-[10px] uppercase font-bold text-slate-400 tracking-wider">
                            🎟️ Masz kod rabatowy?
                        </label>

                        <!-- Formularz wpisywania kodu -->
                        <div v-if="!appliedDiscount" class="flex space-x-2">
                            <input 
                                v-model="discountCodeInput" 
                                type="text" 
                                placeholder="np. SAVONA10" 
                                class="w-2/3 bg-slate-900 border border-slate-800 focus:border-orange-500 rounded-xl px-3 py-2 text-xs text-white uppercase font-mono tracking-wider placeholder:text-slate-600"
                            />
                            <button 
                                type="button"
                                @click="applyDiscountCode"
                                :disabled="!discountCodeInput || isValidatingCode"
                                class="w-1/3 bg-slate-800 hover:bg-orange-600 disabled:opacity-50 text-white font-bold rounded-xl text-xs uppercase tracking-wider transition-all"
                            >
                                {{ isValidatingCode ? '...' : 'Użyj' }}
                            </button>
                        </div>

                        <!-- Błąd walidacji -->
                        <p v-if="discountError" class="text-[10px] text-red-400 font-bold leading-tight pt-1">
                            ⚠️ {{ discountError }}
                        </p>

                        <!-- Po aktywowaniu kodu -->
                        <div v-if="appliedDiscount" class="bg-emerald-950/60 border border-emerald-900 p-2.5 rounded-xl flex justify-between items-center text-xs">
                            <div>
                                <span class="font-mono font-black text-emerald-400 uppercase">{{ appliedDiscount.code }}</span>
                                <span class="text-[10px] text-emerald-500 block">Zniżka aktywna!</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="font-mono font-bold text-emerald-400">-{{ discountValue.toFixed(2) }} zł</span>
                                <button type="button" @click="removeDiscountCode" class="text-slate-500 hover:text-red-400 font-bold">✕</button>
                            </div>
                        </div>
                    </div>

                    <!-- 💰 PODSUMOWANIE FINANSOWE -->
                    <div class="bg-slate-950 p-3.5 rounded-xl border border-slate-850 space-y-1.5">
                        <div v-if="appliedDiscount" class="flex justify-between items-center text-xs text-slate-400">
                            <span>Suma częściowa:</span>
                            <span class="font-mono">{{ cartSubtotal.toFixed(2) }} zł</span>
                        </div>
                        <div v-if="appliedDiscount" class="flex justify-between items-center text-xs text-emerald-400 font-bold">
                            <span>Rabat:</span>
                            <span class="font-mono">-{{ discountValue.toFixed(2) }} zł</span>
                        </div>
                        <div class="flex justify-between items-center pt-1 border-t border-slate-900">
                            <span class="text-xs text-slate-300 uppercase font-bold tracking-wider">Razem do zapłaty:</span>
                            <span class="text-2xl font-black font-mono text-emerald-400">
                                {{ cartTotal.toFixed(2) }} zł
                            </span>
                        </div>
                    </div>

                    <!-- PRZYCISK ZAMÓWIENIA Z BLOKADĄ WARUNKOWĄ -->
                    <button 
                        @click="checkout"
                        :disabled="(form.type === 'dostawa' && (!form.delivery_address || minOrderWarning)) || form.processing"
                        class="w-full bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 disabled:from-slate-850 disabled:to-slate-850 disabled:text-slate-600 disabled:border disabled:border-slate-850 text-white font-black py-3.5 rounded-xl text-xs uppercase tracking-wider transition-all shadow-md active:scale-98"
                    >
                        {{ form.processing ? 'Przetwarzanie transakcji...' : '🚀 Wyślij zamówienie do kuchni' }}
                    </button>
                </div>
            </div>
        </section>

        <!-- 5. PODSTRONA: KONTAKT -->
        <section id="contact-section" class="bg-slate-900 border-t border-b border-slate-850 py-20 mt-20 scroll-mt-20">
            <div class="max-w-5xl mx-auto px-6 grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
                <div class="space-y-6">
                    <div class="space-y-2">
                        <h2 class="text-xs font-black uppercase tracking-widest text-orange-500">Zatrzymaj się u nas</h2>
                        <p class="text-2xl font-black uppercase tracking-wide text-slate-100">Odwiedź nas osobiście</p>
                    </div>
                    <p class="text-xs text-slate-400 leading-relaxed font-medium">
                        Zapraszamy do naszego lokalu, gdzie poczujesz niesamowity aromat świeżo siekanej bazylii i pieczonego ciasta. Znajdujemy się w samym centrum miasta, z dogodnym parkingiem dla gości.
                    </p>
                    <div class="space-y-3 text-xs">
                        <div class="flex items-center space-x-3 text-slate-300"><span class="text-base">📍</span> <span>ul. Legionowa 10, 15-001 Białystok</span></div>
                        <div class="flex items-center space-x-3 text-slate-300"><span class="text-base">📞</span> <span>+48 500 600 700 (rezerwacje stolików)</span></div>
                        <div class="flex items-center space-x-3 text-slate-300"><span class="text-base">✉️</span> <span>ciao@pizzeriasavona.pl</span></div>
                    </div>
                </div>

                <div class="bg-slate-950 border border-slate-850 p-6 rounded-2xl space-y-4 shadow-inner">
                    <h4 class="font-black text-xs uppercase text-slate-200 tracking-wider border-b border-slate-900 pb-2">🕒 Godziny Pracy Kuchni</h4>
                    <div class="space-y-2 text-xs">
                        <div class="flex justify-between border-b border-slate-900 pb-1 text-slate-400"><span>Poniedziałek - Czwartek</span> <span class="font-mono font-bold text-slate-300">12:00 - 22:00</span></div>
                        <div class="flex justify-between border-b border-slate-900 pb-1 text-slate-400"><span>Piątek - Sobota</span> <span class="font-mono font-bold text-orange-500">12:00 - 00:00</span></div>
                        <div class="flex justify-between text-slate-400"><span>Niedziela</span> <span class="font-mono font-bold text-slate-300">13:00 - 22:00</span></div>
                    </div>
                    <div class="bg-orange-950/40 border border-orange-900/40 p-3 rounded-xl text-[10px] text-orange-400 font-bold leading-relaxed">
                        ⚡ Dowozimy w promieniu lokalu! Gorąca pizza z pieca prosto pod Twoje drzwi.
                    </div>
                </div>
            </div>
        </section>

        <!-- FOOTER / STOPKA -->
        <footer class="py-8 text-center text-slate-600 text-[10px] uppercase font-bold tracking-widest bg-slate-950">
            &copy; 2026 Pizzeria Savona Ecosystem — Wszelkie prawa zastrzeżone.
        </footer>

        <!-- MODAL PERSONALIZACJI SKŁADNIKÓW -->
        <div v-if="isModifierModalOpen" class="fixed inset-0 bg-black/80 flex items-center justify-center p-4 backdrop-blur-sm z-50">
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 w-full max-w-md shadow-2xl flex flex-col max-h-[85vh]">
                <div class="border-b border-slate-850 pb-3 mb-4">
                    <h3 class="text-base font-black text-orange-400 uppercase tracking-wide">Komponujesz własną pizzę</h3>
                    <p class="text-xs text-slate-400 font-medium">{{ activeProduct?.name }} ({{ activeVariant?.size_name }})</p>
                </div>

                <div class="overflow-y-auto space-y-2 pr-1 flex-1">
                    <div v-for="ing in activeVariant.ingredients" :key="ing.id" class="bg-slate-950 p-2.5 rounded-xl border border-slate-850 flex justify-between items-center text-xs">
                        <span class="font-bold text-slate-300 uppercase tracking-wide text-[11px]">{{ ing.name }}</span>
                        <div class="flex space-x-2">
                            <button 
                                @click="toggleModifier(ing, 'REMOVE')"
                                :class="getModifierAction(ing.id) === 'REMOVE' ? 'bg-red-600 text-white border-red-500' : 'bg-slate-900 text-red-400 border-slate-800'"
                                class="px-3 py-1.5 text-[10px] font-black rounded-lg border uppercase transition-colors">
                                Bez
                            </button>
                            <button 
                                @click="toggleModifier(ing, 'ADD')"
                                :class="getModifierAction(ing.id) === 'ADD' ? 'bg-emerald-600 text-white border-emerald-500' : 'bg-slate-900 text-emerald-400 border-emerald-800'"
                                class="px-3 py-1.5 text-[10px] font-black rounded-lg border uppercase transition-colors">
                                + Dodaj Extra
                            </button>
                        </div>
                    </div>
                </div>

                <div class="flex space-x-3 pt-4 border-t border-slate-800 mt-4">
                    <button @click="isModifierModalOpen = false" class="w-1/3 bg-slate-800 py-3 rounded-xl text-xs font-bold uppercase tracking-wider text-slate-300">Anuluj</button>
                    <button @click="addCustomizedToCart" class="w-2/3 bg-gradient-to-r from-orange-600 to-red-600 hover:from-orange-500 hover:to-red-500 text-white font-black py-3 rounded-xl text-xs uppercase tracking-wider transition-all shadow-md">
                        Zatwierdź składniki
                    </button>
                </div>
            </div>
        </div>

    </div>
</template>