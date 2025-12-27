<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'gtin',
        'ntin',
        'nameKk',
        'nameRu',
        'nameEn',
        'shortNameKk',
        'shortNameRu',
        'shortNameEn',
        'createdDate',
        'updatedDate',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Validate GTIN format (13 digits, numeric only).
     */
    public static function isValidGtin(string $gtin): bool
    {
        return preg_match('/^\d{13}$/', $gtin) === 1;
    }

    /**
     * Normalize GTIN by trimming whitespace.
     */
    public static function normalizeGtin(string $gtin): string
    {
        return trim($gtin);
    }
}
