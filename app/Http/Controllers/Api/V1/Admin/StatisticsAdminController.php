<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\StatisticsService;

class StatisticsAdminController extends Controller
{
    public function __construct(private readonly StatisticsService $stats) {}

    public function show()
    {
        return response()->json([
            'data' => $this->stats->getCached(),
        ]);
    }
}
