<?php

namespace Tests\Feature;

use App\Domain\ApiKeys\EndpointFamily;
use Illuminate\Routing\Route;
use Symfony\Component\Yaml\Yaml;
use Tests\TestCase;

class ApiContractParityTest extends TestCase
{
    /** @var list<string> */
    private const HTTP_METHODS = [
        'delete',
        'get',
        'head',
        'options',
        'patch',
        'post',
        'put',
        'trace',
    ];

    public function test_runtime_routes_openapi_and_the_live_catalogue_expose_the_same_operations(): void
    {
        $runtime = $this->runtimeOperations();
        $openApi = $this->openApiOperations();
        $catalogue = $this->catalogueOperations();

        $this->assertSame(
            array_keys($runtime),
            array_keys($openApi),
            'The customer API routes and OpenAPI operations must match exactly.',
        );
        $this->assertSame(
            array_keys($runtime),
            array_keys($catalogue),
            'Only deployed customer API operations may be marked live in the public catalogue.',
        );
    }

    public function test_every_customer_route_has_a_stable_endpoint_code_and_usage_label(): void
    {
        $operations = $this->runtimeOperations();
        $labels = config('ukapi.usage_endpoint_labels', []);

        $this->assertIsArray($labels);

        $endpointCodes = [];

        foreach ($operations as $operation => $route) {
            $endpoint = $route->defaults['ukapi_endpoint'] ?? null;

            $this->assertIsString($endpoint, "{$operation} must define a ukapi_endpoint route default.");
            $this->assertMatchesRegularExpression(
                '/^[a-z][a-z0-9]*(?:\.[a-z][a-z0-9]*)+$/',
                $endpoint,
                "{$operation} must use a stable dotted endpoint code.",
            );
            $this->assertArrayHasKey($endpoint, $labels, "{$operation} endpoint [{$endpoint}] needs a usage label.");
            $this->assertIsString($labels[$endpoint]);
            $this->assertNotSame('', trim($labels[$endpoint]), "{$operation} endpoint [{$endpoint}] needs a non-empty usage label.");
            $this->assertNotSame('unknown', EndpointFamily::forEndpoint($endpoint), "{$operation} endpoint [{$endpoint}] needs an API-key scope family.");
            $this->assertMatchesRegularExpression(
                '/^api\.v1\.[a-z0-9.]+$/',
                (string) $route->getName(),
                "{$operation} needs a stable api.v1 route name.",
            );
            $endpointCodes[] = $endpoint;
        }

        sort($endpointCodes);
        $labelCodes = array_keys($labels);
        sort($labelCodes);
        $this->assertSame($endpointCodes, $labelCodes, 'Usage labels must match the deployed endpoint codes exactly.');
        $this->assertSame($endpointCodes, array_values(array_unique($endpointCodes)), 'Endpoint codes must be unique.');
    }

    public function test_documented_features_match_route_entitlements(): void
    {
        $openApi = $this->openApiOperations();
        $catalogue = $this->catalogueOperations();
        $declaredFeatures = $this->openApiDocument()['info']['x-ukapi-features'] ?? null;

        $this->assertIsArray($declaredFeatures);

        foreach ($this->runtimeOperations() as $operation => $route) {
            $feature = $this->routeFeature($route, $operation);

            $this->assertArrayHasKey($feature, $declaredFeatures, "{$operation} uses undocumented feature [{$feature}].");
            $this->assertSame(
                $feature,
                $openApi[$operation]['x-ukapi-feature'] ?? null,
                "{$operation} has an OpenAPI feature that differs from its route middleware.",
            );
            $this->assertSame(
                $feature,
                $catalogue[$operation],
                "{$operation} has a catalogue feature that differs from its route middleware.",
            );
        }
    }

    public function test_openapi_operation_ids_are_present_and_unique(): void
    {
        $operationIds = [];

        foreach ($this->openApiOperations() as $operation => $definition) {
            $operationId = $definition['operationId'] ?? null;
            $this->assertIsString($operationId, "{$operation} needs an OpenAPI operationId.");
            $this->assertNotSame('', trim($operationId), "{$operation} needs a non-empty OpenAPI operationId.");
            $operationIds[] = $operationId;
        }

        $this->assertSame(
            count($operationIds),
            count(array_unique($operationIds)),
            'OpenAPI operationId values must be unique.',
        );
    }

    /** @return array<string, Route> */
    private function runtimeOperations(): array
    {
        $operations = [];

        foreach (app('router')->getRoutes() as $route) {
            if (! str_starts_with($route->uri(), 'v1/')) {
                continue;
            }

            foreach (array_diff($route->methods(), ['HEAD']) as $method) {
                $operations[$this->operationKey($method, '/'.$route->uri())] = $route;
            }
        }

        ksort($operations);

        return $operations;
    }

    /** @return array<string, array<string, mixed>> */
    private function openApiOperations(): array
    {
        $paths = $this->openApiDocument()['paths'] ?? null;
        $this->assertIsArray($paths);

        $operations = [];

        foreach ($paths as $path => $pathItem) {
            $this->assertIsString($path);
            $this->assertIsArray($pathItem);

            foreach (array_intersect(array_keys($pathItem), self::HTTP_METHODS) as $method) {
                $operation = $pathItem[$method];
                $this->assertIsArray($operation, "OpenAPI operation {$method} {$path} must be an object.");
                $operations[$this->operationKey($method, $path)] = $operation;
            }
        }

        ksort($operations);

        return $operations;
    }

    /** @return array<string, string> */
    private function catalogueOperations(): array
    {
        $contents = file_get_contents(resource_path('js/lib/ukapi-content.ts'));
        $this->assertIsString($contents);

        $matched = preg_match_all(
            "/^\s*\{\R\s*method:\s*'([A-Z]+)',\R\s*path:\s*'([^']+)'/m",
            $contents,
            $matches,
            PREG_OFFSET_CAPTURE,
        );
        $this->assertGreaterThan(0, $matched, 'The endpoint catalogue must contain endpoint definitions.');

        $operations = [];

        foreach ($matches[0] as $index => [$match, $offset]) {
            $nextOffset = $matches[0][$index + 1][1] ?? strlen($contents);
            $definition = substr($contents, $offset, $nextOffset - $offset);

            if (! preg_match('/\blive:\s*true\b/', $definition)) {
                continue;
            }

            $method = $matches[1][$index][0];
            $path = explode('?', $matches[2][$index][0], 2)[0];
            $feature = preg_match("/\bfeature:\s*'([^']+)'/", $definition, $featureMatch)
                ? $featureMatch[1]
                : 'current_data';

            $operations[$this->operationKey($method, $path)] = $feature;
        }

        ksort($operations);

        return $operations;
    }

    /** @return array<string, mixed> */
    private function openApiDocument(): array
    {
        $document = Yaml::parseFile(base_path('openapi/openapi.yaml'));
        $this->assertIsArray($document);

        return $document;
    }

    private function routeFeature(Route $route, string $operation): string
    {
        $features = [];

        foreach ($route->gatherMiddleware() as $middleware) {
            if (str_starts_with($middleware, 'ukapi.feature:')) {
                $features[] = substr($middleware, strlen('ukapi.feature:'));
            }
        }

        $this->assertNotSame([], $features, "{$operation} must be protected by feature middleware.");

        return $features[array_key_last($features)];
    }

    private function operationKey(string $method, string $path): string
    {
        return strtoupper($method).' '.$path;
    }
}
