# UKApi.co.uk pre-live QA checklist

**Status:** Release runbook for QA, engineering, product, operations and the launch owner  
**Last reviewed:** 28 October 2026  
**Scope:** Current repository baseline and the 25 live `/v1` customer endpoints  
**Primary sources:** `AGENTS.md`, `ROADMAP.md`, `DEPLOYMENT.md`, `HEALTH_MONITORING.md`, `RECOVERY.md`, `routes/api.php`, `openapi/openapi.yaml`, `resources/js/lib/ukapi-content.ts`, configuration, migrations and the existing feature tests

This is an evidence-led go-live checklist. A tester should record the test environment, release commit, test data or fixture identifiers, timestamp in UTC, expected result, actual result, evidence link, defect reference and tester initials for every failed or blocked item.

Do not mark an item passed because the code looks correct. A pass needs a successful automated assertion, a repeatable manual observation, or an operator record from the target production-like environment.

The live customer scope is the 25 `GET /v1` routes in `routes/api.php`. The OpenAPI document is served separately at `/openapi/v1.yaml` and is included in smoke and contract checks. Catalogue entries for outcodes, councils, bank holidays, working days, tax years, food establishments and planning constraints are planned/non-live scope: do not report them as launch failures unless they are accidentally advertised as runnable or appear in the deployed route list.

## 1. Release decision rules

### 1.1 Launch must not proceed until all of these are true

- [ ] The release commit is identified and is the commit tested by CI, staging and the deployment approval.
- [ ] `git diff --check` is clean and the worktree contains no unreviewed release files.
- [ ] The full CI workflow is green, including PHPUnit, Pint, PHPStan, frontend checks, production build, OpenAPI parsing, route caching, Composer audit and the MySQL integration job.
- [ ] The 25 live runtime routes, OpenAPI operations and live catalogue entries remain in exact parity.
- [ ] All production configuration checks pass without printing secrets: `php artisan ukapi:production:check --probe`.
- [ ] Approved legal identity values are configured: legal entity name, registered office and contact email. Company number and ICO number are set when applicable and otherwise intentionally empty. No placeholder values remain.
- [ ] The production Stripe mode, products, monthly prices, annual prices, webhook endpoint and signing secret have been verified in the same mode as the release.
- [ ] Redis is a shared production Redis resource with a unique `CACHE_PREFIX`; rate limits and scheduler locks are not using process-local state.
- [ ] MySQL migrations have been applied successfully and the target schema is compatible with the tested application commit.
- [ ] The active SIC snapshot exists, is checksummed, is readable by the application and has an owner-approved freshness decision.
- [ ] The dedicated synthetic account and `SYNTHETIC_API_KEY` GitHub secret are active, scoped only to the intended test family, and have no billing or admin privileges.
- [ ] The isolated MySQL restore drill has been completed and recorded in `RECOVERY.md`, including achieved RPO, RTO, manifest comparison and SIC checksum verification.
- [ ] The launch owner has reviewed data-source licences, attribution, coverage, retention promises and the independent-service disclaimer.
- [ ] No unresolved P0 or release-blocking P1 defect remains. Any accepted exception has a named owner, expiry date, mitigation and written launch approval.

### 1.2 Immediate stop conditions

Stop the test or deployment and escalate if any of the following occurs:

- a bearer key, upstream credential, OAuth token, Stripe secret, password/passkey material or sensitive provider payload appears in logs, mail, browser storage, analytics or an issue;
- an endpoint returns raw provider JSON, provider URLs, stack traces, exception messages or database details;
- a request can bypass API-key authentication, feature entitlement, rate limiting or durable quota reservation;
- a quota or usage-accounting failure grants access instead of returning the controlled unavailable response;
- a flood warning is served stale after the live provider is unavailable;
- a customer can view or change another customer’s keys, billing, usage, account or support data;
- a customer API response claims coverage, freshness or source timestamps that the source does not support;
- a live route is advertised in the catalogue or OpenAPI but is not actually deployed, or a deployed route is missing from those contracts;
- production data is deleted, migrated destructively or altered outside the approved runbook.

## 2. Test environments and test accounts

### 2.1 Environment matrix

Run the relevant sections in each environment. Record base URLs and the exact configuration class, but never record secret values.

| Environment                         | Purpose                                                     | Required checks                                                                                                                  |
| ----------------------------------- | ----------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------- |
| Local/CI SQLite                     | Fast regression and provider mapping                        | Full PHPUnit, static analysis, formatting and contract tests                                                                     |
| CI MySQL 8.4                        | Production query and migration compatibility                | Fresh migration, rollback/reapply, durable usage, snapshots, recovery manifest, company mapping and concurrency                  |
| Staging or isolated production-like | Browser, Redis, Stripe test mode, real deployment behaviour | Full smoke, entitlement, provider probes, queue mode, webhook and recovery rehearsal                                             |
| Production                          | Read-only or tightly controlled smoke after approval        | Public pages, readiness, OpenAPI, synthetic API request, one safe request per provider family, monitoring and rollback readiness |

- [ ] Confirm staging and production use HTTPS and the trusted proxy/host settings expected by Laravel Cloud.
- [ ] Confirm staging uses test Stripe products, prices, customers and webhooks, and production uses live-mode values. Never cross the two modes.
- [ ] Confirm provider credentials are injected from the secret manager and are absent from tracked files, fixtures and CI logs.
- [ ] Confirm production has `QUEUE_CONNECTION=sync`, `UKAPI_QUEUE_WORKER_ENABLED=false`, `UKAPI_QUEUE_MONITOR_ENABLED=false` and `UKAPI_QUEUE_MONITOR_WAIVED=true` on the current no-worker Laravel Cloud plan, or complete the separate worker activation checks in section 12 if the hosting plan has changed.
- [ ] Create test accounts representing Free, Hobby, Pro and Scale entitlements. Use separate email addresses and separate API keys.
- [ ] Create a verified non-admin user, an unverified user, an administrator, a revoked-key account and a user with at least two keys.
- [ ] Create an account with a scoped key for each endpoint family and, where supported by the plan, an IP allow-list key.
- [ ] Use fictional or public reference identifiers only. Do not use customer data, private vehicle data, personal contact details or real secrets as test inputs.

### 2.2 Recommended safe test data

Use the existing sanitised fixtures for deterministic automated tests. For staging smoke tests, use values that are documented in the test data register and are safe to appear in a request log if logging is accidentally enabled. Include at least:

- `BL2 6XX` and its compact form `BL26XX` for postcode and geography tests;
- a known Companies House fixture company such as `SC012345`, plus a known not-found number and an invalid-format number;
- SIC `62012`, a valid search term such as `software`, a valid historical snapshot version, and a missing code;
- a valid example registration and VIN accepted by the MOT test account/provider fixture;
- crime month values in `YYYY-MM` form, including a month with no rows if available;
- Environment Agency station `5380TH` and a safe invalid station identifier;
- VAT amounts `0`, `100`, `100.00`, the largest accepted-length amount, and values with more than two decimal places.

## 3. Automated quality gates

Run these before manual exploratory testing and attach the output to the release record.

- [ ] `composer lint:check`
- [ ] `composer types:check`
- [ ] `php artisan test`
- [ ] `npm run check`
- [ ] `npm run types:check`
- [ ] `npm run build`
- [ ] `composer audit --locked --no-interaction`
- [ ] Parse `openapi/openapi.yaml` with Symfony YAML.
- [ ] Run `php artisan route:cache`, then `php artisan route:clear`.
- [ ] Run the CI MySQL job or an equivalent MySQL 8.4 test set.
- [ ] Run the focused backend tests before the full suite when debugging: `ApiV1Test`, `ApiKeyTest`, `OnboardingTest`, `CompanyApiTest`, `MotHistoryApiTest`, `PostcodeApiTest`, `CrimeApiTest`, `FloodApiTest`, `SicExportTest`, `PlanEntitlementsTest`, `BillingTest`, `ConcurrentUsageTest`, `DurableUsageTest`, `OperationalReadinessTest`, `ServiceHealthTest`, `RecoveryManifestTest` and `PublicPagesTest`.
- [ ] Confirm PHPUnit treats risky and assertion-free tests as failures.
- [ ] Confirm no test performs a live provider request or requires a real credential.
- [ ] Confirm every API response assertion checks the canonical envelope, not only HTTP status.

### 3.1 Required automated contract assertions

Build or retain a contract test that fails when any of the following changes without an intentional contract update:

- [ ] Runtime route path and HTTP method.
- [ ] Route name `api.v1.<family>.<action>`.
- [ ] Stable `ukapi_endpoint` code.
- [ ] OpenAPI path, operation ID, method, auth requirement and declared statuses.
- [ ] Live catalogue entry and its `live: true` flag.
- [ ] Feature middleware and plan entitlement.
- [ ] Usage-dashboard label in `config/ukapi.php`.
- [ ] API-key scope family mapping.
- [ ] Query/path parameter names, required state, type, format, bounds and normalisation rules.
- [ ] Response envelope, error envelope, metadata fields and rate/quota headers.
- [ ] Representative response shape for success, empty, not-found, validation and upstream failure.

The current parity test covers the first route/catalogue/feature/code family. Extend automation for parameter, bounds, response-schema and header parity as a launch hardening item if it is not already present.

## 4. Universal API contract checklist

Run this matrix against every live endpoint, then repeat with one endpoint from every family after deployment.

### 4.1 Authentication and transport

- [ ] No `Authorization` header returns HTTP 401 with `error.code=invalid_api_key`.
- [ ] `Authorization: Bearer` with an empty, malformed, unknown, expired or revoked key returns the documented 401 envelope.
- [ ] A valid key in a non-Bearer scheme is rejected.
- [ ] Leading/trailing whitespace and case variations do not create an authentication bypass.
- [ ] The same key works from two requests only within its entitlement and scope.
- [ ] HTTPS is enforced at the public edge. Confirm HTTP does not expose credentials or return an unexpected application response.
- [ ] `Content-Type` and `Accept` variations do not produce an ad hoc error shape.
- [ ] Every response includes `X-Request-Id`; the ID is safe, non-empty and matches `meta.request_id` on success or `error.request_id` on failure.
- [ ] A caller-supplied request ID is either safely handled according to the contract or replaced. It must not allow response splitting, log injection or cross-request correlation spoofing.
- [ ] Security headers are present on browser and API responses where applicable: `X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY`, appropriate referrer policy and HTTPS transport security.

### 4.2 Success and error envelopes

- [ ] A success is `{ "data": ..., "meta": { ... } }`, with no top-level vendor payload fields.
- [ ] An error is `{ "error": { "code": ..., "message": ..., "status": ..., "request_id": ... } }`.
- [ ] Validation uses `invalid_parameter` and includes field details where documented.
- [ ] Unknown failures become `internal_error`, with no stack trace or provider body in the response.
- [ ] 404s distinguish a known missing resource from malformed input.
- [ ] 502/503 responses use the documented upstream error code and safe retry guidance where applicable.
- [ ] Error status in the body equals the HTTP status.
- [ ] Error messages are stable, human-readable and free of secrets, SQL, URLs containing credentials, file paths and exception text.

### 4.3 Metering, quotas and rate limits

- [ ] A successful request returns the expected rate and quota headers, including `RateLimit-Limit`, `RateLimit-Remaining`, `X-Quota-Limit` and `X-Quota-Remaining` where applicable.
- [ ] A request admitted to the API is reserved before controller validation and provider work. An admitted validation error reduces the durable quota as specified.
- [ ] A rate-limit rejection does not consume monthly quota.
- [ ] A quota rejection does not consume another quota unit and returns 429 with `Retry-After` or the documented reset guidance.
- [ ] Quota headers never become negative and do not expose another account’s values.
- [ ] Two keys owned by one account share the same hard monthly quota.
- [ ] Keys owned by different accounts do not share usage.
- [ ] Calendar-month reset occurs at UTC month start, including an annual-plan account.
- [ ] Durable accounting failure returns 503 `usage_unavailable` and does not grant unmetered access.
- [ ] Endpoint usage is recorded under the stable label, without query strings, request bodies or secrets.
- [ ] First-success tracking occurs only for an authenticated 2xx API response, not for a public page, invalid key, validation error, feature denial, rate denial or upstream failure.

### 4.4 Caching and idempotency

- [ ] Repeat an identical normalised request and verify the cache metadata changes as documented, usually `cached: false` then `cached: true`.
- [ ] Equivalent input normalisations share the intended cache key: postcode spacing/case, vehicle registration case/spacing, station ID case, coordinates with insignificant formatting and search input rules.
- [ ] Different semantic inputs do not collide.
- [ ] Negative cache responses do not turn a later valid resource into a false 404.
- [ ] Corrupt cached data is evicted and produces the controlled invalid-response error rather than malformed output.
- [ ] Stale data is only served for provider families where the contract permits it. Flood warnings must not use stale fallback.
- [ ] GET requests do not create customer-visible side effects beyond metering, cache, snapshot/health records and first-success tracking.

### 4.5 Pagination and bounds

For every paginated endpoint:

- [ ] Default page is 1 and default `per_page` is 25.
- [ ] `page=1`, `per_page=1`, maximum plan page size and hard maximum 100 work as documented.
- [ ] `page=0`, negative page, non-integer page, page above 10,000 and oversized values return `invalid_parameter`.
- [ ] `per_page=0`, negative, non-integer, above the current plan limit and above 100 return `invalid_parameter`.
- [ ] Empty results still return a valid array and complete pagination metadata.
- [ ] First, middle, final and out-of-range pages have correct `total`, `total_pages`, `next_page` and `previous_page` values.
- [ ] Changing only page/per-page does not return a cached page from a different request.

## 5. Live endpoint-by-endpoint test matrix

Every row below is a release test. The request should be run with a valid unrestricted key, then with a key that is not entitled to the feature, an endpoint-family-scoped key that is allowed, and a key that is scoped away from the family. Where a provider is involved, repeat using HTTP fakes in automated tests for success, optional fields, empty, 404, 401/403, 429, 5xx, timeout and malformed/schema-drift responses.

### 5.1 VAT utilities

| Endpoint                              | Happy path and boundary coverage                                                                                                                                                                                                                                                       | Failure and semantic checks                                                                                                                                                                                                                                                                     |
| ------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `GET /v1/vat/calculate?amount=&rate=` | Test `100` at `20`, `100.00` at `20.00`, zero amount, zero rate, maximum accepted amount length, decimal rounding and repeated identical input. Assert decimal strings, `vat_amount`, `total_amount`, `currency=GBP`, `source=ukapi`, `cached=false` and the no-tax-advice disclaimer. | Missing amount/rate, blank strings, negative values, signs, commas, scientific notation, more than two amount decimals, more than two rate decimals, rate over 100, non-numeric text and very long input. Confirm validation counts only after admission and never leaks calculator exceptions. |
| `GET /v1/vat/remove?amount=&rate=`    | Test `120` at `20`, zero amount, zero rate, exact gross/net relationship and rounding. Assert `gross_amount`, `rate_percent`, `vat_amount`, `net_amount`, GBP and disclaimer.                                                                                                          | Apply the same malformed and boundary inputs. Verify `rate=100` behaves according to the documented formula and does not divide by zero or return a negative amount.                                                                                                                            |

### 5.2 MOT history

| Endpoint                                  | Happy path and boundary coverage                                                                                                                                                                                                                                                                                              | Provider and security checks                                                                                                                                                                                                                                               |
| ----------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `GET /v1/mot/registration/{registration}` | Test case/space normalisation, valid registration, repeat cache hit, known no-history response and allowed registration length bounds. Assert the whitelisted typed vehicle contract, `coverage=Great Britain and Northern Ireland`, no raw DVSA fields and no source timestamp claim when the provider does not provide one. | Invalid characters, empty/too-short/too-long registration, 401/403 upstream, token acquisition failure, expired cached token, 429, 5xx, timeout, malformed vehicle payload and corrupt semantic cache. Verify OAuth/token responses never appear in logs or client output. |
| `GET /v1/mot/vin/{vin}`                   | Test valid 17-character VIN, uppercasing and repeat cache hit. Assert the same whitelisted contract and metadata.                                                                                                                                                                                                             | Test invalid length, forbidden VIN letters `I`, `O`, `Q`, punctuation, spaces and unknown VIN. Repeat all upstream fault cases and ensure registration and VIN cache keys cannot collide.                                                                                  |

### 5.3 Companies House and SIC

| Endpoint                                     | Happy path and boundary coverage                                                                                                                                                                                                                                                            | Failure, privacy and pagination checks                                                                                                                                                                                                   |
| -------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `GET /v1/companies/{companyNumber}`          | Test normalisation of case/spacing, UK company number formats, optional profile fields present and absent, repeat cache hit and known not-found. Assert stable profile DTO, source/coverage metadata and no provider payload passthrough.                                                   | Invalid number, malformed provider JSON, missing required provider fields, Companies House 401/403, 429, 5xx and timeout. Verify safe 502/503 mappings, negative caching and no credential in Basic-auth diagnostics.                    |
| `GET /v1/companies/search?q={query}`         | Test 2-character and 200-character queries, case/space normalisation, exact and broad result sets, empty result, default pagination and plan-specific page sizes. Assert stable summaries and pagination.                                                                                   | Missing, blank, one-character, over-200-character, invalid type, page bounds, upstream 401/403/429/5xx/timeout/malformed response. Query values must not appear in usage aggregates or sensitive logs.                                   |
| `GET /v1/companies/{companyNumber}/officers` | Test valid company, optional officer fields, pagination, empty officers and repeat cache hit. Confirm officer DTOs intentionally exclude date-of-birth data and raw provider links.                                                                                                         | Invalid/not-found company, pagination errors, upstream faults and malformed officer rows. Search responses must not be confused with the more-specific officers route.                                                                   |
| `GET /v1/companies/{companyNumber}/filings`  | Test valid company, filing metadata, pagination, empty filings and repeat cache hit. Confirm only metadata is returned.                                                                                                                                                                     | Invalid/not-found company, pagination errors, upstream faults and malformed rows. Confirm document contents, barcodes, filing links and document payloads are not proxied.                                                               |
| `GET /v1/sic/{code}`                         | Test valid five-digit code including leading zero behaviour, case/space normalisation if accepted, cache hit, missing code and snapshot/reference metadata.                                                                                                                                 | Four- or six-digit, non-numeric, blank and unknown code. Confirm snapshot unavailable is controlled and no customer request performs an upstream refresh.                                                                                |
| `GET /v1/sic/search?q={query}`               | Test code search and description search, 2- and 200-character boundaries, pagination, empty result, leading zero code and cached snapshot response. Assert `reference_version`, `snapshot_version`, checksum/version consistency where exposed and stable rows.                             | Missing/blank/one-character/overlong query, bad pagination, unavailable snapshot, corrupt snapshot and stale/invalid snapshot pointer.                                                                                                   |
| `GET /v1/sic/changes?since=&until=`          | Test current snapshot, `since` only, `since` plus `until`, empty diff, added/removed/changed records and snapshot metadata.                                                                                                                                                                 | Missing or overlong versions, unknown versions, reversed range, malformed version and pruned snapshot. Verify only Pro/Scale-style `change_feeds` entitlement is admitted and an ordinary Free/Hobby key is denied before provider work. |
| `GET /v1/sic/export`                         | Test Scale entitlement, valid CSV download, UTF-8, header row, sorted code order, immutable version/checksum headers, `Content-Disposition`, `Cache-Control: private, no-store`, `nosniff`, formula-cell prefixing and leading-zero preservation. Verify one download consumes one request. | Free/Hobby/Pro denial, any query parameter, quota exhaustion, unavailable snapshot and corrupt snapshot. Confirm export cannot be turned into arbitrary file access or an unbounded filtered export.                                     |

### 5.4 Postcodes and geography

| Endpoint                                              | Happy path and boundary coverage                                                                                                                             | Failure and semantic checks                                                                                                                                                                                            |
| ----------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------ | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `GET /v1/postcodes/{postcode}`                        | Test spaced/unspaced and mixed-case valid postcode, all expected postcode fields, repeat cache hit and a known provider result.                              | Invalid format, valid-looking but missing postcode, provider empty/malformed/5xx/timeout. Confirm normalised display postcode, compact semantic cache key and UK coverage.                                             |
| `GET /v1/postcodes/{postcode}/validate`               | Test valid, invalid-format and provider-known/unknown postcodes. Assert `{postcode, valid}` only, correct cache metadata and no accidental lookup payload.   | Whitespace and oversized values, provider failure and repeated negative result. Verify validation does not return 500 for a normal invalid postcode.                                                                   |
| `GET /v1/postcodes/{postcode}/nearby?limit=&radius=`  | Test default limit/radius, minimum and maximum `limit` 1/100, minimum and maximum radius 1/2000, ordering by distance, empty result and repeat cache hit.    | Invalid postcode, zero/negative/non-integer/oversized limit or radius, provider 404/5xx/timeout and malformed item. Confirm radius is metres in the response contract and does not accept unbounded values.            |
| `GET /v1/coordinates/{latitude}/{longitude}/postcode` | Test representative UK coordinates, decimal precision, negative longitude and semantically equivalent formatting. Assert nearest postcode DTO and cache hit. | Latitude outside -90..90, longitude outside -180..180, non-numeric, malformed sign/decimal, ocean/outside supported area, provider empty/5xx/timeout. Confirm coordinate cache keys are normalised without collisions. |

### 5.5 Crime data

| Endpoint                                        | Happy path and boundary coverage                                                                                                                                                                     | Failure and safety/coverage checks                                                                                                                                                                                                             |
| ----------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `GET /v1/crime/nearby?postcode=&month=`         | Test valid postcode with explicit `YYYY-MM`, omitted month/current source month, mixed postcode spacing/case, non-empty and empty crime arrays, cached repeat and snapshot metadata where available. | Invalid postcode, malformed month, future month, unavailable source, 429/5xx/timeout/malformed rows. Assert approximate/anonymised caveat, correct coverage text and no implication of exact address or Scotland-wide coverage.                |
| `GET /v1/crime/summary?postcode=&month=`        | Compare `total` and sorted `by_category` counts with the nearby fixture, including empty result and duplicate categories.                                                                            | Same input/provider faults as nearby. Confirm summary is derived from the same normalised records and cannot disagree with the raw nearby total.                                                                                               |
| `GET /v1/crime/categories?month=`               | Test omitted month, valid month, cached repeat and empty/provider response if supported. Assert category code/name contract and coverage caveat.                                                     | Malformed/future month, 429/5xx/timeout/schema drift and safe error mapping. Confirm this endpoint does not perform an unnecessary postcode lookup.                                                                                            |
| `GET /v1/crime/changes?postcode=&since=&until=` | Test valid month range, since-only and since/until diff, empty diff, added/removed/changed arrays, snapshot checksums and captured timestamps.                                                       | Missing/invalid postcode/months, reversed range, missing snapshot, provider/source failure and inaccessible historical feature. Confirm no stale current warning semantics are copied into this historical feed and coverage remains explicit. |

### 5.6 Flood monitoring

| Endpoint                                            | Happy path and boundary coverage                                                                                                                                                                     | Failure and safety checks                                                                                                                                                                       |
| --------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `GET /v1/flood/warnings`                            | Test non-empty and empty warning fixture, severity mapping, timestamps, area fields, five-minute cache hit and England coverage. Assert the emergency-alert disclaimer on every successful response. | Provider 429/5xx/timeout/malformed response, missing optional warning fields and stale cache after outage. Must return controlled failure, never stale warning data.                            |
| `GET /v1/flood/nearby?postcode=`                    | Test valid postcode, normalisation, nearby distance, no nearby warnings, cached repeat and warning mapping.                                                                                          | Invalid or unknown postcode, provider postcode lookup failure, provider flood failure, timeout, malformed warning and stale fallback attempt. Confirm England-only coverage and safety notice.  |
| `GET /v1/flood/stations/nearby?postcode=`           | Test valid postcode, station list, station metadata, measures, no nearby stations and cache hit.                                                                                                     | Invalid/unknown postcode, malformed station/measure, upstream 429/5xx/timeout and safe error. Confirm station results are bounded and source timestamp is not invented when unavailable.        |
| `GET /v1/flood/stations/{id}/readings`              | Test valid station, lower/upper-case ID normalisation, bounded recent readings, reading timestamps/values, source timestamp, cache hit and snapshot metadata if captured.                            | Invalid ID characters, unknown station, provider failure, malformed reading and corrupt cached data. Confirm result is bounded and cannot request arbitrary provider URLs or unlimited history. |
| `GET /v1/flood/stations/{id}/changes?since=&until=` | Test valid snapshot versions, diff arrays, from/to snapshot metadata and source timestamp.                                                                                                           | Invalid station ID/version, reversed range, unavailable snapshot and feature denial. Confirm this archive response retains the safety notice and cannot be mistaken for live warning alerting.  |

## 6. Entitlements, API-key lifecycle and onboarding

### 6.1 Plan matrix

Verify the actual configured values, not copied marketing text:

| Plan  | Monthly quota | Burst limit | Max page size | Expected features                                                 |
| ----- | ------------: | ----------: | ------------: | ----------------------------------------------------------------- |
| Free  |         5,000 |       5/sec |            25 | `current_data`                                                    |
| Hobby |        50,000 |      10/sec |            50 | `current_data`                                                    |
| Pro   |       250,000 |      25/sec |           100 | current, historical, change feeds, commercial use, IP allow lists |
| Scale |     1,000,000 |      50/sec |           100 | Pro features plus bulk export                                     |

- [ ] Verify each plan’s local entitlement is selected from the local subscription state and does not call Stripe during API authorisation.
- [ ] Verify Free and Hobby cannot access change feeds, and Pro cannot access bulk export.
- [ ] Verify feature denial occurs before provider work and has the documented 403 envelope.
- [ ] Verify the current plan’s page-size limit is enforced even when the request asks for 100.
- [ ] Verify commercial-use and IP-allow-list copy matches the entitlement and the UI does not overstate access.
- [ ] Verify a cancelled paid subscription remains paid through the paid-through date, then falls back to Free after the grace period.
- [ ] Verify an annual subscription has monthly quota reset semantics and does not inherit incorrect annual quota assumptions.

### 6.2 API keys

- [ ] A verified user can create a key and sees the plaintext secret once only.
- [ ] The secret is not returned by later list, update, rotate, export, mail or API responses.
- [ ] The database stores only a hash or otherwise approved non-reversible representation.
- [ ] Key name, expiry, scope and IP allow-list validation reject malformed and overlong values.
- [ ] A key scoped to one family is allowed only for that family, and a null/all-family key remains compatible with future families.
- [ ] A key update changes the intended fields only and creates the expected audit record.
- [ ] Rotation produces a replacement secret once, keeps the overlap within 24 hours, and expires the old key at the documented time.
- [ ] Revocation is immediate for customer-owned keys and audited for admin support actions.
- [ ] Invalid-key attempts are limited by client IP without revealing whether a partial key exists.
- [ ] A user cannot view, update, rotate or revoke another user’s key.
- [ ] Security export contains no plaintext secret or secret hash and has safe CSV headers/content type.
- [ ] Key-created and rotation notifications identify the safe key name only and never include the secret.

### 6.3 First-use onboarding

- [ ] Registration, email verification and key creation complete the expected onboarding sequence.
- [ ] The first authenticated 2xx API response sets first-success state for the key owner, not the current browser session user.
- [ ] Validation, feature, quota, rate-limit, invalid-key and provider failures do not mark first success.
- [ ] A cached successful response still counts as success; a second key cannot overwrite the original owner’s event incorrectly.
- [ ] The onboarding UI explains Bearer authentication, base URL, first request and where to find the API key without exposing it persistently.

## 7. Authentication, account, browser and billing QA

### 7.1 Public pages and documentation

- [ ] `/`, `/api-catalogue`, `/docs`, every docs section, `/pricing`, `/status`, `/data-sources`, `/terms`, `/paid-plan-terms`, `/privacy` and `/acceptable-use` render without console errors.
- [ ] Page titles, descriptions, canonical links, Open Graph metadata, headings and crawler-visible fallback content are accurate.
- [ ] All 25 live endpoints are documented with real paths, methods, parameters, examples, bounds, auth, errors, source, coverage and freshness.
- [ ] Planned catalogue entries are visibly non-live or absent from runnable public documentation. They must not have working “try” requests.
- [ ] Examples use fictional values and cannot contain a live key, token, customer ID or personal record.
- [ ] `/openapi/v1.yaml` is reachable, parses, has the expected content type and matches runtime routes.
- [ ] Source register and attribution are accurate for Postcodes.io, Companies House, DVSA MOT History, Police.uk, Environment Agency and SIC snapshot data.
- [ ] No page implies government ownership, affiliation or endorsement.
- [ ] Pricing values, plan features, quotas, page sizes, commercial-use wording and annual/monthly choices match local entitlements and Stripe catalogues.

### 7.2 Registration and sign-in

- [ ] Registration validates required fields, rejects duplicate/invalid email and applies the expected password policy.
- [ ] Verification mail is branded HTML and useful plain text, contains the legal footer and does not contain secrets.
- [ ] Login success, invalid credentials, throttling, logout and session expiry behave correctly.
- [ ] Password reset request, token expiry, one-time use, invalid token and completed reset work without account enumeration.
- [ ] Password confirmation protects sensitive operations and expires according to the configured policy.
- [ ] Two-factor challenge, recovery/fallback path, invalid code, replay, throttling and remembered-device behaviour work.
- [ ] Passkey registration, authentication, cancellation, unsupported browser fallback and account isolation work.
- [ ] Unverified users cannot enter verified-only areas or create/use customer API keys where prohibited.
- [ ] Session cookies have correct Secure, HttpOnly and SameSite properties for production.

### 7.3 Dashboard and usage

- [ ] Dashboard is accurate for no-key, one-key, multiple-key, no-usage and high-usage states.
- [ ] Usage totals and endpoint breakdowns match durable database counts and never show request payloads, full queries or secrets.
- [ ] Usage CSV export has correct content type, escaping, date/UTC semantics, totals and no sensitive fields.
- [ ] Threshold notifications are sent once per account and threshold/month, not once per API key.
- [ ] Mail failure does not roll back successful usage, key, login, subscription or support operations.
- [ ] The dashboard shows human labels for every stable endpoint code, never an unknown opaque identifier for a shipped route.

### 7.4 Stripe and billing

Use Stripe test mode in staging and a controlled live-mode verification only after approval.

- [ ] Checkout form is CSRF-protected, uses ordinary browser navigation and returns a normal redirect to Stripe rather than an Inertia 409.
- [ ] Unknown plan, missing price configuration and Stripe API failure return the user to billing with a safe useful message.
- [ ] Monthly and annual price IDs resolve to the intended GBP product and amount.
- [ ] Successful Checkout reconciliation does not override the webhook as the lifecycle authority.
- [ ] Valid signed webhook updates local subscription/entitlement state idempotently.
- [ ] Invalid signature, wrong secret, duplicate event, out-of-order event and unsupported event are handled safely.
- [ ] Cancellation retains paid access through the paid-through date; after that date access falls back to Free.
- [ ] Customer Portal launch works, is scoped to the authenticated customer and does not expose another customer.
- [ ] Stripe failures and webhook logs exclude keys, full payloads and payment details.
- [ ] `/stripe/webhook` is the only narrow CSRF exception and signature verification remains enabled.

## 8. Provider, cache and resilience testing

### 8.1 Automated provider fault matrix

For each provider adapter, use `Http::fake()` and sanitised fixtures. A data provider should cover the same named cases for each provider:

- success with all fields;
- success with optional fields missing or null;
- empty collection;
- known missing resource;
- invalid JSON;
- valid JSON with schema drift or missing required field;
- 401 and 403;
- 429 with and without retry headers;
- 500, 502, 503 and connection timeout;
- repeated transient failure followed by success;
- cached success, cached negative result, stale-allowed result and corrupt cache;
- credential/configuration missing locally.

Assert provider request URL construction, method, headers, authentication style, timeout/retry count, bounded provider throttling, cache key semantics, DTO mapping and safe public error mapping. Never assert or print a real secret.

### 8.2 Provider-specific checks

- [ ] Postcodes.io calls use compact canonical postcode values and keyless authentication.
- [ ] Companies House uses Basic authentication with the API key as username and blank password. The key is never in a URL or log.
- [ ] MOT obtains and caches an OAuth token with client credentials. A manually supplied access token is optional and is never logged.
- [ ] Police.uk is locally throttled and cached despite being keyless. Approximate/anonymised and Scotland limitations are preserved.
- [ ] Environment Agency requests are bounded by station/reading limits and use short caches. Flood warnings fail closed on provider failure.
- [ ] SIC refresh is an explicit maintenance command and never runs inline during a customer request.

### 8.3 Redis, cache and concurrency

- [ ] Two application instances share the same Redis cache/rate-limit state.
- [ ] Scheduler locks prevent duplicate health, queue-monitor and snapshot-prune work.
- [ ] A Redis outage produces the documented safe failure or degraded operator signal. It must not silently switch hard quota enforcement to process-local counters.
- [ ] Concurrent identical cache misses do not create unbounded provider fan-out. Record current behaviour even if coalescing is deferred.
- [ ] Concurrent API requests cannot oversubscribe a hard quota. Run both SQLite-compatible and MySQL cross-process tests.
- [ ] Cache prefixes are unique between staging, production and restore environments.

## 9. Security, privacy and abuse resistance

- [ ] Run dependency audits for Composer and npm and review all actionable advisories.
- [ ] Run a passive DAST scan against staging with test credentials only. Confirm no destructive active scan runs against production.
- [ ] Test SQL injection, JSON/header injection, path traversal, SSRF-shaped inputs, oversized query strings, Unicode confusables and null bytes at every identifier/query boundary.
- [ ] Test mass-assignment attempts on profile, API-key, billing and admin forms.
- [ ] Test CSRF on every state-changing browser route, including key creation/rotation/revocation, profile/security changes, billing checkout/portal and admin actions.
- [ ] Test same-origin and cross-origin requests. Confirm CORS is deliberate and does not allow arbitrary credentialed origins.
- [ ] Verify trusted proxy/host handling cannot spoof HTTPS, request IDs or client IP used for abuse controls.
- [ ] Verify rate limiting is not bypassed by header spoofing, IPv6 formatting, alternate auth headers or multiple keys.
- [ ] Verify admin routes require verified auth, admin role, 2FA/recent password confirmation where specified, and reject ordinary users.
- [ ] Verify admin pages aggregate safely and never include request payloads, provider bodies, bearer tokens, OAuth responses, queue payloads or secret hashes.
- [ ] Search application logs, queue failures, mail previews, browser devtools, database audit records and CI artifacts for secrets and sensitive payloads.
- [ ] Verify API keys are redacted in exceptions and framework request logging.
- [ ] Verify privacy policy claims match actual retention, provider sharing, Stripe, mail, Laravel Cloud logs, backups, usage metrics and deletion behaviour.
- [ ] Test account deletion, anonymisation and ownership boundaries, and record any retention-required records that intentionally remain.
- [ ] Test export/download response headers against content sniffing, caching and CSV formula injection.

## 10. Browser, accessibility and responsive QA

Perform this on the current supported browser set, at desktop, tablet and 320px-wide mobile viewport.

- [ ] Public navigation, sign-in, registration, verification, password reset, 2FA, passkey fallback, dashboard, keys, usage and billing are keyboard reachable.
- [ ] Every form control has a visible or programmatically associated label.
- [ ] Validation and server errors are associated with the relevant control and announced appropriately.
- [ ] Focus is visible, not trapped unexpectedly and restored after dialogs/navigation.
- [ ] Modal, dropdown, navigation and collapsible controls work with keyboard and screen-reader semantics.
- [ ] Colour contrast, link states, disabled states and error states remain readable in the light-only design.
- [ ] There is no dark-mode detection, toggle or dark email variant reintroduced by the release.
- [ ] Docs anchors, copy controls, code blocks, tables and long examples work without horizontal page overflow.
- [ ] API catalogue filters or sections do not hide required endpoint information behind repeated card interactions.
- [ ] Checkout and portal actions are ordinary browser forms and preserve useful navigation on external redirects.
- [ ] Reduced-motion preference is respected; no animation is required to understand or complete a task.
- [ ] Long company names, long error messages, large usage totals, empty states and slow-loading states remain usable.
- [ ] No page breaks at 320px, 400%, keyboard-only navigation or browser zoom.
- [ ] Check the browser console and network panel for failed assets, hydration errors, unexpected third-party requests and secret-bearing URLs.

## 11. Operations, health and monitoring

### 11.1 Readiness and public status

- [ ] `/ready` reports dependency readiness without exposing credentials or internal details.
- [ ] `/ready` is not cacheable and has `X-Robots-Tag: noindex`.
- [ ] Readiness distinguishes healthy, degraded, stale and failed states according to the health model.
- [ ] Public `/status` shows safe provider state, source freshness and incidents without raw failure details.
- [ ] Admin provider and operations views show safe latency/error aggregates, p95 buckets, provider observations, rejection hotspots, queue/notification health and snapshot age.
- [ ] Admin incident acknowledgement creates the expected audit record and does not expose private data in the note.

### 11.2 Scheduler and synthetic monitor

- [ ] Laravel Cloud scheduler is enabled and runs every five minutes for health checks and queue monitoring, with one-server and overlap locks.
- [ ] `ukapi:health:check` records provider/application observations and sends only safe operator alerts.
- [ ] A stale scheduler/health signal is distinguishable from an upstream provider failure.
- [ ] The GitHub synthetic workflow runs on schedule and by manual dispatch.
- [ ] Synthetic checks cover liveness, readiness, public/authentication pages, OpenAPI and one scoped API request.
- [ ] Synthetic failures require repeated failure/latency evidence before opening one incident issue.
- [ ] Recovery closes the incident and records the workflow run.
- [ ] The monitor key has no billing/admin capability and the test account is excluded from customer reporting where intended.
- [ ] A monitor failure cannot page repeatedly for one incident or leak secrets into issue text.

### 11.3 Notifications and queues

On the current synchronous no-worker plan:

- [ ] Confirm notifications deliver synchronously and the readiness check reports the explicit queue-monitor waiver as healthy.
- [ ] Confirm the app is not accumulating database jobs that no worker will consume.
- [ ] Confirm mail-provider failure is isolated and the underlying successful operation remains successful.

If persistent workers become available before launch:

- [ ] Provision the documented `mail-critical,mail` worker process.
- [ ] Set database queue configuration, retry-after, worker and queue-monitor flags together.
- [ ] Test dispatch, consumption, retry/backoff, failed-job capture, stale backlog alert, recovery alert and worker restart.
- [ ] Do not activate worker mode until the worker is demonstrably consuming jobs.

## 12. Data snapshots, maintenance and recovery

- [ ] Run `ukapi:sic:refresh` in a non-customer maintenance context using a safe fixture or approved source access.
- [ ] Verify immutable snapshot version, checksum, source URL, source timestamp, record count and active head.
- [ ] Verify refresh failure leaves the previous active snapshot usable and does not partially replace it.
- [ ] Verify `ukapi:snapshots:prune` preserves active and within-retention snapshots and removes only approved expired data.
- [ ] Verify SIC search, lookup, changes and export all resolve the same active snapshot head.
- [ ] Verify crime and flood reading change feeds compare immutable snapshots with stable identity and deterministic added/removed/changed sets.
- [ ] Verify source licence and retention rules are recorded before enabling new historical or export data.
- [ ] Create a named Laravel Cloud MySQL backup before the release or migration according to the runbook.
- [ ] Complete an isolated restore into a new cluster, never by attaching an unverified restore to production.
- [ ] Run `ukapi:recovery:verify` against the restored database and compare migrations, users, subscriptions, durable usage, service-health rows, jobs, failed jobs and active SIC checksum.
- [ ] Confirm restored Redis uses a fresh prefix and is not accidentally sharing production locks or cache.
- [ ] Record backup time, restore start/end, RPO, RTO, row/checksum results, operator, temporary environment and cleanup evidence in `RECOVERY.md`.
- [ ] Confirm rollback instructions are executable, including migration rollback policy, app version rollback, cache-prefix handling, webhook reconciliation and conservative quota reconciliation.

## 13. Go-live smoke suite

Run this short suite immediately after deployment, using a controlled test key and no destructive operations.

1. [ ] `GET /` returns the expected public page over HTTPS.
2. [ ] `GET /ready` returns the expected healthy/readiness response and noindex header.
3. [ ] `GET /openapi/v1.yaml` returns parseable OpenAPI with the expected content type.
4. [ ] Open the public docs and pricing pages and confirm no asset or console errors.
5. [ ] Call one safe endpoint from each live family: VAT, MOT, Companies House, SIC, postcode/geography, crime and flood.
6. [ ] Confirm each response has a matching request ID in header and body, canonical envelope and expected source/coverage metadata.
7. [ ] Repeat one request to confirm cache behaviour and repeat it with a second key to confirm account isolation.
8. [ ] Trigger one deliberate validation failure and one deliberate invalid-key failure. Confirm safe envelopes, headers and no secret leakage.
9. [ ] Confirm the synthetic workflow succeeds on its next scheduled or manual run.
10. [ ] Confirm admin operations and public status show the smoke requests as safe aggregates only.
11. [ ] Confirm no new error, latency, quota, Redis, MySQL, mail or provider alert appears after the suite.

## 14. Automation playbook

### 14.1 PHPUnit and Laravel feature tests

Use the existing feature-test style and fixtures. Prefer named PHPUnit 12 data-provider cases over a large loop when cases are independent.

Automate a reusable endpoint contract helper that:

- creates a plan/account/key fixture;
- invokes an endpoint with a deterministic request ID;
- asserts HTTP status, JSON envelope, request ID parity, safe headers, quota/rate headers and usage row;
- optionally fakes provider responses and asserts request URL/auth/timeout behaviour;
- repeats the request to assert cache metadata;
- checks the endpoint is not available to an out-of-scope key.

Add data providers for:

- valid, invalid, boundary and Unicode inputs;
- plan/feature combinations;
- provider status/body combinations;
- pagination pages and limits;
- cache states;
- concurrent quota reservations.

Keep provider fixtures under `tests/Fixtures/<Provider>`. Never use a real API key or network call in the normal suite.

### 14.2 OpenAPI and collection automation

Generate an executable staging collection from the maintained OpenAPI document, or maintain a small hand-written collection whose request paths are checked against it. The collection should:

- obtain a test API key out-of-band and inject it only in memory or a CI secret store;
- run one positive, one validation, one authentication, one entitlement and one provider-failure case per endpoint;
- assert status, envelope, request ID, source/coverage metadata and key headers;
- save response schemas and latency without saving bearer keys or full sensitive payloads;
- fail if an endpoint is missing from the collection or if a documented endpoint is not live;
- run against staging on every release candidate and against production only for the approved smoke subset.

Do not commit a collection containing a live bearer token. Redact exported reports and CI artifacts.

### 14.3 Browser automation

Use Playwright or the project’s chosen browser runner for critical journeys:

- public landing page to docs/pricing;
- registration to email verification test harness;
- sign-in, password reset and 2FA fallback;
- create key, show once, copy, revoke and rotate;
- dashboard usage and CSV export;
- Free-to-paid checkout in Stripe test mode;
- cancellation/grace-period display;
- admin provider/operations view and incident acknowledgement;
- 320px mobile navigation and keyboard-only docs navigation.

Capture screenshots and console/network failures only in non-production. Mask email addresses, API keys and billing identifiers in reports.

### 14.4 Load, rate-limit and concurrency automation

Use a bounded staging load test, not an uncontrolled production test.

- warm-up one request per endpoint family;
- ramp to each plan’s configured burst limit;
- verify 429 behaviour, `Retry-After`, latency and absence of provider fan-out;
- run concurrent requests from two keys on one account at the quota boundary;
- run concurrent requests from different accounts to prove isolation;
- include cache-hit and cache-miss profiles;
- include one provider latency/failure simulation;
- stop the test if MySQL lock time, Redis errors, PHP worker saturation or upstream rate limits approach unsafe thresholds.

Record p50, p95, error rate, 429 rate, quota reservation failures, provider calls per logical request and cache-hit ratio. Do not use request payloads as load-test labels.

### 14.5 Synthetic monitoring

Keep the repository-owned GitHub workflow as the minimum independent monitor. Add checks only when they remain safe and cheap:

- public page and asset reachability;
- readiness status and noindex header;
- OpenAPI fetch and parse;
- one scoped API request with a dedicated Free key;
- response time thresholds;
- repeated-failure incident creation and recovery closure.

Keep secrets in GitHub secret storage, use environment variables, and ensure workflow output cannot echo them.

## 15. Defect reporting and evidence template

For every defect, record:

- release commit, environment and UTC timestamp;
- route or browser journey;
- plan, key scope and account type, described without the secret;
- exact safe input or a reference to the fixture name;
- expected result and actual result;
- HTTP status, error code, request ID and relevant safe headers;
- provider state or fake response case, if applicable;
- reproducibility and impact;
- screenshot, redacted response, log correlation or CI artifact;
- severity, owner, fix version and retest result.

Never paste bearer tokens, OAuth tokens, Stripe values, cookies, password-reset links, passkey material, full provider payloads, customer records or unredacted request headers into a defect.

Suggested release evidence record:

```text
Release commit:
Environment/base URL:
Tester/date UTC:
Automated gates:
Endpoint matrix:
Browser journeys:
Security/privacy review:
Provider fault review:
Monitoring verification:
Recovery drill reference:
Open defects/exceptions:
Launch approval:
Rollback owner and command/runbook:
```

## 16. Post-launch observation window

For the first 24 hours, review at least every five minutes during the initial release window and at agreed intervals afterwards:

- [ ] Synthetic monitor remains green and no repeated-failure issue is open.
- [ ] API 2xx/4xx/5xx counts and p95 latency are within the agreed baseline by endpoint family.
- [ ] Provider timeout, 429, schema-error and authentication-failure rates are expected.
- [ ] Redis, MySQL, PHP worker, scheduler, mail and webhook errors remain within baseline.
- [ ] Quota reservations and endpoint usage breakdown reconcile with total API requests.
- [ ] No customer reports a secret in UI, mail, logs or export.
- [ ] Public status, source freshness and admin operations remain coherent.
- [ ] Any incident is acknowledged, classified, documented and either recovered or escalated using `HEALTH_MONITORING.md`.

After the observation window, record what was tested, what was observed, accepted risks, customer-impacting deviations and the next roadmap actions. Keep the checklist current when a route, plan, provider, legal promise, deployment topology or monitoring contract changes.
