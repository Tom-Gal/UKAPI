# UKApi.co.uk Roadmap

**Last audited:** 28 October 2026
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
- 284 PHP tests pass with 3,019 assertions.
- PHPStan passes with no errors.
- TypeScript checking passes.
- The production frontend build succeeds.
- `composer.json` is valid and the locked Composer dependency audit reports no known advisories.
- Durable account-level quotas, endpoint breakdowns, change feeds, SIC bulk export, Stripe billing, authentication, passkeys, structured legal pages, transactional mail, source health sampling, and dataset snapshots are already present.
- The admin area now exposes privacy-safe endpoint latency/error aggregates, provider observations, health incidents and acknowledgement notes, quota/rate-limit hotspots, queue and notification health, snapshot age/version, and audited API-key revocation.
- MOT responses are mapped into a whitelisted typed contract, validated again when read from the semantic cache, and documented with an exact OpenAPI schema rather than exposing the DVSA payload directly.
- All customer and security mail implements after-commit queue delivery with bounded retries, priority handling and familiar sign-in deduplication. The current Laravel Cloud plan has no persistent Worker cluster, so production intentionally uses the safe synchronous fallback until that capability is available; queue backlog and failed-job controls remain ready but inactive.
- Independent GitHub-hosted synthetic checks cover liveness, readiness, public/authentication pages, OpenAPI and a scoped API request with repeated-failure and latency alerting.
- Laravel Cloud MySQL backup/restore policy, RPO/RTO, an isolated restore procedure and a privacy-safe recovery manifest command are documented in `RECOVERY.md`.

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
- customer-route, OpenAPI, live-catalogue, feature-entitlement, endpoint-code,
  usage-label, API-key-scope-family, route-name, and operation-id parity is
  enforced by an automated feature test;
- mail transport failures are isolated from successful sign-in, password,
  API-key, subscription-webhook, usage, health, and support operations;
- notification classes have database-queue, retry and failed-job monitoring
  controls, plus an explicit safe synchronous mode for Laravel Cloud plans
  without persistent workers;
- independent production synthetic monitoring is repository-owned and opens one
  incident after repeated failures rather than paging on a transient response;
- recovery verification covers critical MySQL state and treats the durable MySQL
  SIC snapshot as authoritative on Laravel Cloud;
- `AGENTS.md` and `ROADMAP.md` are committed repository guidance.

The remaining legal launch prerequisite is not code-owned: approved legal entity,
postal address, company-number applicability, ICO applicability, retention wording,
and owner/legal sign-off must be supplied through the deployment environment and
approval process. The readiness command intentionally keeps rejecting missing or
placeholder values; no invented identity has been added to the repository.

## P1: Production operations and resilience

### P1.3 Activate queued notification delivery when persistent workers are available

The application code supports priority database queues, bounded retry/backoff,
queue-boundary tests and failed-job monitoring. It cannot be activated on the
current Laravel Cloud plan because it has no persistent Worker cluster. The
deployed configuration therefore uses `QUEUE_CONNECTION=sync` and an explicit
queue-monitor waiver so mail is delivered rather than left in an unconsumed
database queue.

Work when the hosting capability is available:

- provision a dedicated Laravel Cloud Worker cluster and enable the documented
  `mail-critical,mail` process;
- switch the environment to the database queue configuration and remove the
  monitor waiver;
- verify dispatch, consumption, retry, failed-job alerting and recovery with a
  non-production account; and
- record the operational evidence in `DEPLOYMENT.md` and this roadmap.

### P1.6 Add MySQL integration coverage to CI

Complete in the repository: CI now runs a focused MySQL 8.4 job alongside the
fast full SQLite suite. It validates a fresh schema, rolls back and reapplies
the latest migration, and exercises durable usage, cross-process quota
reservation, plan catalogue, snapshots, recovery manifests and Companies House
fixture mapping against MySQL.

Operational follow-up: review MySQL CI failures alongside Laravel Cloud's MySQL
version when either changes, and expand this job only where a production query
or migration uses MySQL-specific behaviour that the focused suite does not yet
cover.

### P1.7 Improve observability around the API contract

The app records durable usage and provider health. The admin operations view now exposes request/status-family counts, average and bucketed p95 endpoint latency, persisted provider latency, health incidents, rejection hotspots, queue/notification health, and snapshot age without retaining request payloads. It does not yet expose the full KPI/operational picture described in the reports.

Add privacy-conscious measurements for:

- p50 latency and longer-window p95 trends by endpoint family;
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

### P1.10 Expand automated contract parity tests

The first automated parity phase is complete. CI now fails when the 25 runtime routes, OpenAPI operations, live catalogue entries, feature entitlements, endpoint codes, usage labels, API-key scope families, route names, or OpenAPI operation IDs diverge.

Continue progressively with tests that fail when:

- path/query parameter names or required/optional state differ;
- validation and plan-specific bounds differ from OpenAPI;
- documented response or error schemas no longer match representative runtime responses;
- response and quota/rate-limit header declarations drift from middleware behaviour.

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
- set explicit retention for endpoint metrics, health incidents, notification-delivery events, admin/API-key audit events, usage aggregates and rejection counters introduced by the operational dashboards;
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
2. Activate the synthetic-monitor secret and perform the first isolated MySQL restore drill; record the real drill evidence in `RECOVERY.md`.
3. When the Laravel Cloud plan provides a persistent Worker cluster, activate and verify queued notification delivery as P1.3.
4. Add MySQL CI and expand contract-parity coverage to parameters, bounds, schemas, and headers.
5. Remove duplicated plan/pricing truth.
6. Add low-risk outcode, council, bank-holiday, working-day, and tax-year endpoints.
7. Implement food hygiene.
8. Expand observability, resilience, snapshots, and customer usage insight based on production data.
9. Add accessibility/browser coverage and approved retention/deletion operations.
10. Approach planning, broader exports, teams, SDKs, and new datasets only after licensing review and demand validation.
