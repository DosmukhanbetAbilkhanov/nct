<?php

namespace App\Exports;

use App\Models\ImportBatch;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SuccessfulProductsExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(
        protected ImportBatch $batch
    ) {}

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $this->batch->items()
            ->where('status', 'success')
            ->with('product')
            ->get()
            ->pluck('product')
            ->filter(); // Remove nulls
    }

    /**
     * Map each product to Excel row.
     */
    public function map($product): array
    {
        return [
            $product->gtin,
            $product->ntin,
            $product->nameKk,
            $product->nameRu,
            $product->nameEn,
            $product->shortNameKk,
            $product->shortNameRu,
            $product->shortNameEn,
            $product->createdDate,
            $product->updatedDate,
            $product->created_at?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Define column headings.
     */
    public function headings(): array
    {
        return [
            'GTIN',
            'NTIN',
            'Name (Kazakh)',
            'Name (Russian)',
            'Name (English)',
            'Short Name (Kazakh)',
            'Short Name (Russian)',
            'Short Name (English)',
            'Created Date (API)',
            'Updated Date (API)',
            'Imported At',
        ];
    }
    
}
