<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Companies\SicExport;
use App\Http\Controllers\Controller;
use App\Support\Api\ApiException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class SicExportController extends Controller
{
    public function __invoke(Request $request, SicExport $export): Response
    {
        // No arbitrary datasets, filters, pagination or user-selected output paths.
        if ($request->query->count() !== 0) {
            throw ApiException::invalidParameter('query', 'SIC export accepts no query parameters.');
        }

        $result = $export->download();

        return response($result['csv'], 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="sic-'.$result['version'].'.csv"',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
            'X-Snapshot-Version' => $result['version'],
            'X-Snapshot-Checksum' => $result['checksum'],
        ]);
    }
}
