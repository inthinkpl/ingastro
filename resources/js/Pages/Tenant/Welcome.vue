<script setup>
defineProps({
    tenantId: String,
    restaurantName: String,
});
</script>

<template>
    <div class="min-h-screen bg-slate-900 text-white flex items-center justify-center p-6">
        <div class="max-w-md w-full bg-slate-800 rounded-xl p-8 shadow-2xl border border-slate-700 text-center">
            <h1 class="text-3xl font-bold text-amber-500 mb-2">{{ restaurantName }}</h1>
            <p class="text-slate-400 mb-6">Identyfikator tenanta: <span class="font-mono text-emerald-400">{{ tenantId }}</span></p>
            
            <div class="space-y-3">
                <a href="/login" class="block w-full py-3 bg-amber-500 hover:bg-amber-600 font-semibold rounded-lg transition">
                    Zaloguj do systemu POS
                </a>
            </div>
        </div>
    </div>
</template>