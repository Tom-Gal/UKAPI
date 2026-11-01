<?php

namespace Tests\Unit;

use App\Domain\Support\RequestLogRecorder;
use Tests\TestCase;

final class RequestLogRecorderTest extends TestCase
{
    public function test_cache_state_is_classified_from_safe_response_metadata_only(): void
    {
        $recorder = app(RequestLogRecorder::class);

        $this->assertSame('fresh', $recorder->cacheStateFromResponse(response()->json([
            'meta' => ['cached' => false, 'stale' => false],
        ])));
        $this->assertSame('hit', $recorder->cacheStateFromResponse(response()->json([
            'meta' => ['cached' => true, 'stale' => false],
        ])));
        $this->assertSame('stale', $recorder->cacheStateFromResponse(response()->json([
            'meta' => ['cached' => true, 'stale' => true],
        ])));
        $this->assertNull($recorder->cacheStateFromResponse(response()->json([
            'error' => ['code' => 'upstream_unavailable'],
        ], 503)));
    }
}
