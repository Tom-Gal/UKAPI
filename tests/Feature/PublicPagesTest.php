<?php

namespace Tests\Feature;

use App\Domain\Billing\PlanCatalog;
use App\Http\Middleware\HandleInertiaRequests;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    #[DataProvider('publicProductPages')]
    public function test_public_product_page_can_be_rendered(string $routeName, array $parameters = []): void
    {
        $this->get(route($routeName, $parameters))->assertOk();
    }

    /** @return iterable<string, array{string, array<string, string>}> */
    public static function publicProductPages(): iterable
    {
        yield 'home' => ['home', []];
        yield 'api catalogue' => ['api-catalogue', []];
        yield 'pricing' => ['pricing', []];
        yield 'documentation' => ['docs', []];
        yield 'documentation section' => ['docs', ['section' => 'getting-started']];
        yield 'status' => ['status', []];
        yield 'terms' => ['terms', []];
        yield 'paid plan terms' => ['paid-plan-terms', []];
        yield 'privacy' => ['privacy', []];
        yield 'acceptable use' => ['acceptable-use', []];
        yield 'data sources' => ['data-sources', []];
    }

    public function test_documentation_section_is_passed_to_the_inertia_page(): void
    {
        $this->withHeader('X-Inertia', 'true')
            ->withHeader(
                'X-Inertia-Version',
                app(HandleInertiaRequests::class)->version(request()),
            )
            ->get('/docs/getting-started')
            ->assertOk()
            ->assertJsonPath('component', 'public/docs')
            ->assertJsonPath('props.section', 'getting-started');
    }

    public function test_public_pricing_uses_the_server_plan_catalogue_without_private_billing_fields(): void
    {
        $catalogue = app(PlanCatalog::class)->publicPlans();

        $this->withHeader('X-Inertia', 'true')
            ->withHeader(
                'X-Inertia-Version',
                app(HandleInertiaRequests::class)->version(request()),
            )
            ->get('/pricing')
            ->assertOk()
            ->assertJsonPath('component', 'public/pricing')
            ->assertJsonPath('props.plans', $catalogue)
            ->assertJsonMissingPath('props.plans.0.stripe_price')
            ->assertJsonMissingPath('props.plans.0.prices');
    }

    public function test_crawl_assets_are_publicly_available(): void
    {
        $robots = public_path('robots.txt');
        $sitemap = public_path('sitemap.xml');
        $llms = public_path('llms.txt');

        $this->assertFileExists($robots);
        $this->assertFileExists($sitemap);
        $this->assertFileExists($llms);
        $this->assertStringContainsString(
            'Sitemap: https://ukapi.co.uk/sitemap.xml',
            file_get_contents($robots),
        );
        $this->assertStringContainsString(
            'https://ukapi.co.uk/docs',
            file_get_contents($sitemap),
        );
        $this->assertStringContainsString(
            'A UK data API for developers',
            file_get_contents($llms),
        );
    }

    public function test_readiness_endpoint_is_not_indexable(): void
    {
        $this->get('/ready')->assertHeader('X-Robots-Tag', 'noindex');
    }
}
