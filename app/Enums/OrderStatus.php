<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case PaymentInitiated = 'payment_initiated';
    case Paid = 'paid';
    case Processing = 'processing';
    case Completed = 'completed';
    case Expired = 'expired';
    case Cancelled = 'cancelled';
}
