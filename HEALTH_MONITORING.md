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

## Independent synthetic monitoring

`.github/workflows/production-monitor.yml` runs outside Laravel Cloud every five
minutes. It checks the public website, `/up`, `/ready`, OpenAPI, `/login`,
`/register`, and one authenticated postcode request. Each target must fail or
exceed its latency threshold repeatedly before the workflow fails. A sustained
failure opens one GitHub incident issue; a later successful run closes it.

Configure these repository settings before enabling the workflow:

- secret `SYNTHETIC_API_KEY`: a dedicated Free-account test key scoped only to
  the `geography` endpoint family. Never reuse a customer or operator key and do
  not place the value in repository variables, workflow output or issue text;
- optional variables `SYNTHETIC_WEB_BASE_URL` and `SYNTHETIC_API_BASE_URL` when
  testing a non-production environment;
- optional latency variables `SYNTHETIC_PUBLIC_MAX_MS`,
  `SYNTHETIC_DEPENDENCY_MAX_MS` and `SYNTHETIC_API_MAX_MS` when production
  evidence justifies thresholds different from 2500, 3500 and 6000 ms;
- GitHub Actions failure notifications for the on-call repository owners. The
  repository must have Issues enabled so the workflow can maintain its single
  incident issue.

The synthetic account must have no billing role, no administrator privileges,
one active test key, and no other credentials. Its quota is intentionally small.
Rotate the key through the normal API-key screen and update only the encrypted
GitHub secret. GitHub-hosted runner addresses are not stable, so do not add an IP
allow list unless the monitor is moved to a runner with a controlled egress IP.

This monitor is independent of the application and Laravel Cloud scheduler, but
GitHub Actions is not an SLA monitoring vendor. If contractual paging or
multi-channel escalation is required, reproduce the same checks in the chosen
external uptime service and keep this workflow as a second path.

Provider probes consume the same provider request budgets as customer traffic;
keep their frequency within source terms and published limits.

## Queue and failed-job monitoring

The current Laravel Cloud plan has no persistent Worker cluster, so production
uses the explicit synchronous fallback: `QUEUE_CONNECTION=sync`,
`UKAPI_QUEUE_WORKER_ENABLED=false`, `UKAPI_QUEUE_MONITOR_ENABLED=false`, and
`UKAPI_QUEUE_MONITOR_WAIVED=true`. No jobs are intentionally written to the
database queue in that mode. Monitor the mail provider and the ordinary health
alerts; do not create a scheduler workaround to consume queue jobs.

When a persistent database worker becomes available, remove that waiver and
enable the queue configuration below.

`ukapi:queue:monitor` runs every five minutes and watches the database-backed
mail queue. It reports a degraded queue when any failed job exists, more than
`UKAPI_QUEUE_MONITOR_MAX_PENDING` jobs are waiting, or the oldest job exceeds
`UKAPI_QUEUE_MONITOR_MAX_OLDEST_SECONDS`. Repeated alerts are limited by
`UKAPI_QUEUE_MONITOR_REPEAT_AFTER_MINUTES`, and one recovery alert is sent.

The queue alarm is the only synchronous application email when queue mode is
enabled. It runs from the scheduler, never a web or API request, and deliberately
bypasses the unhealthy queue. Laravel Cloud worker logs and the admin Operations
page provide the job details. Do not include serialized job payloads in incident
tickets because they can contain mail content and customer identifiers.

## Incident ownership and response

- Primary application/provider and queue contact: the approved address in
  `HEALTH_ALERT_EMAIL`.
- Infrastructure contact: the production environment owners in the Laravel
  Cloud organisation. Enable Cloud email or Slack notifications for resource,
  cluster, failed deployment and failed command events.
- Independent monitor record: the open GitHub issue named
  `[monitor] Production synthetic checks failing` and its linked workflow run.
- Recovery procedure and database escalation: `RECOVERY.md`.

For an incident, acknowledge it in the admin Operations page, inspect the
independent monitor and Laravel Cloud logs/metrics, decide whether the failure is
application, queue, MySQL, Redis, mail or upstream-provider owned, and record a
safe note. Never paste secrets, authorization headers, provider bodies, queue
payloads or personal data into GitHub issues or acknowledgement notes.

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
4. The production synthetic workflow fails only after repeated errors or latency
   breaches, opens one issue, and closes it after recovery.
5. When queue mode is enabled, a deliberately stopped Laravel Cloud worker causes
   the queue monitor to alert once the oldest-job threshold is crossed; restarting
   it sends recovery after the backlog drains.
