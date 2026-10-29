<?php

namespace Tests\Feature;

use App\Domain\ApiKeys\EndpointFamily;
use App\Models\ApiKey;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use ReflectionMethod;
use ReflectionNamedType;
use Symfony\Component\Yaml\Yaml;
use Tests\TestCase;

class ApiContractParityTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_openapi_parameters_match_runtime_routes_and_form_requests(): void
    {
        $openApi = $this->openApiDocument();

        foreach ($this->runtimeOperations() as $operation => $route) {
            $definition = $this->openApiOperations()[$operation] ?? null;
            $this->assertIsArray($definition, "{$operation} needs an OpenAPI operation definition.");

            $parameters = $this->operationParameters($definition, $openApi);
            $parameterMap = [];

            foreach ($parameters as $parameter) {
                $name = $parameter['name'] ?? null;
                $location = $parameter['in'] ?? null;

                $this->assertIsString($name, "{$operation} has a parameter without a name.");
                $this->assertContains($location, ['path', 'query'], "{$operation} has an unsupported parameter location.");

                $key = $location.':'.$name;
                $this->assertArrayNotHasKey($key, $parameterMap, "{$operation} declares {$key} more than once.");
                $parameterMap[$key] = $parameter;
            }

            $runtimePathParameters = $this->routeParameterNames($route);
            $documentedPathParameters = array_keys(array_filter(
                $parameterMap,
                static fn (array $parameter): bool => $parameter['in'] === 'path',
            ));
            $documentedPathParameters = array_map(
                static fn (string $key): string => substr($key, strlen('path:')),
                $documentedPathParameters,
            );
            sort($runtimePathParameters);
            sort($documentedPathParameters);

            $this->assertSame(
                $runtimePathParameters,
                $documentedPathParameters,
                "{$operation} OpenAPI path parameters must match the runtime route placeholders.",
            );

            foreach ($runtimePathParameters as $name) {
                $this->assertTrue(
                    (bool) ($parameterMap['path:'.$name]['required'] ?? false),
                    "{$operation} path parameter [{$name}] must be required in OpenAPI.",
                );
            }

            $rules = $this->routeFormRequestRules($route);
            $runtimeParameters = [];

            foreach ($rules as $name => $ruleSet) {
                $this->assertIsArray($ruleSet, "{$operation} runtime validation rules for [{$name}] must be an array.");
                $location = in_array($name, $runtimePathParameters, true) ? 'path' : 'query';
                $key = $location.':'.$name;

                $this->assertArrayHasKey(
                    $key,
                    $parameterMap,
                    "{$operation} runtime validation field [{$name}] is missing from OpenAPI.",
                );
                $runtimeParameters[$key] = true;

                $normalisedRules = array_map(static fn (mixed $rule): string => (string) $rule, $ruleSet);
                $isRequired = in_array('required', $normalisedRules, true);

                $this->assertSame(
                    $isRequired,
                    (bool) ($parameterMap[$key]['required'] ?? false),
                    "{$operation} parameter [{$name}] requiredness differs between runtime validation and OpenAPI.",
                );
            }

            foreach ($parameterMap as $key => $parameter) {
                if ($parameter['in'] === 'query') {
                    $this->assertArrayHasKey(
                        $key,
                        $runtimeParameters,
                        "{$operation} OpenAPI query parameter [{$parameter['name']}] has no runtime validation rule.",
                    );
                }
            }
        }
    }

    public function test_openapi_operations_use_reusable_success_and_error_contracts(): void
    {
        $openApi = $this->openApiDocument();
        $operations = $this->openApiOperations();

        foreach ($operations as $operation => $definition) {
            $this->assertArrayHasKey('200', $definition['responses'] ?? [], "{$operation} needs a 200 response.");

            $successResponse = $definition['responses']['200'];
            $this->assertIsArray($successResponse, "{$operation} 200 response must be an object.");
            $successContent = $successResponse['content'] ?? null;
            $this->assertIsArray($successContent, "{$operation} 200 response needs content metadata.");

            $successMediaType = str_starts_with($operation, 'GET /v1/sic/export')
                ? 'text/csv'
                : 'application/json';
            $this->assertArrayHasKey($successMediaType, $successContent, "{$operation} needs {$successMediaType} success content.");

            foreach ([
                'X-Request-Id',
                'RateLimit-Limit',
                'RateLimit-Remaining',
                'RateLimit-Reset',
                'X-Quota-Limit',
                'X-Quota-Remaining',
                'X-Quota-Reset',
            ] as $header) {
                $this->assertArrayHasKey(
                    $header,
                    $successResponse['headers'] ?? [],
                    "{$operation} 200 response must declare the runtime header [{$header}].",
                );
            }

            if ($successMediaType === 'application/json') {
                $schemaReference = $successContent[$successMediaType]['schema']['$ref'] ?? null;
                $this->assertIsString($schemaReference, "{$operation} JSON success content needs a schema reference.");
                $this->assertMatchesRegularExpression(
                    '~^#/components/schemas/[A-Za-z][A-Za-z0-9]*Envelope$~',
                    $schemaReference,
                    "{$operation} should use a named response envelope schema.",
                );
            }

            foreach ([
                '401' => 'InvalidApiKey',
                '403' => 'Forbidden',
                '429' => 'RateLimited',
            ] as $status => $responseName) {
                $reference = $definition['responses'][$status]['$ref'] ?? null;

                $this->assertSame(
                    '#/components/responses/'.$responseName,
                    $reference,
                    "{$operation} must use the reusable {$status} response.",
                );
            }

            $parameters = $this->operationParameters($definition, $openApi);
            if ($parameters !== []) {
                $this->assertSame(
                    '#/components/responses/InvalidParameter',
                    $definition['responses']['422']['$ref'] ?? null,
                    "{$operation} with parameters must document the reusable validation response.",
                );
            }
        }

        $rateLimited = $openApi['components']['responses']['RateLimited']['headers'] ?? null;
        $this->assertIsArray($rateLimited);
        $this->assertSame(
            [
                'X-Request-Id',
                'RateLimit-Limit',
                'RateLimit-Remaining',
                'RateLimit-Reset',
                'X-Quota-Limit',
                'X-Quota-Remaining',
                'X-Quota-Reset',
                'Retry-After',
            ],
            array_keys($rateLimited),
            'The reusable rate/quota response must declare every throttling header in the runtime contract.',
        );

        $usageUnavailable = $openApi['components']['responses']['UsageUnavailable']['headers'] ?? null;
        $this->assertIsArray($usageUnavailable);
        $this->assertArrayHasKey('Retry-After', $usageUnavailable);
    }

    public function test_representative_runtime_responses_match_documented_openapi_schemas(): void
    {
        [, $token] = $this->createApiKey();
        $openApi = $this->openApiDocument();

        $calculation = $this->getJson('/v1/vat/calculate?amount=99.99&rate=20', [
            'Authorization' => "Bearer {$token}",
        ])->assertOk();

        $this->assertJsonMatchesSchema(
            $calculation->json(),
            $this->successSchemaFor('GET /v1/vat/calculate', $openApi),
            $openApi,
            'GET /v1/vat/calculate',
        );

        $removal = $this->getJson('/v1/vat/remove?amount=120.00&rate=20', [
            'Authorization' => "Bearer {$token}",
        ])->assertOk();

        $this->assertJsonMatchesSchema(
            $removal->json(),
            $this->successSchemaFor('GET /v1/vat/remove', $openApi),
            $openApi,
            'GET /v1/vat/remove',
        );

        $invalid = $this->getJson('/v1/vat/calculate?amount=invalid&rate=20', [
            'Authorization' => "Bearer {$token}",
        ])->assertUnprocessable();

        $this->assertJsonMatchesSchema(
            $invalid->json(),
            $this->resolveOpenApiReference('#/components/schemas/ErrorEnvelope', $openApi),
            $openApi,
            'GET /v1/vat/calculate validation error',
        );
    }

    public function test_runtime_validation_bounds_and_examples_match_openapi(): void
    {
        $openApi = $this->openApiDocument();

        foreach ($this->openApiOperations() as $operation => $definition) {
            foreach ($this->operationParameters($definition, $openApi) as $parameter) {
                $schema = $parameter['schema'] ?? null;
                $this->assertIsArray($schema, "{$operation} parameter [{$parameter['name']}] needs a schema.");

                $example = $parameter['example'] ?? null;
                $pattern = $schema['pattern'] ?? null;
                if (is_string($example) && is_string($pattern)) {
                    $this->assertSame(
                        1,
                        preg_match('~'.$pattern.'~', $example),
                        "{$operation} parameter [{$parameter['name']}] example must match its OpenAPI pattern.",
                    );
                }
            }
        }

        foreach ($this->runtimeOperations() as $operation => $route) {
            $definition = $this->openApiOperations()[$operation] ?? [];
            $parameterMap = [];
            foreach ($this->operationParameters($definition, $openApi) as $parameter) {
                $parameterMap[$parameter['in'].':'.$parameter['name']] = $parameter;
            }

            $pathParameters = $this->routeParameterNames($route);
            foreach ($this->routeFormRequestRules($route) as $name => $ruleSet) {
                $this->assertIsArray($ruleSet);
                $location = in_array($name, $pathParameters, true) ? 'path' : 'query';
                $key = $location.':'.$name;
                $this->assertArrayHasKey($key, $parameterMap, "{$operation} runtime field [{$name}] needs an OpenAPI parameter.");

                $schema = $parameterMap[$key]['schema'] ?? null;
                $this->assertIsArray($schema, "{$operation} parameter [{$name}] needs an OpenAPI schema.");
                $this->assertValidationRulesMatchSchema($operation, $name, $ruleSet, $schema);
            }
        }

        $perPage = $this->resolveOpenApiReference('#/components/parameters/PerPage', $openApi);
        $perPageSchema = $perPage['schema'] ?? null;
        $this->assertIsArray($perPageSchema);
        $this->assertSame('max_page_size', $perPageSchema['x-ukapi-plan-bound'] ?? null);

        $documentedMaximum = $perPageSchema['maximum'] ?? null;
        $this->assertIsInt($documentedMaximum);

        $plans = [config('ukapi.free_plan'), ...array_values(config('ukapi.paid_plans', []))];
        foreach ($plans as $plan) {
            $this->assertIsArray($plan);
            $this->assertLessThanOrEqual(
                $documentedMaximum,
                (int) ($plan['max_page_size'] ?? 0),
                'Every plan page-size limit must fit within the OpenAPI hard maximum.',
            );
        }
    }

    /** @return array<string, Route> */
    private function runtimeOperations(): array
    {
        $operations = [];

        foreach (app('router')->getRoutes()->get() as $route) {
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

    /**
     * @param  array<string, mixed>  $operation
     * @param  array<string, mixed>  $openApi
     * @return list<array<string, mixed>>
     */
    private function operationParameters(array $operation, array $openApi): array
    {
        $parameters = $operation['parameters'] ?? [];
        $this->assertIsArray($parameters);

        $resolved = [];

        foreach ($parameters as $parameter) {
            $this->assertIsArray($parameter);

            if (isset($parameter['$ref'])) {
                $reference = $parameter['$ref'];
                $this->assertIsString($reference);
                $parameter = $this->resolveOpenApiReference($reference, $openApi);
            }

            $resolved[] = $parameter;
        }

        return $resolved;
    }

    /**
     * @param  array<string, mixed>  $openApi
     * @return array<string, mixed>
     */
    private function resolveOpenApiReference(string $reference, array $openApi): array
    {
        $this->assertStringStartsWith('#/', $reference);
        $value = $openApi;

        foreach (explode('/', substr($reference, 2)) as $segment) {
            $segment = str_replace(['~1', '~0'], ['/', '~'], $segment);
            $this->assertIsArray($value);
            $this->assertArrayHasKey($segment, $value, "OpenAPI reference [{$reference}] cannot be resolved.");
            $value = $value[$segment];
        }

        $this->assertIsArray($value, "OpenAPI reference [{$reference}] must resolve to an object.");

        return $value;
    }

    /**
     * @param  array<string, mixed>  $openApi
     * @return array<string, mixed>
     */
    private function successSchemaFor(string $operation, array $openApi): array
    {
        $definition = $this->openApiOperations()[$operation] ?? null;
        $this->assertIsArray($definition);
        $schema = $definition['responses']['200']['content']['application/json']['schema'] ?? null;
        $this->assertIsArray($schema);

        if (isset($schema['$ref'])) {
            $this->assertIsString($schema['$ref']);

            return $this->resolveOpenApiReference($schema['$ref'], $openApi);
        }

        return $schema;
    }

    /**
     * @param  array<string, mixed>  $schema
     * @param  array<string, mixed>  $openApi
     */
    private function assertJsonMatchesSchema(mixed $value, array $schema, array $openApi, string $path): void
    {
        if (isset($schema['$ref'])) {
            $this->assertIsString($schema['$ref']);
            $this->assertJsonMatchesSchema(
                $value,
                $this->resolveOpenApiReference($schema['$ref'], $openApi),
                $openApi,
                $path,
            );

            return;
        }

        $type = $schema['type'] ?? null;
        if ($type === 'object') {
            $this->assertIsArray($value, "{$path} must be an object.");
            foreach ($schema['required'] ?? [] as $required) {
                $this->assertIsString($required);
                $this->assertArrayHasKey($required, $value, "{$path}.{$required} is required.");
            }
            foreach ($schema['properties'] ?? [] as $name => $propertySchema) {
                if (array_key_exists($name, $value)) {
                    $this->assertIsArray($propertySchema);
                    $this->assertJsonMatchesSchema($value[$name], $propertySchema, $openApi, "{$path}.{$name}");
                }
            }
        } elseif ($type === 'array') {
            $this->assertIsArray($value, "{$path} must be an array.");
            if (isset($schema['items'])) {
                $this->assertIsArray($schema['items']);
                foreach ($value as $index => $item) {
                    $this->assertJsonMatchesSchema($item, $schema['items'], $openApi, "{$path}.{$index}");
                }
            }
        } elseif ($type === 'string') {
            $this->assertIsString($value, "{$path} must be a string.");
        } elseif ($type === 'integer') {
            $this->assertIsInt($value, "{$path} must be an integer.");
        } elseif ($type === 'number') {
            $this->assertIsFloat($value, "{$path} must be numeric.");
        } elseif ($type === 'boolean') {
            $this->assertIsBool($value, "{$path} must be a boolean.");
        }

        if (array_key_exists('const', $schema)) {
            $this->assertSame($schema['const'], $value, "{$path} must equal its documented constant.");
        }
    }

    /**
     * @param  array<int, mixed>  $rules
     * @param  array<string, mixed>  $schema
     */
    private function assertValidationRulesMatchSchema(string $operation, string $name, array $rules, array $schema): void
    {
        $schemaType = $schema['type'] ?? null;

        foreach (array_map(static fn (mixed $rule): string => (string) $rule, $rules) as $rule) {
            if ($rule === 'string' || $rule === 'integer') {
                $this->assertSame($rule, $schemaType, "{$operation} parameter [{$name}] type differs from runtime validation.");
            }

            if (preg_match('/^(min|max|size):(\d+)$/', $rule, $matches) === 1) {
                $bound = (int) $matches[2];
                if ($matches[1] === 'size') {
                    $this->assertSame($bound, $schema['minLength'] ?? null, "{$operation} parameter [{$name}] needs its runtime minimum length.");
                    $this->assertSame($bound, $schema['maxLength'] ?? null, "{$operation} parameter [{$name}] needs its runtime maximum length.");
                } elseif ($schemaType === 'string') {
                    $schemaBound = $matches[1] === 'min' ? ($schema['minLength'] ?? null) : ($schema['maxLength'] ?? null);
                    $this->assertSame($bound, $schemaBound, "{$operation} parameter [{$name}] string length differs from runtime validation.");
                }
            }

            if (preg_match('/^between:(-?\d+),(-?\d+)$/', $rule, $matches) === 1) {
                $minimum = (int) $matches[1];
                $maximum = (int) $matches[2];

                if ($schemaType === 'integer' || $schemaType === 'number') {
                    $this->assertSame($minimum, $schema['minimum'] ?? null, "{$operation} parameter [{$name}] minimum differs from runtime validation.");
                    $this->assertSame($maximum, $schema['maximum'] ?? null, "{$operation} parameter [{$name}] maximum differs from runtime validation.");
                } else {
                    $validation = $schema['x-ukapi-validation'] ?? null;
                    $this->assertIsArray($validation, "{$operation} parameter [{$name}] needs machine-readable runtime bounds.");
                    $this->assertSame($minimum, $validation['numeric_minimum'] ?? null, "{$operation} parameter [{$name}] numeric minimum differs from runtime validation.");
                    $this->assertSame($maximum, $validation['numeric_maximum'] ?? null, "{$operation} parameter [{$name}] numeric maximum differs from runtime validation.");
                }
            }

            if (str_starts_with($rule, 'regex:/')) {
                $lastDelimiter = strrpos($rule, '/');
                $pattern = substr($rule, strlen('regex:/'), $lastDelimiter - strlen('regex:/'));
                $flags = substr($rule, $lastDelimiter + 1);

                $this->assertIsString($schema['pattern'] ?? null, "{$operation} parameter [{$name}] needs its runtime regex pattern.");
                if ($flags === '') {
                    $this->assertSame($pattern, $schema['pattern'], "{$operation} parameter [{$name}] regex differs from runtime validation.");
                }
            }

            if (str_starts_with($rule, 'date_format:')) {
                $this->assertIsString($schema['pattern'] ?? null, "{$operation} parameter [{$name}] date format needs a documented pattern.");
            }
        }
    }

    /** @return list<string> */
    private function routeParameterNames(Route $route): array
    {
        preg_match_all('/\{([^}:]+)(?::[^}]+)?}/', $route->uri(), $matches);

        return array_map(static fn (string $name): string => $name, $matches[1]);
    }

    /** @return array<string, mixed> */
    private function routeFormRequestRules(Route $route): array
    {
        $controllerClass = $route->getControllerClass();
        if (! is_string($controllerClass) || ! class_exists($controllerClass)) {
            return [];
        }

        $actionMethod = $route->getActionMethod();
        if (! method_exists($controllerClass, $actionMethod)) {
            $actionMethod = '__invoke';
        }

        $method = new ReflectionMethod($controllerClass, $actionMethod);
        foreach ($method->getParameters() as $parameter) {
            $type = $parameter->getType();
            if (! $type instanceof ReflectionNamedType || $type->isBuiltin()) {
                continue;
            }

            $requestClass = $type->getName();
            if (! is_a($requestClass, FormRequest::class, true)) {
                continue;
            }

            $request = new $requestClass;
            if (! method_exists($request, 'rules')) {
                return [];
            }

            $rules = $request->rules();
            if (! is_array($rules)) {
                return [];
            }

            return $rules;
        }

        return [];
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

        $last = array_key_last($features);
        $this->assertIsInt($last);

        return $features[$last];
    }

    private function operationKey(string $method, string $path): string
    {
        return strtoupper($method).' '.$path;
    }

    /** @return array{0: ApiKey, 1: string} */
    private function createApiKey(): array
    {
        $user = User::factory()->create();
        $secret = Str::lower(Str::random(43));
        $apiKey = $user->apiKeys()->create([
            'public_id' => 'k_'.Str::lower(Str::random(10)),
            'secret_hash' => Hash::make($secret),
            'name' => 'API contract test key',
            'environment' => 'test',
            'status' => 'active',
        ]);

        return [$apiKey, "uk_test_{$apiKey->public_id}.{$secret}"];
    }
}
