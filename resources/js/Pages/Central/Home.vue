<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { 
    Utensils, ShoppingCart, ChefHat, Truck, PackageCheck, 
    Gift, Clock, CheckCircle2, ArrowRight, ShieldCheck, Sparkles, Zap, Calculator,
    Building2, Store, Coffee, Pizza, Flame, Check, Monitor, Smartphone, Layers
} from 'lucide-vue-next';

// Domyślne moduły w ofercie z cenami dla każdego typu gastronomii
const modules = ref([
    { key: 'pos', name: 'Kasa POS & Sprzedaż w Lokalu', price: 69, icon: ShoppingCart, desc: 'Szybkie przyjmowanie zamówień przy ladzie i stolikach, bonowanie, wybór sosów, mięs i dodatków.' },
    { key: 'shop', name: 'Sklep E-Commerce Online', price: 79, icon: Utensils, desc: 'Własny system zamówień online bez prowizji dla Pyszne/Glovo. Twój własny kanał sprzedaży.' },
    { key: 'kds', name: 'Ekran Kuchenny (KDS)', price: 49, icon: ChefHat, desc: 'Brak papierowych bonów. Kucharze i osoby przy grillu/piecu widzą czas i priorytety zamówień.' },
    { key: 'delivery', name: 'Moduł & Aplikacja dla Kurierów', price: 59, icon: Truck, desc: 'Nawigacja GPS, przypisywanie stref dostaw i automatyczne rozliczanie gotówki kierowców.' },
    { key: 'inventory_bom', name: 'Magazyn & Receptury BOM', price: 49, icon: PackageCheck, desc: 'Automatyczne schodzenie surowców ze stanu po każdym sprzedanym daniu, kebabie, pizzie czy napoju.' },
    { key: 'loyalty', name: 'Program Lojalnościowy & Kody', price: 39, icon: Gift, desc: 'Karty stałego klienta, punkty za zakupy, SMS OTP i automatyczne rabaty powracające.' },
    { key: 'rcp', name: 'Rejestracja Czasu Pracy (RCP)', price: 29, icon: Clock, desc: 'Ewidencja godzin pracy kelnerów, kucharzy i kurierów, kontrola pauz i wyliczanie wypłat.' },
]);

// Aktywna zakładka w podglądzie ekranów modułów
const activePreviewModule = ref('pos');

// Szczegółowe podglądy ekranów modułów
const modulePreviews = [
    {
        key: 'pos',
        title: 'Kasa POS & Sprzedaż w Lokalu',
        tagline: 'Niebywale szybkie przyjmowanie zamówień przy ladzie i na stolikach',
        description: 'Intuicyjny interfejs dotykowy zaprojektowany tak, aby skrócić czas obsługi klienta do minimum. Pozwala na szybkie modyfikacje dań (np. sosy, stopień wysmażenia, składy pizzy), dzielenie rachunków oraz drukowanie bonów i paragonów.',
        bullets: ['Błyskawiczne modyfikatory dań i dodatków', 'Obsługa stolików i połączenie z terminalem płatniczym', 'Numeracja zamówień do wydawki i wydruk na kuchnię'],
        badge: 'Terminale & Kasy',
        image: 'https://images.unsplash.com/photo-1556740758-90de374c12ad?auto=format&fit=crop&w=1200&q=80'
    },
    {
        key: 'kds',
        title: 'Ekran Kuchenny KDS (Kitchen Display System)',
        tagline: 'Koniec z gubieniem papierowych bonów na kuchni',
        description: 'Zamówienia z kasy POS i sklepu online natychmiast trafiają na ekrany kucharzy i pizzaiolo. Domyślny timer podświetla dania wymagające natychmiastowej uwagi, co drastycznie skraca czas oczekiwania gości.',
        bullets: ['Grupowanie dań według sekcji (grill, piec, zimna płyta)', 'Statusy przygotowania w czasie rzeczywistym', 'Sygnał dźwiękowy dla nowych zamówień'],
        badge: 'Kuchnia & Szef Kuchni',
        image: 'https://images.unsplash.com/photo-1556910103-1c02745aae4d?auto=format&fit=crop&w=1200&q=80'
    },
    {
        key: 'delivery',
        title: 'Moduł & Aplikacja dla Kurierów',
        tagline: 'Brak pomyłek w adresach i pełna kontrola nad gotówką',
        description: 'Aplikacja mobilna dla kierowców pokazuje trasę, nawiguje do klienta i pozwala na szybki kontakt telefoniczny 1 kliknięciem. Menedżer w lokalu widzi pozycję kurierów na mapie oraz rozlicza zebraną gotówkę.',
        bullets: ['Automatyczne przydzielanie stref dostaw z opłatami', 'Interaktywna mapa i GPS dla kierowców', 'Szybkie rozliczanie zmian i zebranej gotówki'],
        badge: 'Dostawy & LOGISTYKA',
        image: 'https://images.unsplash.com/photo-1526367790999-0150786686a2?auto=format&fit=crop&w=1200&q=80'
    },
    {
        key: 'shop',
        title: 'Sklep E-Commerce Online (Bez Prowizji)',
        tagline: 'Własny kanał sprzedaży online z płatnościami',
        description: 'Zbuduj własną bazę klientów i nie dziel się marżą z portalami dostaw. Twoi klienci zamawiają jedzenie bezpośrednio z Twojej strony internetowej, płacąc online lub przy odbiorze.',
        bullets: ['Brak prowizji od wartości zamówień', 'Szybka integracja z płatnościami online (PayU / Tpay / Stripe)', 'Dostosowane menu pod urządzenia mobilne'],
        badge: 'E-Commerce & Zamówienia',
        image: 'https://images.unsplash.com/photo-1526304640581-d334cdbbf45e?auto=format&fit=crop&w=1200&q=80'
    },
    {
        key: 'inventory_bom',
        title: 'Magazyn & Receptury BOM (Bill of Materials)',
        tagline: 'Precyzyjna kontrola zużycia składników i marży dań',
        description: 'Powiąż produkty w menu z konkretnymi surowcami w magazynie. Sprzedaż kebaba, pizzy czy napoju automatycznie zmniejsza stan sera, mięsa czy sosów w magazynie głównym.',
        bullets: ['Automatyczny ubytek surowców przy sprzedaży', 'Ostrzeżenia o niskim stanie magazynowym', 'Analiza kosztów dań (Food Cost)'],
        badge: 'Magazyn & Finanse',
        image: 'https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?auto=format&fit=crop&w=1200&q=80'
    },
    {
        key: 'rcp',
        title: 'Rejestracja Czasu Pracy (RCP)',
        tagline: 'Ewidencja godzin pracy i sprawne rozliczenia zespołu',
        description: 'Pracownicy logują rozpoczęcie, przerwy i koniec pracy na dedykowanym widoku w lokalu. Menedżer dostaje gotowe zestawienia do wypłat i uniknie rozbieżności w grafikach.',
        bullets: ['Osobiste kody PIN dla pracowników', 'Kontrola pauz i nadgodzin', 'Eksport raportów do rozliczeń wypłat'],
        badge: 'Kadry & Zespół',
        image: 'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?auto=format&fit=crop&w=1200&q=80'
    }
];

const currentPreview = computed(() => {
    return modulePreviews.find(p => p.key === activePreviewModule.value) || modulePreviews[0];
});

// Typy lokali gastronomicznych w Polsce
const venueTypes = [
    { name: 'Kebab & Fast Food', icon: Flame, desc: 'Szybkie modyfikatory (sosy, mięsa), numery do wydawki, błyskawiczna obsługa kolejki.' },
    { name: 'Restauracje & Pizzerie', icon: Pizza, desc: 'Pełna obsługa sali, stolików, dzielenie pizzy na pół oraz zarządzanie dostawami.' },
    { name: 'Kawiarnie, Piekarnie & Burgerownie', icon: Coffee, desc: 'Szybka sprzedaż przy ladzie, nabijanie zestawów i moduł lojalnościowy.' },
    { name: 'Food Trucki & Multi-Location', icon: Store, desc: 'Mobilny POS oraz centralne zarządzanie menu i magazynem dla wielu punktów.' },
];

// Kalkulator dla klienta na stronie głównej
const selectedKeys = ref(['pos', 'shop', 'kds']);

const toggleModule = (key) => {
    if (selectedKeys.value.includes(key)) {
        if (selectedKeys.value.length === 1) return;
        selectedKeys.value = selectedKeys.value.filter(k => k !== key);
    } else {
        selectedKeys.value.push(key);
    }
};

const calculatedTotal = computed(() => {
    return modules.value
        .filter(m => selectedKeys.value.includes(m.key))
        .reduce((sum, m) => sum + m.price, 0);
});
</script>

<template>
    <Head title="InGastro SaaS - Elastyczny System ERP dla Kebaba, Pizzerii i Gastronomii" />

    <div class="min-h-screen bg-slate-50 text-slate-800 font-sans antialiased selection:bg-amber-500 selection:text-white">
        
        <!-- PASEK NAWIGACJI -->
        <header class="bg-white border-b border-slate-200 sticky top-0 z-50 shadow-sm">
            <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="p-2.5 bg-amber-500 rounded-xl text-slate-950 font-black shadow-md shadow-amber-500/20">
                        <Utensils class="w-6 h-6" />
                    </div>
                    <span class="text-2xl font-black text-slate-900 tracking-tight">InGastro <span class="text-amber-500">SaaS</span></span>
                </div>

                <div class="flex items-center space-x-4">
                    <Link :href="route('central.register')" class="px-6 py-3 bg-amber-500 hover:bg-amber-400 text-slate-950 font-black rounded-xl text-xs uppercase tracking-wider transition shadow-md shadow-amber-500/20 flex items-center space-x-2">
                        <span>Wypróbuj 14 dni za darmo</span>
                        <ArrowRight class="w-4 h-4" />
                    </Link>
                </div>
            </div>
        </header>

        <!-- HERO SECTION Z GRAFIKĄ GASTRONOMICZNĄ -->
        <section class="max-w-7xl mx-auto px-6 pt-12 pb-16">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
                
                <!-- LEWA STRONA: TEKST -->
                <div class="lg:col-span-7 space-y-6 text-left">
                    <div class="inline-flex items-center space-x-2 px-4 py-2 rounded-full bg-amber-100 border border-amber-300 text-amber-900 text-xs font-bold uppercase tracking-wider">
                        <Sparkles class="w-4 h-4 text-amber-600" />
                        <span>System stworzony dla każdego lokalu gastronomicznego</span>
                    </div>

                    <h1 class="text-4xl sm:text-6xl font-black text-slate-900 leading-[1.1] tracking-tight">
                        Nowoczesny system dla całej Gastronomii. <br />
                        <span class="text-amber-600">
                            Płacisz tylko za wybrane moduły.
                        </span>
                    </h1>

                    <p class="text-slate-600 text-lg sm:text-xl leading-relaxed">
                        Niezależnie czy prowadzisz popularny punkt z kebabem, lokalną pizzerię, burgerownię czy sieć restauracji – skomponuj idealny zestaw narzędzi bez płacenia prowizji od sprzedaży.
                    </p>

                    <div class="pt-2 flex flex-col sm:flex-row items-stretch sm:items-center gap-4">
                        <Link :href="route('central.register')" class="px-8 py-4 bg-amber-500 hover:bg-amber-400 text-slate-950 font-black rounded-2xl text-base uppercase tracking-wider transition shadow-lg shadow-amber-500/20 flex items-center justify-center space-x-3">
                            <span>Załóż darmowe konto (14 Dni Trial)</span>
                            <ArrowRight class="w-5 h-5" />
                        </Link>
                    </div>

                    <div class="pt-4 flex flex-wrap items-center gap-6 text-xs font-bold text-slate-600 uppercase tracking-wider">
                        <span class="flex items-center"><ShieldCheck class="w-5 h-5 text-emerald-600 mr-2" /> Bez karty przy rejestracji</span>
                        <span class="flex items-center"><Zap class="w-5 h-5 text-amber-600 mr-2" /> Konfiguracja w 5 minut</span>
                        <span class="flex items-center"><CheckCircle2 class="w-5 h-5 text-emerald-600 mr-2" /> 0% prowizji od obrotu</span>
                    </div>
                </div>

                <!-- PRAWA STRONA: ZDJĘCIE HERO GASTRONOMII -->
                <div class="lg:col-span-5 relative">
                    <div class="relative mx-auto rounded-3xl overflow-hidden shadow-2xl border-4 border-white bg-slate-100">
                        <img 
                            src="https://images.unsplash.com/photo-1556740758-90de374c12ad?auto=format&fit=crop&w=1200&q=80" 
                            alt="System POS i KDS dla Gastronomii" 
                            class="w-full h-80 sm:h-96 object-cover"
                        />
                        <div class="absolute bottom-0 inset-x-0 bg-gradient-to-t from-slate-950/80 via-slate-950/40 to-transparent p-6 text-white">
                            <span class="text-xs font-bold uppercase tracking-wider text-amber-400">Terminal POS & Ekran Kuchni</span>
                            <h3 class="text-lg font-bold">Pełna kontrola nad czasem wydawania dań</h3>
                        </div>
                    </div>
                </div>

            </div>
        </section>

        <!-- TYPY LOKALI W POLSCE -->
        <section class="bg-white py-16 border-y border-slate-200">
            <div class="max-w-7xl mx-auto px-6 space-y-10">
                <div class="text-center space-y-2">
                    <h2 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">Obsługujemy każdy rodzaj lokalu gastronomicznego</h2>
                    <p class="text-slate-600 text-sm sm:text-base">Od szybkich dań na wynos po pełnowymiarową obsługę kelnerską</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div v-for="venue in venueTypes" :key="venue.name" class="p-6 bg-slate-50 rounded-2xl border border-slate-200/80 hover:border-amber-400 transition-all space-y-3 shadow-sm">
                        <div class="p-3 bg-amber-500/10 text-amber-600 rounded-xl w-max">
                            <component :is="venue.icon" class="w-6 h-6" />
                        </div>
                        <h3 class="text-base font-bold text-slate-900 uppercase tracking-wider">{{ venue.name }}</h3>
                        <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">{{ venue.desc }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- PODGLĄD SYSTEMU DLA KAŻDEGO LOKALU GASTRONOMICZNEGO -->
        <section class="max-w-7xl mx-auto px-6 py-16 space-y-12">
            <div class="bg-gradient-to-br from-amber-500 to-amber-600 rounded-3xl p-8 sm:p-12 text-slate-950 shadow-xl grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
                <div class="lg:col-span-7 space-y-4">
                    <div class="inline-flex items-center space-x-1.5 px-3 py-1 bg-slate-950/10 rounded-full text-slate-950 text-xs font-extrabold uppercase tracking-wider">
                        <Utensils class="w-4 h-4 fill-current" />
                        <span>Dedykowane funkcje dla każdego lokalu gastronomicznego</span>
                    </div>
                    <h2 class="text-3xl sm:text-4xl font-black leading-tight">Błyskawiczna obsługa i wygoda pracy całego zespołu</h2>
                    <p class="text-slate-950/80 text-base sm:text-lg leading-relaxed">
                        Niezależnie od charakteru Twojej gastronomii, nasz system adaptuje się do Twoich potrzeb. Wymagasz szybkich modyfikatorów sosów i mięs w kebabie? Obsługujesz stoliki i dzielisz pizzę na pół? A może zarządzasz własną flotą kurierów? InGastro usprawnia pracę od przyjęcia zamówienia aż po wydanie i rozliczenie.
                    </p>
                    <ul class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs font-bold uppercase tracking-wider pt-2">
                        <li class="flex items-center"><Check class="w-4 h-4 mr-2 text-slate-950 stroke-[3]" /> Szybkie modyfikatory dań i składników</li>
                        <li class="flex items-center"><Check class="w-4 h-4 mr-2 text-slate-950 stroke-[3]" /> Numeracja zamówień na wynos</li>
                        <li class="flex items-center"><Check class="w-4 h-4 mr-2 text-slate-950 stroke-[3]" /> Bony na ekrany KDS na kuchni</li>
                        <li class="flex items-center"><Check class="w-4 h-4 mr-2 text-slate-950 stroke-[3]" /> Rozliczanie gotówki i zmian kurierów</li>
                    </ul>
                </div>

                <div class="lg:col-span-5">
                    <img 
                        src="https://images.unsplash.com/photo-1555396273-367ea4eb4db5?auto=format&fit=crop&w=1200&q=80" 
                        alt="Restauracja obsługa gastronomiczna" 
                        class="rounded-2xl shadow-2xl border-4 border-white/40 object-cover h-64 sm:h-80 w-full"
                    />
                </div>
            </div>
        </section>

        <!-- KALKULATOR MODUŁOWY (INTERAKTYWNY DLA KLIENTA) -->
        <section class="max-w-6xl mx-auto px-6 py-8">
            <div class="bg-white border border-slate-200 rounded-3xl p-8 sm:p-10 shadow-xl space-y-8">
                <div class="text-center space-y-2">
                    <h2 class="text-2xl sm:text-3xl font-black text-slate-900 flex items-center justify-center space-x-2">
                        <Calculator class="w-7 h-7 text-amber-500" />
                        <span>Kalkulator Zestawu Modułowego</span>
                    </h2>
                    <p class="text-slate-600 text-sm">Wybierz moduły potrzebne w Twoim lokalu i sprawdź miesięczną kwotę abonamentu:</p>
                </div>

                <!-- SIATKA MODUŁÓW -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div 
                        v-for="mod in modules" 
                        :key="mod.key"
                        @click="toggleModule(mod.key)"
                        :class="[
                            'p-5 rounded-2xl border-2 transition-all cursor-pointer space-y-3 relative',
                            selectedKeys.includes(mod.key)
                                ? 'bg-amber-500/10 border-amber-500 shadow-md'
                                : 'bg-slate-50 border-slate-200 hover:border-slate-300 opacity-70'
                        ]"
                    >
                        <div class="flex items-center justify-between">
                            <div class="p-2.5 rounded-xl bg-white border border-slate-200 text-amber-600">
                                <component :is="mod.icon" class="w-5 h-5" />
                            </div>
                            <span class="text-base font-black font-mono text-amber-600">+{{ mod.price }} zł <span class="text-xs text-slate-500 font-sans font-normal">/ mies.</span></span>
                        </div>

                        <div>
                            <h3 class="text-base font-bold text-slate-900">{{ mod.name }}</h3>
                            <p class="text-xs text-slate-600 mt-1 leading-relaxed">{{ mod.desc }}</p>
                        </div>
                    </div>
                </div>

                <!-- PODSUMOWANIE KALKULATORA -->
                <div class="bg-slate-900 text-white p-6 sm:p-8 rounded-2xl flex flex-col sm:flex-row items-center justify-between gap-6">
                    <div>
                        <div class="text-xs text-slate-400 font-bold uppercase tracking-wider">Szacowany abonament miesięczny:</div>
                        <div class="text-3xl sm:text-4xl font-black font-mono text-amber-400 mt-1">
                            {{ calculatedTotal }} <span class="text-base text-slate-400 font-sans font-normal">zł / mies. netto</span>
                        </div>
                        <div class="text-xs text-emerald-400 font-bold mt-1">0 zł prowizji od sprzedaży. Wybrane moduły aktywne w 14-dniowym trialu.</div>
                    </div>

                    <Link :href="route('central.register')" class="w-full sm:w-auto px-8 py-4 bg-amber-500 hover:bg-amber-400 text-slate-950 font-black rounded-xl text-sm uppercase tracking-wider transition shadow-lg shadow-amber-500/20 text-center">
                        Rozpocznij darmowy test
                    </Link>
                </div>
            </div>
        </section>

        <!-- 🌟 NOWA SEKCJA: INTERAKTYWNY PODGLĄD MODUŁÓW SYSTEMU (PREVIEW / MOCKUP) -->
        <section class="max-w-7xl mx-auto px-6 py-16 space-y-12">
            <div class="text-center space-y-3">
                <div class="inline-flex items-center space-x-2 px-3.5 py-1.5 rounded-full bg-amber-100 border border-amber-300 text-amber-900 text-xs font-bold uppercase tracking-wider">
                    <Layers class="w-4 h-4 text-amber-600" />
                    <span>Przegląd funkcji i ekranów</span>
                </div>
                <h2 class="text-3xl sm:text-4xl font-black text-slate-900 tracking-tight">Zobacz, jak poszczególne moduły pracują w praktyce</h2>
                <p class="text-slate-600 text-base max-w-2xl mx-auto">Kliknij w wybrany moduł poniżej, aby poznać jego szczegółowe możliwości i zobaczyć podgląd interfejsu:</p>
            </div>

            <!-- PRZYCISKI ZAKŁADEK MODUŁÓW -->
            <div class="flex flex-wrap justify-center gap-2 border-b border-slate-200 pb-4">
                <button 
                    v-for="preview in modulePreviews" 
                    :key="preview.key"
                    @click="activePreviewModule = preview.key"
                    :class="[
                        'px-5 py-3 rounded-2xl text-xs sm:text-sm font-bold uppercase tracking-wider transition flex items-center space-x-2 cursor-pointer',
                        activePreviewModule === preview.key
                            ? 'bg-amber-500 text-slate-950 shadow-md shadow-amber-500/20'
                            : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-100'
                    ]"
                >
                    <component :is="modules.find(m => m.key === preview.key)?.icon || Utensils" class="w-4 h-4" />
                    <span>{{ preview.title.split('&')[0] }}</span>
                </button>
            </div>

            <!-- KARTA DEDYKOWANA DLA AKTYWNEGO PODGLĄDU -->
            <div class="bg-white border border-slate-200 rounded-3xl p-8 sm:p-12 shadow-xl grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
                <div class="lg:col-span-6 space-y-6">
                    <span class="px-3 py-1 rounded-md bg-amber-100 text-amber-900 text-xs font-black uppercase tracking-wider">
                        {{ currentPreview.badge }}
                    </span>

                    <h3 class="text-2xl sm:text-3xl font-black text-slate-900 leading-tight">
                        {{ currentPreview.title }}
                    </h3>

                    <p class="text-amber-600 font-bold text-sm sm:text-base">
                        {{ currentPreview.tagline }}
                    </p>

                    <p class="text-slate-600 text-sm sm:text-base leading-relaxed">
                        {{ currentPreview.description }}
                    </p>

                    <ul class="space-y-2.5 pt-2">
                        <li v-for="(bullet, idx) in currentPreview.bullets" :key="idx" class="flex items-start text-xs sm:text-sm font-bold text-slate-800">
                            <CheckCircle2 class="w-5 h-5 text-emerald-600 mr-2.5 shrink-0 mt-0.5" />
                            <span>{{ bullet }}</span>
                        </li>
                    </ul>
                </div>

                <div class="lg:col-span-6">
                    <div class="relative rounded-2xl overflow-hidden border-4 border-slate-100 shadow-2xl bg-slate-900">
                        <img 
                            :src="currentPreview.image" 
                            :alt="currentPreview.title"
                            class="w-full h-80 sm:h-96 object-cover"
                        />
                    </div>
                </div>
            </div>
        </section>

        <!-- FOOTER -->
        <footer class="bg-white border-t border-slate-200 py-8 text-center text-xs text-slate-500">
            &copy; 2026 InGastro SaaS. Kompleksowe rozwiązania dla gastronomi małej i dużej.
        </footer>

    </div>
</template>