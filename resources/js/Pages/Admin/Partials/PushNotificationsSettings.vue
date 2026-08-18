<script setup>
import { useForm } from '@inertiajs/vue3';
import { Bell, Save, Tag } from 'lucide-vue-next';

const props = defineProps({
    notificationSettings: Array
});

const pushForm = useForm({
    settings: props.notificationSettings || []
});

const insertTag = (index, tag) => {
    if (pushForm.settings[index]) {
        pushForm.settings[index].body_template += ` ${tag}`;
    }
};

const savePushSettings = () => {
    pushForm.put(route('admin.settings.notifications.update'), {
        preserveScroll: true,
        onSuccess: () => alert('Szablony powiadomień Web Push zostały pomyślnie zapisane!')
    });
};
</script>

<template>
    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 space-y-6 shadow-xl text-xs">
        <div class="border-b border-slate-800 pb-3">
            <h2 class="text-xs font-bold uppercase tracking-widest text-slate-300 flex items-center space-x-2">
                <Bell class="w-4 h-4 text-amber-500" />
                <span>Szablony Powiadomień Web Push</span>
            </h2>
            <p class="text-[11px] text-slate-400 mt-1">Dostosuj komunikaty wysyłane automatycznie na telefony klientów.</p>
        </div>

        <form @submit.prevent="savePushSettings" class="space-y-4">
            <div v-for="(setting, index) in pushForm.settings" :key="setting.id" class="p-4 rounded-2xl border border-slate-800 bg-[#0B0F19] space-y-3">
                <div class="flex justify-between items-center border-b border-slate-800/80 pb-2">
                    <span class="font-bold text-amber-400 uppercase tracking-wider text-xs">{{ setting.status_label }}</span>
                    <span class="text-[10px] font-mono text-slate-500 bg-slate-900 px-2 py-0.5 rounded border border-slate-800">{{ setting.status_key }}</span>
                </div>

                <div class="grid grid-cols-1 gap-3">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Tytuł powiadomienia</label>
                        <input v-model="setting.title_template" type="text" class="w-full bg-slate-900 border border-slate-800 rounded-xl p-2.5 text-white font-medium focus:border-red-500" required />
                    </div>

                    <div>
                        <div class="flex justify-between items-center mb-1">
                            <label class="block text-[10px] font-bold text-slate-400 uppercase">Treść powiadomienia</label>
                            <div class="flex gap-1 text-[10px]">
                                <span class="text-slate-500 self-center hidden sm:inline">Kliknij tag:</span>
                                <button type="button" v-for="tag in ['{name}', '{order_id}', '{address}', '{total}']" :key="tag" @click="insertTag(index, tag)" class="bg-slate-900 border border-slate-800 text-amber-400 hover:border-amber-500 px-2 py-0.5 rounded font-mono text-[10px] transition cursor-pointer">
                                    {{ tag }}
                                </button>
                            </div>
                        </div>
                        <textarea v-model="setting.body_template" rows="2" class="w-full bg-slate-900 border border-slate-800 rounded-xl p-2.5 text-white font-medium focus:border-red-500" required></textarea>
                    </div>
                </div>
            </div>

            <div class="flex justify-end pt-4 border-t border-slate-800">
                <button type="submit" :disabled="pushForm.processing" class="bg-gradient-to-r from-red-600 to-amber-500 hover:from-red-700 hover:to-amber-600 text-white font-bold px-8 py-3 rounded-xl uppercase tracking-wider text-xs transition shadow-lg flex items-center space-x-2 cursor-pointer disabled:opacity-50">
                    <Save class="w-4 h-4" />
                    <span>{{ pushForm.processing ? 'Zapisywanie...' : 'Zapisz Szablony Powiadomień' }}</span>
                </button>
            </div>
        </form>
    </div>
</template>