<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Initiated = 'initiated';
    case Processing = 'processing';
    case Success = 'success';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
    case Expired = 'expired';
}
