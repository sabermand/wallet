<?php

namespace App\Rules;

use App\Models\Wallet;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SufficientBalance implements ValidationRule
{
    public function __construct(
        private readonly Wallet $wallet,
        private readonly float $amount
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ((float)$this->wallet->balance < $this->amount) {
            $fail("Insufficient wallet balance.");
        }
    }
}
