<?php

namespace App\Domain\ApiKeys;

use InvalidArgumentException;

final class IpAllowList
{
    /** @return list<string> */
    public static function fromText(?string $value): array
    {
        if (blank($value)) {
            return [];
        }

        $entries = preg_split('/[\s,]+/', trim((string) $value)) ?: [];

        return self::normalise($entries);
    }

    /** @param array<int, mixed> $entries
     * @return list<string>
     */
    public static function normalise(array $entries): array
    {
        if (count($entries) > 20) {
            throw new InvalidArgumentException('Use no more than 20 IP addresses or CIDR ranges.');
        }

        $normalised = [];
        foreach ($entries as $entry) {
            if (! is_string($entry) || blank($entry)) {
                continue;
            }

            [$address, $prefix] = array_pad(explode('/', trim($entry), 2), 2, null);
            $binary = @inet_pton($address);
            if ($binary === false) {
                throw new InvalidArgumentException("{$entry} is not a valid IP address or CIDR range.");
            }

            $bits = strlen($binary) * 8;
            $prefixLength = $prefix === null ? $bits : filter_var(
                $prefix,
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 0, 'max_range' => $bits]],
            );
            if ($prefixLength === false) {
                throw new InvalidArgumentException("{$entry} has an invalid CIDR prefix.");
            }

            $normalised[] = inet_ntop($binary).'/'.$prefixLength;
        }

        return array_values(array_unique($normalised));
    }

    /** @param list<string> $entries */
    public static function allows(?string $ip, array $entries): bool
    {
        if ($entries === []) {
            return true;
        }

        $candidate = is_string($ip) ? @inet_pton($ip) : false;
        if ($candidate === false) {
            return false;
        }

        foreach ($entries as $entry) {
            [$networkAddress, $prefix] = explode('/', $entry, 2);
            $network = @inet_pton($networkAddress);
            if ($network === false || strlen($network) !== strlen($candidate)) {
                continue;
            }

            $prefixLength = (int) $prefix;
            $fullBytes = intdiv($prefixLength, 8);
            $remainingBits = $prefixLength % 8;
            if (substr($candidate, 0, $fullBytes) !== substr($network, 0, $fullBytes)) {
                continue;
            }

            if ($remainingBits === 0) {
                return true;
            }

            $mask = (0xFF << (8 - $remainingBits)) & 0xFF;
            if ((ord($candidate[$fullBytes]) & $mask) === (ord($network[$fullBytes]) & $mask)) {
                return true;
            }
        }

        return false;
    }
}
