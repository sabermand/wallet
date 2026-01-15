<?php

namespace App\Enums;

enum TransactionStatus: string
{
    case COMPLETED = 'completed';
    case PENDING = 'pending';
    case FAILED = 'failed';
    case PENDING_REVIEW = 'pending_review';
    case REJECTED = 'rejected';
}
