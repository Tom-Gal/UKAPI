# Laravel Cloud backup and recovery runbook

This runbook applies to the production Laravel Cloud environment and its
attached Laravel MySQL resource. It does not describe Forge, Vapor, a generic
VM, or a self-managed MySQL server.

## Recovery policy

- Laravel Cloud automatic MySQL backups run daily. Configure the production
  cluster for **14 days** retention in **Organization > Resources > Databases >
  production cluster > Backups > Settings**. Laravel Cloud permits 1 to 30 days;
  its Prod preset starts at 7 days, so verify the selected value rather than
  assuming the preset.
- Create a named manual backup immediately before a risky migration, billing
  repair, destructive maintenance operation, or recovery drill.
- Laravel Cloud restores a backup into a new database cluster. It does not
  overwrite the source cluster. Keep production attached to the original until
  the restored cluster has passed this runbook.
- Laravel Cloud encrypts applications, databases and backups by default. UKApi
  remains the owner of its application data; Laravel Cloud operates the managed
  backup infrastructure. Limit Cloud database and backup permissions to the
  smallest operator group that needs them and review that group quarterly.
- Internal recovery targets are **RPO 24 hours** and **RTO 4 hours**. These are
  UKApi operational targets, not a Laravel Cloud SLA. A named pre-change manual
  backup reduces the effective RPO for planned work.
- Maintain an additional operator-controlled export for business continuity as
  required by Laravel Cloud's terms. Generate it from a restored temporary
  cluster, not by enabling the production database public endpoint for routine
  downloads.

## What the MySQL backup covers

The recovery manifest verifies migrations, users, Cashier subscriptions and
items, plans and Stripe price catalogue, API keys, monthly/daily durable usage,
rejection aggregates, immutable dataset snapshots and active heads, service
health, pending jobs and failed jobs. It uses counts, aggregates and checksums
and never prints API-key hashes, Stripe customer identifiers, email addresses,
queue payloads or snapshot payloads.

The active SIC reference is part of `dataset_snapshots` and
`dataset_snapshot_heads`, so it is recovered with MySQL. The local
`SIC_REFERENCE_SNAPSHOT_PATH` file is a derivative staging file on Laravel
Cloud's ephemeral filesystem and is not a backup dependency. The bundled
version remains the first-deployment fallback.

Valkey contains caches, rate-limit state, scheduler locks, OAuth token cache and
familiar-login deduplication markers. Treat those as reconstructable and do not
restore them. Durable quotas and usage remain in MySQL. After a database restore,
use a fresh `CACHE_PREFIX` if there is any chance that the restored environment
could see production Valkey keys.

Pending database jobs and failed jobs are included in the MySQL backup. During a
drill, do not start a worker against the restored database. For a real recovery,
inspect pending/failed jobs before enabling workers: notification jobs use
at-least-once delivery, so retrying old work may send duplicate mail. Delete a
job only after recording why it is safe to discard.

## Isolated restore drill

Perform this at least quarterly and after a material schema, billing, usage or
snapshot change.

1. Choose an off-peak window. In Laravel Cloud, pause the database queue worker
   and place the application in maintenance mode long enough to produce a stable
   baseline.
2. Run this command through Laravel Cloud Commands and securely retain the
   printed recovery checksum in the drill record:

    ```sh
    php artisan ukapi:recovery:verify --checksum-only
    ```

3. In the production Laravel MySQL cluster's Backups panel, create a named
   manual backup such as `restore-drill-YYYY-MM-DD`. Once Cloud reports it
   complete, bring the application up and resume the worker.
4. From the backup menu select **Restore**, creating a new cluster with an
   unmistakable isolated name. Never attach the restored database to the
   production environment.
5. Create or use an isolated Laravel Cloud recovery environment at the same
   application commit. Attach the restored database. Use a fresh Valkey prefix,
   `MAIL_MAILER=array`, `QUEUE_CONNECTION=sync`,
   `UKAPI_QUEUE_WORKER_ENABLED=false`, `UKAPI_QUEUE_MONITOR_ENABLED=false`, and
   disable Stripe/webhook, provider-health and outbound mail activity. Do not
   expose this environment through production DNS.
6. Run these commands in the isolated environment, replacing the example with
   the production checksum recorded before the backup:

    ```sh
    php artisan migrate:status
    php artisan ukapi:recovery:verify --checksum-only --expect-checksum=<64-character-checksum>
    ```

7. Confirm the manifest matches and specifically review users, subscriptions,
   subscription items, plans/prices, durable usage totals, snapshot heads and
   SIC checksum, service-health rows, pending jobs and failed jobs. Verify no
   orphan checks fail. Do not send customer mail or call Stripe/providers as
   part of the drill.
8. Record backup time, restore start/end, achieved RPO/RTO, row/checksum result,
   operator, anomalies and corrective work in the drill record below. Delete the
   temporary environment and restored cluster after evidence is retained and a
   second operator confirms production was never reattached.

## Real recovery

Declare an incident before changing database attachment. Restore the selected
backup to a new cluster, verify it in isolation, inspect queued jobs, place the
app in maintenance mode, pause workers, attach the verified restored database,
redeploy so Laravel Cloud injects the new credentials, run
`ukapi:production:check --probe`, then resume the worker and traffic. Retain the
old cluster until reconciliation is complete. Stripe remains authoritative for
subscription lifecycle changes that occurred after the restored point; replay
or reconcile signed webhooks deliberately rather than editing Cashier records by
hand. Reconcile usage conservatively so a restore never grants unmetered access.

## Drill record

Do not mark a drill complete until the Laravel Cloud restore has actually run.

| Field                           | Value            |
| ------------------------------- | ---------------- |
| Drill date                      | Not yet recorded |
| Production backup               | Not yet recorded |
| Restored cluster/environment    | Not yet recorded |
| Operator and reviewer           | Not yet recorded |
| Baseline/restore manifest match | Not yet recorded |
| Achieved RPO                    | Not yet recorded |
| Achieved RTO                    | Not yet recorded |
| Queue/failed-job decision       | Not yet recorded |
| SIC active checksum verified    | Not yet recorded |
| Anomalies and follow-up         | Not yet recorded |
