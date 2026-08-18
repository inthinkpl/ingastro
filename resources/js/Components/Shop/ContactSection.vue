<script setup>
import { MapPin, Phone, Clock } from 'lucide-vue-next';
</script>

<template>
    <section id="kontakt" class="py-16 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 scroll-mt-20">
        <div class="bg-slate-900 rounded-3xl overflow-hidden text-white shadow-2xl border border-slate-800 grid md:grid-cols-2">
            <div class="p-8 md:p-12 space-y-8 flex flex-col justify-center">
                <div class="space-y-3">
                    <span class="text-amber-400 font-semibold tracking-wider uppercase text-sm">Odwiedź nas lub zadzwoń</span>
                    <h2 class="text-3xl font-bold">Zapraszamy do Savony!</h2>
                    <p class="text-slate-400 text-sm">
                        Znajdziesz nas w dogodnej lokalizacji w Białymstoku. Dbamy o to, by zamówienia na dowóz docierały gorące!
                    </p>
                </div>

                <div class="space-y-4 font-light text-slate-300">
                    <div class="flex items-start space-x-3">
                        <MapPin class="w-5 h-5 text-amber-400 mt-1 shrink-0" />
                        <div>
                            <strong class="font-semibold text-white block">Adresy lokali:</strong>
                            <span class="text-white font-medium">Pizzeria Savona Legionowa</span><br>ul. Legionowa 9/1, 15-369 Białystok<br><br>
                            <span class="text-white font-medium">Pizzeria Primo Savona</span><br>Rynek Kościuszki 8/1, 15-426 Białystok
                        </div>
                    </div>
                    <div class="flex items-center space-x-3">
                        <Phone class="w-5 h-5 text-amber-400 shrink-0" />
                        <div>
                            <strong class="font-semibold text-white">Telefon: </strong>
                            <a href="tel:+48785555455" class="hover:text-amber-400 transition text-amber-400 font-medium">+48 785 555 455</a>
                        </div>
                    </div>
                    <div class="flex items-start space-x-3">
                        <Clock class="w-5 h-5 text-amber-400 mt-1 shrink-0" />
                        <div>
                            <strong class="font-semibold text-white block">Godziny otwarcia:</strong>
                            Pon - Czw: 11:00 - 22:00<br>
                            Pt - Sob: 11:00 - 23:00<br>
                            Niedziela: 12:00 - 22:00
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="h-96 md:h-auto bg-[#0B0F19] relative border-t md:border-t-0 md:border-l border-slate-800 overflow-hidden">
                <iframe src="https://www.google.com/maps/d/embed?mid=1jZkPl1rLXnNmAGChU_ttv8vYePFnGdc&ehbc=2E312F&noprof=1" class="w-full h-full border-0"></iframe>
            </div>
        </div>
    </section>
</template>