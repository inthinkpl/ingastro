<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { Pencil, Search, Calendar, Filter } from 'lucide-vue-next';

const props = defineProps({
    shifts: Object,
    users: Array,
    filters: Object
});

const emit = defineEmits(['edit-shift']);

const filterForm = ref({
    user_id: props.filters?.user_id || 'all',
    date_from: props.filters?.date_from || '',
    date_to: props.filters?.date_to || ''
});

const applyFilters = () => {
    router.get(route('admin.rcp.index'), filterForm.value, {
        preserveState: true,
        preserveScroll: true
    });
};

const calculateTotalShiftHours = (clockIn, clockOut, breakMinutes = 0) => {
    if (!clockOut) return 'W trakcie';
    const start = new Date(clockIn).getTime();
    const end = new Date(clockOut).getTime();
    const minutes = Math.max(0, Math.floor((end - start) / 60000) - breakMinutes);
    return (minutes / 60).toFixed(2) + ' h';
};
</script>

<template>
    <div class="space-y-4">
        <!-- FILTRY TABELI -->
        <div class="bg-slate-900 border border-slate-800 p-4 rounded-3xl flex flex-wrap gap-3 items-center justify-between shadow-xl">
            <div class="flex flex-wrap gap-3 items-center">
                <select 
                    v-model="filterForm.user_id" 
                    @change="applyFilters" 
                    class="bg-[#0B0F19] border border-slate-800 text-xs text-white rounded-xl p-2.5 focus:border-amber-500"
                >
                    <option value="all">Wszyscy Pracownicy</option>
                    <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
                </select>

                <input 
                    v-model="filterForm.date_from" 
                    @change="applyFilters" 
                    type="date" 
                    class="bg-[#0B0F19] border border-slate-800 text-xs text-white rounded-xl p-2.5 focus:border-amber-500" 
                />

                <input 
                    v-model="filterForm.date_to" 
                    @change="applyFilters" 
                    type="date" 
                    class="bg-[#0B0F19] border border-slate-800 text-xs text-white rounded-xl p-2.5 focus:border-amber-500" 
                />
            </div>
        </div>

        <!-- TABELA HISTORII -->
        <div class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden shadow-2xl">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-[#0B0F19] text-slate-400 font-bold uppercase tracking-wider border-b border-slate-800">
                            <th class="p-4">Pracownik</th>
                            <th class="p-4">Rozpoczęcie</th>
                            <th class="p-4">Zakończenie</th>
                            <th class="p-4">Przerwa</th>
                            <th class="p-4">Łączny Czas</th>
                            <th class="p-4 text-right">Akcje</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 font-medium">
                        <tr v-for="s in shifts?.data" :key="s.id" class="hover:bg-slate-800/40 transition">
                            <td class="p-4 font-bold text-white">{{ s.user?.name }}</td>
                            <td class="p-4 font-mono text-slate-300">{{ new Date(s.clock_in).toLocaleString('pl-PL') }}</td>
                            <td class="p-4 font-mono text-slate-300">{{ s.clock_out ? new Date(s.clock_out).toLocaleString('pl-PL') : '---' }}</td>
                            <td class="p-4 font-mono text-slate-400">{{ s.break_minutes }} min</td>
                            <td class="p-4 font-mono font-bold text-amber-400">
                                {{ calculateTotalShiftHours(s.clock_in, s.clock_out, s.break_minutes) }}
                            </td>
                            <td class="p-4 text-right">
                                <button 
                                    @click="$emit('edit-shift', s)" 
                                    class="bg-slate-800 hover:bg-slate-700 text-slate-200 px-3 py-1.5 rounded-xl text-[10px] font-bold uppercase transition inline-flex items-center space-x-1 cursor-pointer border border-slate-700"
                                >
                                    <Pencil class="w-3 h-3 text-amber-500" />
                                    <span>Korekta</span>
                                </button>
                            </td>
                        </tr>
                        <tr v-if="!shifts?.data || shifts.data.length === 0">
                            <td colspan="6" class="p-8 text-center italic text-slate-500">Brak historii wpisów dla wybranych filtrów.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>