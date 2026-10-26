# UKApi.co.uk Roadmap

**Last audited:** 25 October 2026  
**Purpose:** A living, prioritised list of work that remains after comparing the current repository, production runbooks, tests, public contract, and original product reports.  
**Governing conventions:** See `AGENTS.md`.

## How to use this roadmap

- Work top-down within a priority unless an incident or user request supersedes it.
- Treat P0 as release health, P1 as production hardening, P2 as near-term product expansion, and P3 as demand-led work.
- Do not mark an item complete from code inspection alone. Apply the relevant definition of done in `AGENTS.md` and record the verification performed.
- Keep current code, tests, OpenAPI, and deployment reality above the older reports when they disagree.
- Reassess priority after production incidents, provider/licensing changes, or material customer feedback.

## Current baseline

The audit found a strong functional baseline:

- 25 customer `/v1` routes are live.
- All 25 runtime routes have exact path counterparts in `openapi/openapi.yaml`.
- Planned endpoint definitions are retained internally but filtered from the public catalogue and documentation.
- 229 PHP tests pass with 2,152 assertions.
- PHPStan passes with no errors.
- TypeScript checking passes.
- The production frontend build succeeds.
- `composer.json` is valid and the locked Composer dependency audit reports no known advisories.
- Durable account-level quotas, endpoint breakdowns, change feeds, SIC bulk export, Stripe billing, authentication, passkeys, structured legal pages, transactional mail, source health sampling, and dataset snapshots are already present.

The local quality gates are green: Pint, PHPStan, PHPUnit, Vite Plus formatting
and linting, TypeScript, the production frontend build, Composer audit, OpenAPI
parsing, configuration caching, and route caching all pass.

## Release baseline completed

The repository-owned P0 release-baseline work is complete and has been verified in
the current branch:

- formatting and frontend quality gates are clean;
- CI runs the same Composer backend gate used locally;
- environment, Valkey, MOT, and legal configuration checks agree with the runbook;
- PHPUnit is configured to fail the build for risky or assertion-free tests;
- independent public-page, entitlement, SIC-export, and notification cases use
  named PHPUnit 12 data-provider datasets;
- `AGENTS.md` and `ROADMAP.md` are committed repository guidance.

The remaining legal launch prerequisite is not code-owned: approved legal entity,
postal address, company-number applicability, ICO applicability, retention wording,
and owner/legal sign-off must be supplied through the deployment environment and
approval process. The readiness command intentionally keeps rejecting missing or
placeholder values; no invented identity has been added to the repository.

## P1: Production operations and resilience

### P1.1 Add MOT to health, status, and admin provider visibility

**Completed 25 October 2026.** The scheduled monitor now performs a bounded
OAuth and authenticated DVSA sample, shares the customer provider budget,
persists safe failure categories, and feeds public status, admin visibility,
operator alerts, and focused fixture-backed tests without retaining secrets or
provider bodies.

**Why:** MOT endpoints are live, but `HealthStatus::SERVICES`, `HealthProbe`, and the admin provider list cover Postcodes.io, Police.uk, Environment Agency, Companies House, and SIC only.

Work:

- Add a bounded DVSA configuration/token/API probe that does not expose or persist token bodies.
- Share the provider budget and avoid using customer quota.
- Distinguish missing configuration, rejected credentials, token failure, API failure, and stale measurement safely.
- Add MOT to `/status`, admin provider visibility, operator alerts, fixtures/fakes, and health tests.

**Acceptance criteria:** A bad MOT credential is visible as a controlled provider failure before customers discover it, with no secret leakage.

### P1.2 Expand the production-readiness command

**Completed 26 October 2026.** The command now checks the existing provider,
legal, database, cache, mail, and Stripe settings plus the release identifier,
HTTPS web/API host and CORS alignment, health-monitoring and operator-alert
enablement or explicit waivers, writable SIC refresh storage, and explicit
Laravel Cloud scheduler/queue-worker expectations. It never prints credential
values. `--probe` remains limited to bounded MySQL and Valkey connectivity checks.

### P1.3 Move non-critical notifications off web/API requests

No notification currently implements `ShouldQueue`, and there is no queued application work in `app/`. Sign-in, API-key, subscription, rotation, and operator mail therefore depend directly on mail transport latency and availability.

Work:

- Classify which emails must remain synchronous, if any.
- Queue security, subscription, API-key, usage, rotation, and health notifications with safe retry/backoff rules.
- Configure a Laravel Cloud worker for the chosen database or Valkey queue.
- Ensure mail failure cannot reverse a successful login, key operation, webhook, API request, or health measurement.
- Add failed-job monitoring and test queue boundaries.
- Consider deduplicating routine sign-in alerts so users are informed without being spammed on every familiar login.

### P1.4 Establish independent uptime and synthetic API monitoring

The current scheduler and `/status` run inside the same application they monitor. That is useful for provider visibility but cannot satisfy independent availability monitoring by itself.

Work:

- Monitor `/up`, `/ready`, the public website, OpenAPI, authentication entry points, and one safe synthetic API request externally.
- Alert on latency and repeated failures, not a single transient response.
- Keep the synthetic API key tightly scoped/limited and outside source control.
- Link incident handling and operator contacts from `HEALTH_MONITORING.md`.

### P1.5 Test backup and restore, not only backup creation

Work:

- Document Laravel Cloud MySQL backup frequency, retention, encryption, and ownership.
- Perform and record a restore drill into an isolated environment.
- Confirm migrations, Cashier subscription records, durable usage, plan catalogue, snapshot heads, service health, and users survive as intended.
- Document recovery-time and recovery-point expectations.
- Include persistent SIC snapshot storage and any queue/failed-job data in the recovery decision.

### P1.6 Add MySQL integration coverage to CI

SQLite is fast and should remain the default test database, but it cannot validate every MySQL locking, collation, JSON, index, or concurrent quota behaviour.

Work:

- Add a focused MySQL CI job for migrations, durable usage/concurrency, plan catalogue, snapshots, and representative provider-backed feature tests.
- Keep the full fast SQLite suite.
- Verify a fresh MySQL schema and an upgrade from a representative prior migration state.

### P1.7 Improve observability around the API contract

The app records durable usage and provider health but does not yet expose the full KPI/operational picture described in the reports.

Add privacy-conscious measurements for:

- request count and status family by stable endpoint code;
- p50/p95 latency by endpoint family;
- cache hit/stale/negative-cache rates;
- upstream latency, timeout, rejection, throttle, and schema-error rates;
- quota rejections and rate-limit rejections;
- registration to first successful request;
- active API keys, returning developers, conversion, cancellation, and churn where lawful and useful.

Do not record API keys, request payloads, full query values, provider bodies, or unnecessary personal data.

### P1.8 Add targeted resilience controls

Provider adapters have bounded timeouts and retries, but customer-path request coalescing and circuit breaking are not currently implemented.

Work incrementally where production evidence justifies it:

- coalesce concurrent cache misses for expensive identical provider requests;
- add short provider circuit breakers to stop repeated calls during confirmed outages;
- apply concurrency caps so one degraded provider cannot exhaust PHP workers;
- preserve provider-specific stale rules, especially no stale flood warnings;
- test recovery and half-open behaviour.

Avoid building a generic resilience framework before two or more providers genuinely share the same semantics.

### P1.9 Security hardening review

Work:

- Add and report-test log redaction for bearer tokens, OAuth responses, Stripe data, passkey material, and provider credentials.
- Evaluate a nonce-compatible Content Security Policy for the Inertia/Vite application and transactional surfaces.
- Review HSTS scope before adding `includeSubDomains` or preload because the API and any other subdomains must already be HTTPS-safe.
- Review session encryption, trusted proxies/hosts, CORS, CSRF exceptions, webhook signatures, password/passkey flows, and API-key brute-force controls in the deployed topology.
- Add dependency/security scanning that complements, rather than duplicates, Composer and npm audits.

## P1: Contract and maintenance automation

### P1.10 Add automated contract parity tests

The audit manually confirmed exact parity between 25 runtime routes and 25 OpenAPI paths, but CI currently only parses the YAML.

Add tests that fail when:

- a `/v1` runtime route is absent from OpenAPI or vice versa;
- a metered route lacks `ukapi_endpoint`;
- a stable endpoint code lacks a usage-dashboard label;
- a live catalogue endpoint is missing from routes/OpenAPI;
- a public route is accidentally marked live before deployment;
- a plan-gated route lacks the documented feature requirement.

Method, parameters, bounds, and response-schema parity can be added progressively after path parity.

### P1.11 Remove duplicated plan/pricing truth

Plan quotas and prices currently exist in configuration/database records and again as hard-coded public TypeScript content. This can make marketing, Checkout, and entitlements disagree.

Work:

- Render public pricing from the normalised server-side plan catalogue or a generated, validated shared artifact.
- Keep display copy separate from entitlement values.
- Verify monthly/annual amounts, quotas, page sizes, features, commercial-use status, and Stripe price availability together.
- Add a public-pricing/entitlement parity test.

### P1.12 Add scheduled source/licensing review

Create an owned review cadence for each source covering:

- licence and attribution wording;
- redistribution and historical-retention rights;
- geographic scope;
- upstream beta/stability status;
- published rate limits;
- schema/deprecation notices;
- public source register and legal-policy accuracy.

This is particularly important before expanding Companies House history, filing data, bulk exports, or Planning Data.

## P2: Near-term API expansion

Implement each vertical slice completely across provider/domain/controller, contract, documentation, source register, health, usage labels, fixtures, and tests.

### P2.1 Low-cost geography and calendar utilities

Recommended first because they add useful breadth with relatively low operational risk:

1. `GET /v1/outcodes/{outcode}` using the existing Postcodes.io boundary.
2. `GET /v1/councils/postcode/{postcode}`.
3. `GET /v1/councils/{code}` backed by maintained council reference metadata.
4. `GET /v1/bank-holidays`.
5. `GET /v1/bank-holidays/{division}` using the official GOV.UK source.
6. `GET /v1/working-days` as a bounded derived calculation.
7. `GET /v1/tax-years/current` and `GET /v1/tax-years/{year}` as clearly scoped utilities, not tax advice.

The internal endpoint catalogue already contains non-live planning entries for these routes. Review and complete them rather than creating competing definitions.

### P2.2 Food hygiene family

Candidate endpoints:

- `GET /v1/food-establishments/{id}`
- `GET /v1/food-establishments/search`
- `GET /v1/food-establishments/nearby`

Requirements:

- preserve differences between FHRS and FHIS rating schemes;
- make provider coverage and freshness explicit;
- resolve postcode proximity through the existing geography boundary;
- set bounded pagination/radius rules;
- confirm attribution, caching, and redistribution terms before launch.

### P2.3 Planning constraints family

Candidate endpoints:

- `GET /v1/planning/constraints?postcode=...`
- `GET /v1/planning/constraints?lat=...&lng=...`

Treat this as higher risk than calendar or food work:

- Planning Data remains source-specific and England-only unless reliable equivalents are integrated deliberately.
- Start with a curated set of well-understood constraints.
- Label beta/source limitations and never present results as an official property search.
- Design stable normalised geometry/constraint identifiers before exposing the contract.
- Review licensing, cache duration, source completeness, and outage behaviour first.

### P2.4 Expand historical snapshots and change feeds

The snapshot store already supports SIC, crime, and flood-reading data. The next candidate is Companies House, only after confirming retention and redistribution rights.

Suggested order:

1. company profile snapshots scoped by company number and capture date;
2. officer appointment changes keyed by stable appointment identity;
3. filing metadata changes keyed by transaction ID;
4. customer-visible history/change endpoints gated by plan.

Keep warnings out of historical flood feeds unless the product, safety, and licensing implications are explicitly revisited.

### P2.5 Broaden bulk export carefully

SIC CSV export is the existing pattern. Before adding other exports:

- confirm source terms and reconstruction restrictions;
- use immutable snapshot/version metadata;
- enforce plan, quota, size, and concurrency limits;
- stream large exports rather than building them in PHP memory;
- consider queued generation and expiring signed downloads for genuinely large data.

## P2: Customer and operator experience

### P2.6 Improve usage insight without request logging

The usage page currently shows monthly total and per-endpoint aggregate counts. Useful additions that preserve the privacy model:

- daily usage trend;
- plan/quota projection;
- rate-limit and quota rejection counts;
- previous-month comparison;
- downloadable aggregate CSV;
- clearer upgrade prompts at notification thresholds.

Do not add raw request payload, bearer-key, arbitrary query, or sensitive response logging.

### P2.7 Mature API-key management

Potential improvements:

- user-initiated key rotation that creates a replacement and supports a short controlled overlap;
- optional endpoint-family scopes;
- last-used endpoint family and approximate timestamp, without payload logging;
- optional IP allow lists for advanced plans;
- clearer test/live key semantics if the environments gain behavioural differences;
- audit-event display and security export.

Each feature needs a migration path for existing keys and must preserve one-time secret display.

### P2.8 Improve admin operations

The current admin area is intentionally small. Add only operationally useful capabilities:

- endpoint/provider error and latency overview;
- failed health incidents and acknowledgement notes;
- user plan/subscription state with safe Stripe links;
- quota/rate-limit hotspots;
- failed jobs and notification delivery health;
- source snapshot age/version;
- auditable, narrowly authorised support actions.

Do not build arbitrary impersonation or database-edit screens without a clear support need and strong audit controls.

### P2.9 Accessibility and browser regression coverage

Add automated coverage for critical public/auth/dashboard journeys while retaining manual visual review:

- keyboard navigation and focus visibility;
- form labels/errors and two-factor/passkey fallbacks;
- mobile navigation and overflow at 320px;
- docs anchors and copy controls;
- reduced-motion behaviour;
- checkout/portal form navigation boundaries;
- basic automated accessibility checks on representative pages.

### P2.10 Retention and deletion operations

Turn approved policy wording into an operational retention schedule:

- map each stored table/log/cache/backup/mail/support record to purpose, owner, and retention;
- confirm account deletion cascades/anonymises the intended local data;
- preserve records only where an approved legal/business basis requires it;
- document Stripe, mail provider, Laravel Cloud log, and backup retention as third-party/system boundaries;
- add scheduled pruning/anonymisation commands and tests where data is not deleted immediately;
- keep privacy copy aligned with actual behaviour.

Obtain legal approval before changing retention promises.

## P3: Demand-led product work

Do not start these merely because they were mentioned in the original reports. Prioritise them after customer demand and operational evidence.

### Additional data families

- weather and location conveniences;
- schools and education reference data;
- NHS/service directories where reuse permits;
- energy performance/property datasets;
- elections and constituencies;
- station and transport references;
- properly licensed commercial address lookup.

### Developer ecosystem

- generated or hand-supported SDKs, starting with TypeScript and PHP only when the OpenAPI contract is stable enough;
- Postman/Bruno examples generated from the maintained OpenAPI document without reintroducing Scribe;
- webhook notifications for opted-in dataset changes;
- MCP/tool integrations for agentic applications;
- status/incident subscriptions.

### Account and commercial expansion

- organisation/team accounts, roles, shared keys, and consolidated billing;
- enterprise quotas, support workflows, audit exports, and contractual SLAs;
- usage-based overages only after explicit product/legal/billing design;
- batch APIs where source terms and abuse controls allow them.

### Infrastructure deliberately deferred

Continue to avoid microservices, Kafka, Kubernetes, Elasticsearch/OpenSearch, GraphQL, multi-region active/active, a custom billing engine, or a data lake until measured constraints justify them. The current modular Laravel monolith remains the preferred architecture.

## Suggested execution order

1. Supply and approve the production legal identity and retention values.
2. Queue notifications and verify Laravel Cloud worker/scheduler operation.
3. Establish external uptime monitoring and complete a restore drill.
4. Add MySQL CI and contract-parity tests.
5. Remove duplicated plan/pricing truth.
6. Add low-risk outcode, council, bank-holiday, working-day, and tax-year endpoints.
7. Implement food hygiene.
8. Expand observability, resilience, snapshots, and customer usage insight based on production data.
9. Improve admin operations and add a privacy-conscious marketing workflow only after the operational and legal foundations are approved.
10. Approach planning, broader exports, teams, SDKs, and new datasets only after licensing review and demand validation.
