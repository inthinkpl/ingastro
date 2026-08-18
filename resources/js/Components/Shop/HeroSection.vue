<script setup>
import { Link } from '@inertiajs/vue3';
import { Gift, Percent } from 'lucide-vue-next';

defineEmits(['scroll-to-menu']);
</script>

<template>
    <section id="hero-section" class="relative bg-slate-950 text-white overflow-hidden py-24 lg:py-36">
        <div class="absolute inset-0 opacity-20">
            <img src="https://images.unsplash.com/photo-1513104890138-7c749659a591?q=80&w=1920&auto=format&fit=crop" alt="Pizza Savona Białystok" class="w-full h-full object-cover">
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
                        <button @click="$emit('scroll-to-menu')" class="border-2 border-slate-700 hover:border-slate-500 text-white font-semibold px-8 py-4 rounded-xl transition text-center backdrop-blur-sm bg-slate-900/40 cursor-pointer">
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
</template>