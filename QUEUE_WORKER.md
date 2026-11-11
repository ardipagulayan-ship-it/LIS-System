# Queue Worker (Laravel)

The LIS uses Laravel's database queue driver for asynchronous work
(notably ULM dispatch). The queue worker MUST be running for the
integration to function.

---

## 1. Quick start (development)

```bash
cd backend
php artisan queue:work --tries=3 --backoff=5 --sleep=1 --timeout=60
```

This blocks the terminal and processes jobs as they arrive.

---

## 2. Production deployment (Supervisor)

Create `/etc/supervisor/conf.d/lis-queue.conf`:

```ini
[program:lis-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/lis/backend/artisan queue:work database --sleep=1 --tries=3 --backoff=5 --timeout=60 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/lis/backend/storage/logs/queue-worker.log
stopwaitsecs=3600
```

Adjust `user`, `numprocs`, and paths for your environment.

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start lis-queue:*
sudo supervisorctl status
```

---

## 3. systemd alternative (no supervisor)

Create `/etc/systemd/system/lis-queue.service`:

```ini
[Unit]
Description=LIS Laravel Queue Worker
After=network.target mysql.service

[Service]
User=www-data
Group=www-data
WorkingDirectory=/var/www/lis/backend
ExecStart=/usr/bin/php artisan queue:work database --sleep=1 --tries=3 --backoff=5 --timeout=60
Restart=always
RestartSec=5
StandardOutput=append:/var/www/lis/backend/storage/logs/queue-worker.log
StandardError=append:/var/www/lis/backend/storage/logs/queue-worker.log

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl daemon-reload
sudo systemctl enable --now lis-queue
sudo journalctl -u lis-queue -f
```

---

## 4. Queue tables

The `jobs` table was added in migration
`2026_09_03_023958_create_jobs_table.php`. Run migrations:

```bash
php artisan migrate
```

The `failed_jobs` table was already present (Laravel default).

---

## 5. Monitoring

| Signal | Where to look |
|---|---|
| Pending jobs | `SELECT COUNT(*) FROM jobs;` |
| Failed jobs | `SELECT COUNT(*) FROM failed_jobs;` |
| Recent failures | `SELECT * FROM failed_jobs ORDER BY failed_at DESC LIMIT 20;` |
| ULM-specific logs | `storage/logs/ulm.log` |
| Worker process logs | `storage/logs/queue-worker.log` |

To retry a single failed job:
```bash
php artisan queue:retry <uuid>
```

To retry all:
```bash
php artisan queue:retry all
```

To flush the queue (DANGER):
```bash
php artisan queue:flush
```

---

## 6. Restarting the worker after code deploys

`queue:work` is a long-running process — it caches the application
bootstrapping. After a deploy, you **must restart** the worker:

```bash
# supervisor
sudo supervisorctl restart lis-queue:*
# OR systemd
sudo systemctl restart lis-queue
# OR for a manual worker, just kill it and restart
```

---

## 7. Disabling ULM temporarily

Set `ULM_ENABLED=false` in `.env` and restart the queue. The Job will
short-circuit on the `enabled` check and log "dispatch skipped" without
making any HTTP calls.