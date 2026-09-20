<script setup>
import { Head, Link } from '@inertiajs/vue3';

const features = [
    {
        title: 'Sklep Online & E-Commerce',
        description: 'Dedykowana strona dla Twojej pizzerii z koszykiem, automatycznymi promocjami i płatnościami online. Bez prowizji od zamówień.',
        icon: 'shop',
    },
    {
        title: 'Magazyn & Receptury BOM',
        description: 'Precyzyjne przeliczanie zużycia składników na podstawie gramatur. Automatyczne zdejmowanie ze stanu przy każdym zamówieniu.',
        icon: 'inventory',
    },
    {
        title: 'System POS & KDS dla Kuchni',
        description: 'Dotykowy interfejs dla kasjerów oraz ekran KDS dla kucharzy. Zero pomyłek przy realizacji zamówień na sali i w dostawie.',
        icon: 'pos',
    },
    {
        title: 'Moduł Kurierów & Rozliczenia',
        description: 'Aplikacja dla kierowców, przypisywanie stref dostaw oraz szybkie rozliczanie gotówki na koniec zmiany.',
        icon: 'delivery',
    },
    {
        title: 'Rejestracja Czasu Pracy (RCP)',
        description: 'Ewidencja godzin pracy personelu, grafiki oraz pełne wsparcie dla zarządzania zespołem w lokalu.',
        icon: 'rcp',
    },
    {
        title: 'Analityka BI i Raporty',
        description: 'Śledź przychody, najpopularniejsze produkty, wartość magazynu i koszty surowców w czasie rzeczywistym.',
        icon: 'bi',
    },
];

const plans = [
    {
        name: 'Starter',
        price: '149',
        description: 'Dla małych pizzerii i punktów typu Take-away.',
        features: [
            'Dedykowana subdomena i sklep online',
            'System POS do przyjmowania zamówień',
            'Podstawowy magazyn (do 50 składników)',
            'Brak prowizji od zamówień',
            'Wsparcie mailowe 24/7',
        ],
        highlighted: false,
    },
    {
        name: 'Pro Gastro',
        price: '299',
        description: 'Pełna moc dla rozwijających się restauracji.',
        features: [
            'Wszystko co w planie Starter',
            'Zaawansowany moduł BOM (Receptury)',
            'Ekran KDS dla Kuchni',
            'Panel i aplikacja dla Kierowców',
            'Rejestracja Czasu Pracy (RCP)',
            'Program Lojalnościowy dla klientów',
        ],
        highlighted: true,
    },
    {
        name: 'Enterprise Multi-Lokal',
        price: '499',
        description: 'Dla sieci pizzerii i franczyzobiorców.',
        features: [
            'Pełna funkcjonalność bez limitów',
            'Własna domena custom (np. mojapizzeria.pl)',
            'Wsparcie dla wielu lokalizacji',
            'Dedykowany opiekun konta',
            'Integracje API z drukarkami fiskalnymi',
        ],
        highlighted: false,
    },
];
</script>

<template>
    <Head title="Savona SaaS - Kompleksowy System POS, Magazyn i E-Commerce dla Pizzerii" />

    <div class="min-h-screen bg-slate-950 text-slate-100 font-sans selection:bg-amber-500 selection:text-slate-950">
        <!-- Nawigacja -->
        <header class="border-b border-slate-800/80 bg-slate-950/80 backdrop-blur-xl fixed w-full top-0 z-50">
            <div class="max-w-7xl mx-auto px-6 h-20 flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-amber-400 to-amber-600 flex items-center justify-center shadow-lg shadow-amber-500/20">
                        <svg class="w-6 h-6 text-slate-950" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.25C2.001 21 1.8 20.8 1.8 20.551V12.75c0-.414.336-.75.75-.75h18.9c.414 0 .75.336.75.75v7.801c0 .249-.201.449-.45.449H13.5zM3 12l9-9 9 9" />
                        </svg>
                    </div>
                    <span class="text-2xl font-black tracking-tight text-white">SAVONA <span class="text-amber-500 text-xs px-2 py-0.5 rounded-md bg-amber-500/10 border border-amber-500/20 font-mono tracking-wide uppercase">SaaS</span></span>
                </div>
                
                <nav class="hidden md:flex space-x-8 text-sm font-semibold text-slate-400">
                    <a href="#funkcje" class="hover:text-amber-400 transition">Funkcje</a>
                    <a href="#cennik" class="hover:text-amber-400 transition">Cennik</a>
                </nav>

                <div class="flex items-center space-x-4">
                    <Link 
                        href="/register-restaurant"
                        class="px-5 py-2.5 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold rounded-xl transition shadow-lg shadow-amber-500/20 text-sm"
                    >
                        Uruchom swój lokal
                    </Link>
                </div>
            </div>
        </header>

        <!-- Sekcja Hero -->
        <section class="pt-40 pb-20 px-6 relative overflow-hidden">
            <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-amber-500/10 via-transparent to-transparent pointer-events-none"></div>
            
            <div class="max-w-5xl mx-auto text-center relative z-10">
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-slate-900 border border-slate-800 text-amber-400 text-xs font-semibold uppercase tracking-wider mb-8">
                    <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
                    <span>Wszystko, czego potrzebuje Twoja pizzeria w jednym miejscu</span>
                </div>

                <h1 class="text-5xl md:text-7xl font-black text-white tracking-tight leading-tight mb-8">
                    Zwiększ zyski pizzerii z własnym systemem <span class="text-transparent bg-clip-text bg-gradient-to-r from-amber-400 to-amber-600">POS i E-Commerce</span>
                </h1>

                <p class="text-lg md:text-xl text-slate-400 max-w-3xl mx-auto mb-10 leading-relaxed font-normal">
                    Odrzuć wysokie prowizje portalom dostawczym. Otrzymaj własną stronę do zamawiania online, precyzyjną kontrolę magazynu BOM, panel KDS dla kuchni i aplikację dla kurierów.
                </p>

                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <Link 
                        href="/register-restaurant" 
                        class="group px-8 py-4 bg-amber-500 hover:bg-amber-400 text-slate-950 text-base font-bold rounded-xl transition shadow-xl shadow-amber-500/25 flex items-center justify-center gap-3"
                    >
                        <span>Przetestuj za darmo przez 14 dni</span>
                        <svg class="w-5 h-5 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
                        </svg>
                    </Link>
                    <a 
                        href="#funkcje" 
                        class="px-8 py-4 bg-slate-900 hover:bg-slate-800 text-slate-200 border border-slate-800 text-base font-bold rounded-xl transition"
                    >
                        Zobacz możliwości
                    </a>
                </div>
            </div>
        </section>

        <!-- Grid Funkcji -->
        <section id="funkcje" class="py-24 bg-slate-900/40 border-y border-slate-800/60">
            <div class="max-w-7xl mx-auto px-6">
                <div class="text-center max-w-3xl mx-auto mb-16">
                    <h2 class="text-3xl md:text-4xl font-black text-white mb-4">Wszystkie moduły w jednej subskrypcji</h2>
                    <p class="text-slate-400">Nie musisz płacić oddzielnie za sklep online, system POS i program magazynowy.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <div 
                        v-for="item in features" 
                        :key="item.title"
                        class="p-8 bg-slate-900/90 border border-slate-800 rounded-2xl hover:border-slate-700 transition group"
                    >
                        <div class="w-12 h-12 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                            <!-- Ikona: Sklep Online -->
                            <svg v-if="item.icon === 'shop'" class="w-6 h-6 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121 0 2.085-.79 2.29-1.892l1.246-6.697A.75.75 0 0021.525 5H5.111M7.5 14.25L5.111 5" />
                            </svg>
                            <!-- Ikona: Magazyn -->
                            <svg v-else-if="item.icon === 'inventory'" class="w-6 h-6 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                            </svg>
                            <!-- Ikona: POS & KDS -->
                            <svg v-else-if="item.icon === 'pos'" class="w-6 h-6 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 20.25h12m-12 0a2.25 2.25 0 01-2.25-2.25V6.75A2.25 2.25 0 016 4.5h12a2.25 2.25 0 012.25 2.25v11.25a2.25 2.25 0 01-2.25 2.25m-12 0h12" />
                            </svg>
                            <!-- Ikona: Kurierzy -->
                            <svg v-else-if="item.icon === 'delivery'" class="w-6 h-6 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 .999.999 0 00-.987 1.106v7.635m12-6.677a2.056 2.056 0 00-1.58-.86H14.25" />
                            </svg>
                            <!-- Ikona: RCP -->
                            <svg v-else-if="item.icon === 'rcp'" class="w-6 h-6 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <!-- Ikona: BI & Raporty -->
                            <svg v-else-if="item.icon === 'bi'" class="w-6 h-6 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-white mb-2">{{ item.title }}</h3>
                        <p class="text-slate-400 leading-relaxed text-sm font-normal">{{ item.description }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Cennik -->
        <section id="cennik" class="py-24">
            <div class="max-w-7xl mx-auto px-6">
                <div class="text-center max-w-3xl mx-auto mb-16">
                    <h2 class="text-3xl md:text-4xl font-black text-white mb-4">Prosty cennik bez ukrytych opłat</h2>
                    <p class="text-slate-400">Wybierz plan dopasowany do wielkości Twojej restauracji. Możesz zrezygnować w dowolnym momencie.</p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-stretch">
                    <div 
                        v-for="plan in plans" 
                        :key="plan.name"
                        :class="[
                            'p-8 rounded-3xl border flex flex-col justify-between transition relative',
                            plan.highlighted 
                                ? 'bg-slate-900 border-amber-500 shadow-2xl shadow-amber-500/10' 
                                : 'bg-slate-950 border-slate-800'
                        ]"
                    >
                        <div v-if="plan.highlighted" class="absolute -top-4 left-1/2 -translate-x-1/2 px-4 py-1 bg-amber-500 text-slate-950 font-bold text-xs rounded-full uppercase tracking-wider">
                            Najpopularniejszy
                        </div>

                        <div>
                            <h3 class="text-2xl font-bold text-white mb-2">{{ plan.name }}</h3>
                            <p class="text-slate-400 text-sm mb-6">{{ plan.description }}</p>

                            <div class="flex items-baseline gap-1 mb-8">
                                <span class="text-4xl font-black text-white">{{ plan.price }} zł</span>
                                <span class="text-slate-400 text-sm">/ miesiąc + VAT</span>
                            </div>

                            <ul class="space-y-4 mb-8 text-sm">
                                <li v-for="feat in plan.features" :key="feat" class="flex items-center gap-3 text-slate-300">
                                    <svg class="w-5 h-5 text-amber-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                                    </svg>
                                    <span>{{ feat }}</span>
                                </li>
                            </ul>
                        </div>

                        <Link 
                            href="/register-restaurant" 
                            :class="[
                                'w-full py-3.5 text-center font-bold rounded-xl transition text-sm',
                                plan.highlighted 
                                    ? 'bg-amber-500 hover:bg-amber-400 text-slate-950' 
                                    : 'bg-slate-900 hover:bg-slate-800 text-white border border-slate-800'
                            ]"
                        >
                            Wybierz plan
                        </Link>
                    </div>
                </div>
            </div>
        </section>

        <!-- Stopka -->
        <footer class="border-t border-slate-800/80 py-12 bg-slate-950">
            <div class="max-w-7xl mx-auto px-6 text-center text-slate-500 text-sm">
                <p>© {{ new Date().getFullYear() }} Savona SaaS System. Wszystkie prawa zastrzeżone.</p>
            </div>
        </footer>
    </div>
</template>