<?php

namespace Tests\Feature;

use App\Http\Middleware\HandleInertiaRequests;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class SourceReviewTest extends TestCase
{
    public function test_public_source_register_uses_the_reviewed_registry(): void
    {
        $this->withHeader('X-Inertia', 'true')
            ->withHeader(
                'X-Inertia-Version',
                app(HandleInertiaRequests::class)->version(request()),
            )
            ->get('/data-sources')
            ->assertOk()
            ->assertJsonPath('component', 'public/data-sources')
            ->assertJsonPath('props.sources.0.key', 'postcodes_io')
            ->assertJsonPath('props.sources.0.label', 'Postcodes.io')
            ->assertJsonPath('props.sources.0.licence_url', 'https://postcodes.io/docs/licences/')
            ->assertJsonPath('props.sources.0.attribution', 'Preserve the applicable Ordnance Survey, Royal Mail, ONS and NRS acknowledgements stated by the source.')
            ->assertJsonMissingPath('props.sources.0.review_owner')
            ->assertJsonMissingPath('props.sources.0.review_topics');
    }

    public function test_scheduled_source_review_succeeds_when_all_reviews_are_current(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-29 12:00:00'));

        $this->artisan('ukapi:sources:review --due')
            ->expectsOutput('No source reviews are due.')
            ->assertSuccessful();
    }

    public function test_scheduled_source_review_fails_and_identifies_due_sources(): void
    {
        config()->set('source_reviews.sources.police_uk.last_reviewed_at', '2026-01-01');
        $this->travelTo(CarbonImmutable::parse('2026-10-29 12:00:00'));

        $this->artisan('ukapi:sources:review --due')
            ->expectsOutput('1 source review(s) require attention.')
            ->assertFailed();
    }

    public function test_source_review_rejects_an_invalid_as_of_date(): void
    {
        $this->artisan('ukapi:sources:review --due --as-of=not-a-date')
            ->expectsOutput('The source review date must be a valid ISO-8601 date.')
            ->assertFailed();
    }
}
