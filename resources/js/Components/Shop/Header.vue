<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { Phone, ShoppingBag } from 'lucide-vue-next';

defineProps({
    cartCount: { type: Number, default: 0 }
});

defineEmits(['open-cart']);

const page = usePage();

// Sprawdzamy aktualny URL, aby określić, która zakładka jest aktywna
const isHomeActive = computed(() => page.url === '/' || page.url.startsWith('/#'));
const isMenuActiv = computed(() => page.url.startsWith('/menu'));
</script>

<template>
    <header class="sticky top-0 z-50 bg-[#0B0F19]/90 backdrop-blur-md border-b border-slate-900 shadow-xl">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
            
            <!-- LOGO -->
            <Link :href="route('shop.index')" class="flex items-center space-x-2 cursor-pointer">
                <span class="text-2xl font-black text-red-500 tracking-wider">SAVONA</span>
                <span class="text-[10px] bg-amber-500 text-slate-950 px-2 py-0.5 rounded-full font-bold uppercase tracking-wide">pizza</span>
            </Link>

            <!-- NAWIGACJA Z DYNAMICZNYM PODŚWIETLENIEM -->
            <nav class="hidden md:flex space-x-8 text-sm font-semibold">
                <Link 
                    :href="route('shop.index')" 
                    :class="isHomeActive ? 'text-amber-400 font-bold' : 'text-slate-300 hover:text-white'"
                    class="transition"
                >
                    Strona Główna
                </Link>

                <Link 
                    :href="route('shop.menu')" 
                    :class="isMenuActiv ? 'text-amber-400 font-bold' : 'text-slate-300 hover:text-white'"
                    class="transition"
                >
                    Karta Dań
                </Link>

                <a href="/#kontakt" class="text-slate-300 hover:text-white transition">
                    Kontakt
                </a>
            </nav>

            <!-- AKCJE -->
            <div class="flex items-center space-x-3">
                <a href="tel:+48785555455" class="border border-slate-800 hover:border-slate-700 text-slate-300 px-3.5 py-2.5 rounded-full font-semibold transition inline-flex items-center space-x-2 text-xs sm:text-sm bg-slate-900/50">
                    <Phone class="w-4 h-4 text-amber-500" />
                    <span class="hidden sm:inline">785 555 455</span>
                </a>
                
                <button @click="$emit('open-cart')" class="bg-red-600 hover:bg-red-700 text-white px-4 sm:px-5 py-2.5 rounded-full font-bold transition inline-flex items-center space-x-2 shadow-lg shadow-red-600/20 text-xs sm:text-sm cursor-pointer">
                    <ShoppingBag class="w-4 h-4" />
                    <span>Koszyk</span>
                    <span v-if="cartCount > 0" class="bg-amber-500 text-slate-950 text-[11px] px-2 py-0.5 rounded-full font-black ml-1">
                        {{ cartCount }}
                    </span>
                </button>
            </div>

        </div>
    </header>
</template>