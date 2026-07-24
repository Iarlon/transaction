<?php

namespace App\Enums;

enum TransactionStatus: string
{
    case Processing = 'processing';
    case Approved = 'approved';
    case Denied = 'denied';

    public function label(): string
    {
        return match ($this) {
            self::Processing => 'Em processamento',
            self::Approved => 'Aprovada',
            self::Denied => 'Negada',
        };
    }
}
