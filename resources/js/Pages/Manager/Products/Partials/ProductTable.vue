<script setup>
import { Pizza, Layers, Pencil, Trash2 } from 'lucide-vue-next';

defineProps({
    products: Array
});

defineEmits(['open-recipe', 'open-edit', 'delete']);
</script>

<template>
    <div class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden shadow-2xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-[#0B0F19] border-b border-slate-800 text-slate-400 font-bold uppercase tracking-wider">
                        <th class="p-4 w-20">Zdjęcie</th>
                        <th class="p-4">Nazwa potrawy</th>
                        <th class="p-4">Kategoria</th>
                        <th class="p-4 hidden md:table-cell">Opis / Składniki</th>
                        <th class="p-4 text-center w-24">Status</th>
                        <th class="p-4 text-center w-52">Akcje</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    <tr v-for="product in products" :key="product.id" class="hover:bg-slate-800/40 transition">
                        <td class="p-4">
                            <div class="h-12 w-12 rounded-2xl overflow-hidden bg-[#0B0F19] border border-slate-800 flex items-center justify-center shrink-0">
                                <img 
                                    v-if="product.image_path" 
                                    :src="'/storage/' + product.image_path" 
                                    alt="Zdjęcie potrawy" 
                                    class="h-full w-full object-cover"
                                />
                                <Pizza v-else class="w-6 h-6 text-slate-600" />
                            </div>
                        </td>

                        <td class="p-4 font-bold text-white text-sm">
                            {{ product.name }}
                        </td>

                        <td class="p-4">
                            <span class="bg-[#0B0F19] px-2.5 py-1 border border-slate-800 text-slate-300 rounded-lg uppercase text-[10px] font-bold">
                                {{ product.category }}
                            </span>
                        </td>

                        <td class="p-4 text-slate-400 max-w-xs truncate hidden md:table-cell text-[11px]">
                            {{ product.description || 'Brak opisu potrawy.' }}
                        </td>

                        <td class="p-4 text-center">
                            <span 
                                :class="product.is_active ? 'bg-emerald-950/80 text-emerald-400 border-emerald-900' : 'bg-red-950/80 text-red-400 border-red-900'"
                                class="text-[9px] px-2.5 py-1 rounded-full border font-bold uppercase tracking-wider"
                            >
                                {{ product.is_active ? 'Aktywny' : 'Ukryty' }}
                            </span>
                        </td>

                        <td class="p-4 text-center">
                            <div class="flex justify-center items-center space-x-1.5">
                                <button 
                                    @click="$emit('open-recipe', product)"
                                    class="bg-[#0B0F19] hover:bg-emerald-950 text-emerald-400 border border-slate-800 hover:border-emerald-900 px-2.5 py-1.5 rounded-xl text-[10px] font-bold uppercase transition flex items-center space-x-1 cursor-pointer"
                                    title="Zarządzaj surowcami zużywanymi przez to danie"
                                >
                                    <Layers class="w-3.5 h-3.5" />
                                    <span>BOM</span>
                                </button>

                                <button 
                                    @click="$emit('open-edit', product)"
                                    class="bg-slate-800 hover:bg-orange-600 text-slate-200 hover:text-white border border-slate-700 px-2.5 py-1.5 rounded-xl text-[10px] font-bold uppercase transition flex items-center space-x-1 cursor-pointer"
                                >
                                    <Pencil class="w-3.5 h-3.5" />
                                    <span>Edytuj</span>
                                </button>

                                <button 
                                    @click="$emit('delete', product.id)"
                                    class="bg-[#0B0F19] hover:bg-red-950 text-red-400 border border-slate-800 hover:border-red-900 px-2.5 py-1.5 rounded-xl text-[10px] font-bold uppercase transition flex items-center space-x-1 cursor-pointer"
                                >
                                    <Trash2 class="w-3.5 h-3.5" />
                                    <span>Usuń</span>
                                </button>
                            </div>
                        </td>
                    </tr>

                    <tr v-if="products.length === 0">
                        <td colspan="6" class="p-10 text-center italic text-slate-500">
                            Brak pozycji w menu lokalu. Dodaj pierwszą potrawę, aby rozpocząć!
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>