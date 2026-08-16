<script setup>
import { ref } from 'vue';
import { ShoppingBag, Flame, Sparkles, Check } from 'lucide-vue-next';

const props = defineProps({
    product: { type: Object, required: true }
});

const emit = defineEmits(['add-to-cart']);
const selectedVariant = ref(props.product.variants?.[0] || null);
const added = ref(false);

const handleAdd = () => {
    emit('add-to-cart', {
        product: props.product,
        variant: selectedVariant.value
    });
    added.value = true;
    setTimeout(() => added.value = false, 1500);
};
</script>

<template>
    <div class="bg-slate-900 rounded-2xl overflow-hidden shadow-xl border border-slate-800 hover:border-slate-700 transition group flex flex-col justify-between">
        
        <div>
            <!-- ZDJĘCIE ORAZ BADGE -->
            <div class="h-52 overflow-hidden relative bg-slate-950">
                <img 
                    :src="product.image_url || 'https://images.unsplash.com/photo-1513104890138-7c749659a591?q=80&w=600&auto=format&fit=crop'" 
                    :alt="product.name" 
                    class="w-full h-full object-cover group-hover:scale-105 transition duration-500 opacity-90 group-hover:opacity-100"
                />
                
                <span v-if="product.badge === 'klasyka'" class="absolute top-3 right-3 bg-red-600 text-white text-[10px] px-3 py-1 rounded-full font-bold uppercase tracking-wider shadow-md flex items-center space-x-1">
                    <Flame class="w-3 h-3" />
                    <span>Klasyka</span>
                </span>
                <span v-else-if="product.badge === 'szef'" class="absolute top-3 right-3 bg-amber-500 text-slate-950 text-[10px] px-3 py-1 rounded-full font-bold uppercase tracking-wider shadow-md flex items-center space-x-1">
                    <Sparkles class="w-3 h-3" />
                    <span>Szef Kuchni</span>
                </span>
            </div>

            <!-- OPIS -->
            <div class="p-6 space-y-3">
                <div class="flex justify-between items-start gap-2">
                    <h4 class="text-lg font-bold text-white group-hover:text-amber-400 transition">{{ product.name }}</h4>
                    <span class="text-red-500 font-black text-base whitespace-nowrap">
                        {{ selectedVariant ? `${selectedVariant.price} zł` : `${product.price} zł` }}
                    </span>
                </div>

                <p class="text-xs text-slate-400 leading-relaxed line-clamp-3">
                    {{ product.description }}
                </p>
            </div>
        </div>

        <!-- PRZYCISK DODANIA -->
        <div class="p-6 pt-0 space-y-4">
            <div v-if="product.variants && product.variants.length > 0" class="grid grid-cols-2 gap-2">
                <button 
                    v-for="variant in product.variants" 
                    :key="variant.id"
                    @click="selectedVariant = variant"
                    :class="selectedVariant?.id === variant.id ? 'bg-amber-500/10 border-amber-500 text-amber-400' : 'bg-slate-950 border-slate-800 text-slate-400 hover:border-slate-700'"
                    class="py-1.5 px-3 rounded-xl border text-[11px] font-semibold transition text-center cursor-pointer"
                >
                    {{ variant.name }}
                </button>
            </div>

            <button 
                @click="handleAdd"
                :class="added ? 'bg-emerald-600 text-white border-emerald-500' : 'bg-slate-950 hover:bg-red-600 text-white border-slate-800 hover:border-red-500'"
                class="w-full py-3 rounded-xl font-bold text-xs uppercase tracking-wider border transition-all flex items-center justify-center space-x-2 shadow-md cursor-pointer"
            >
                <Check v-if="added" class="w-4 h-4 text-white" />
                <ShoppingBag v-else class="w-4 h-4 text-amber-400 group-hover:text-white transition" />
                <span>{{ added ? 'Dodano do zamówienia' : 'Dodaj do koszyka' }}</span>
            </button>
        </div>

    </div>
</template>