<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    // Jawne wskazanie tabeli w bazie danych (liczba mnoga)
    protected $table = 'system_settings';

    // Pola, które zezwalamy masowo zapisywać
    protected $fillable = ['key', 'value'];

    /**
     * Inteligentna funkcja pomocnicza do szybkiego wyciągania ustawień.
     * Zwraca wartość lub opcjonalny fallback, jeśli klucza nie ma jeszcze w bazie.
     */
    public static function get(string $key, $default = null)
    {
        $setting = self::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }
}