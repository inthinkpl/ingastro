<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RolePermission extends Model
{
    use HasFactory;

    protected $fillable = ['role', 'permission'];

    /**
     * Sprawdza czy dana rola posiada uprawnienie.
     */
    public static function hasPermission(string $role, string $permission): bool
    {
        if ($role === 'admin') {
            return true; // Admin zawsze ma pełny dostęp do wszystkiego
        }

        return self::where('role', $role)
            ->where('permission', $permission)
            ->exists();
    }
}