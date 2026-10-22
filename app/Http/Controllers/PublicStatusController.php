<?php

namespace App\Http\Controllers;

use App\Domain\Health\HealthStatus;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

final class PublicStatusController extends Controller
{
    public function __invoke(Request $request, HealthStatus $health): Response
    {
        $response = Inertia::render('public/status', [
            'services' => $health->services(),
            'measured_at' => now()->toIso8601String(),
        ])->toResponse($request);
        $response->headers->set('Cache-Control', 'no-store');

        return $response;
    }
}
