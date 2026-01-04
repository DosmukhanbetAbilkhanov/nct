<?php

namespace App\Services;

use App\Models\Order;

class ReceiptService
{
    /**
     * Generate payment receipt PDF content.
     *
     * Note: PDF library (barryvdh/laravel-dompdf) will be installed in Phase 8.
     * This is a placeholder that will be implemented with actual PDF generation.
     */
    public function generatePaymentReceipt(Order $order): string
    {
        return '';
    }

    /**
     * Download payment receipt.
     *
     * Note: This will be implemented in Phase 8 with actual PDF download response.
     */
    public function downloadPaymentReceipt(Order $order)
    {
        return null;
    }
}
