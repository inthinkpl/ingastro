<script setup>
import { computed, onMounted, onUnmounted } from 'vue';
import { useForm, router, Head, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import KdsHeader from './Partials/KdsHeader.vue';
import KdsOrderCard from './Partials/KdsOrderCard.vue';
import { UtensilsCrossed } from 'lucide-vue-next';

const props = defineProps({
    orders: {
        type: Array,
        default: () => []
    }
});

// Sprawdzamy, czy zalogowany jest kucharz
const page = usePage();
const userRole = computed(() => page.props.auth?.role || page.props.auth?.user?.role);
const isChef = computed(() => userRole.value === 'chef');

const form = useForm({ status: '' });

const handleChangeStatus = ({ orderId, nextStatus }) => {
    form.status = nextStatus;
    form.patch(route('order.updateStatus', orderId), { preserveScroll: true });
};

onMounted(() => {
    if (window.Echo) {
        window.Echo.channel('kds').listen('.order.placed', () => {
            router.reload({ only: ['orders'], preserveScroll: true });
        });
    }
});

onUnmounted(() => {
    if (window.Echo) window.Echo.leaveChannel('kds');
});
</script>

<template>
    <Head title="KDS - Monitor Kuchenny - Savona Admin" />

    <!-- Ukrywamy sidebar tylko jeśli zalogowany użytkownik ma rolę 'chef' -->
    <AuthenticatedLayout :hide-sidebar="isChef">
        <div class="min-h-screen bg-[#0B0F19] text-slate-300 p-6 font-sans">
            
            <KdsHeader :active-orders-count="orders.length" :show-logout="isChef" />

            <div v-if="orders.length === 0" class="flex flex-col items-center justify-center py-28 text-center space-y-3">
                <div class="h-16 w-16 rounded-full bg-slate-900 border border-slate-800 flex items-center justify-center text-slate-600">
                    <UtensilsCrossed class="w-8 h-8" />
                </div>
                <p class="text-sm text-slate-500 font-medium">Brak aktywnych zamówień do realizacji. Kuchnia jest czysta!</p>
            </div>

            <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <KdsOrderCard 
                    v-for="order in orders" 
                    :key="order.id" 
                    :order="order"
                    :processing="form.processing"
                    @change-status="handleChangeStatus"
                />
            </div>

        </div>
    </AuthenticatedLayout>
</template>