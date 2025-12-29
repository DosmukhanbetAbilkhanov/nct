<?php

namespace App\Exports;

use App\Models\ImportBatch;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class FailedGtinsExport implements FromCollection, WithHeadings, WithMapping
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
            ->where('status', 'failed')
            ->get();
    }

    /**
     * Map each failed item to Excel row.
     */
    public function map($item): array
    {
        return [
            $item->gtin,
            $item->error_message ?? 'Unknown error',
            $item->created_at?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Define column headings.
     */
    public function headings(): array
    {
        return [
            'GTIN',
            'Error Message',
            'Failed At',
        ];
    }
}
