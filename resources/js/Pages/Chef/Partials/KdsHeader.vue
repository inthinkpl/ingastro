<script setup>
import { Link } from '@inertiajs/vue3';
import { ChefHat, Flame, LogOut } from 'lucide-vue-next';

defineProps({
    activeOrdersCount: Number,
    showLogout: {
        type: Boolean,
        default: false
    }
});
</script>

<template>
    <header class="mb-8 border-b border-slate-800 pb-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div class="flex items-center space-x-3">
            <div class="h-10 w-10 rounded-2xl bg-red-600/10 border border-red-500/20 flex items-center justify-center text-red-500">
                <ChefHat class="w-6 h-6" />
            </div>
            <div>
                <h1 class="text-xl font-black tracking-wider text-amber-500 uppercase">System KDS: Monitor Kuchenny</h1>
                <p class="text-xs text-slate-400">Podgląd i zmiana statusów zamówień w czasie rzeczywistym.</p>
            </div>
        </div>

        <div class="flex items-center space-x-3">
            <div class="flex items-center space-x-2 bg-slate-900 border border-slate-800 px-3.5 py-2 rounded-xl text-xs font-bold">
                <Flame class="w-4 h-4 text-amber-500" />
                <span class="text-slate-400">Aktywne zamówienia:</span>
                <span class="text-amber-400 font-mono font-bold text-sm">{{ activeOrdersCount }}</span>
            </div>

            <!-- Przycisk wylogowania widoczny gdy sidebar jest ukryty -->
            <Link 
                v-if="showLogout"
                :href="route('logout')" 
                method="post" 
                as="button" 
                class="bg-slate-900 hover:bg-red-950/50 text-red-400 border border-slate-800 hover:border-red-900 px-3.5 py-2 rounded-xl text-xs font-bold uppercase transition flex items-center space-x-1.5 cursor-pointer"
            >
                <LogOut class="w-4 h-4" />
                <span>Wyloguj</span>
            </Link>
        </div>
    </header>
</template>