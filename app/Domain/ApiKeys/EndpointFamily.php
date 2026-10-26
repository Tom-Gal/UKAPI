<?php

namespace App\Domain\ApiKeys;

final class EndpointFamily
{
    public static function forEndpoint(string $endpoint): string
    {
        foreach (self::definitions() as $family => $definition) {
            foreach ($definition['endpoint_prefixes'] as $prefix) {
                if (str_starts_with($endpoint, $prefix)) {
                    return $family;
                }
            }
        }

        return 'unknown';
    }

    /** @return list<array{key: string, label: string}> */
    public static function options(): array
    {
        $options = [];
        foreach (self::definitions() as $key => $definition) {
            $options[] = ['key' => $key, 'label' => $definition['label']];
        }

        return $options;
    }

    /** @return list<string> */
    public static function keys(): array
    {
        return array_keys(self::definitions());
    }

    public static function label(string $family): string
    {
        return self::definitions()[$family]['label'] ?? ucfirst($family);
    }

    /** @return array<string, array{label: string, endpoint_prefixes: list<string>}> */
    private static function definitions(): array
    {
        $definitions = config('ukapi.api_endpoint_families', []);

        return is_array($definitions) ? $definitions : [];
    }
}
