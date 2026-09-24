<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

final class HealthController extends Controller
{
    public function __invoke(Repository $cache): JsonResponse
    {
        try {
            DB::select('select 1');
            $cache->get('ukapi:health:readiness');
        } catch (\Throwable $exception) {
            report($exception);

            return response()
                ->json(['status' => 'unavailable'], 503)
                ->header('Cache-Control', 'no-store');
        }

        return response()
            ->json([
                'status' => 'ready',
                'version' => (string) config('app.version'),
            ])
            ->header('Cache-Control', 'no-store');
    }
}
