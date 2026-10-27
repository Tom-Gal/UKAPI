<?php

declare(strict_types=1);

/**
 * Independent production checks for the scheduled GitHub Actions monitor.
 * Response bodies and authorization values are never written to output.
 */
if (! extension_loaded('curl')) {
    fwrite(STDERR, "The cURL PHP extension is required.\n");
    exit(2);
}

$webBaseUrl = baseUrl('SYNTHETIC_WEB_BASE_URL', 'https://ukapi.co.uk');
$apiBaseUrl = baseUrl('SYNTHETIC_API_BASE_URL', 'https://api.ukapi.co.uk');
$apiKey = getenv('SYNTHETIC_API_KEY');

if (! is_string($apiKey) || trim($apiKey) === '') {
    fwrite(STDERR, "SYNTHETIC_API_KEY is required.\n");
    exit(2);
}

$publicMaxMs = integerEnvironment('SYNTHETIC_PUBLIC_MAX_MS', 2500, 100, 30_000);
$dependencyMaxMs = integerEnvironment('SYNTHETIC_DEPENDENCY_MAX_MS', 3500, 100, 30_000);
$apiMaxMs = integerEnvironment('SYNTHETIC_API_MAX_MS', 6000, 100, 30_000);
$attempts = integerEnvironment('SYNTHETIC_ATTEMPTS', 3, 2, 5);
$retryDelaySeconds = integerEnvironment('SYNTHETIC_RETRY_DELAY_SECONDS', 15, 1, 60);

$checks = [
    ['name' => 'public website', 'url' => $webBaseUrl.'/', 'max_ms' => $publicMaxMs, 'content_type' => 'text/html'],
    ['name' => 'liveness', 'url' => $webBaseUrl.'/up', 'max_ms' => $publicMaxMs, 'content_type' => 'text/html'],
    ['name' => 'readiness', 'url' => $webBaseUrl.'/ready', 'max_ms' => $dependencyMaxMs, 'content_type' => 'application/json'],
    ['name' => 'OpenAPI', 'url' => $apiBaseUrl.'/openapi/v1.yaml', 'max_ms' => $publicMaxMs, 'content_type' => 'yaml'],
    ['name' => 'sign in', 'url' => $webBaseUrl.'/login', 'max_ms' => $publicMaxMs, 'content_type' => 'text/html'],
    ['name' => 'registration', 'url' => $webBaseUrl.'/register', 'max_ms' => $publicMaxMs, 'content_type' => 'text/html'],
    [
        'name' => 'synthetic postcode API',
        'url' => $apiBaseUrl.'/v1/postcodes/SW1A1AA',
        'max_ms' => $apiMaxMs,
        'content_type' => 'application/json',
        'authorization' => 'Bearer '.trim($apiKey),
    ],
];

$failed = false;
foreach ($checks as $check) {
    $passed = false;
    for ($attempt = 1; $attempt <= $attempts; $attempt++) {
        $result = performCheck($check);
        if ($result['passed']) {
            printf("[ok] %s: HTTP %d in %dms\n", $check['name'], $result['status'], $result['elapsed_ms']);
            $passed = true;
            break;
        }

        fprintf(
            STDERR,
            "[attempt %d/%d] %s failed: %s\n",
            $attempt,
            $attempts,
            $check['name'],
            $result['reason'],
        );

        if ($attempt < $attempts) {
            sleep($retryDelaySeconds);
        }
    }

    if (! $passed) {
        $failed = true;
    }
}

exit($failed ? 1 : 0);

/** @param array{name: string, url: string, max_ms: int, content_type: string, authorization?: string} $check
 * @return array{passed: bool, status: int, elapsed_ms: int, reason: string}
 */
function performCheck(array $check): array
{
    $handle = curl_init($check['url']);
    $headers = [
        'Accept: */*',
        'User-Agent: UKApi.co.uk production synthetic monitor/1.0',
    ];
    if (isset($check['authorization'])) {
        $headers[] = 'Authorization: '.$check['authorization'];
    }

    curl_setopt_array($handle, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
    ]);

    $body = curl_exec($handle);
    $error = curl_error($handle);
    $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
    $elapsedMs = (int) round((float) curl_getinfo($handle, CURLINFO_TOTAL_TIME) * 1000);
    $contentType = mb_strtolower((string) curl_getinfo($handle, CURLINFO_CONTENT_TYPE));
    curl_close($handle);

    if (! is_string($body)) {
        return ['passed' => false, 'status' => $status, 'elapsed_ms' => $elapsedMs, 'reason' => $error !== '' ? $error : 'request failed'];
    }

    if ($status !== 200) {
        return ['passed' => false, 'status' => $status, 'elapsed_ms' => $elapsedMs, 'reason' => 'unexpected HTTP '.$status];
    }

    if (! str_contains($contentType, mb_strtolower($check['content_type']))) {
        return ['passed' => false, 'status' => $status, 'elapsed_ms' => $elapsedMs, 'reason' => 'unexpected content type'];
    }

    if ($elapsedMs > $check['max_ms']) {
        return ['passed' => false, 'status' => $status, 'elapsed_ms' => $elapsedMs, 'reason' => 'latency '.$elapsedMs.'ms exceeds '.$check['max_ms'].'ms'];
    }

    return ['passed' => true, 'status' => $status, 'elapsed_ms' => $elapsedMs, 'reason' => ''];
}

function baseUrl(string $name, string $default): string
{
    $value = getenv($name);
    $value = is_string($value) && trim($value) !== '' ? trim($value) : $default;
    $parts = parse_url($value);

    if (! is_array($parts) || ($parts['scheme'] ?? null) !== 'https' || ! isset($parts['host'])
        || isset($parts['query']) || isset($parts['fragment']) || isset($parts['user']) || isset($parts['pass'])) {
        fwrite(STDERR, $name." must be an HTTPS origin.\n");
        exit(2);
    }

    return rtrim($value, '/');
}

function integerEnvironment(string $name, int $default, int $minimum, int $maximum): int
{
    $value = getenv($name);
    if (! is_string($value) || filter_var($value, FILTER_VALIDATE_INT) === false) {
        return $default;
    }

    return max($minimum, min($maximum, (int) $value));
}
