export type Endpoint = {
    method: 'GET';
    path: string;
    examplePath?: string;
    exampleResponse?: string;
    live?: boolean;
    parameters?: {
        name: string;
        location: 'path' | 'query';
        required: boolean;
        description: string;
    }[];
    description: string;
    coverage: string;
    source: string;
    freshness: string;
};

export type EndpointFamily = {
    slug: string;
    name: string;
    summary: string;
    endpoints: Endpoint[];
};

export const endpointFamilies: EndpointFamily[] = [
    {
        slug: 'postcodes',
        name: 'Postcodes & location',
        summary: 'Canonical postcodes, local authorities and coordinates.',
        endpoints: [
            {
                method: 'GET',
                path: '/v1/postcodes/{postcode}',
                examplePath: '/v1/postcodes/BL2%206XX',
                exampleResponse: `{
  "data": {
    "postcode": "BL2 6XX",
    "outcode": "BL2",
    "incode": "6XX",
    "country": "England",
    "region": "North West",
    "latitude": 53.5922,
    "longitude": -2.4117,
    "local_authority": { "name": "Bolton", "code": "E08000001" }
  },
  "meta": {
    "request_id": "req_01…",
    "cached": false,
    "source": "postcodes_io",
    "source_updated_at": null,
    "coverage": "UK"
  }
}`,
                live: true,
                parameters: [
                    {
                        name: 'postcode',
                        location: 'path',
                        required: true,
                        description:
                            'A UK postcode. Spacing and case are normalised; for example, bl2 6xx becomes BL2 6XX.',
                    },
                ],
                description:
                    'Look up canonical postcode, coordinates and selected geographic references.',
                coverage: 'UK',
                source: 'Postcodes.io',
                freshness: 'Up to 30 days',
            },
            {
                method: 'GET',
                path: '/v1/postcodes/{postcode}/validate',
                examplePath: '/v1/postcodes/BL2%206XX/validate',
                exampleResponse: `{
  "data": { "postcode": "BL2 6XX", "valid": true },
  "meta": { "request_id": "req_01…", "cached": true, "source": "postcodes_io", "source_updated_at": null, "coverage": "UK" }
}`,
                live: true,
                parameters: [
                    {
                        name: 'postcode',
                        location: 'path',
                        required: true,
                        description: 'A syntactically valid UK postcode.',
                    },
                ],
                description:
                    'Check whether a normalised UK postcode has a provider record.',
                coverage: 'UK',
                source: 'Postcodes.io',
                freshness: 'Up to 30 days',
            },
            {
                method: 'GET',
                path: '/v1/postcodes/{postcode}/nearby',
                examplePath:
                    '/v1/postcodes/BL2%206XX/nearby?limit=5&radius=500',
                live: true,
                parameters: [
                    {
                        name: 'postcode',
                        location: 'path',
                        required: true,
                        description: 'The postcode used as the search origin.',
                    },
                    {
                        name: 'limit',
                        location: 'query',
                        required: false,
                        description:
                            'Number of results, from 1 to 100. Defaults to 10.',
                    },
                    {
                        name: 'radius',
                        location: 'query',
                        required: false,
                        description:
                            'Search radius in metres, from 1 to 2,000. Defaults to 100.',
                    },
                ],
                description: 'Find nearby postcodes with distance in metres.',
                coverage: 'UK',
                source: 'Postcodes.io',
                freshness: 'Up to 30 days',
            },
            {
                method: 'GET',
                path: '/v1/coordinates/{latitude}/{longitude}/postcode',
                examplePath: '/v1/coordinates/53.5922/-2.4117/postcode',
                live: true,
                parameters: [
                    {
                        name: 'latitude',
                        location: 'path',
                        required: true,
                        description: 'WGS84 latitude from -90 to 90.',
                    },
                    {
                        name: 'longitude',
                        location: 'path',
                        required: true,
                        description: 'WGS84 longitude from -180 to 180.',
                    },
                ],
                description:
                    'Return the nearest postcode to WGS84 coordinates.',
                coverage: 'UK',
                source: 'Postcodes.io',
                freshness: 'Up to 30 days',
            },
            {
                method: 'GET',
                path: '/v1/outcodes/{outcode}',
                description: 'Look up an outcode.',
                coverage: 'UK',
                source: 'Postcodes.io',
                freshness: 'Up to 30 days',
            },
        ],
    },
    {
        slug: 'councils',
        name: 'Councils',
        summary: 'A council identity that other UK data can join to.',
        endpoints: [
            {
                method: 'GET',
                path: '/v1/councils/postcode/{postcode}',
                description: 'Resolve council from a postcode.',
                coverage: 'UK',
                source: 'Postcodes.io + council metadata',
                freshness: 'Up to 30 days',
            },
            {
                method: 'GET',
                path: '/v1/councils/{code}',
                description: 'Look up a council by code.',
                coverage: 'UK',
                source: 'Council metadata',
                freshness: 'Up to 30 days',
            },
        ],
    },
    {
        slug: 'companies',
        name: 'Companies & SIC',
        summary:
            'Simplified Companies House information and SIC reference data.',
        endpoints: [
            {
                method: 'GET',
                path: '/v1/companies/{companyNumber}',
                examplePath: '/v1/companies/SC012345',
                exampleResponse: `{
  "data": {
    "company_number": "SC012345",
    "name": "Example Technology Ltd",
    "status": "active",
    "type": "ltd",
    "jurisdiction": "scotland",
    "incorporated_on": "2019-04-12",
    "sic_codes": ["62012", "63110"]
  },
  "meta": {
    "request_id": "req_01…",
    "cached": false,
    "stale": false,
    "source": "companies_house",
    "source_updated_at": null,
    "coverage": "UK"
  }
}`,
                live: true,
                parameters: [
                    {
                        name: 'companyNumber',
                        location: 'path',
                        required: true,
                        description:
                            'A Companies House company number. Letter prefixes and whitespace are normalised; for example, sc 012345 becomes SC012345.',
                    },
                ],
                description:
                    'Get a simplified company profile with status, dates, SIC codes and registered office details.',
                coverage: 'UK',
                source: 'Companies House',
                freshness: '15 minutes',
            },
            {
                method: 'GET',
                path: '/v1/companies/{companyNumber}/officers',
                examplePath:
                    '/v1/companies/SC012345/officers?page=1&per_page=25',
                live: true,
                parameters: [
                    {
                        name: 'companyNumber',
                        location: 'path',
                        required: true,
                        description:
                            'The company whose officers are requested.',
                    },
                    {
                        name: 'page',
                        location: 'query',
                        required: false,
                        description: 'One-based result page. Defaults to 1.',
                    },
                    {
                        name: 'per_page',
                        location: 'query',
                        required: false,
                        description:
                            'Results per page, from 1 to 100. Defaults to 25.',
                    },
                ],
                description:
                    'List officers with UKAPI.io pagination. The response intentionally excludes date-of-birth data.',
                coverage: 'UK',
                source: 'Companies House',
                freshness: '1 hour',
            },
            {
                method: 'GET',
                path: '/v1/companies/{companyNumber}/filings',
                examplePath:
                    '/v1/companies/SC012345/filings?page=1&per_page=25',
                exampleResponse: `{
  "data": [
    {
      "transaction_id": "MzAwMDAwMDAwMGFkaXF6a2N4",
      "category": "accounts",
      "type": "AA",
      "filed_on": "2025-03-31",
      "description": "accounts-with-accounts-type-full",
      "pages": 12
    }
  ],
  "meta": {
    "request_id": "req_01…",
    "cached": false,
    "stale": false,
    "source": "companies_house",
    "source_updated_at": null,
    "coverage": "UK",
    "pagination": { "page": 1, "per_page": 25, "total": 42 }
  }
}`,
                live: true,
                parameters: [
                    {
                        name: 'companyNumber',
                        location: 'path',
                        required: true,
                        description:
                            'The company whose filing-history metadata is requested.',
                    },
                    {
                        name: 'page',
                        location: 'query',
                        required: false,
                        description: 'One-based result page. Defaults to 1.',
                    },
                    {
                        name: 'per_page',
                        location: 'query',
                        required: false,
                        description:
                            'Results per page, from 1 to 100. Defaults to 25.',
                    },
                ],
                description:
                    'List filing metadata with UKAPI.io pagination. Filing-document links, barcodes and document contents are not relayed.',
                coverage: 'UK',
                source: 'Companies House',
                freshness: '15 minutes',
            },
            {
                method: 'GET',
                path: '/v1/companies/search?q={query}',
                examplePath:
                    '/v1/companies/search?q=example&page=1&per_page=25',
                live: true,
                parameters: [
                    {
                        name: 'q',
                        location: 'query',
                        required: true,
                        description:
                            'Company-name search text from 2 to 200 characters.',
                    },
                    {
                        name: 'page',
                        location: 'query',
                        required: false,
                        description: 'One-based result page. Defaults to 1.',
                    },
                    {
                        name: 'per_page',
                        location: 'query',
                        required: false,
                        description:
                            'Results per page, from 1 to 100. Defaults to 25.',
                    },
                ],
                description:
                    'Search companies using a stable, source-agnostic result shape.',
                coverage: 'UK',
                source: 'Companies House',
                freshness: '15 minutes',
            },
            {
                method: 'GET',
                path: '/v1/sic/{code}',
                examplePath: '/v1/sic/62012',
                exampleResponse: `{
  "data": {
    "code": "62012",
    "description": "Business and domestic software development"
  },
  "meta": {
    "request_id": "req_01…",
    "cached": false,
    "source": "companies_house_sic_2007",
    "source_updated_at": "2026-09-24T12:00:00+00:00",
    "coverage": "UK",
    "reference_version": "companies_house_condensed_sic_2007"
  }
}`,
                live: true,
                parameters: [
                    {
                        name: 'code',
                        location: 'path',
                        required: true,
                        description:
                            'A five-digit condensed SIC 2007 code. Whitespace is removed.',
                    },
                ],
                description:
                    'Look up a code from the locally refreshed Companies House condensed SIC 2007 reference.',
                coverage: 'UK',
                source: 'Companies House SIC 2007 snapshot',
                freshness: 'Local snapshot; result cache 24 hours',
            },
            {
                method: 'GET',
                path: '/v1/sic/search?q={query}',
                examplePath: '/v1/sic/search?q=software&page=1&per_page=25',
                live: true,
                parameters: [
                    {
                        name: 'q',
                        location: 'query',
                        required: true,
                        description:
                            'A code or description search term from 2 to 200 characters.',
                    },
                    {
                        name: 'page',
                        location: 'query',
                        required: false,
                        description: 'One-based result page. Defaults to 1.',
                    },
                    {
                        name: 'per_page',
                        location: 'query',
                        required: false,
                        description:
                            'Results per page, from 1 to 100. Defaults to 25.',
                    },
                ],
                description:
                    'Search the locally refreshed Companies House condensed SIC 2007 reference by code or description.',
                coverage: 'UK',
                source: 'Companies House SIC 2007 snapshot',
                freshness: 'Local snapshot; result cache 24 hours',
            },
        ],
    },
    {
        slug: 'dates',
        name: 'Dates & utilities',
        summary:
            'Bank holidays and deliberately small UK business-date utilities.',
        endpoints: [
            {
                method: 'GET',
                path: '/v1/bank-holidays',
                description: 'Get bank holidays for every division.',
                coverage: 'UK',
                source: 'GOV.UK',
                freshness: '24 hours',
            },
            {
                method: 'GET',
                path: '/v1/bank-holidays/{division}',
                description: 'Get holidays for one division.',
                coverage: 'UK',
                source: 'GOV.UK',
                freshness: '24 hours',
            },
            {
                method: 'GET',
                path: '/v1/working-days?from=&to=&division=',
                description: 'Calculate working days.',
                coverage: 'UK divisions',
                source: 'UKAPI.io',
                freshness: 'Calculated',
            },
            {
                method: 'GET',
                path: '/v1/tax-years/current',
                description: 'Get the current tax-year identifier.',
                coverage: 'UK',
                source: 'UKAPI.io',
                freshness: 'Calculated',
            },
            {
                method: 'GET',
                path: '/v1/tax-years/{year}',
                description: 'Get tax-year boundaries.',
                coverage: 'UK',
                source: 'UKAPI.io',
                freshness: 'Calculated',
            },
            {
                method: 'GET',
                path: '/v1/vat/calculate?amount=&rate=',
                examplePath: '/v1/vat/calculate?amount=100.00&rate=20.00',
                exampleResponse: `{
  "data": {
    "amount": "100.00",
    "rate_percent": "20.00",
    "vat_amount": "20.00",
    "total_amount": "120.00",
    "currency": "GBP"
  },
  "meta": {
    "request_id": "req_01…",
    "cached": false,
    "source": "ukapi",
    "coverage": "UK"
  }
}`,
                live: true,
                description: 'Calculate VAT.',
                coverage: 'UK',
                source: 'UKAPI.io',
                freshness: 'Calculated',
            },
            {
                method: 'GET',
                path: '/v1/vat/remove?amount=&rate=',
                examplePath: '/v1/vat/remove?amount=120.00&rate=20.00',
                exampleResponse: `{
  "data": {
    "gross_amount": "120.00",
    "rate_percent": "20.00",
    "vat_amount": "20.00",
    "net_amount": "100.00",
    "currency": "GBP"
  },
  "meta": {
    "request_id": "req_01…",
    "cached": false,
    "source": "ukapi",
    "coverage": "UK"
  }
}`,
                live: true,
                description: 'Remove VAT.',
                coverage: 'UK',
                source: 'UKAPI.io',
                freshness: 'Calculated',
            },
        ],
    },
    {
        slug: 'food-hygiene',
        name: 'Food hygiene',
        summary:
            'FHRS and FHIS results without flattening their different schemes.',
        endpoints: [
            {
                method: 'GET',
                path: '/v1/food-establishments/{id}',
                description: 'Get an establishment.',
                coverage: 'UK',
                source: 'Food Standards Agency',
                freshness: '6 hours',
            },
            {
                method: 'GET',
                path: '/v1/food-establishments/search?name=&postcode=&rating=',
                description: 'Search establishments.',
                coverage: 'UK',
                source: 'Food Standards Agency',
                freshness: '6 hours',
            },
            {
                method: 'GET',
                path: '/v1/food-establishments/nearby?postcode=&radius=',
                description: 'Find nearby establishments.',
                coverage: 'UK',
                source: 'Food Standards Agency',
                freshness: '6 hours',
            },
        ],
    },
    {
        slug: 'crime',
        name: 'Crime',
        summary:
            'Postcode-friendly summaries of approximate street-level data.',
        endpoints: [
            {
                method: 'GET',
                path: '/v1/crime/nearby?postcode=&month=',
                description: 'Find nearby crime records.',
                coverage: 'Provider-specific partial coverage',
                source: 'Police.uk',
                freshness: '24 hours',
            },
            {
                method: 'GET',
                path: '/v1/crime/summary?postcode=&month=',
                description: 'Get a postcode crime summary.',
                coverage: 'Provider-specific partial coverage',
                source: 'Police.uk',
                freshness: '24 hours',
            },
            {
                method: 'GET',
                path: '/v1/crime/categories',
                description: 'List crime categories.',
                coverage: 'Provider-specific partial coverage',
                source: 'Police.uk',
                freshness: 'Provider dependent',
            },
        ],
    },
    {
        slug: 'flood',
        name: 'Flood information',
        summary: 'Warnings and station data with explicit source timestamps.',
        endpoints: [
            {
                method: 'GET',
                path: '/v1/flood/warnings',
                description: 'List flood warnings and alerts.',
                coverage: 'England',
                source: 'Environment Agency',
                freshness: '5 minutes',
            },
            {
                method: 'GET',
                path: '/v1/flood/nearby?postcode=',
                description: 'Find nearby flood information.',
                coverage: 'England',
                source: 'Environment Agency',
                freshness: '5 minutes',
            },
            {
                method: 'GET',
                path: '/v1/flood/stations/nearby?postcode=',
                description: 'Find nearby stations.',
                coverage: 'England',
                source: 'Environment Agency',
                freshness: '5 minutes',
            },
            {
                method: 'GET',
                path: '/v1/flood/stations/{id}/readings',
                description: 'Get station readings.',
                coverage: 'England',
                source: 'Environment Agency',
                freshness: '5 minutes',
            },
        ],
    },
    {
        slug: 'planning',
        name: 'Planning constraints',
        summary:
            'Curated planning constraints, not a substitute for official searches.',
        endpoints: [
            {
                method: 'GET',
                path: '/v1/planning/constraints?postcode=',
                description: 'Find planning constraints by postcode.',
                coverage: 'England',
                source: 'Planning Data (beta)',
                freshness: '24 hours',
            },
            {
                method: 'GET',
                path: '/v1/planning/constraints?lat=&lng=',
                description: 'Find planning constraints by coordinates.',
                coverage: 'England',
                source: 'Planning Data (beta)',
                freshness: '24 hours',
            },
        ],
    },
];

export const plans = [
    {
        name: 'Free',
        price: '£0',
        quota: '5,000',
        description: 'Evaluation and hobby projects.',
        emphasis: false,
    },
    {
        name: 'Hobby',
        price: '£4.99',
        quota: '50,000',
        description: 'Small applications.',
        emphasis: false,
    },
    {
        name: 'Pro',
        price: '£12.99',
        quota: '250,000',
        description: 'Production SaaS and agencies.',
        emphasis: true,
    },
    {
        name: 'Scale',
        price: '£29.99',
        quota: '1,000,000',
        description: 'Higher-volume small businesses.',
        emphasis: false,
    },
];

export const documentationGuides = [
    ['quickstart', 'Quickstart'],
    ['authentication', 'Authentication'],
    ['errors', 'Errors'],
    ['rate-limits', 'Rate limits'],
    ['pagination', 'Pagination'],
    ['sources-and-coverage', 'Sources & coverage'],
] as const;
