<script setup>
import { ref, computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    ingredients: Array
});

const page = usePage();
const successMessage = computed(() => page.props.flash?.success);

// Kontrola okien modalnych
const selectedIngredient = ref(null); // do dostaw
const crudModalOpen = ref(false);     // do tworzenia/edycji
const isEditMode = ref(false);        // flaga trybu edycji
const editingIngredientId = ref(null);

// Formularz przyjęcia dostawy
const restockForm = useForm({
    amount: ''
});

// Formularz CRUD (Dodawanie / Edycja)
const crudForm = useForm({
    name: '',
    stock_quantity: 0,
    min_limit: 0,
    unit: 'kg',
    purchase_price: 0
});

// Otwarcia modalów
const openRestockModal = (ingredient) => {
    selectedIngredient.value = ingredient;
    restockForm.amount = '';
};

const openCreateModal = () => {
    isEditMode.value = false;
    editingIngredientId.value = null;
    crudForm.reset();
    crudModalOpen.value = true;
};

const openEditModal = (ingredient) => {
    isEditMode.value = true;
    editingIngredientId.value = ingredient.id;
    crudForm.name = ingredient.name;
    crudForm.stock_quantity = ingredient.stock_quantity; // w edycji zablokujemy to pole
    crudForm.min_limit = ingredient.min_limit;
    crudForm.unit = ingredient.unit;
    crudForm.purchase_price = ingredient.purchase_price;
    crudModalOpen.value = true;
};

// Wysyłki formularzy
const submitRestock = () => {
    restockForm.post(route('manager.restock', selectedIngredient.value.id), {
        onSuccess: () => selectedIngredient.value = null
    });
};

const submitCrudForm = () => {
    if (isEditMode.value) {
        crudForm.put(route('manager.inventory.update', editingIngredientId.value), {
            onSuccess: () => crudModalOpen.value = false
        });
    } else {
        crudForm.post(route('manager.inventory.store'), {
            onSuccess: () => crudModalOpen.value = false
        });
    }
};

const deleteIngredient = (id) => {
    if (confirm('Czy na pewno chcesz usunąć ten surowiec z magazynu ERP?')) {
        crudForm.delete(route('manager.inventory.destroy', id));
    }
};
</script>

<template>
    <AuthenticatedLayout>
    <div class="min-h-screen bg-gray-950 text-white p-6">
        <header class="mb-8 border-b border-gray-800 pb-4 flex justify-between items-center">
            <div class="flex items-center space-x-3">
                <span class="text-2xl">📊</span>
                <h1 class="text-2xl font-black tracking-wider text-orange-400">PANEL MANAGERA: ERP INVENTORY</h1>
            </div>
            <button 
                @click="openCreateModal"
                class="bg-orange-600 hover:bg-orange-500 text-white font-bold text-xs py-2.5 px-4 rounded-xl transition-all uppercase tracking-wider shadow-md"
            >
                + Nowy surowiec
            </button>
        </header>

        <div v-if="successMessage" class="mb-6 p-4 bg-emerald-600 text-white rounded-lg font-bold shadow-lg">
            🎉 {{ successMessage }}
        </div>

        <div class="bg-gray-900 border border-gray-800 rounded-xl overflow-hidden shadow-2xl">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-950 text-xs font-bold uppercase tracking-wider text-gray-400 border-b border-gray-800">
                            <th class="p-4">Nazwa surowca</th>
                            <th class="p-4">Aktualny stan</th>
                            <th class="p-4">Minimum logistyczne</th>
                            <th class="p-4">Status</th>
                            <th class="p-4">Cena zakupu</th>
                            <th class="p-4 text-right">Akcje</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-850 text-sm">
                        <tr v-for="ing in ingredients" :key="ing.id" class="hover:bg-gray-850 transition-colors">
                            <td class="p-4 font-bold text-slate-200">{{ ing.name }}</td>
                            <td class="p-4 font-mono font-bold">{{ parseFloat(ing.stock_quantity).toFixed(2) }} {{ ing.unit }}</td>
                            <td class="p-4 text-gray-400 font-mono">{{ parseFloat(ing.min_limit).toFixed(2) }} {{ ing.unit }}</td>
                            
                            <td class="p-4">
                                <span v-if="parseFloat(ing.stock_quantity) <= parseFloat(ing.min_limit)" class="bg-red-950 text-red-400 border border-red-900 text-xs px-2 py-1 rounded font-black uppercase tracking-wide">
                                    ⚠️ BRAKI
                                </span>
                                <span v-else class="bg-green-950 text-green-400 border border-green-900 text-xs px-2 py-1 rounded font-bold uppercase tracking-wide">
                                    OK
                                </span>
                            </td>
                            
                            <td class="p-4 text-gray-400 font-mono">{{ ing.purchase_price }} zł / {{ ing.unit }}</td>
                            
                            <td class="p-4 text-right space-x-2">
                                <button @click="openRestockModal(ing)" class="bg-gray-800 hover:bg-gray-700 text-xs font-bold py-1 px-2.5 rounded text-orange-400 border border-gray-700">
                                    + Dostawa
                                </button>
                                <button @click="openEditModal(ing)" class="bg-gray-800 hover:bg-blue-600 text-xs font-bold py-1 px-2.5 rounded text-blue-400 hover:text-white border border-gray-700">
                                    Edytuj
                                </button>
                                <button @click="deleteIngredient(ing.id)" class="bg-gray-800 hover:bg-red-600 text-xs font-bold py-1 px-2.5 rounded text-red-400 hover:text-white border border-gray-700">
                                    Usuń
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div v-if="selectedIngredient" class="fixed inset-0 bg-black/70 flex items-center justify-center p-4 backdrop-blur-sm z-50">
            <div class="bg-gray-900 border border-gray-800 rounded-xl p-6 w-full max-w-md shadow-2xl">
                <h3 class="text-lg font-black text-orange-400 mb-2 uppercase">Dostawa: {{ selectedIngredient.name }}</h3>
                <form @submit.prevent="submitRestock" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Ilość do dodania ({{ selectedIngredient.unit }})</label>
                        <input v-model="restockForm.amount" type="number" step="0.01" required class="w-full bg-gray-800 border border-gray-700 rounded p-2 text-sm text-white font-mono" />
                    </div>
                    <div class="flex space-x-3">
                        <button type="button" @click="selectedIngredient = null" class="w-1/2 bg-gray-800 py-2 rounded text-xs font-bold">Anuluj</button>
                        <button type="submit" class="w-1/2 bg-orange-600 py-2 rounded text-xs font-bold uppercase">Zatwierdź</button>
                    </div>
                </form>
            </div>
        </div>

        <div v-if="crudModalOpen" class="fixed inset-0 bg-black/70 flex items-center justify-center p-4 backdrop-blur-sm z-50">
            <div class="bg-gray-900 border border-gray-800 rounded-xl p-6 w-full max-w-md shadow-2xl">
                <h3 class="text-lg font-black text-orange-400 mb-4 uppercase">
                    {{ isEditMode ? 'Edycja parametrów surowca' : 'Dodawanie nowego surowca' }}
                </h3>
                
                <form @submit.prevent="submitCrudForm" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Nazwa Składnika</label>
                        <input v-model="crudForm.name" type="text" required placeholder="np. Kukurydza" class="w-full bg-gray-800 border border-gray-700 rounded p-2 text-sm text-white font-medium" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Jednostka miary</label>
                            <select v-model="crudForm.unit" class="w-full bg-gray-800 border border-gray-700 rounded p-2 text-sm text-white">
                                <option value="kg">kilogram (kg)</option>
                                <option value="l">litr (l)</option>
                                <option value="szt">sztuka (szt)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Cena zakupu j.</label>
                            <input v-model="crudForm.purchase_price" type="number" step="0.01" required class="w-full bg-gray-800 border border-gray-700 rounded p-2 text-sm text-white font-mono" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div v-if="!isEditMode">
                            <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Stan początkowy</label>
                            <input v-model="crudForm.stock_quantity" type="number" step="0.01" required class="w-full bg-gray-800 border border-gray-700 rounded p-2 text-sm text-white font-mono" />
                        </div>
                        <div :class="isEditMode ? 'col-span-2' : ''">
                            <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Minimum logistyczne</label>
                            <input v-model="crudForm.min_limit" type="number" step="0.01" required class="w-full bg-gray-800 border border-gray-700 rounded p-2 text-sm text-white font-mono" />
                        </div>
                    </div>

                    <div class="flex space-x-3 pt-2">
                        <button type="button" @click="crudModalOpen = false" class="w-1/2 bg-gray-800 py-2.5 rounded text-xs font-bold">Anuluj</button>
                        <button type="submit" :disabled="crudForm.processing" class="w-1/2 bg-orange-600 py-2.5 rounded text-xs font-bold uppercase">
                            {{ isEditMode ? 'Zapisz' : 'Utwórz surowiec' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
    </AuthenticatedLayout>
</template>