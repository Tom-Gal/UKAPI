# Source and licensing review

`config/source_reviews.php` is the controlled register for every live external
source used by UKApi.co.uk. It contains the public source-register wording and
the internal review owner, cadence, last review date, review status and review
topics. Do not put credentials, provider response bodies or customer data in
this register.

The scheduled command is:

```sh
php artisan ukapi:sources:review --due
```

It runs every Monday at 09:00 through Laravel's scheduler, with the same
single-server and overlap protections as the other maintenance commands. A
non-zero result means that at least one source review is due. The command does
not fetch provider pages automatically: source terms, attribution, coverage,
stability, rate limits, schema notices and public wording require deliberate
human review and an auditable change.

For an operator-led review, run the full report:

```sh
php artisan ukapi:sources:review
```

After completing a review:

1. Check the official source documentation and any current licence or API
   terms linked in the register.
2. Confirm licence scope, attribution, coverage, retention, redistribution,
   schema/deprecation notices and published limits for the actual fields and
   endpoints UKApi.co.uk uses.
3. Reconcile the public source register, endpoint catalogue, OpenAPI examples,
   CSV/export attribution, legal copy and provider configuration.
4. Update `last_reviewed_at`, `review_status` and the relevant source notes in
   `config/source_reviews.php`.
5. Add or update focused tests when public wording, source scope, caching,
   retention or redistribution behaviour changes.
6. Run the relevant provider, contract and public-page tests before release.

The registry intentionally marks mixed-rights, authorised-access and bulk
redistribution cases for follow-up. A source being publicly accessible does
not by itself grant UKApi.co.uk or its customers permission to cache,
redistribute, retain or commercially use every field.
