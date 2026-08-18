<script setup>
import { ref, computed } from 'vue';
import { Pizza, X, CheckCircle2 } from 'lucide-vue-next';

const props = defineProps({
    isOpen: { type: Boolean, default: false },
    products: { type: Array, default: () => [] }
});

const emit = defineEmits(['close', 'add-to-cart']);

const pizzaProducts = computed(() => props.products.filter(p => p.category?.toLowerCase() === 'pizza'));

const availableSizes = computed(() => {
    const sizeMap = new Map();
    pizzaProducts.value.forEach(p => {
        p.variants?.forEach(v => {
            if (!sizeMap.has(v.size_name)) {
                sizeMap.set(v.size_name, v.size_name);
            }
        });
    });
    return Array.from(sizeMap.keys());
});

const selectedSize = ref('');
const selectedLeft = ref(pizzaProducts.value[0] || null);
const selectedRight = ref(pizzaProducts.value[1] || pizzaProducts.value[0] || null);

if (availableSizes.value.length > 0) {
    selectedSize.value = availableSizes.value[0];
}

const calculatedPrice = computed(() => {
    if (!selectedLeft.value || !selectedRight.value || !selectedSize.value) return 0;
    const leftVar = selectedLeft.value.variants?.find(v => v.size_name === selectedSize.value);
    const rightVar = selectedRight.value.variants?.find(v => v.size_name === selectedSize.value);
    return Math.max(leftVar ? parseFloat(leftVar.price) : 0, rightVar ? parseFloat(rightVar.price) : 0);
});

const handleAddToCart = () => {
    if (!selectedLeft.value || !selectedRight.value || !selectedSize.value) return;

    const leftVar = selectedLeft.value.variants?.find(v => v.size_name === selectedSize.value);
    const rightVar = selectedRight.value.variants?.find(v => v.size_name === selectedSize.value);

    if (!leftVar || !rightVar) {
        alert('Jeden z wybranych smaków nie posiada tego rozmiaru.');
        return;
    }

    emit('add-to-cart', {
        variantId: leftVar.id,
        name: `Pizza ½ na ½ (${selectedLeft.value.name} + ${selectedRight.value.name})`,
        size: selectedSize.value,
        price: calculatedPrice.value,
        quantity: 1,
        modifiers: []
    });

    emit('close');
};
</script>

<template>
    <div v-if="isOpen" class="fixed inset-0 bg-black/80 flex items-center justify-center p-4 backdrop-blur-sm z-50">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 w-full max-w-lg shadow-2xl flex flex-col max-h-[90vh]">
            <div class="border-b border-slate-800 pb-3 mb-4 flex justify-between items-start">
                <div>
                    <h3 class="text-sm font-bold text-amber-400 uppercase tracking-wide flex items-center space-x-1.5">
                        <Pizza class="w-4 h-4" />
                        <span>Konfigurator Pizzy Pół na Pół</span>
                    </h3>
                    <p class="text-xs text-slate-400 mt-0.5">Wybierz rozmiar oraz dwa smaki, które chcesz połączyć.</p>
                </div>
                <button @click="$emit('close')" class="text-slate-500 hover:text-white transition cursor-pointer">
                    <X class="w-5 h-5" />
                </button>
            </div>

            <div class="overflow-y-auto space-y-5 pr-1 flex-1">
                <div class="space-y-2">
                    <label class="block text-xs font-bold text-slate-300 uppercase tracking-wide">1. Wybierz Rozmiar:</label>
                    <div class="grid grid-cols-2 gap-2">
                        <button 
                            v-for="size in availableSizes" 
                            :key="size"
                            @click="selectedSize = size"
                            :class="selectedSize === size ? 'bg-amber-500 text-slate-950 font-black border-amber-500' : 'bg-[#0B0F19] text-slate-300 border-slate-800 hover:border-slate-700'"
                            class="p-2.5 rounded-xl border text-xs uppercase font-bold transition cursor-pointer text-center"
                        >
                            {{ size }}
                        </button>
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="block text-xs font-bold text-slate-300 uppercase tracking-wide">2. Pierwsza Połówka (Lewa Strona):</label>
                    <select v-model="selectedLeft" class="w-full bg-[#0B0F19] border border-slate-800 focus:border-amber-500 rounded-xl p-2.5 text-xs text-white uppercase font-bold">
                        <option v-for="p in pizzaProducts" :key="p.id" :value="p">{{ p.name }}</option>
                    </select>
                </div>

                <div class="space-y-2">
                    <label class="block text-xs font-bold text-slate-300 uppercase tracking-wide">3. Druga Połówka (Prawa Strona):</label>
                    <select v-model="selectedRight" class="w-full bg-[#0B0F19] border border-slate-800 focus:border-amber-500 rounded-xl p-2.5 text-xs text-white uppercase font-bold">
                        <option v-for="p in pizzaProducts" :key="p.id" :value="p">{{ p.name }}</option>
                    </select>
                </div>

                <div class="bg-[#0B0F19] p-4 rounded-xl border border-slate-800 space-y-2">
                    <span class="text-[11px] text-slate-400 font-bold uppercase tracking-wider block">Podsumowanie Twojej Pizzy:</span>
                    <div class="text-xs text-white font-bold flex justify-between items-center">
                        <span>½ {{ selectedLeft?.name }} + ½ {{ selectedRight?.name }}</span>
                    </div>
                    <div class="text-xs text-slate-400 flex justify-between items-center pt-1 border-t border-slate-800">
                        <span>Rozmiar: <strong class="text-slate-200">{{ selectedSize }}</strong></span>
                        <span class="text-base font-black font-mono text-emerald-400">{{ calculatedPrice.toFixed(2) }} zł</span>
                    </div>
                </div>
            </div>

            <div class="flex space-x-3 pt-4 border-t border-slate-800 mt-4">
                <button @click="$emit('close')" class="w-1/3 bg-slate-800 hover:bg-slate-700 py-2.5 rounded-xl text-xs font-bold uppercase text-slate-300 transition cursor-pointer">Anuluj</button>
                <button @click="handleAddToCart" class="w-2/3 bg-amber-500 hover:bg-amber-600 text-slate-950 font-extrabold py-2.5 rounded-xl text-xs uppercase transition shadow-md cursor-pointer flex items-center justify-center space-x-1.5">
                    <CheckCircle2 class="w-4 h-4" />
                    <span>Dodaj Pół na Pół do koszyka</span>
                </button>
            </div>
        </div>
    </div>
</template>