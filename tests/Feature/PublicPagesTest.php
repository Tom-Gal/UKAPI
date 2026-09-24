<?php

namespace Tests\Feature;

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
            route('docs.section', 'postcodes'),
            route('docs.endpoint', ['family' => 'postcodes', 'endpoint' => 1]),
            route('status'),
            route('terms'),
            route('privacy'),
            route('acceptable-use'),
            route('data-sources'),
        ] as $url) {
            $this->get($url)->assertOk();
        }
    }
}
