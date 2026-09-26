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
- Valkey settings with `CACHE_STORE=redis`, a unique `CACHE_PREFIX`, and either `VALKEY_URL` or host/credential values.
- Mailtrap SMTP values: `MAIL_MAILER=smtp`, `MAIL_HOST=live.smtp.mailtrap.io`, `MAIL_PORT=587`, `MAIL_USERNAME=api`, and a `MAIL_PASSWORD` token in the Cloud secret manager. Set `MAIL_FROM_ADDRESS` to an address on a domain verified in Mailtrap; the local `log` mailer does not deliver verification or password-reset messages.
- `SESSION_SECURE_COOKIE=true`, `SESSION_ENCRYPT=true`, and `SESSION_SAME_SITE=lax`.
- `TRUSTED_PROXIES` only when the host terminates TLS through a proxy you trust. Use the host's documented proxy addresses, or `*` only when the application is reachable exclusively through that trusted ingress.
- `COMPANIES_HOUSE_API_KEY` from the secret manager. The previously exposed credential must not be reused.
- Stripe secret-manager values: `STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_PRICE_HOBBY`, `STRIPE_PRICE_PRO`, and `STRIPE_PRICE_SCALE`. Run `php artisan stripe:provision-plans` against the applicable Stripe mode to create the documented GBP monthly prices when needed.
- Create a Stripe webhook for `https://<your-domain>/stripe/webhook`, subscribe it to Cashier's customer/subscription and invoice events, and store its signing secret as `STRIPE_WEBHOOK_SECRET`. The release readiness command rejects a production configuration without it.

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

## Operational checks

- `/up` is a lightweight liveness endpoint.
- `/ready` verifies MySQL and the configured cache without disclosing dependency details; it returns `503` when either is unavailable.
- Run `php artisan ukapi:sic:refresh` deliberately after confirming the source and its terms. It never runs during a customer request.
- Promote the first verified operator with `php artisan ukapi:grant-admin operator@example.com`.
- Back up MySQL before migrations and verify email delivery, Valkey connectivity, CORS, API-key issuance, and the live Companies House credential before widening access.
