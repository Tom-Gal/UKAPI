<?php

namespace Tests\Feature;

use App\Http\Middleware\HandleInertiaRequests;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    public function test_public_product_pages_can_be_rendered(): void
    {
        foreach ([
            route('home'),
            route('api-catalogue'),
            route('pricing'),
            route('docs'),
            route('docs', ['section' => 'getting-started']),
            route('status'),
            route('terms'),
            route('paid-plan-terms'),
            route('privacy'),
            route('acceptable-use'),
            route('data-sources'),
        ] as $url) {
            $this->get($url)->assertOk();
        }
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
