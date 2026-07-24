<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    // Zmieniamy na true, aby zezwolić na składanie zamówień
    public function authorize(): bool
    {
        return true;
    }

    // Definiujemy rygorystyczne reguły walidacji
    public function rules(): array
    {
        return [
            'type' => 'required|in:lokal,wynos,dostawa',
            'payment_method' => 'required|string',
            'delivery_address' => 'required_if:type,dostawa|nullable|string',
            
            // Sprawdzamy tablicę pozycji koszyka
            'items' => 'required|array|min:1',
            'items.*.product_variant_id' => 'required|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1',
            
            // Sprawdzamy tablicę opcjonalnych modyfikatorów
            'items.*.modifiers' => 'nullable|array',
            'items.*.modifiers.*.ingredient_id' => 'required|exists:ingredients,id',
            'items.*.modifiers.*.action' => 'required|in:ADD,REMOVE',
        ];
    }
}