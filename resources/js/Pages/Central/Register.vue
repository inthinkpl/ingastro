<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const form = useForm({
    restaurant_name: '',
    subdomain: '',
    admin_name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

// Automatyczne formatowanie subdomeny na żywo
const handleSubdomainInput = (e) => {
    const raw = e.target.value;
    form.subdomain = raw
        .toLowerCase()
        .replace(/[^a-z0-9-]/g, '')
        .replace(/--+/g, '-');
};

// Generowanie podglądu docelowego adresu URL
const previewUrl = computed(() => {
    const sub = form.subdomain || 'twoja-pizzeria';
    return `http://${sub}.localhost`;
});

const submit = () => {
    form.post('/register-restaurant', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Uruchom własny lokal z ingastro" />

    <div class="min-h-screen bg-slate-950 text-slate-100 font-sans flex flex-col justify-center items-center p-4 sm:p-6 relative overflow-hidden">
        <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-amber-500/10 via-transparent to-transparent pointer-events-none"></div>

        <!-- Powrót do strony głównej SaaS -->
        <div class="w-full max-w-xl mb-6">
            <Link 
                href="/" 
                class="inline-flex items-center gap-2 text-sm text-slate-400 hover:text-amber-500 transition"
            >
                <span>← Powrót do strony głównej</span>
            </Link>
        </div>

        <div class="w-full max-w-xl bg-slate-900 border border-slate-800 rounded-3xl p-6 sm:p-10 shadow-2xl relative z-10">
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center text-4xl mb-3">🍕</div>
                <h1 class="text-2xl sm:text-3xl font-black text-white tracking-tight mb-2">
                    Uruchom swój system w 30 sekund
                </h1>
                <p class="text-slate-400 text-sm">
                    Podaj dane restauracji. Twoja subdomena i baza danych zostaną skonfigurowane automatycznie.
                </p>
            </div>

            <form @submit.prevent="submit" class="space-y-5">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-2">
                        Nazwa Lokalu / Restauracji
                    </label>
                    <input 
                        v-model="form.restaurant_name" 
                        type="text" 
                        placeholder="np. Pizzeria Bella Roma" 
                        class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-white placeholder-slate-600 focus:border-amber-500 focus:ring-1 focus:ring-amber-500 outline-none transition text-sm"
                        required 
                    />
                    <div v-if="form.errors.restaurant_name" class="text-red-400 text-xs mt-1.5 font-medium">
                        {{ form.errors.restaurant_name }}
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-2">
                        Adres subdomeny lokalu
                    </label>
                    <div class="flex items-center">
                        <input 
                            :value="form.subdomain"
                            @input="handleSubdomainInput"
                            type="text" 
                            placeholder="bella-roma" 
                            class="w-full bg-slate-950 border border-slate-800 rounded-l-xl px-4 py-3 text-white placeholder-slate-600 focus:border-amber-500 focus:ring-1 focus:ring-amber-500 outline-none transition text-sm font-mono"
                            required 
                        />
                        <span class="bg-slate-800 px-4 py-3 border border-l-0 border-slate-800 rounded-r-xl text-slate-400 text-sm font-mono whitespace-nowrap">
                            .localhost
                        </span>
                    </div>
                    <div class="mt-2 text-xs text-slate-500 flex items-center gap-1.5 font-mono">
                        <span>Adres Twojego lokalu:</span>
                        <span class="text-amber-400 font-semibold underline">{{ previewUrl }}</span>
                    </div>
                    <div v-if="form.errors.subdomain" class="text-red-400 text-xs mt-1.5 font-medium">
                        {{ form.errors.subdomain }}
                    </div>
                </div>

                <div class="border-t border-slate-800/80 my-6"></div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-2">
                            Imię i Nazwisko Właściciela
                        </label>
                        <input 
                            v-model="form.admin_name" 
                            type="text" 
                            placeholder="Jan Kowalski"
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-white placeholder-slate-600 focus:border-amber-500 focus:ring-1 focus:ring-amber-500 outline-none transition text-sm"
                            required 
                        />
                        <div v-if="form.errors.admin_name" class="text-red-400 text-xs mt-1.5 font-medium">
                            {{ form.errors.admin_name }}
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-2">
                            Adres E-mail (Logowanie)
                        </label>
                        <input 
                            v-model="form.email" 
                            type="email" 
                            placeholder="admin@bellaroma.pl"
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-white placeholder-slate-600 focus:border-amber-500 focus:ring-1 focus:ring-amber-500 outline-none transition text-sm"
                            required 
                        />
                        <div v-if="form.errors.email" class="text-red-400 text-xs mt-1.5 font-medium">
                            {{ form.errors.email }}
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-2">
                            Hasło
                        </label>
                        <input 
                            v-model="form.password" 
                            type="password" 
                            placeholder="••••••••"
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-white placeholder-slate-600 focus:border-amber-500 focus:ring-1 focus:ring-amber-500 outline-none transition text-sm"
                            required 
                        />
                        <div v-if="form.errors.password" class="text-red-400 text-xs mt-1.5 font-medium">
                            {{ form.errors.password }}
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-2">
                            Potwierdź Hasło
                        </label>
                        <input 
                            v-model="form.password_confirmation" 
                            type="password" 
                            placeholder="••••••••"
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-white placeholder-slate-600 focus:border-amber-500 focus:ring-1 focus:ring-amber-500 outline-none transition text-sm"
                            required 
                        />
                    </div>
                </div>

                <button 
                    type="submit" 
                    :disabled="form.processing" 
                    class="w-full py-4 bg-amber-500 hover:bg-amber-400 disabled:opacity-50 text-slate-950 font-bold rounded-xl transition shadow-lg shadow-amber-500/20 text-base mt-4 flex items-center justify-center gap-2"
                >
                    <span v-if="form.processing" class="inline-block animate-spin">⏳</span>
                    <span>{{ form.processing ? 'Tworzenie lokalu i bazy danych...' : 'Załóż Konto i Uruchom System' }}</span>
                </button>
            </form>
        </div>
    </div>
</template>