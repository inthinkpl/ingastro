<script setup>
import { ref, onMounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import axios from 'axios';
import { Gift, Percent, Tag, Sparkles } from 'lucide-vue-next';

defineEmits(['scroll-to-menu']);

const promotions = ref([]);
const isLoading = ref(true);

onMounted(async () => {
    try {
        const response = await axios.get(route('promotions.public'));
        promotions.value = response.data || [];
    } catch (e) {
        promotions.value = [];
    } finally {
        isLoading.value = false;
    }
});
</script>

<template>
    <section id="hero-section" class="relative bg-slate-950 text-white overflow-hidden py-24 lg:py-36">
        <div class="absolute inset-0 opacity-20">
            <img src="https://images.unsplash.com/photo-1513104890138-7c749659a591?q=80&w=1920&auto=format&fit=crop" alt="Pizza Savona Białystok" class="w-full h-full object-cover">
        </div>
        <div class="absolute inset-0 bg-gradient-to-t from-[#0B0F19] via-transparent to-transparent"></div>
        
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 z-10">
            <div class="grid lg:grid-cols-12 gap-12 items-center">
                
                <!-- LEWA POŁOWA: TYTUŁ I PRZYCISKI -->
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
                        <button @click="$emit('scroll-to-menu')" class="border-2 border-slate-700 hover:border-slate-500 text-white font-semibold px-8 py-4 rounded-xl transition text-center backdrop-blur-sm bg-slate-900/40 cursor-pointer">
                            Zamów przez Internet
                        </button>
                    </div>
                </div>
                
                <!-- PRAWA POŁOWA: DYNAMICZNE PROMOCJE -->
                <div class="lg:col-span-5 space-y-4 max-w-md mx-auto lg:mx-0 w-full">
                    <div class="text-center lg:text-left mb-1">
                        <span class="inline-flex items-center space-x-1.5 bg-red-600/90 text-white text-xs px-3 py-1 rounded-full font-bold uppercase tracking-wider shadow-md shadow-red-600/10">
                            <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span>
                            <span>Aktualne Promocje</span>
                        </span>
                    </div>
                    
                    <!-- KARTY AKTUALNYCH PROMOCJI -->
                    <div v-if="!isLoading && promotions.length > 0" class="space-y-3">
                        <div 
                            v-for="promo in promotions" 
                            :key="promo.id" 
                            class="bg-slate-900/80 border-2 border-white/20 backdrop-blur-md p-5 rounded-2xl shadow-xl flex items-start space-x-4 hover:border-amber-500/50 transition duration-300"
                        >
                            <!-- IKONA W ZALEŻNOŚCI OD TYPU -->
                            <div class="p-3 rounded-xl border flex-shrink-0" :class="promo.type === 'free_product' ? 'bg-red-500/10 border-red-500/20 text-red-500' : 'bg-amber-500/10 border-amber-500/20 text-amber-500'">
                                <Gift v-if="promo.type === 'free_product'" class="w-6 h-6" />
                                <Percent v-else-if="promo.type === 'percent_discount'" class="w-6 h-6" />
                                <Tag v-else class="w-6 h-6" />
                            </div>

                            <div class="space-y-1">
                                <h3 class="text-amber-400 font-bold tracking-wide uppercase text-xs">
                                    {{ promo.name }}
                                </h3>

                                <p class="text-white font-medium text-sm leading-snug">
                                    Kup <span class="text-amber-400 font-bold">{{ promo.min_quantity || 1 }}x {{ promo.required_category || 'Danie' }}</span> 
                                    <template v-if="promo.required_size_name && promo.required_size_name !== 'Dowolny'"> (Rozmiar: {{ promo.required_size_name }})</template>
                                    <template v-if="promo.type === 'free_product' && promo.reward_variant">
                                        i odbierz <span class="text-emerald-400 font-bold">{{ promo.reward_variant.product?.name }} ({{ promo.reward_variant.size_name }}) GRATIS!</span>
                                    </template>
                                    <template v-else-if="promo.type === 'percent_discount'">
                                        , a zyskasz <span class="text-red-400 font-bold">{{ promo.value }}% RABATU</span> {{ promo.discount_target === 'cheapest_item' ? 'na najtańsze danie!' : 'na całe zamówienie!' }}
                                    </template>
                                    <template v-else>
                                        , a zyskasz <span class="text-emerald-400 font-bold">{{ promo.value }} zł RABATU!</span>
                                    </template>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- BRAK AKTYWNYCH PROMOCJI -->
                    <div v-else-if="!isLoading && promotions.length === 0" class="bg-slate-900/60 border border-slate-800 backdrop-blur-md p-6 rounded-2xl text-center space-y-2">
                        <Sparkles class="w-6 h-6 text-amber-400 mx-auto" />
                        <h4 class="text-sm font-bold text-white uppercase">Wszystkie Dania w Dobrej Cenie</h4>
                        <p class="text-xs text-slate-400">Sprawdź nasz program lojalnościowy i zbieraj punkty za każde zamówienie!</p>
                    </div>
                </div>
                
            </div>
        </div>
    </section>
</template>