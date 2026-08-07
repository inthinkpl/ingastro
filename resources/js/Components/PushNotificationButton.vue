<template>
    <div class="push-notification-wrapper my-4">
        <button 
            v-if="isSupported && !isSubscribed"
            @click="subscribe"
            :disabled="loading"
            class="flex items-center gap-2 px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white font-medium rounded-lg shadow transition"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
            <span>{{ loading ? 'Włączanie...' : 'Włącz powiadomienia o statusie pizzy 🍕' }}</span>
        </button>

        <div v-else-if="isSubscribed" class="text-sm text-emerald-600 font-semibold flex items-center gap-1">
            <span>✓ Powiadomienia na żywo są aktywne</span>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';

const isSupported = ref(false);
const isSubscribed = ref(false);
const loading = ref(false);

onMounted(async () => {
    if ('serviceWorker' in navigator && 'PushManager' in window) {
        isSupported.value = true;
        
        // Zarejestruj Service Worker
        const registration = await navigator.serviceWorker.register('/sw.js');
        const subscription = await registration.pushManager.getSubscription();
        isSubscribed.value = !!subscription;
    }
});

async function subscribe() {
    loading.value = true;
    try {
        const permission = await Notification.requestPermission();
        if (permission !== 'granted') {
            alert('Wymagana jest zgoda na powiadomienia w przeglądarce.');
            return;
        }

        const registration = await navigator.serviceWorker.ready;
        const vapidPublicKey = import.meta.env.VITE_VAPID_PUBLIC_KEY;

        const subscription = await registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array(vapidPublicKey)
        });

        // Wyślij subskrypcję do Laravela przez Inertia / Axios
        router.post('/push/subscribe', subscription.toJSON(), {
            preserveScroll: true,
            onSuccess: () => {
                isSubscribed.value = true;
            }
        });
    } catch (error) {
        console.error('Błąd subskrypcji Web Push:', error);
    } finally {
        loading.value = false;
    }
}

// Konwersja klucza VAPID do formatu Uint8Array
function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding).replace(/\-/g, '+').replace(/_/g, '/');
    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);
    for (let i = 0; i < rawData.length; ++i) {
        outputArray[i] = rawData.charCodeAt(i);
    }
    return outputArray;
}
</script>