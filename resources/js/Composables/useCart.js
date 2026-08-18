import { ref, computed, watch } from 'vue';

// Stan koszyka ładowany z pamięci przeglądarki (localStorage)
const savedCart = localStorage.getItem('savona_cart');
const cart = ref(savedCart ? JSON.parse(savedCart) : []);

// Automatyczny zapis w localStorage przy każdej zmianie w koszyku
watch(cart, (newCart) => {
    localStorage.setItem('savona_cart', JSON.stringify(newCart));
}, { deep: true });

export function useCart() {
    const addToCart = (item) => {
        cart.value.push(item);
    };

    const removeFromCart = (index) => {
        cart.value.splice(index, 1);
    };

    const clearCart = () => {
        cart.value = [];
    };

    const cartSubtotal = computed(() => {
        return cart.value.reduce((sum, i) => sum + (i.price * i.quantity), 0);
    });

    const cartItemsCount = computed(() => {
        return cart.value.reduce((sum, item) => sum + item.quantity, 0);
    });

    return {
        cart,
        addToCart,
        removeFromCart,
        clearCart,
        cartSubtotal,
        cartItemsCount
    };
}