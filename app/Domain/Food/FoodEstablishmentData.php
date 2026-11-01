<?php

namespace App\Domain\Food;

use InvalidArgumentException;

final readonly class FoodEstablishmentData
{
    /**
     * @param  array{line_1: ?string, line_2: ?string, line_3: ?string, line_4: ?string, postcode: ?string}  $address
     * @param  array{code: ?string, name: ?string, website: ?string, email: ?string}  $localAuthority
     * @param  array{latitude: ?float, longitude: ?float, distance_km: ?float}  $location
     * @param  array{hygiene: ?int, structural: ?int, confidence_in_management: ?int}  $scores
     * @param  array{key: ?string, value: ?string}  $rating
     */
    public function __construct(
        public int $id,
        public string $name,
        public ?string $businessType,
        public ?int $businessTypeId,
        public string $schemeType,
        public array $rating,
        public ?string $ratingDate,
        public bool $newRatingPending,
        public array $address,
        public ?string $phone,
        public array $localAuthority,
        public array $location,
        public array $scores,
        public ?string $rightToReply,
    ) {}

    /** @param array<string, mixed> $payload */
    public static function fromFsa(array $payload): self
    {
        $id = self::integer($payload['FHRSID'] ?? null);
        $name = self::string($payload['BusinessName'] ?? null);
        $scheme = self::string($payload['SchemeType'] ?? null);

        if ($id === null || $id < 1 || $name === null || $scheme === null) {
            throw new InvalidArgumentException('FSA establishment response is missing required fields.');
        }

        $geocode = is_array($payload['geocode'] ?? null) ? $payload['geocode'] : [];
        $scores = is_array($payload['scores'] ?? null) ? $payload['scores'] : [];

        return new self(
            id: $id,
            name: $name,
            businessType: self::string($payload['BusinessType'] ?? null),
            businessTypeId: self::integer($payload['BusinessTypeID'] ?? null),
            schemeType: $scheme,
            rating: [
                'key' => self::string($payload['RatingKey'] ?? null),
                'value' => self::string($payload['RatingValue'] ?? null),
            ],
            ratingDate: self::string($payload['RatingDate'] ?? null),
            newRatingPending: (bool) ($payload['NewRatingPending'] ?? false),
            address: [
                'line_1' => self::string($payload['AddressLine1'] ?? null),
                'line_2' => self::string($payload['AddressLine2'] ?? null),
                'line_3' => self::string($payload['AddressLine3'] ?? null),
                'line_4' => self::string($payload['AddressLine4'] ?? null),
                'postcode' => self::string($payload['PostCode'] ?? null),
            ],
            phone: self::string($payload['Phone'] ?? null),
            localAuthority: [
                'code' => self::string($payload['LocalAuthorityCode'] ?? null),
                'name' => self::string($payload['LocalAuthorityName'] ?? null),
                'website' => self::string($payload['LocalAuthorityWebSite'] ?? null),
                'email' => self::string($payload['LocalAuthorityEmailAddress'] ?? null),
            ],
            location: [
                'latitude' => self::number($geocode['latitude'] ?? null),
                'longitude' => self::number($geocode['longitude'] ?? null),
                'distance_km' => self::milesToKilometres($payload['Distance'] ?? null),
            ],
            scores: [
                'hygiene' => self::integer($scores['Hygiene'] ?? null),
                'structural' => self::integer($scores['Structural'] ?? null),
                'confidence_in_management' => self::integer($scores['ConfidenceInManagement'] ?? null),
            ],
            rightToReply: self::string($payload['RightToReply'] ?? null),
        );
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $address = is_array($data['address'] ?? null) ? $data['address'] : [];
        $authority = is_array($data['local_authority'] ?? null) ? $data['local_authority'] : [];
        $location = is_array($data['location'] ?? null) ? $data['location'] : [];
        $scores = is_array($data['scores'] ?? null) ? $data['scores'] : [];
        $rating = is_array($data['rating'] ?? null) ? $data['rating'] : [];
        $id = self::integer($data['id'] ?? null);
        $name = self::string($data['name'] ?? null);
        $scheme = self::string($data['scheme_type'] ?? null);

        if ($id === null || $id < 1 || $name === null || $scheme === null) {
            throw new InvalidArgumentException('Cached food establishment data is incomplete.');
        }

        return new self(
            id: $id,
            name: $name,
            businessType: self::string($data['business_type'] ?? null),
            businessTypeId: self::integer($data['business_type_id'] ?? null),
            schemeType: $scheme,
            rating: [
                'key' => self::string($rating['key'] ?? null),
                'value' => self::string($rating['value'] ?? null),
            ],
            ratingDate: self::string($data['rating_date'] ?? null),
            newRatingPending: (bool) ($data['new_rating_pending'] ?? false),
            address: [
                'line_1' => self::string($address['line_1'] ?? null),
                'line_2' => self::string($address['line_2'] ?? null),
                'line_3' => self::string($address['line_3'] ?? null),
                'line_4' => self::string($address['line_4'] ?? null),
                'postcode' => self::string($address['postcode'] ?? null),
            ],
            phone: self::string($data['phone'] ?? null),
            localAuthority: [
                'code' => self::string($authority['code'] ?? null),
                'name' => self::string($authority['name'] ?? null),
                'website' => self::string($authority['website'] ?? null),
                'email' => self::string($authority['email'] ?? null),
            ],
            location: [
                'latitude' => self::number($location['latitude'] ?? null),
                'longitude' => self::number($location['longitude'] ?? null),
                'distance_km' => self::number($location['distance_km'] ?? null),
            ],
            scores: [
                'hygiene' => self::integer($scores['hygiene'] ?? null),
                'structural' => self::integer($scores['structural'] ?? null),
                'confidence_in_management' => self::integer($scores['confidence_in_management'] ?? null),
            ],
            rightToReply: self::string($data['right_to_reply'] ?? null),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'business_type' => $this->businessType,
            'business_type_id' => $this->businessTypeId,
            'scheme_type' => $this->schemeType,
            'rating' => $this->rating,
            'rating_date' => $this->ratingDate,
            'new_rating_pending' => $this->newRatingPending,
            'address' => $this->address,
            'phone' => $this->phone,
            'local_authority' => $this->localAuthority,
            'location' => $this->location,
            'scores' => $this->scores,
            'right_to_reply' => $this->rightToReply,
        ];
    }

    private static function string(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }

    private static function integer(mixed $value): ?int
    {
        return is_int($value) || (is_string($value) && ctype_digit($value)) || is_float($value)
            ? (int) $value
            : null;
    }

    private static function number(mixed $value): ?float
    {
        return is_numeric($value) && is_finite((float) $value) ? (float) $value : null;
    }

    private static function milesToKilometres(mixed $value): ?float
    {
        $miles = self::number($value);

        return $miles === null ? null : round($miles * 1.609344, 3);
    }
}
