# Beta production runbook

This repository's CI workflow verifies every pushed commit. Hosting remains the
deployment authority; no second deployment workflow is included here. Configure
the hosting provider to wait for the `Verify` check (or protect `master`) if a
failed verification must block a beta deployment.

## Required production configuration

Set these values in the hosting provider's encrypted environment configuration,
never in Git:

- `APP_ENV=production`, `APP_DEBUG=false`, and `APP_URL` set to the final HTTPS origin.
- A unique `APP_KEY`, plus a release identifier in `APP_VERSION` (normally the commit SHA).
- Set `UKAPI_API_URL=https://api.ukapi.co.uk` and include the public web origin in `CORS_ALLOWED_ORIGINS`; both origins must be HTTPS in production.
- Attach the Laravel Cloud MySQL resource. Cloud injects its connection credentials; the application default is `DB_CONNECTION=mysql`. Do not copy database passwords into Git or duplicate Cloud-managed credentials.
- Valkey settings with `CACHE_STORE=redis`, a unique `CACHE_PREFIX`, and either `VALKEY_URL` or host/credential values. Laravel's Redis-compatible driver talks to Valkey through Predis. Production deliberately uses the shared store directly so rate limits and scheduler locks cannot fall back to process-local state. Monthly quotas use MySQL separately and never fall back to cache.
- Mailtrap SMTP values: `MAIL_MAILER=smtp`, `MAIL_HOST=live.smtp.mailtrap.io`, `MAIL_PORT=587`, `MAIL_USERNAME=api`, and a `MAIL_PASSWORD` token in the Cloud secret manager. Set `MAIL_FROM_ADDRESS` to an address on a domain verified in Mailtrap; the local `log` mailer does not deliver verification or password-reset messages.
- `SESSION_SECURE_COOKIE=true`, `SESSION_ENCRYPT=true`, and `SESSION_SAME_SITE=lax`.
- `TRUSTED_PROXIES` only when the host terminates TLS through a proxy you trust. Use the host's documented proxy addresses, or `*` only when the application is reachable exclusively through that trusted ingress.
- Ensure the HTTPS edge preserves `X-Forwarded-Proto` for trusted proxies and serves the `Strict-Transport-Security` response header on both `ukapi.co.uk` and `api.ukapi.co.uk`. The application emits HSTS for requests it recognises as secure.
- `COMPANIES_HOUSE_API_KEY` from the secret manager. The previously exposed credential must not be reused.
- DVSA MOT History `MOT_HISTORY_CLIENT_ID`, `MOT_HISTORY_CLIENT_SECRET`, `MOT_HISTORY_API_KEY`, and the issued scope/token URLs. The application obtains and caches access tokens automatically; do not maintain a long-lived `MOT_HISTORY_ACCESS_TOKEN`. Vehicle responses use a 15-minute positive cache and 10-minute not-found cache by default; tune `MOT_HISTORY_CACHE_TTL_MINUTES` and `MOT_HISTORY_NEGATIVE_CACHE_TTL_MINUTES` only with a documented operational reason.
- Approved `LEGAL_ENTITY_NAME`, `LEGAL_REGISTERED_OFFICE`, and `LEGAL_CONTACT_EMAIL` values. Set `LEGAL_COMPANY_NUMBER` and `LEGAL_ICO_NUMBER` when applicable; otherwise leave them empty. The readiness command rejects blanks or known placeholders for the required identity fields.
- Set `UKAPI_SCHEDULER_ENABLED=true` when Laravel Cloud's scheduler is enabled. Production uses `QUEUE_CONNECTION=database`, `UKAPI_QUEUE_WORKER_ENABLED=true`, and `UKAPI_QUEUE_MONITOR_ENABLED=true`. Keep `HEALTH_ALERT_EMAIL` valid because the queue monitor sends its own out-of-band operator alert when the queue is stalled.
- Stripe secret-manager values: `STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET`, `STRIPE_PRICE_HOBBY`, `STRIPE_PRICE_PRO`, `STRIPE_PRICE_SCALE`, `STRIPE_PRICE_HOBBY_ANNUAL`, `STRIPE_PRICE_PRO_ANNUAL`, and `STRIPE_PRICE_SCALE_ANNUAL`. Use live-mode `pk_live_`, `sk_live_`, and `price_` values in production. Run `php artisan stripe:provision-plans` against the applicable Stripe mode to create the documented GBP monthly and annual prices. The command also synchronizes the durable local plan-price catalog, while existing Stripe price IDs remain valid for current subscriptions.
- Create a Stripe webhook for `https://<your-domain>/stripe/webhook`. Subscribe it to Cashier's customer/subscription and invoice events, or run `php artisan cashier:webhook --url=https://<your-domain>/stripe/webhook` to create the complete Cashier event set. Store its signing secret as `STRIPE_WEBHOOK_SECRET`. The release readiness command rejects a production configuration without it.

The active SIC reference is stored durably in MySQL's immutable dataset
snapshot tables. `SIC_REFERENCE_SNAPSHOT_PATH` is only the refresh command's
local staging copy on Laravel Cloud's ephemeral application filesystem. The
application reads the active MySQL snapshot first and falls back to the bundled
versioned reference on first boot, so the staging file is not a recovery
dependency. Set `SIC_REFERENCE_REFRESH_ENABLED=true` only when operators intend
to run the explicit refresh command.

## Laravel Cloud queue worker

Customer, security, authentication, subscription, usage, rotation and provider
health mail is queued after database commit. `mail-critical` contains password,
verification, security, API-key and provider-health alerts; `mail` contains
subscription, usage and routine rotation mail. Every job has four attempts, a
45-second timeout and 60, 300 and 900-second backoff intervals.
Until `UKAPI_QUEUE_WORKER_ENABLED=true` is explicitly present, notifications use
the synchronous connection as a safe rollout fallback rather than accumulating
undeliverable database jobs. Enable the flag only with the worker running.

Configure the production environment from Laravel Cloud's infrastructure
canvas:

1. Add a dedicated **Worker cluster** in the same region as the App and Laravel
   MySQL resources. Do not put the production queue process on the App cluster,
   where mail processing would compete with web/API traffic.
2. Add one Laravel queue-worker background process with one process initially.
   Use the database connection and priority order:

    ```sh
    php artisan queue:work database --queue=mail-critical,mail --sleep=3 --tries=4 --timeout=60 --max-time=3600
    ```

3. Set `QUEUE_CONNECTION=database`, `DB_QUEUE_RETRY_AFTER=120`,
   `UKAPI_QUEUE_WORKER_ENABLED=true` and `UKAPI_QUEUE_MONITOR_ENABLED=true` in
   the environment, then redeploy. Laravel Cloud restarts queue workers after
   each deployment, so do not add a separate `queue:restart` deployment step.
4. Keep the Laravel Cloud Scheduler enabled. It runs
   `ukapi:queue:monitor` every five minutes and synchronously emails the operator
   only when pending count, oldest-job age, or failed jobs breach their
   thresholds. This scheduler-only mail is deliberately not placed on the queue
   it monitors.
5. Verify dispatch and consumption by requesting a password reset in a test
   account, checking that `jobs` returns to zero, and confirming delivery. The
   admin Operations page must show no failed jobs and no stale backlog.

Laravel Cloud managed queues are not used for this deployment. If the service
later migrates to them, update the driver, dependency, readiness check, admin
metrics and recovery policy together rather than attaching a managed queue
silently.

## Release sequence

Run the following from the release artifact after its environment is configured:

```sh
composer install --no-dev --prefer-dist --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan optimize
php artisan ukapi:production:check --probe
```

Laravel Cloud rolls the App and Worker clusters during deployment. Confirm both
clusters report the new release before widening traffic.

Enable Laravel Cloud's Scheduler for the production environment and set
`UKAPI_SCHEDULER_ENABLED=true`. It runs the
daily API-key rotation reminder, which sends customers a grouped reminder when
an active key reaches 90 days and again no sooner than every 90 days.
It also runs sampled service checks and queue health monitoring every five
minutes, plus snapshot pruning daily. See `HEALTH_MONITORING.md` before enabling
operator alerts. If alerts are not enabled, set `HEALTH_ALERTS_WAIVED=true` and
document the external alert owner. Queue monitoring should not be waived in
production.

## Operational checks

- `/up` is a lightweight liveness endpoint.
- `/ready` verifies MySQL and the configured cache without disclosing dependency details; it returns `503` when either is unavailable.
- Run `php artisan ukapi:sic:refresh` deliberately after confirming the source and its terms. It never runs during a customer request.
- Promote the first verified operator with `php artisan ukapi:grant-admin operator@example.com`.
- Create a named Laravel Cloud manual MySQL backup before risky migrations and follow `RECOVERY.md`; backup creation alone is not proof that restoration works.
- Run `php artisan ukapi:queue:monitor`, confirm it reports a healthy empty backlog, and inspect Laravel Cloud worker logs.
- Run `php artisan ukapi:health:check` and confirm the MOT observation passes token acquisition and the authenticated DVSA API sample before widening access. The command never prints tokens or provider bodies.

## Dataset snapshots and change feeds

The dataset_snapshots table stores immutable, checksummed payloads. The
dataset_snapshot_heads table is only a mutable pointer to the active version,
so publishing a new source extract never rewrites history. The daily
ukapi:snapshots:prune task removes snapshots older than
SNAPSHOT_RETENTION_DAYS while retaining every active version.

Police.uk monthly crime snapshots, Companies House SIC reference snapshots, and
Environment Agency station-reading snapshots now use the store. Their scopes
are respectively postcode/month, the SIC reference, and station id. Each has a
stable record identity so deterministic added, removed, and changed diffs do
not rely on a mutable upstream response.

The same store can support the next datasets with different scope and identity
rules:

- Flood change feeds currently cover station readings, scoped by station id and
  using the measure id plus observation timestamp as identity. Warnings remain
  operationally sensitive and rapidly mutable, so they are explicitly excluded
  from the historical feed and must not be treated as an alerting or safety
  guarantee.
- Companies House should start with company profile snapshots, scoped by
  company number and capture date, then add officers and filings as separate
  dataset names. Stable company numbers, officer appointment ids, and filing
  transaction ids should be used as identities. The implementation must also
  confirm Companies House API caching, retention, and redistribution terms
  before making these snapshots customer-visible.

Both extensions should reuse SnapshotStore, but each provider should define
its own canonical normalisation, source timestamp, identity function, and
retention policy rather than treating all upstream records as interchangeable.

## Launch-hardening release notes

Apply all migrations before serving traffic. This release adds durable usage
counters, service-health observations and the permanent first-success marker.
Restart application and queue workers after migration.

### Import current-month usage

If an existing installation has already admitted requests this month, use a
maintenance window. Stop old web workers, run migrations, and import from the
shared store that held the old counters before reopening traffic:

```sh
php artisan ukapi:usage:import-cache --store=redis
# Or, only when the old counters were in the database cache:
php artisan ukapi:usage:import-cache --store=database
```

Run with the original `CACHE_PREFIX` and do not clear the old cache first. The
import is idempotent and never lowers a durable total. It cannot recover lost
process-local counters or combine counters split across failover stores; those
must be reconciled before reopening. New installations can skip this command.

All account keys share a hard quota for a UTC calendar month. Validation errors
count after admission; quota-rejected requests do not. An accounting database
failure returns HTTP 503 rather than granting unmetered access. Annual billing
does not change the monthly reset. No overage billing is enabled.

First successful API requests are recorded from this deployment onward;
historical usage cannot reliably prove that an earlier request succeeded.

Scale accounts can download `GET /v1/sic/export`. A complete CSV download is
one quota-counted request and includes immutable snapshot and attribution data.
