<script setup>
import { ref, computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';

const props = defineProps({
    products: Array,
    ingredients: Array // Odbieramy składniki z backendu
});

const cart = ref([]);

// Stan do kontrolowania, który przedmiot w koszyku ma otwarte menu modyfikatorów
const activeModifierIndex = ref(null);

const form = useForm({
    type: 'lokal',
    payment_method: 'karta',
    delivery_address: '',
    items: []
});

const page = usePage();
const successMessage = computed(() => page.props.flash?.success);
const errorMessage = computed(() => page.props.errors?.error);

const addToCart = (product, variant) => {
    cart.value.push({
        product_variant_id: variant.id,
        product_name: product.name,
        size_name: variant.size_name,
        price: parseFloat(variant.price),
        quantity: 1,
        modifiers: [] // Tablica na modyfikatory: { ingredient_id, name, action }
    });
    // Automatycznie otwieramy modyfikatory dla nowo dodanej pozycji
    activeModifierIndex.value = cart.value.length - 1;
};

const removeFromCart = (index) => {
    if (activeModifierIndex.value === index) activeModifierIndex.value = null;
    cart.value.splice(index, 1);
};

// Funkcja dodająca/zmieniająca modyfikator dla danej pozycji w koszyku
const toggleModifier = (itemIndex, ingredient, action) => {
    const item = cart.value[itemIndex];
    
    // Szukamy, czy ten składnik ma już przypisaną akcję w tym produkcie
    const existingModIndex = item.modifiers.findIndex(m => m.ingredient_id === ingredient.id);

    if (existingModIndex !== -1) {
        // Jeśli kliknięto tę samą akcję, która już jest – usuwamy modyfikator (cofnięcie zmiany)
        if (item.modifiers[existingModIndex].action === action) {
            item.modifiers.splice(existingModIndex, 1);
        } else {
            // Jeśli akcja jest inna (np. zmiana z REMOVE na ADD), podmieniamy ją
            item.modifiers[existingModIndex].action = action;
        }
    } else {
        // Dodajemy nowy modyfikator
        item.modifiers.push({
            ingredient_id: ingredient.id,
            name: ingredient.name,
            action: action
        });
    }
};

// Pomocnicza funkcja do sprawdzania statusu przycisku modyfikatora
const hasModifier = (itemIndex, ingredientId, action) => {
    const item = cart.value[itemIndex];
    return item?.modifiers.some(m => m.ingredient_id === ingredientId && m.action === action);
};

const cartTotal = computed(() => {
    return cart.value.reduce((sum, item) => sum + (item.price * item.quantity), 0).toFixed(2);
});

const submitOrder = () => {
    form.items = cart.value.map(item => ({
        product_variant_id: item.product_variant_id,
        quantity: item.quantity,
        modifiers: item.modifiers.map(m => ({
            ingredient_id: m.ingredient_id,
            action: m.action
        }))
    }));

    form.post(route('order.store'), {
        onSuccess: () => {
            cart.value = [];
            activeModifierIndex.value = null;
        }
    });
};
</script>

<template>
    <div class="min-h-screen bg-gray-900 text-white p-6">
        <header class="mb-6 border-b border-gray-800 pb-4 flex justify-between items-center">
            <h1 class="text-2xl font-bold tracking-wider text-orange-500">🍕 Pizzeria CloudPOS</h1>
            <span class="bg-gray-800 px-3 py-1 rounded text-sm text-gray-400">Moduł: POS + Modyfikatory</span>
        </header>

        <div v-if="successMessage" class="mb-4 p-4 bg-green-600 text-white rounded-lg font-bold">
            {{ successMessage }}
        </div>
        <div v-if="errorMessage" class="mb-4 p-4 bg-red-600 text-white rounded-lg font-bold">
            ⚠️ {{ errorMessage }}
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <div class="lg:col-span-2 bg-gray-800 p-6 rounded-xl border border-gray-700">
                <h2 class="text-xl font-semibold mb-4 text-gray-300">Menu Restauracji</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div v-for="product in products" :key="product.id" class="p-4 rounded-lg border border-gray-700 bg-gray-850">
                        <div class="font-bold text-lg mb-2 text-orange-400">{{ product.name }}</div>
                        <div class="text-xs text-gray-400 mb-3 uppercase tracking-wider">{{ product.category }}</div>
                        
                        <div class="space-y-2">
                            <button 
                                v-for="variant in product.variants" 
                                :key="variant.id"
                                @click="addToCart(product, variant)"
                                class="w-full text-left bg-gray-700 hover:bg-orange-600 transition-colors px-3 py-2 rounded flex justify-between items-center text-sm"
                            >
                                <span>{{ variant.size_name }}</span>
                                <span class="font-bold text-orange-300">{{ variant.price }} zł</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-gray-800 p-6 rounded-xl border border-gray-700 flex flex-col justify-between">
                <div>
                    <h2 class="text-xl font-semibold mb-4 text-gray-300">Aktualny Koszyk</h2>

                    <div v-if="cart.length === 0" class="text-gray-500 text-center py-8">
                        Koszyk jest pusty.
                    </div>
                    
                    <div v-else class="space-y-3 max-h-[28rem] overflow-y-auto pr-2 mb-4">
                        <div v-for="(item, index) in cart" :key="index" class="bg-gray-900 rounded border border-gray-700 p-3">
                            <div class="flex justify-between items-center">
                                <div>
                                    <div class="font-bold text-sm">{{ item.product_name }}</div>
                                    <div class="text-xs text-gray-400">{{ item.size_name }}</div>
                                </div>
                                <div class="flex items-center space-x-3">
                                    <span class="font-bold text-sm text-orange-400">{{ item.price.toFixed(2) }} zł</span>
                                    <button @click="removeFromCart(index)" class="text-red-400 hover:text-red-600 text-sm">✕</button>
                                </div>
                            </div>

                            <div v-if="item.modifiers.length > 0" class="mt-2 flex flex-wrap gap-1">
                                <span 
                                    v-for="mod in item.modifiers" 
                                    :key="mod.ingredient_id"
                                    :class="mod.action === 'ADD' ? 'bg-green-900/60 text-green-300 border-green-700' : 'bg-red-900/60 text-red-300 border-red-700'"
                                    class="text-[10px] px-2 py-0.5 rounded border font-semibold uppercase"
                                >
                                    {{ mod.action === 'ADD' ? '+' : 'bez:' }} {{ mod.name }}
                                </span>
                            </div>

                            <div class="mt-2 pt-2 border-t border-gray-800 flex justify-between items-center">
                                <button 
                                    @click="activeModifierIndex = activeModifierIndex === index ? null : index"
                                    class="text-xs font-semibold text-orange-400 hover:text-orange-300 flex items-center"
                                >
                                    {{ activeModifierIndex === index ? '🔼 Ukryj modyfikatory' : '🔽 Składniki / Modyfikacje' }}
                                </button>
                            </div>

                            <div v-if="activeModifierIndex === index" class="mt-3 pt-3 border-t border-gray-800 space-y-2 bg-gray-950 p-2 rounded">
                                <div v-for="ing in ingredients" :key="ing.id" class="flex justify-between items-center text-xs">
                                    <span class="text-gray-300 font-medium">{{ ing.name }}</span>
                                    <div class="flex space-x-1">
                                        <button 
                                            @click="toggleModifier(index, ing, 'ADD')"
                                            :class="hasModifier(index, ing.id, 'ADD') ? 'bg-green-600 text-white font-bold' : 'bg-gray-800 text-gray-400 hover:bg-gray-700'"
                                            class="px-2 py-1 rounded text-[10px] transition-colors"
                                        >
                                            + EXTRA
                                        </button>
                                        <button 
                                            @click="toggleModifier(index, ing, 'REMOVE')"
                                            :class="hasModifier(index, ing.id, 'REMOVE') ? 'bg-red-600 text-white font-bold' : 'bg-gray-800 text-gray-400 hover:bg-gray-700'"
                                            class="px-2 py-1 rounded text-[10px] transition-colors"
                                        >
                                            BEZ
                                        </button>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="border-t border-gray-700 pt-4 space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-400 uppercase mb-2">Typ Zamówienia</label>
                            <select v-model="form.type" class="w-full bg-gray-700 border border-gray-650 rounded p-2 text-sm focus:bg-gray-800 focus:border-orange-500 text-white cursor-pointer font-medium">
                                <option value="lokal">Konsumpcja na miejscu</option>
                                <option value="wynos">Na wynos</option>
                                <option value="dostawa">Dostawa pod adres</option>
                            </select>
                        </div>

                        <div v-if="form.type === 'dostawa'">
                            <label class="block text-xs font-semibold text-gray-400 uppercase mb-2">Adres Dostawy (Białystok)</label>
                            <input v-model="form.delivery_address" type="text" placeholder="np. ul. Legionowa 10/2" class="w-full bg-gray-700 border border-gray-650 rounded p-2 text-sm focus:border-orange-500 text-white" />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-400 uppercase mb-2">Płatność</label>
                            <select v-model="form.payment_method" class="w-full bg-gray-700 border border-gray-650 rounded p-2 text-sm focus:bg-gray-800 focus:border-orange-500 text-white cursor-pointer font-medium">
                                <option value="karta">Karta płatnicza</option>
                                <option value="gotowka">Gotówka</option>
                                <option value="BLIK">BLIK</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="border-t border-gray-700 pt-4 mt-6">
                    <div class="flex justify-between items-center mb-4">
                        <span class="text-gray-400 font-semibold">Razem do zapłaty:</span>
                        <span class="text-2xl font-black text-orange-500">{{ cartTotal }} zł</span>
                    </div>

                    <button 
                        @click="submitOrder"
                        :disabled="cart.length === 0 || form.processing"
                        class="w-full bg-orange-600 hover:bg-orange-500 disabled:bg-gray-700 disabled:text-gray-500 font-bold py-3 px-4 rounded-lg transition-colors tracking-wide uppercase text-sm"
                    >
                        {{ form.processing ? 'Przetwarzanie...' : 'Zatwierdź i Wyślij (Zapisz w DB)' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>