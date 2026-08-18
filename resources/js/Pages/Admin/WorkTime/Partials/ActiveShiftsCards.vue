<script setup>
import { Clock, Pause, Square, Utensils, ShieldCheck, UserCheck, ShoppingBag, Truck } from 'lucide-vue-next';

defineProps({
    shifts: Array
});

const calculateLiveDuration = (clockIn) => {
    const start = new Date(clockIn).getTime();
    const now = new Date().getTime();
    const diff = Math.max(0, Math.floor((now - start) / 1000));
    
    const hours = Math.floor(diff / 3600);
    const minutes = Math.floor((diff % 3600) / 60);
    return `${hours}h ${minutes}m`;
};

const getRoleBadge = (role) => {
    switch (role) {
        case 'admin': return { label: 'Admin', class: 'text-red-400 bg-red-950/60 border-red-900' };
        case 'manager': return { label: 'Manager', class: 'text-purple-400 bg-purple-950/60 border-purple-900' };
        case 'chef': return { label: 'Kuchnia', class: 'text-blue-400 bg-blue-950/60 border-blue-900' };
        case 'waiter': return { label: 'Kelner', class: 'text-amber-400 bg-amber-950/60 border-amber-900' };
        case 'driver': return { label: 'Kierowca', class: 'text-emerald-400 bg-emerald-950/60 border-emerald-900' };
        default: return { label: role, class: 'text-slate-400 bg-slate-800 border-slate-700' };
    }
};
</script>

<template>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div 
            v-for="shift in shifts" 
            :key="shift.id"
            class="bg-slate-900 border border-slate-800 rounded-3xl p-5 space-y-4 shadow-xl relative overflow-hidden"
        >
            <!-- WSKAŹNIK STATUSU (PAUZA LUB PRACA) -->
            <div 
                :class="shift.status === 'on_break' ? 'bg-amber-500' : 'bg-emerald-500'" 
                class="absolute top-0 left-0 right-0 h-1"
            ></div>

            <div class="flex justify-between items-start">
                <div class="flex items-center space-x-3">
                    <div class="h-10 w-10 rounded-2xl bg-[#0B0F19] border border-slate-800 flex items-center justify-center font-bold text-white font-mono">
                        {{ shift.user?.name.charAt(0) }}
                    </div>
                    <div>
                        <h4 class="font-bold text-white text-sm">{{ shift.user?.name }}</h4>
                        <span :class="getRoleBadge(shift.user?.role).class" class="text-[9px] px-2 py-0.5 rounded-md border font-bold uppercase">
                            {{ getRoleBadge(shift.user?.role).label }}
                        </span>
                    </div>
                </div>

                <span 
                    :class="shift.status === 'on_break' ? 'bg-amber-500/10 text-amber-400 border-amber-500/30' : 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30'"
                    class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase border"
                >
                    {{ shift.status === 'on_break' ? 'Przerwa' : 'W pracy' }}
                </span>
            </div>

            <div class="bg-[#0B0F19] p-3 rounded-2xl border border-slate-800/80 flex justify-between items-center text-xs font-mono">
                <div>
                    <span class="text-[9px] text-slate-500 font-bold block uppercase">Rozpoczęcie:</span>
                    <span class="text-slate-300 font-bold">{{ new Date(shift.clock_in).toLocaleTimeString('pl-PL', { hour: '2-digit', minute: '2-digit' }) }}</span>
                </div>

                <div class="text-right">
                    <span class="text-[9px] text-slate-500 font-bold block uppercase">Przepracowano:</span>
                    <span class="text-amber-400 font-black text-sm">{{ calculateLiveDuration(shift.clock_in) }}</span>
                </div>
            </div>
        </div>

        <div v-if="!shifts || shifts.length === 0" class="col-span-full text-center py-12 text-slate-500 italic bg-slate-900 border border-slate-800 rounded-3xl">
            Brak pracowników obecnie na zmianie w lokalu.
        </div>
    </div>
</template>