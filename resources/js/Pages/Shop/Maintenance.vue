<script setup>
import { Wrench, Clock, Phone, MapPin } from 'lucide-vue-next';

defineProps({
    message: {
        type: String,
        default: 'Nasz sklep internetowy jest obecnie niedostępny. Przepraszamy za utrudnienia i zapraszamy do składania zamówień stacjonarnie oraz telefonicznie!'
    }
});
</script>

<template>
    <div class="min-h-screen bg-[#0B0F19] text-white flex items-center justify-center p-4 font-sans">
        <div class="max-w-md w-full bg-slate-900 border border-slate-800 rounded-3xl p-8 text-center space-y-6 shadow-2xl">
            
            <!-- IKONA -->
            <div class="w-16 h-16 bg-amber-500/10 border border-amber-500/20 text-amber-500 rounded-2xl flex items-center justify-center mx-auto shadow-inner">
                <Wrench class="w-8 h-8 animate-pulse" />
            </div>

            <!-- NAGŁÓWEK -->
            <div class="space-y-1">
                <h1 class="text-xl font-black uppercase tracking-wider text-amber-400">Przerwa Techniczna</h1>
                <p class="text-xs text-slate-400 font-medium">Sklep internetowy chwilowo wyłączony</p>
            </div>
            
            <!-- KOMUNIKAT -->
            <p class="text-xs text-slate-300 leading-relaxed bg-[#0B0F19] p-4 rounded-2xl border border-slate-800">
                {{ message }}
            </p>

            <!-- INFOLINIA / ZAMÓWIENIA TELEFONICZNE -->
            <div class="pt-4 border-t border-slate-800 space-y-2 text-xs text-slate-400">
                <div class="flex items-center justify-center space-x-2 text-amber-400 font-bold">
                    <Clock class="w-4 h-4" />
                    <span>Zamówienia realizujemy telefonicznie!</span>
                </div>
            </div>

        </div>
    </div>
</template>