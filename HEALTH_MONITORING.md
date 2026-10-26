# Service health monitoring

`php artisan ukapi:health:check` samples application dependencies and selected
Postcodes.io, Police.uk, Environment Agency, Companies House and DVSA MOT
History operations. It also validates the local SIC reference. The MOT check
uses the normal cached OAuth client-credential token and samples the
authenticated bulk-download manifest without downloading vehicle files. The
scheduler runs it every five minutes with a distributed lock. Store the
scheduler and lock cache on shared
infrastructure when more than one application node is running.

The public `/status` page starts as **unknown**, shows **unavailable** after a
failed completed check, and becomes **stale** when no recent attempt completes.
A successful provider request proves only that sampled operation. Police.uk's
reported data month and the local SIC retrieval age are displayed separately;
other providers remain `unknown` for source freshness because their sampled
responses do not expose a reliable publication date.

Configure thresholds with the `HEALTH_*` variables in `.env.example`. In
production, set `HEALTH_MONITORING_ENABLED=true` or explicitly record a waiver
with `HEALTH_MONITORING_WAIVED=true`. To email an operator, set
`HEALTH_ALERTS_ENABLED=true` and a valid `HEALTH_ALERT_EMAIL`; otherwise set
`HEALTH_ALERTS_WAIVED=true` and document the external alert owner. The default
sends after two consecutive failures, repeats
after six hours, and sends one recovery message. Alert state is durable in
MySQL, so clearing cache does not duplicate an incident notification.

Use an independent external monitor for `/up`, `/ready`, `/status`, and the
scheduler heartbeat. In-process monitoring cannot notify anyone when the whole
application, scheduler, database, cache, network or mail transport is down.
Provider probes consume the same provider request budgets as customer traffic;
keep their frequency within source terms and published limits.

MOT failures are classified without retaining response bodies or secrets:
incomplete configuration, rejected credentials, token-service failure, invalid
token response, provider API failure, invalid API response, or exhausted local
provider budget. The public status page and admin provider register show only
these safe categories. Operator failure alerts include the same category so a
bad or expired credential is visible before customer reports arrive.

Before launch, verify:

1. The scheduler runs `ukapi:health:check` every five minutes.
2. `/status` changes from unknown after the first run and becomes stale when
   the scheduler is stopped for longer than `HEALTH_STALE_AFTER_SECONDS`.
3. A test failure reaches the operator after the configured threshold and a
   later successful check sends recovery.
4. The external monitor alerts independently when the app or scheduler stops.
