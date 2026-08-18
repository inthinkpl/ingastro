# ERP SAVONA - STAN PROJEKTU

## Standardy i Technologia
* **Backend:** Laravel / Inertia.js
* **Frontend:** Vue 3 (Composition API) + Tailwind CSS (`#0B0F19`) + Lucide Icons (`lucide-vue-next`)
* **Architektura:** Wzorzec Partials w `resources/js/Pages/[Module]/Partials/`

---

## Zrealizowane Moduły

### 1. Layout Główny (`AuthenticatedLayout.vue`)
* Ciemny motyw z podmenu под **Zarządzanie Zespołem** (*Pracownicy*, *Ewidencja RCP*).
* Widget RCP z licznikiem czasu na żywo (`Clock-In`) i przyciskami *Start*, *Pauza*, *Koniec*.
* Obsługa Sidebara oraz Topbara dla widoków pełnoekranowych (`hideSidebar: true`).

### 2. Rejestracja Czasu Pracy – RCP (`Pages/Admin/WorkTime/`)
* **Backend:** Tabela i model `WorkShift`, `RcpController.php`, udostępnianie `auth.active_shift` w middleware Inertia.
* **Partials:** `ActiveShiftsCards.vue`, `WorkHistoryTable.vue`, `ManualShiftModal.vue`.

### 3. Magazyn Surowców (`Pages/Manager/Inventory.vue`)
* **Partials:** `InventoryTable.vue`, `RestockModal.vue`, `IngredientCrudModal.vue`.
* Kontrola stanów, alerty minimów logistycznych, przyjmowanie dostaw.

### 4. Zarządzanie Zespołem (`Pages/Admin/Users.vue`)
* **Partials:** `UserTable.vue`, `UserFormModal.vue`.
* Rejestracja pracowników, modyfikacja haseł, odznaki ról RBAC.

### 5. Strefy Dostaw (`Pages/Manager/DeliveryZones.vue`)
* **Partials:** `ZoneCardsGrid.vue`, `DeliveryZoneModal.vue`.
* Parametry logistyczne stref, opłaty, darmowe dostawy od kwoty, domyślni kurierzy.

### 6. Rozliczenia Gotówkowe Kurierów (`Pages/Manager/Reconciliation.vue`)
* **Partials:** `ReconciliationList.vue`, `DriverOrdersTable.vue`.
* Wskaźniki KPI, rozwijany wykaz bonów, zamykanie raportów kasowych.

---

## Planowane Zadania
1. Integration Stref Dostaw z koszykiem sklepowym (`ShopController`, `OrderController`, koszyk Vue/Pinia).
2. Eksport Ewidencji RCP do pliku CSV/Excel dla księgowości.