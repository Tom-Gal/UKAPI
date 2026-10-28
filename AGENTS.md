# AGENTS.md

## Purpose and scope

This file is the working contract for contributors and coding agents operating in this repository. It applies to the whole repository unless a more specific `AGENTS.md` is added in a subdirectory.

UKApi.co.uk is a live, production-facing Laravel application and versioned UK data API. Treat changes as production changes: preserve stable external contracts, protect customer data and credentials, keep documentation aligned with behaviour, and verify work in proportion to its risk.

## Instruction and source-of-truth order

When project sources disagree, use this order:

1. The current user request and any narrower `AGENTS.md`.
2. Executable behaviour: tests, current code, migrations, routes, configuration, and lockfiles.
3. `openapi/openapi.yaml`, `DEPLOYMENT.md`, and current public documentation.
4. `UKApi.co.uk_Senior_Engineer_Report.md` and `UKApi.co.uk_MVP_Report.md` as architecture and product intent.
5. Historical chat decisions and old generated reports.

The two reports are useful background, but some details are intentionally superseded. In particular, the live baseline is PHP 8.4.1+, Laravel 13, React 19, MySQL in Laravel Cloud, and Redis through Laravel's Redis cache driver. Do not revert current code to older PHP, Redis, endpoint, pricing, or product assumptions merely because a report mentions them.

## Product in one paragraph

UKApi.co.uk gives developers one account, one API key, one billing relationship, and one predictable `/v1` JSON contract over useful UK datasets. It is an independent normalisation and reliability layer over authoritative public or first-party sources, not the owner of those datasets and not a government service. The core promise is stable, source-aware, well-documented data access despite inconsistent upstream authentication, schemas, coverage, freshness, and reliability.

## Product principles

- Public contracts are ours; upstream contracts are not. Never expose a provider payload directly.
- Prefer a modular monolith. Do not introduce a service boundary, queue, or abstraction without a concrete operational need.
- Keep each external source replaceable behind a contract and provider adapter.
- Schema stability is more valuable than mirroring every upstream field.
- Geographic coverage, source, freshness, cache state, and known limitations are product data, not fine print.
- Fail in a bounded and understandable way. Every upstream call needs timeouts, controlled retries where safe, local throttling, and an explicit cache/fallback policy.
- Keep billing network calls out of the API request hot path. Runtime access decisions use local entitlements.
- Meter consistently at the API gateway and fail closed if durable accounting is unavailable.
- Make a first successful request possible within minutes. Documentation must be practical, scannable, and accurate.
- Do not imply affiliation with or endorsement by the UK Government or any source provider.

## Current technology and deployment baseline

- Backend: PHP `^8.4.1`, Laravel 13, Fortify, Passkeys, Cashier, Wayfinder, PHPUnit 12, Larastan/PHPStan level 7, Pint.
- Frontend: React 19, TypeScript with strict checking, Inertia 3, Tailwind CSS 4, Radix primitives, Lucide icons, Vite Plus.
- Production: Laravel Cloud, MySQL, HTTPS, scheduler enabled, and a shared Redis cache/rate-limit store.
- Local and CI tests: SQLite in memory, array cache, synchronous queue, and array mailer unless a test overrides them.
- Billing: Stripe Checkout, Customer Portal, Cashier webhooks, monthly and annual GBP prices.
- Delivery branch: `master`. A push can trigger deployment, so never push or deploy unless the user explicitly asks.
- Public website: `https://ukapi.co.uk`.
- Intended API host: `https://api.ukapi.co.uk`; the version prefix belongs in the path (`/v1`).
- Product spelling in prose: `UKApi.co.uk`. `UKAPI` is acceptable for the logo, legal entity default, internal identifiers, and all-caps headings where already established.
- Never reintroduce `ukapi.io`. Do not alter unrelated third-party names such as Postcodes.io.

## Repository map

| Path                                | Responsibility                                                                                          |
| ----------------------------------- | ------------------------------------------------------------------------------------------------------- |
| `app/Domain`                        | Source-independent domain services, immutable DTOs/value objects, usage, billing, health, and snapshots |
| `app/Integrations/Contracts`        | Interfaces for replaceable upstream providers                                                           |
| `app/Integrations/*`                | Provider-specific HTTP/authentication/schema mapping                                                    |
| `app/Http/Controllers/Api/V1`       | Thin public API transport layer                                                                         |
| `app/Http/Requests/Api/V1`          | Validation, bounds, and request normalisation                                                           |
| `app/Http/Middleware`               | API keys, feature access, rate limits, quotas, request IDs, security headers                            |
| `app/Support/Api`                   | Canonical response envelope, exceptions, pagination                                                     |
| `app/Models`                        | Eloquent persistence models only; keep upstream mapping out of them                                     |
| `app/Console/Commands`              | Explicit operational, import, provisioning, health, and maintenance commands                            |
| `config/ukapi.php`                  | Plans, features, provider configuration, limits, endpoint usage labels                                  |
| `database/migrations`               | Forward-only schema history; production is MySQL                                                        |
| `openapi/openapi.yaml`              | Public API contract distributed at `/openapi/v1.yaml`                                                   |
| `resources/js/lib/ukapi-content.ts` | Shared endpoint catalogue and documentation content                                                     |
| `resources/js/pages/public`         | Public catalogue, docs, pricing, status, legal, and source-register pages                               |
| `resources/js/pages/dashboard`      | Authenticated billing, usage, and account surfaces                                                      |
| `resources/js/components/ui`        | Reusable UI primitives; avoid local copies of existing components                                       |
| `resources/js/content`              | Structured legal-policy content rendered by the shared legal page                                       |
| `resources/views/emails`            | Branded HTML and plain-text transactional templates                                                     |
| `resources/reference`               | Bundled, versioned reference snapshots such as SIC 2007                                                 |
| `tests/Fixtures`                    | Sanitised provider payload fixtures; no live credentials or personal data                               |
| `tests/Feature`                     | Behavioural and integration-boundary tests                                                              |
| `DEPLOYMENT.md`                     | Laravel Cloud release and environment runbook                                                           |
| `HEALTH_MONITORING.md`              | Health sampling and operator alerting runbook                                                           |
| `ROADMAP.md`                        | Prioritised, evidence-based work still to be completed                                                  |
| `public/branding`                   | Approved SVG/PNG brand exports and usage notes                                                          |

## Architecture boundaries

Use the following request flow for a provider-backed API endpoint:

`route -> API middleware -> FormRequest/value object -> controller -> domain service -> provider contract -> provider adapter`

The return path must be:

`provider response -> provider-specific parsing -> source-independent DTO -> domain result/cache metadata -> ApiResponse`

Rules:

- Controllers coordinate. They do not authenticate to providers, build cache keys, decode arbitrary provider payloads, or contain business rules.
- Form Requests validate query/body input. Value objects normalise and validate route identifiers such as postcodes, company numbers, dates, coordinates, registrations, and station IDs.
- Domain services own semantic caching, stale/negative-cache policy, source-independent behaviour, and controlled error mapping.
- Provider adapters own URL construction, upstream authentication, request timeouts, bounded retries, provider throttles, and provider schema mapping.
- Provider interfaces return typed domain values, not `Response`, raw arrays, or vendor objects.
- DTOs and value objects should be `final`, and `readonly` when mutation is unnecessary. Prefer named constructors such as `from()`, `fromArray()`, and explicit `toArray()` methods.
- Validate cached data when hydrating it. If cache contents no longer match the expected DTO schema, evict the entry and return `upstream_invalid_response`; do not silently return malformed data.
- Bind interfaces to adapters in `AppServiceProvider`.

## Public API contract

### Routes and identifiers

- All customer endpoints live under `/v1` and use plural, resource-oriented paths. Use action-like suffixes only for derived operations such as `/validate`, `/changes`, or `/export`.
- Route names follow `api.v1.<family>.<action>`.
- Every metered route must set a stable dotted `ukapi_endpoint` default such as `companies.search` or `flood.stations.readings`.
- Add a matching human label to `ukapi.usage_endpoint_labels`; the usage dashboard must not fall back to opaque identifiers for a newly shipped endpoint.
- Put more specific routes before parameter catch-alls, for example `sic/search` before `sic/{code}`.
- Use feature middleware for plan-gated capabilities. Current-data access, change feeds, and bulk export are entitlements, not controller conditionals.

### Authentication and middleware order

The order on `/v1` is deliberate:

1. `AuthenticateApiKey`
2. current-data feature check
3. `EnforceApiRateLimit`
4. `EnforceMonthlyQuota`
5. `TrackFirstApiSuccess`

Do not reorder these without updating and running the authentication, quota, concurrency, onboarding, and feature-entitlement tests.

- Customer keys use `Authorization: Bearer ...`.
- Persist only the key hash. The plaintext secret may be shown once at creation and must never be logged, emailed, or redisplayed.
- Invalid/revoked-key attempts are separately limited by client IP to bound hash/database work.
- All keys belonging to one user share the account's hard monthly quota.
- Quotas reset at the start of a UTC calendar month. Annual billing does not change this.
- A request is reserved durably before controller validation/provider work. Admitted validation errors therefore count; quota-rejected requests do not.
- Durable accounting failures return `usage_unavailable`/503. Never grant unmetered access as a fallback.
- Rate and quota headers are part of the contract. Preserve their spelling and semantics.

### Response envelopes

Successful responses use `ApiResponse::success()`:

```json
{
    "data": {},
    "meta": {
        "request_id": "req_..."
    }
}
```

Errors use `ApiException` and `ApiResponse::error()`:

```json
{
    "error": {
        "code": "stable_machine_code",
        "message": "A safe human-readable message.",
        "status": 422,
        "request_id": "req_..."
    }
}
```

- Never return ad hoc JSON shapes from a `/v1` controller.
- Never expose exception messages, stack traces, provider bodies, credentials, internal URLs, or database details.
- Reuse an existing stable error code when its meaning matches. Add a named `ApiException` constructor for a genuinely new public error.
- Validation failures use `invalid_parameter` with `details.fields`; identifier-specific errors may use their established codes.
- Unknown failures on `/v1` become `internal_error`; report the original exception server-side.
- Send the same request ID in the response body and `X-Request-Id` header.

### Metadata and pagination

- Include applicable `source`, `source_updated_at`, `coverage`, `cached`, and `stale` metadata. Do not claim a source timestamp if the provider does not supply one.
- Coverage values must be truthful: UK, Great Britain, England-only, partial, or another documented scope. Do not flatten coverage differences for marketing convenience.
- List endpoints use `Pagination::from()` and expose `page`, `per_page`, `total`, `total_pages`, `next_page`, and `previous_page`.
- Pagination defaults to page 1 and 25 items. The hard maximum is 100, further constrained by the current plan's `max_page_size`; page is bounded at 10,000.
- Define and test empty pages, final pages, out-of-range inputs, and upstream totals.
- GET endpoints are naturally idempotent. Do not add writes or side effects beyond metering, caches, health/snapshot records, and first-success tracking without explicitly revisiting the public contract.

## Provider integration rules

- Credentials come only from environment-backed configuration. Never hard-code them, put them in tracked fixtures, include them in URLs, or print them in diagnostics.
- Trim/normalise credentials only where the provider contract permits it. Do not transform opaque secrets.
- Set short connect and overall timeouts. Retry only transient, idempotent failures, and keep the retry count bounded.
- Map upstream states deliberately:
    - missing local credential/configuration -> controlled 503;
    - rejected upstream credential -> `upstream_authentication_failed`/503;
    - provider throttling/unavailability -> controlled 503, normally with retry guidance;
    - malformed/schema-drift response -> `upstream_invalid_response`/502;
    - known missing resource -> the resource-specific 404.
- Do not perform live provider calls in the normal test suite. Use `Http::fake()` and realistic, sanitised fixtures under `tests/Fixtures/<Provider>`.
- Tests must cover success, missing optional fields, empty results, not found, 401/403, 429, 5xx, malformed JSON/schema drift, timeout/fallback behaviour, and cache hits where applicable.
- Cache keys describe normalised semantics, not raw URLs. Follow the `ukapi:v1:<family>:...` pattern and hash sensitive or unbounded search strings.
- Use positive, negative, and stale caches only where the data semantics permit them. Document TTLs in `config/ukapi.php` and `.env.example` if they are operator-tunable.
- Never serve stale safety-sensitive state merely to stay green. Flood warnings intentionally return a controlled failure instead of stale warning data.
- Maintenance data must not be fetched during customer requests. Refresh bundled/reference datasets through explicit commands and persistent writable paths.
- If a provider is added or removed, update its contract/binding, configuration, readiness or sampled health logic, public status/source register, docs, OpenAPI, fixtures, tests, and deployment variables together.

### Current sources and special constraints

- Postcodes.io: keyless geography source; normalise UK postcodes before lookup and cache by compact canonical form.
- Companies House: API-key Basic authentication with the key as username and blank password. Do not proxy filing documents, barcodes, or document contents. Officer responses intentionally exclude date-of-birth data.
- DVSA MOT History: client credentials obtain and cache an OAuth access token; a manually supplied access token is optional. Never log token responses.
- Police.uk: keyless upstream but still locally throttled and cached; street crime is approximate/anonymised and Scotland coverage is incomplete.
- Environment Agency flood monitoring: England-only, short-lived caches, bounded station readings, and no stale warning fallback. It is not an emergency alerting guarantee.
- Companies House SIC 2007: serve the versioned local snapshot; refresh through `ukapi:sic:refresh`, never inline with a customer request.
- VAT: locally derived utility. Do not present results as tax advice.

## Persistence, migrations, and snapshots

- Production uses MySQL; CI uses SQLite. New schema/query work must behave correctly on both unless a production-only capability is explicitly isolated and tested.
- Use additive, forward-only migrations. Never edit a migration that may have run in production unless the user explicitly directs a controlled repair.
- Do not drop or rewrite customer data casually. Backfills must be bounded, restartable, and safe for Laravel Cloud releases.
- Destructive database commands are prohibited in production by `AppServiceProvider`; do not bypass that guard.
- Keep database transactions short. Quota reservation relies on an atomic conditional update plus endpoint breakdown in one transaction.
- Dataset snapshots are immutable and checksummed; snapshot heads are mutable pointers. Each dataset defines its own scope, canonical normalisation, record identity, source timestamp, retention, and diff rules.
- Do not treat all providers as having compatible retention or redistribution rights. Confirm source licences before making historical provider data customer-visible.
- Store timestamps in UTC and use immutable dates in backend domain code. Convert for display only at the UI boundary.

## Billing and entitlements

- Stripe is the payment system; local plan/catalogue and subscription state drive API entitlement decisions.
- Never call Stripe while authorising a customer API request.
- Paid-plan access continues through Cashier's grace period after cancellation, and UI copy must show the paid-through date. After the period ends, fall back to Free entitlements.
- Keep monthly and annual Stripe price IDs environment-specific. Test and live products, prices, customers, portal configuration, and webhooks are isolated.
- Checkout and portal launches use ordinary CSRF-protected browser forms so external Stripe redirects produce normal 303 navigation, not Inertia external-navigation 409 responses.
- Stripe failures should return the user to billing with a safe, useful message and be logged without keys or customer-sensitive payloads. Do not turn a provider error into an unexplained 500.
- Completed Checkout may be reconciled immediately, but the Cashier webhook remains authoritative for lifecycle changes.
- The webhook endpoint is `/stripe/webhook`. Keep its CSRF exception narrow and preserve signature verification.
- Plan additions or pricing changes must update configuration/database seeding or provisioning, Stripe docs, pricing UI, billing tests, entitlement tests, and deployment variables.
- There is no overage billing at launch. Hard quotas are a product promise.

## Frontend and design direction

The established visual direction is a refined UK data field-atlas/registry system:

- light-only interface;
- clean sans-serif typography with restrained monospace for data, labels, coordinates, and code;
- warm paper/light neutral backgrounds, deep ink green, signal blue, survey green, and sparing registry accents;
- crisp borders, square or modest radii, deliberate grid/cartographic details, and strong information hierarchy;
- practical developer-tool tone rather than government imitation or generic SaaS marketing.

Preferences learned from previous design passes:

- Avoid generic “AI slop”: gratuitous gradients, glassmorphism, excessive pills, floating rounded cards, oversized empty hero areas, cursive/editorial type, decorative copy, and animation without purpose.
- Do not make the site look like Anthropic or GOV.UK.
- Documentation should read as a continuous, scannable field guide. Basic information must not be hidden behind repeated card clicks.
- Endpoint parameters need obvious name, location, type, required/optional state, constraints, and examples.
- Sources and coverage should be a matrix/register, not dense paragraphs.
- Prefer concise, factual copy. Use British English (`normalise`, `licence` as a noun) and avoid em dashes in public-facing prose; use commas, colons, or full stops instead.
- Keep the public site, authentication, dashboard, settings, admin, and transactional email family visually coherent.

Implementation rules:

- Reuse `atlas-*` classes and existing primitives before introducing a new visual language.
- Keep TypeScript strict. Do not use `any` to bypass prop or response modelling.
- Use the `@/` alias for `resources/js` imports.
- Components and exported types use PascalCase; functions, props, and local values use camelCase; files use the existing lowercase kebab-case convention.
- Prefer Inertia `Link` for internal navigation. Use semantic anchors/forms where browser or third-party navigation requires them.
- Use Lucide icons already in the project; do not add a second icon system casually.
- Preserve keyboard navigation, visible focus, semantic headings, labels, error associations, contrast, 320px minimum layout support, reduced-motion behaviour, and responsive checks.
- Do not reintroduce dark-mode detection, toggles, storage, CSS variants, or dark email variants as application behaviour. Approved dark-background brand export files may remain.
- Do not create a new standalone “Try it” surface. The manual docs are the maintained documentation experience. If request examples are interactive, secrets must remain in memory and never enter persistent browser storage, analytics, or logs.

## Documentation, catalogue, and public content

Endpoint work is not complete when the controller works. For every new, changed, gated, or removed endpoint, reconcile all applicable surfaces:

1. `routes/api.php` and the stable endpoint identifier.
2. Request validation, controller, domain service, provider, and DTOs.
3. `openapi/openapi.yaml`, including auth, examples, errors, bounds, metadata, and coverage.
4. `resources/js/lib/ukapi-content.ts`.
5. `/docs` and `/api-catalogue` rendering.
6. `/data-sources`, source licence/attribution, freshness, and coverage.
7. Status/admin provider visibility and health checks.
8. Usage-dashboard label.
9. Provider fixtures and focused feature tests.
10. `public/llms.txt`, homepage/SEO copy, or sitemap only when the public information architecture changes.

Additional rules:

- Only advertise routes that are actually deployed and usable. If planned work is ever shown, label it explicitly and do not make it runnable.
- The OpenAPI document and runtime must agree on path, method, parameters, bounds, status codes, and response shape.
- Maintain docs manually in React and OpenAPI. Do not reintroduce Scribe or generated Scribe views/assets.
- Keep examples realistic but fictional. Never paste a live key, token, customer ID, or personal record.
- Preserve the independent-service disclaimer and source-specific attribution.
- Public pages need accurate titles, descriptions, canonical URLs, Open Graph metadata, headings, and crawler-visible fallback content where the shared shell requires it.
- Update `public/sitemap.xml`, `public/robots.txt`, and `public/llms.txt` deliberately; authenticated, health, readiness, checkout, and draft/private surfaces must not be indexed.

## Legal, privacy, email, and security

- Legal policies are structured TypeScript data rendered by the shared legal component. Do not add raw policy HTML or `dangerouslySetInnerHTML`.
- When replacing legal copy, preserve UKApi-specific provisions that the replacement omits, especially source licences, API keys, plan limits, data handling, suspension, paid terms, and governing law.
- Legal/contact identity is environment-backed. Keep config, `.env.example`, email footers, public footer, and policy copy aligned. Note the current historical naming mismatch: `config/legal.php` reads `LEGAL_CONTACT_EMAIL`, while `.env.example` still contains `LEGAL_SUPPORT_EMAIL`; reconcile this if touching those variables rather than creating another alias.
- Do not invent unresolved company number, registered office, ICO registration, or legal conclusions. Flag placeholders and obtain approved values.
- Transactional email changes require both branded HTML and useful plain-text templates.
- Service emails must not be presented as marketing. Include the shared legal footer and the correct contact address.
- Never email API-key secrets. Key-created mail may identify the key safely but the secret is shown only in the creation response/UI.
- Sign-in, password, 2FA, key, subscription, limit, and rotation notifications are security/operational features. Notification transport failure must not corrupt the underlying successful account/API operation unless the product explicitly requires synchronous delivery.
- Retain only the minimum personal/request data needed. The usage page shows aggregate endpoint counts, never request payloads or secrets.
- Do not log bearer tokens, upstream credentials, Stripe secrets, OAuth tokens, password/passkey material, full payment details, or sensitive provider payloads.
- `.env` is local and ignored. Add safe names/defaults to `.env.example`; never copy real values into tracked files, tests, comments, screenshots, or chat summaries.
- If a credential appears in a request, terminal output, fixture, or chat, treat it as compromised: do not repeat it, advise rotation, and ensure the repository does not contain it.

## PHP and Laravel conventions

- Follow PSR-4 and the Laravel Pint preset. Use four spaces and LF endings.
- Add `declare(strict_types=1)` only if the surrounding area adopts it; do not create inconsistent one-off style churn.
- Prefer constructor injection and framework contracts over facades in domain code where practical. Facades are acceptable at Laravel boundaries and where already established.
- Use explicit return types, parameter types, promoted `private readonly` dependencies, named arguments for non-obvious calls, and PHPDoc array shapes where PHP cannot express the type.
- Prefer small `final` classes with one responsibility. Avoid service classes that mix provider transport, caching, mapping, persistence, and presentation.
- Use `CarbonImmutable`/the configured immutable Date facade for domain time.
- Use `config()` outside configuration files; never call `env()` from application code.
- Keep configuration keys snake_case and environment variables uppercase snake case.
- Catch broad `Throwable` only at resilience boundaries where degradation is intentional, log/report the exception safely, and preserve a tested fallback. Do not swallow errors silently.
- Avoid unrelated reformatting and generated-file churn. Run formatters on touched files or the whole tree only when that is the intended change.

## Naming rules

- PHP classes, enums, DTOs, React components, and exported TypeScript types: `PascalCase`.
- PHP/TypeScript methods, variables, props, and JSON-to-object locals: `camelCase`.
- Database tables/columns, config keys, JSON API fields, query parameters, and route placeholders where established: `snake_case`.
- Environment variables: `UPPER_SNAKE_CASE` with a provider/feature prefix.
- Frontend source files: lowercase `kebab-case.tsx`/`.ts`.
- PHP files: one class per file and filename matches class name.
- API paths: lowercase plural nouns with kebab case only if a multiword segment is unavoidable.
- Route names: dot notation; public API endpoint codes are stable dotted identifiers.
- Cache keys: colon-delimited, versioned, semantic, normalised, and non-secret.
- Error codes: lowercase snake case and stable once public.
- Test methods: descriptive `test_*` names matching the behaviour/contract under test.
- Commit messages: concise imperative descriptions consistent with history, for example `Add MOT history endpoints` or `Fix cancelled plan entitlements`. Conventional prefixes are optional; do not mix unrelated changes into one commit.

## Testing strategy

Add the narrowest regression test that would have failed before the change, then run broader checks based on impact.

### Backend

- Unit-test pure value objects, calculations, pagination, normalisation, and snapshot/diff logic when isolation adds value.
- Feature-test routes, middleware order/effects, envelopes, headers, authentication, entitlements, bounds, provider mappings, and database side effects.
- Use factories and in-memory SQLite. Do not depend on developer-local records.
- Use provider fixtures and HTTP fakes; no normal test should require network access or real credentials.
- Prefer PHPUnit 12's `#[DataProvider('methodName')]` attribute with descriptive, named dataset keys when one behaviour must be checked against multiple independent inputs. Do not hide independent cases inside a large loop in one test: the first failure masks the remaining cases and produces poor diagnostics.
- Keep loops when the loop itself is the behaviour under test, when constructing a single aggregate fixture, or when asserting invariants across one returned collection. Include an assertion message or enough context to identify the failing member.
- Prefer strict, type-sensitive assertions such as `assertSame()`, `assertTrue()`, `assertFalse()`, and exact JSON-path assertions. Use `assertEquals()` only when its non-strict semantic comparison is intentional and explain why strict comparison is unsuitable.
- Every test must perform an assertion, configure a recognised expectation, or explicitly call `expectNotToPerformAssertions()` when the absence of an assertion is the intended test contract. PHPUnit is configured to treat assertion-free tests as risky and risky tests as build failures.
- Concurrency-sensitive usage changes must run `ConcurrentUsageTest` and `DurableUsageTest`, not just a happy-path endpoint test.
- Billing changes must cover active, changed, cancelled/grace-period, ended, missing configuration, and provider-failure cases as applicable.
- Public page/legal/SEO changes should update `PublicPagesTest` or notification template coverage.

### Frontend

- `npm run types:check` is mandatory for TypeScript changes.
- `npm run check` is the formatter/linter gate.
- `npm run build` is mandatory for page, route-component, CSS, Vite, or deploy-manifest changes.
- For meaningful UI changes, inspect at desktop, tablet, and mobile widths and exercise keyboard/focus, overflow, empty, loading, error, and long-content states.
- A successful build is not a visual review. When browser access is unavailable, say so rather than claiming visual verification.

### Contract and deployment

- Parse `openapi/openapi.yaml` after editing it.
- Run route caching after route/config changes: `php artisan route:cache` followed by `php artisan route:clear`.
- Run `php artisan config:cache`/`config:clear` when changing environment-backed configuration if the local environment is safe to cache.
- Use `php artisan ukapi:production:check --probe` for deployment-readiness work, never as a substitute for tests.
- `composer audit --locked --no-interaction` belongs in dependency/security changes.

## Standard commands

```sh
# Install/setup
composer install
npm ci

# Focused backend test
php artisan test tests/Feature/CompanyApiTest.php

# Full backend quality gate (Pint check, PHPStan, PHPUnit)
composer test

# Individual backend checks
vendor/bin/pint --test
vendor/bin/phpstan analyse
php artisan test

# Frontend checks
npm run check
npm run types:check
npm run build

# OpenAPI and routes
php -r 'require "vendor/autoload.php"; Symfony\Component\Yaml\Yaml::parseFile("openapi/openapi.yaml");'
php artisan route:cache
php artisan route:clear

# Whitespace and repository state
git diff --check
git status --short --branch
```

On Windows/PowerShell, use the equivalent quoting without changing command semantics. Do not start by running the full suite if a focused failure can provide faster feedback, but do run the full relevant gate before handing off a high-risk or cross-cutting change.

## Change workflow

1. Read this file, `git status`, the relevant tests, and the local code before editing.
2. Preserve unrelated user changes. A dirty worktree is not permission to clean, reset, stage, or rewrite those files.
3. Identify the contract and all synchronised surfaces affected by the change.
4. Implement the smallest coherent change at the correct boundary.
5. Add or update regression coverage.
6. Run focused checks, then the appropriate full checks from the matrix above.
7. Review `git diff`, `git diff --check`, generated artifacts, secrets, public copy, and deployment implications.
8. Report what changed, what was verified, and any remaining production action or verification limitation.
9. Update `ROADMAP.md` when roadmap work is completed, reprioritised, or disproved by current code.
10. Commit only when requested or clearly part of the user's request. Push/deploy only with explicit user authorisation because `master` is deployment-sensitive.

Never use destructive Git commands (`reset --hard`, checkout-overwrite, clean) to solve a dirty worktree. Never amend, force-push, rewrite history, or include unrelated changes without explicit direction.

## Definition of done

### Any change

- The implementation is at the correct architecture boundary and matches nearby conventions.
- Relevant regression coverage exists and passes.
- Static analysis, formatting, types, and build checks appropriate to the touched code pass.
- No secret, personal data, debug output, raw provider response, or accidental generated artifact was added.
- `git diff --check` passes and unrelated changes remain untouched.
- User-facing copy uses the current domain, brand, British English, and independent-service framing.

### New or changed API endpoint

- Route, endpoint code, auth/feature/rate/quota behaviour, validation bounds, controller, domain logic, and provider mapping agree.
- Canonical success/error envelope and headers are preserved.
- Cache, negative-cache, stale, timeout, retry, provider-limit, coverage, and freshness policies are explicit.
- OpenAPI, catalogue/docs registry, source register, usage label, examples, errors, and tests are synchronised.
- Fixtures cover success and failure modes without network access.
- Pagination/boundaries/idempotency and plan entitlements are tested where relevant.

### Provider addition/change

- Contract and adapter remain source-isolated.
- Credentials/config defaults are documented safely in `.env.example` and `DEPLOYMENT.md` when needed.
- Status/admin/health behaviour is updated without leaking diagnostic secrets.
- Source attribution, licensing, redistribution, retention, and geographic limitations are recorded.
- Outage behaviour cannot exhaust PHP workers or return unsafe stale data.

### Frontend/public content change

- Existing design language and primitives are reused.
- Desktop/tablet/mobile, keyboard/focus, overflow, empty/error, and reduced-motion states are considered.
- Metadata, canonical links, sitemap/robots/llms, and crawler fallback are updated only where relevant.
- Email templates are updated too when the same user journey sends mail.
- Type checking, frontend check, production build, and relevant PHP page tests pass.

### Billing/auth/security change

- Failure paths are safe and understandable, with no open redirects or secret leakage.
- Test/live environment separation and cached configuration are considered.
- Grace periods, webhook timing, idempotency, and local entitlement fallbacks are covered.
- Auth URLs derive from a correctly normalised `APP_URL`; do not generate duplicated hosts or an invalid passkey RP ID.
- Production runbook/environment changes are documented.

## Common traps to avoid

- Do not trust the old reports over current runtime requirements or deployed routes.
- Do not add an endpoint only to Laravel routes and forget OpenAPI/docs/catalogue/source/usage surfaces.
- Do not expose a provider response or upstream error body directly.
- Do not cache by raw user input when a canonical value exists.
- Do not serve stale flood warnings or describe operational status as an emergency guarantee.
- Do not use Stripe test prices/customers with live keys or assume live Customer Portal configuration exists.
- Do not use Inertia navigation for an external Stripe redirect.
- Do not infer `APP_URL`; production expects a complete HTTPS origin with no path or trailing slash, for example `https://ukapi.co.uk`.
- Do not reintroduce Scribe, dark mode, `.io` branding, unfinished placeholder pages, generic AI-style design, or raw legal HTML.
- Do not say a browser/production/live-provider check passed unless it was actually performed.
- Do not commit `.env`, build output, caches, test result caches, IDE state, or credentials.
