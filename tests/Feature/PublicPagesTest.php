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
}
