<script setup>
import { 
    Car, Store, User, Phone, MapPin, AlertTriangle, ArrowLeft, ArrowRight 
} from 'lucide-vue-next';

defineProps({
    form: { type: Object, required: true },
    minOrderWarning: { type: String, default: null }
});

defineEmits(['go-to-step1', 'go-to-step3']);
</script>

<template>
    <div class="space-y-4">
        <div class="grid grid-cols-2 gap-2">
            <button 
                @click="form.type = 'dostawa'" 
                :class="form.type === 'dostawa' ? 'bg-red-600 text-white border-red-500 shadow-lg shadow-red-600/20 font-black' : 'bg-[#0B0F19] text-slate-400 border-slate-800 hover:border-slate-700'"
                class="py-2.5 rounded-xl text-xs uppercase tracking-wider border transition flex items-center justify-center space-x-1.5 cursor-pointer"
            >
                <Car class="w-4 h-4" />
                <span>Dostawa</span>
            </button>
            <button 
                @click="form.type = 'wynos'" 
                :class="form.type === 'wynos' ? 'bg-red-600 text-white border-red-500 shadow-lg shadow-red-600/20 font-black' : 'bg-[#0B0F19] text-slate-400 border-slate-800 hover:border-slate-700'"
                class="py-2.5 rounded-xl text-xs uppercase tracking-wider border transition flex items-center justify-center space-x-1.5 cursor-pointer"
            >
                <Store class="w-4 h-4" />
                <span>Odbiór Osobisty</span>
            </button>
        </div>

        <div class="bg-[#0B0F19] p-4 rounded-2xl border border-amber-500/30 space-y-3.5 shadow-inner">
            <div class="text-[11px] font-bold text-amber-400 uppercase tracking-wider flex items-center space-x-1.5">
                <User class="w-4 h-4" />
                <span>Dane Zamawiającego</span>
            </div>

            <div>
                <label class="block text-[10px] font-bold text-slate-300 uppercase mb-1">
                    Numer Telefonu: <span class="text-red-400">*</span>
                </label>
                <div class="relative">
                    <Phone class="w-4 h-4 text-amber-500 absolute left-3 top-2.5" />
                    <input 
                        v-model="form.phone" 
                        type="tel" 
                        placeholder="np. 785 555 455" 
                        class="w-full bg-slate-900 border border-slate-700 focus:border-amber-500 focus:ring-1 focus:ring-amber-500 rounded-xl pl-9 pr-3 py-2 text-xs text-white font-mono font-bold"
                        required
                    />
                </div>
            </div>

            <div v-if="form.type === 'dostawa'">
                <label class="block text-[10px] font-bold text-slate-300 uppercase mb-1">
                    Adres Dostawy: <span class="text-red-400">*</span>
                </label>
                <div class="relative">
                    <MapPin class="w-4 h-4 text-amber-500 absolute left-3 top-2.5" />
                    <input 
                        v-model="form.delivery_address" 
                        type="text" 
                        placeholder="ul. Lipowa 10 m. 5, Suwałki" 
                        class="w-full bg-slate-900 border border-slate-700 focus:border-amber-500 focus:ring-1 focus:ring-amber-500 rounded-xl pl-9 pr-3 py-2 text-xs text-white"
                        :required="form.type === 'dostawa'"
                    />
                </div>
            </div>
        </div>

        <p v-if="minOrderWarning" class="text-[10px] text-red-400 bg-red-950/40 p-2.5 rounded-xl border border-red-900/50 font-bold leading-relaxed flex items-center space-x-1">
            <AlertTriangle class="w-3.5 h-3.5 text-red-400 shrink-0" />
            <span>{{ minOrderWarning }}</span>
        </p>

        <div class="flex space-x-2 pt-2">
            <button 
                @click="$emit('go-to-step1')"
                class="w-1/3 bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold py-3 rounded-xl text-xs uppercase cursor-pointer flex items-center justify-center space-x-1"
            >
                <ArrowLeft class="w-4 h-4" />
                <span>Wstecz</span>
            </button>

            <button 
                @click="$emit('go-to-step3')"
                :disabled="!form.phone || (form.type === 'dostawa' && (!form.delivery_address || minOrderWarning))"
                class="w-2/3 bg-red-600 hover:bg-red-700 disabled:bg-slate-800 disabled:text-slate-600 text-white font-bold py-3 rounded-xl text-xs uppercase cursor-pointer flex items-center justify-center space-x-1 shadow-lg"
            >
                <span>Przejdź do Płatności</span>
                <ArrowRight class="w-4 h-4" />
            </button>
        </div>
    </div>
</template>