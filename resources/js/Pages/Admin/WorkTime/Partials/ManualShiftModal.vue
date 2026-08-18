<script setup>
import { watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Clock, X, Save } from 'lucide-vue-next';

const props = defineProps({
    isOpen: Boolean,
    shift: Object,
    users: Array
});

const emit = defineEmits(['close']);

const form = useForm({
    user_id: '',
    clock_in: '',
    clock_out: '',
    break_minutes: 0,
    notes: ''
});

watch(() => props.isOpen, (newVal) => {
    if (newVal) {
        if (props.shift) {
            form.user_id = props.shift.user_id;
            form.clock_in = props.shift.clock_in ? props.shift.clock_in.replace(' ', 'T').substring(0, 16) : '';
            form.clock_out = props.shift.clock_out ? props.shift.clock_out.replace(' ', 'T').substring(0, 16) : '';
            form.break_minutes = props.shift.break_minutes || 0;
            form.notes = props.shift.notes || '';
        } else {
            form.reset();
            if (props.users?.length > 0) form.user_id = props.users[0].id;
        }
    }
});

const submitForm = () => {
    if (props.shift) {
        form.put(route('admin.rcp.update', props.shift.id), {
            preserveScroll: true,
            onSuccess: () => emit('close')
        });
    } else {
        form.post(route('admin.rcp.store'), {
            preserveScroll: true,
            onSuccess: () => emit('close')
        });
    }
};
</script>

<template>
    <div v-if="isOpen" class="fixed inset-0 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4 z-50">
        <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 w-full max-w-md shadow-2xl space-y-4">
            <div class="border-b border-slate-800 pb-3 flex justify-between items-center">
                <h3 class="text-xs font-bold text-amber-500 uppercase tracking-widest flex items-center space-x-2">
                    <Clock class="w-4 h-4" />
                    <span>{{ shift ? 'Korekta Zmiany RCP' : 'Ręczny Wpis Czasu Pracy' }}</span>
                </h3>
                <button @click="$emit('close')" class="text-slate-500 hover:text-white p-1 cursor-pointer">
                    <X class="w-5 h-5" />
                </button>
            </div>

            <form @submit.prevent="submitForm" class="space-y-4 text-xs">
                <div>
                    <label class="block font-bold text-slate-400 uppercase mb-1">Pracownik</label>
                    <select v-model="form.user_id" class="w-full bg-[#0B0F19] border border-slate-800 rounded-xl p-2.5 text-white" required>
                        <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-400 uppercase mb-1">Start (Clock In)</label>
                        <input v-model="form.clock_in" type="datetime-local" class="w-full bg-[#0B0F19] border border-slate-800 rounded-xl p-2 text-white font-mono" required />
                    </div>

                    <div>
                        <label class="block font-bold text-slate-400 uppercase mb-1">Koniec (Clock Out)</label>
                        <input v-model="form.clock_out" type="datetime-local" class="w-full bg-[#0B0F19] border border-slate-800 rounded-xl p-2 text-white font-mono" />
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-400 uppercase mb-1">Suma przerw (minuty)</label>
                    <input v-model.number="form.break_minutes" type="number" min="0" class="w-full bg-[#0B0F19] border border-slate-800 rounded-xl p-2.5 text-white font-mono" />
                </div>

                <div>
                    <label class="block font-bold text-slate-400 uppercase mb-1">Powód korekty / Uwagi</label>
                    <textarea v-model="form.notes" rows="2" placeholder="np. Zapomniał kliknąć Wyjście na kasie POS" class="w-full bg-[#0B0F19] border border-slate-800 rounded-xl p-2.5 text-white"></textarea>
                </div>

                <div class="flex space-x-3 pt-3 border-t border-slate-800">
                    <button type="button" @click="$emit('close')" class="w-1/2 bg-[#0B0F19] hover:bg-slate-800 border border-slate-800 text-slate-400 py-3 rounded-xl font-bold uppercase transition cursor-pointer">Anuluj</button>
                    <button type="submit" :disabled="form.processing" class="w-1/2 bg-gradient-to-r from-red-600 to-amber-500 hover:from-red-700 hover:to-amber-600 font-bold text-white py-3 rounded-xl uppercase tracking-wider transition shadow-lg flex items-center justify-center space-x-1 cursor-pointer">
                        <Save class="w-4 h-4" />
                        <span>Zapisz</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>