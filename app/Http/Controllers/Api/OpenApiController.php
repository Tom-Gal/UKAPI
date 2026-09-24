<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class OpenApiController extends Controller
{
    public function __invoke(): BinaryFileResponse
    {
        return response()->file(
            base_path('openapi/openapi.yaml'),
            ['Content-Type' => 'application/yaml; charset=UTF-8'],
        );
    }
}
