<?php

namespace App\Enums;

enum FraudRuleType: string
{
    case MANY_RECIPIENTS_1H = 'many_recipients_1h';
    case NEAR_DAILY_LIMIT_80P = 'near_daily_limit_80p';
    case NIGHT_LARGE_TX = 'night_large_tx';
    case NEW_ACCOUNT_LARGE_TX = 'new_account_large_tx';
    case SAME_IP_MULTI_ACCOUNTS = 'same_ip_multi_accounts';
}
