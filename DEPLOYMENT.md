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
- Attach the Laravel Cloud MySQL resource. Cloud injects its connection credentials; the application default is `DB_CONNECTION=mysql`. Do not copy database passwords into Git or duplicate Cloud-managed credentials.
- Valkey settings with `CACHE_STORE=failover`, a unique `CACHE_PREFIX`, and either `VALKEY_URL` or host/credential values. The failover store prefers Valkey and falls back to the database/array stores if Valkey is temporarily unavailable, keeping authentication and throttling available during a cache restart.
- Mailtrap SMTP values: `MAIL_MAILER=smtp`, `MAIL_HOST=live.smtp.mailtrap.io`, `MAIL_PORT=587`, `MAIL_USERNAME=api`, and a `MAIL_PASSWORD` token in the Cloud secret manager. Set `MAIL_FROM_ADDRESS` to an address on a domain verified in Mailtrap; the local `log` mailer does not deliver verification or password-reset messages.
- `SESSION_SECURE_COOKIE=true`, `SESSION_ENCRYPT=true`, and `SESSION_SAME_SITE=lax`.
- `TRUSTED_PROXIES` only when the host terminates TLS through a proxy you trust. Use the host's documented proxy addresses, or `*` only when the application is reachable exclusively through that trusted ingress.
- Ensure the HTTPS edge preserves `X-Forwarded-Proto` for trusted proxies and serves the `Strict-Transport-Security` response header on both `ukapi.co.uk` and `api.ukapi.co.uk`. The application emits HSTS for requests it recognises as secure.
- `COMPANIES_HOUSE_API_KEY` from the secret manager. The previously exposed credential must not be reused.
- Stripe secret-manager values: `STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET`, `STRIPE_PRICE_HOBBY`, `STRIPE_PRICE_PRO`, `STRIPE_PRICE_SCALE`, `STRIPE_PRICE_HOBBY_ANNUAL`, `STRIPE_PRICE_PRO_ANNUAL`, and `STRIPE_PRICE_SCALE_ANNUAL`. Use live-mode `pk_live_`, `sk_live_`, and `price_` values in production. Run `php artisan stripe:provision-plans` against the applicable Stripe mode to create the documented GBP monthly and annual prices. The command also synchronizes the durable local plan-price catalog, while existing Stripe price IDs remain valid for current subscriptions.
- Create a Stripe webhook for `https://<your-domain>/stripe/webhook`. Subscribe it to Cashier's customer/subscription and invoice events, or run `php artisan cashier:webhook --url=https://<your-domain>/stripe/webhook` to create the complete Cashier event set. Store its signing secret as `STRIPE_WEBHOOK_SECRET`. The release readiness command rejects a production configuration without it.

Keep `SIC_REFERENCE_SNAPSHOT_PATH` on writable persistent storage if the refresh
command will run at runtime. A versioned SIC reference is bundled so first boot
does not depend on an outbound request.

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

Restart any PHP workers after deploy. Create a Laravel Cloud queue worker when
moving queued work off the request path, then include `php artisan queue:restart`
as part of the release.

Enable Laravel Cloud's Scheduler for the production environment. It runs the
daily API-key rotation reminder, which sends customers a grouped reminder when
an active key reaches 90 days and again no sooner than every 90 days.

## Operational checks

- `/up` is a lightweight liveness endpoint.
- `/ready` verifies MySQL and the configured cache without disclosing dependency details; it returns `503` when either is unavailable.
- Run `php artisan ukapi:sic:refresh` deliberately after confirming the source and its terms. It never runs during a customer request.
- Promote the first verified operator with `php artisan ukapi:grant-admin operator@example.com`.
- Back up MySQL before migrations and verify email delivery, Valkey connectivity, CORS, API-key issuance, and the live Companies House credential before widening access.

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
