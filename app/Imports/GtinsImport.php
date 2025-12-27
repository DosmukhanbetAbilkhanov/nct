<?php

namespace App\Imports;

use App\Models\Product;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class GtinsImport implements ToCollection
{
    protected Collection $gtins;

    public function __construct()
    {
        $this->gtins = collect();
    }

    /**
     * Parse Excel file and extract GTINs from column A (first column).
     */
    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            // Extract first column value (column A)
            $value = $row[0] ?? null;

            if ($value === null || $value === '') {
                continue;
            }

            // Convert to string and trim whitespace
            $gtin = trim((string) $value);

            // Validate GTIN format (13 digits, numeric)
            if (Product::isValidGtin($gtin)) {
                $this->gtins->push(Product::normalizeGtin($gtin));
            }
        }

        // Remove duplicates within file
        $this->gtins = $this->gtins->unique()->values();
    }

    /**
     * Get the validated and deduplicated GTINs.
     */
    public function getGtins(): Collection
    {
        return $this->gtins;
    }
}
