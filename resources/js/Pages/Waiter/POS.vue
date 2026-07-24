<script setup>
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';

const props = defineProps({
    products: { type: Array, default: () => [] },
    ingredients: { type: Array, default: () => [] } // Odbieramy składniki z magazynu
});

const cart = ref([]);
const orderType = ref('lokal');
const tableNumber = ref('');

// STANY OKNA MODALNEGO MODYFIKACJI
const isModifierModalOpen = ref(false);
const activeProduct = ref(null);
const activeVariant = ref(null);
const selectedModifiers = ref([]); // Przechowuje aktualnie kliknięte modyfikacje [{ingredient_id, name, action}]

// 🔥 NOWE STANY: OBSŁUGA OKNA MODALNEGO DLA BRAKUJĄCEGO SUROWCA W MAGAZYNIE
const isStockAlertOpen = ref(false);
const missingIngredientName = ref('');

// Uruchamiane po kliknięciu pozycji menu
const openModifierModal = (product, variant) => {
    activeProduct.value = product;
    activeVariant.value = variant;
    selectedModifiers.value = []; // Czyścimy poprzednie modyfikatory
    isModifierModalOpen.value = true;
};

// Przełączanie modyfikatora (EXTRA / BEZ / NEUTRALNY)
const toggleModifier = (ingredient, action) => {
    const existingIdx = selectedModifiers.value.findIndex(m => m.ingredient_id === ingredient.id);

    if (existingIdx > -1) {
        // Jeśli ten sam modyfikator jest kliknięty ponownie – usuwamy go (powrót do neutralnego)
        if (selectedModifiers.value[existingIdx].action === action) {
            selectedModifiers.value.splice(existingIdx, 1);
            return;
        }
        // Jeśli zmieniamy z EXTRA na BEZ (lub odwrotnie) – podmieniamy akcję
        selectedModifiers.value[existingIdx].action = action;
    } else {
        // Jeśli nie było – dodajemy nową regułę modyfikacji
        selectedModifiers.value.push({
            ingredient_id: ingredient.id,
            name: ingredient.name,
            action: action
        });
    }
};

// Sprawdzenie aktywnego stanu dla ostylowania przycisków w modalu
const getModifierAction = (ingredientId) => {
    const found = selectedModifiers.value.find(m => m.ingredient_id === ingredientId);
    return found ? found.action : null;
};

// Zatwierdzenie modyfikacji i dodanie gotowej pozycji do rachunku
const addCustomizedToCart = () => {
    cart.value.push({
        variantId: activeVariant.value.id,
        name: activeProduct.value.name,
        size: activeVariant.value.size_name,
        price: parseFloat(activeVariant.value.price),
        quantity: 1,
        // Klonujemy wybrane modyfikatory do tej konkretnej pozycji w koszyku
        modifiers: [...selectedModifiers.value]
    });

    isModifierModalOpen.value = false;
};

const removeFromCart = (index) => {
    cart.value.splice(index, 1);
};

const form = useForm({
    type: 'lokal',
    payment_method: 'gotówka',
    table_number: null,
    items: []
});

// GŁÓWNA WYSYŁKA ZAMÓWIENIA Z KASY POS
const submitOrder = () => {
    if (cart.value.length === 0) return;

    form.type = orderType.value;
    form.table_number = orderType.value === 'lokal' ? parseInt(tableNumber.value) : null;
    
    // MAPOWANIE: Przekazujemy do backendu strukturę z uwzględnieniem wyklikanych modyfikatorów
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
        // 🔥 NOWOŚĆ: Przechwytujemy błąd braku surowca odesłany z CreateOrderAction
        onError: (errors) => {
            if (errors.missing_ingredient) {
                missingIngredientName.value = errors.missing_ingredient;
                isStockAlertOpen.value = true;
            }
        }
    });
};

// 🔥 NOWA FUNKCJA: Kelner klika w popupie "TAK" (Wymuszenie przyjęcia zamówienia)
const confirmOrderWithMissingStock = () => {
    isStockAlertOpen.value = false;
    
    // Ponownie wysyłamy spakowane dane formularza, dokładając flagę ignore_stock
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

// 🔥 NOWA FUNKCJA: Kelner klika w popupie "NIE" (Wycofanie/Anulowanie)
const cancelOrderWithMissingStock = () => {
    isStockAlertOpen.value = false;
    form.clearErrors('missing_ingredient'); // Czyścimy stan błędu, by odblokować formularz
};
</script>

<template>
    <div class="min-h-screen bg-slate-950 text-white flex flex-col md:flex-row">
        
        <!-- LEWA STRONA: LISTA PRODUKTÓW W MENU -->
        <div class="flex-1 p-6 overflow-y-auto max-h-screen">
            <header class="mb-6 border-b border-slate-800 pb-4">
                <h1 class="text-xl font-black text-orange-400 uppercase tracking-wider">KASA KELNERSKA POS</h1>
            </header>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div v-for="product in products" :key="product.id" class="bg-slate-900 border border-slate-800 p-4 rounded-xl shadow-md">
                    <div class="font-bold text-slate-200 mb-2">{{ product.name }}</div>
                    <div class="space-y-2">
                        <!-- ZMIANA: Kliknięcie otwiera teraz konfigurator zamiast ślepego dodawania -->
                        <button 
                            v-for="variant in product.variants" 
                            :key="variant.id"
                            @click="openModifierModal(product, variant)"
                            class="w-full bg-slate-800 hover:bg-orange-600 text-left text-xs p-2 rounded-lg transition-colors flex justify-between items-center"
                        >
                            <span>{{ variant.size_name }}</span>
                            <span class="font-mono font-bold text-orange-400">{{ variant.price }} zł</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- PRAWA STRONA: RACHUNEK KELNERSKI -->
        <div class="w-full md:w-96 bg-slate-900 border-l border-slate-800 p-6 flex flex-col justify-between max-h-screen">
            <div>
                <h2 class="text-sm font-black text-slate-400 uppercase tracking-widest mb-4 border-b border-slate-800 pb-2">Aktualny Rachunek</h2>
                
                <div class="grid grid-cols-3 gap-2 mb-4">
                    <button @click="orderType = 'lokal'" :class="orderType === 'lokal' ? 'bg-orange-600' : 'bg-slate-800'" class="py-2 text-xs font-bold rounded-lg">Lokal</button>
                    <button @click="orderType = 'wynos'" :class="orderType === 'wynos' ? 'bg-orange-600' : 'bg-slate-800'" class="py-2 text-xs font-bold rounded-lg">Wynos</button>
                    <button @click="orderType = 'dostawa'" :class="orderType === 'dostawa' ? 'bg-orange-600' : 'bg-slate-800'" class="py-2 text-xs font-bold rounded-lg">Dostawa</button>
                </div>

                <div v-if="orderType === 'lokal'" class="mb-4">
                    <label class="block text-[10px] uppercase font-bold text-slate-500 mb-1">Numer Stolika</label>
                    <input v-model="tableNumber" type="number" class="w-full bg-slate-950 border border-slate-800 rounded-lg p-2 text-sm text-white font-mono" />
                </div>

                <!-- POZYCJE RACHUNKU -->
                <div class="space-y-2 overflow-y-auto max-h-[50vh] pr-1">
                    <div v-for="(item, idx) in cart" :key="idx" class="bg-slate-950 border border-slate-850 p-2.5 rounded-lg flex flex-col text-xs space-y-1.5">
                        <div class="flex justify-between items-center">
                            <div>
                                <div class="font-bold text-slate-200">{{ item.name }}</div>
                                <div class="text-slate-500">{{ item.size }} x {{ item.quantity }}</div>
                            </div>
                            <div class="flex items-center space-x-3">
                                <span class="font-mono font-bold text-emerald-400">{{ (item.price * item.quantity).toFixed(2) }} zł</span>
                                <button @click="removeFromCart(idx)" class="text-red-400 hover:text-red-500 font-bold">✕</button>
                            </div>
                        </div>
                        
                        <!-- WIZUALIZACJA MODYFIKATORÓW NA RACHUNKU -->
                        <div v-if="item.modifiers.length > 0" class="flex flex-wrap gap-1 pt-1 border-t border-slate-900">
                            <span 
                                v-for="mod in item.modifiers" 
                                :key="mod.ingredient_id"
                                :class="mod.action === 'ADD' ? 'bg-emerald-950 text-emerald-400 border-emerald-900' : 'bg-red-950 text-red-400 border-red-900'"
                                class="text-[9px] font-bold px-1.5 py-0.5 rounded border uppercase"
                            >
                                {{ mod.action === 'ADD' ? '+' : '-' }} {{ mod.name }}
                            </span>
                        </div>
                    </div>
                    <div v-if="cart.length === 0" class="text-center py-8 text-xs text-slate-600 italic">Rachunek jest pusty</div>
                </div>
            </div>

            <div class="border-t border-slate-800 pt-4 mt-4">
                <div class="flex justify-between items-center mb-4">
                    <span class="text-xs text-slate-400 uppercase font-bold">Suma:</span>
                    <span class="text-2xl font-black font-mono text-emerald-400">
                        {{ cart.reduce((sum, i) => sum + (i.price * i.quantity), 0).toFixed(2) }} zł
                    </span>
                </div>
                <button 
                    @click="submitOrder"
                    :disabled="cart.length === 0 || form.processing"
                    class="w-full bg-emerald-600 hover:bg-emerald-500 disabled:bg-slate-800 disabled:text-slate-600 text-white font-black py-3 rounded-xl text-xs uppercase tracking-wider shadow-lg"
                >
                    {{ form.processing ? 'Rejestracja...' : 'Zatwierdź i Wyślij na kuchnię' }}
                </button>
            </div>
        </div>

        <!-- MODAL KONFIGURATORA SKŁADNIKÓW PIZZY (DOTYKOWY MODIFIER PANEL) -->
        <div v-if="isModifierModalOpen" class="fixed inset-0 bg-black/80 flex items-center justify-center p-4 backdrop-blur-sm z-50">
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 w-full max-w-lg shadow-2xl flex flex-col max-h-[90vh]">
                
                <div class="border-b border-slate-800 pb-3 mb-4">
                    <h3 class="text-base font-black text-orange-400 uppercase">Personalizacja Pozycji</h3>
                    <p class="text-xs text-slate-400">{{ activeProduct?.name }} — {{ activeVariant?.size_name }}</p>
                </div>

                <!-- LISTA SUROWCÓW Z PRZYCISKAMI AKCJI -->
                <div class="overflow-y-auto space-y-2 pr-1 flex-1">
                    <div 
                        v-for="ing in ingredients" 
                        :key="ing.id" 
                        class="bg-slate-950 p-2.5 rounded-xl border border-slate-850 flex justify-between items-center text-sm"
                    >
                        <span class="font-bold text-slate-300">{{ ing.name }}</span>
                        
                        <!-- DWUFUNKCYJNE SELEKTORY MODYFIKACJI -->
                        <div class="flex space-x-2">
                            <!-- PRZYCISK: BEZ SKŁADNIKA -->
                            <button 
                                @click="toggleModifier(ing, 'REMOVE')"
                                :class="getModifierAction(ing.id) === 'REMOVE' ? 'bg-red-600 text-white border-red-500' : 'bg-slate-900 text-red-400 border-slate-800'"
                                class="px-3 py-1 text-xs font-black rounded-lg border uppercase transition-colors"
                            >
                                Bez
                            </button>
                            <!-- PRZYCISK: EXTRA SKŁADNIK -->
                            <button 
                                @click="toggleModifier(ing, 'ADD')"
                                :class="getModifierAction(ing.id) === 'ADD' ? 'bg-emerald-600 text-white border-emerald-500' : 'bg-slate-900 text-emerald-400 border-slate-800'"
                                class="px-3 py-1 text-xs font-black rounded-lg border uppercase transition-colors"
                            >
                                + Extra
                            </button>
                        </div>
                    </div>
                </div>

                <div class="flex space-x-3 pt-4 border-t border-slate-800 mt-4">
                    <button type="button" @click="isModifierModalOpen = false" class="w-1/3 bg-slate-800 py-3 rounded-xl text-xs font-bold uppercase">Anuluj</button>
                    <button type="button" @click="addCustomizedToCart" class="w-2/3 bg-orange-600 hover:bg-orange-500 text-white font-bold py-3 rounded-xl text-xs uppercase tracking-wider">
                        Dodaj do rachunku
                    </button>
                </div>

            </div>
        </div>

    </div>

    <!-- DYNAMICZNY POPUP BRAKU SUROWCA -->
<div v-if="isStockAlertOpen" class="fixed inset-0 bg-black/85 backdrop-blur-sm flex items-center justify-center p-4 z-50">
    <div class="bg-slate-900 border border-amber-900 rounded-2xl p-6 w-full max-w-sm shadow-2xl text-center">
        <div class="text-3xl mb-3">⚠️</div>
        <h3 class="text-sm font-black text-amber-400 uppercase tracking-wider mb-2">Krytyczny stan magazynu</h3>
        
        <p class="text-xs text-slate-300 mb-6 leading-relaxed">
            W magazynie brakuje surowca: <span class="text-white font-bold underline">{{ missingIngredientName }}</span>.<br>
            Czy chcesz mimo to <span class="text-emerald-400 font-bold">przyjąć zamówienie</span> i wyzerować ten surowiec?
        </p>

        <div class="flex space-x-3">
            <!-- Kelner wybiera NIE - wycofanie -->
            <button 
                type="button" 
                @click="cancelOrderWithMissingStock" 
                class="w-1/2 bg-slate-800 hover:bg-slate-750 text-slate-300 font-bold py-2.5 rounded-xl text-xs uppercase tracking-wider transition-colors">
                ❌ Nie, wycofaj
            </button>
            
            <!-- Kelner wybiera TAK - wymuszenie -->
            <button 
                type="button" 
                @click="confirmOrderWithMissingStock" 
                class="w-1/2 bg-amber-600 hover:bg-amber-500 text-white font-black py-2.5 rounded-xl text-xs uppercase tracking-wider transition-colors shadow-lg shadow-amber-950/50">
                ✔️ Tak, przyjmij
            </button>
        </div>
    </div>
</div>
</template>