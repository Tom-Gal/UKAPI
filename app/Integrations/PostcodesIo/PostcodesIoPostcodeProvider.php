<?php

namespace App\Integrations\PostcodesIo;

use App\Domain\Geography\Coordinates;
use App\Domain\Geography\PostcodeData;
use App\Domain\Geography\UkPostcode;
use App\Integrations\Contracts\PostcodeProvider;
use App\Support\Api\ApiException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

final class PostcodesIoPostcodeProvider implements PostcodeProvider
{
    public function find(UkPostcode $postcode): PostcodeData
    {
        $response = $this->request()->get('postcodes/'.rawurlencode($postcode->compact()));

        if ($response->status() === 404) {
            throw ApiException::postcodeNotFound();
        }

        return $this->mapOne($response);
    }

    public function nearby(UkPostcode $postcode, int $limit, int $radiusMetres): array
    {
        $response = $this->request()->get(
            'postcodes/'.rawurlencode($postcode->compact()).'/nearest',
            ['limit' => $limit, 'radius' => $radiusMetres],
        );

        if ($response->status() === 404) {
            throw ApiException::postcodeNotFound();
        }

        return $this->mapMany($response);
    }

    public function reverse(Coordinates $coordinates, int $limit = 1): array
    {
        $response = $this->request()->get('postcodes', [
            'lon' => $coordinates->longitude,
            'lat' => $coordinates->latitude,
            'limit' => $limit,
        ]);

        return $this->mapMany($response);
    }

    private function request(): PendingRequest
    {
        $request = Http::baseUrl((string) config('ukapi.postcodes_io.base_url'))
            ->acceptJson()
            ->connectTimeout((int) config('ukapi.postcodes_io.connect_timeout_seconds'))
            ->timeout((int) config('ukapi.postcodes_io.timeout_seconds'))
            ->retry(1, 100, throw: false);

        $caBundle = config('ukapi.http_ca_bundle');

        return is_string($caBundle) && $caBundle !== ''
            ? $request->withOptions(['verify' => $caBundle])
            : $request;
    }

    private function mapOne(Response $response): PostcodeData
    {
        $result = $this->result($response);

        if (! is_array($result)) {
            throw ApiException::providerInvalidResponse();
        }

        try {
            return PostcodeData::fromPostcodesIo($result);
        } catch (InvalidArgumentException) {
            throw ApiException::providerInvalidResponse();
        }
    }

    /** @return list<PostcodeData> */
    private function mapMany(Response $response): array
    {
        $result = $this->result($response);

        if ($result === null) {
            return [];
        }

        $records = [];

        try {
            foreach ($result as $item) {
                if (! is_array($item)) {
                    throw new InvalidArgumentException('Postcodes.io returned a non-object result.');
                }

                $records[] = PostcodeData::fromPostcodesIo($item);
            }
        } catch (InvalidArgumentException) {
            throw ApiException::providerInvalidResponse();
        }

        return $records;
    }

    /** @return array<string, mixed>|null */
    private function result(Response $response): ?array
    {
        if (! $response->successful()) {
            throw ApiException::providerUnavailable();
        }

        $payload = $response->json();

        if (! is_array($payload) || ! array_key_exists('result', $payload)) {
            throw ApiException::providerInvalidResponse();
        }

        $result = $payload['result'];

        if ($result === null || is_array($result)) {
            return $result;
        }

        throw ApiException::providerInvalidResponse();
    }
}
