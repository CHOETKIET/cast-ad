# Cast Ad Webapp

PHP 7.4 + MySQL web admin and API for Android digital signage players.

## Local Start

From this folder:

```bash
php -S localhost:8080 -t public public/router.php
```

Open:

```text
http://localhost:8080
```

## Database

Default local database name:

```text
cast_ad_db
```

Import:

```text
database/schema.sql
database/seed.sql
```

Update database credentials in:

```text
app/config/database.php
```

or use environment variables:

```text
CAST_AD_DB_HOST
CAST_AD_DB_PORT
CAST_AD_DB_SOCKET
CAST_AD_DB_NAME
CAST_AD_DB_USER
CAST_AD_DB_PASS
```

Local defaults use database `cast_ad_db`, user `root`, empty password,
and socket `/tmp/mysql.sock`.
For another local MySQL socket, set `CAST_AD_DB_SOCKET=/path/to/mysql.sock`.
For DirectAdmin hosting, leave `socket` empty and use the host, database name,
username, and password from the hosting control panel.

## Default Admin

Seed login for local testing:

```text
Username: admin
Password: admin1234
```

Change this password before production use.

## Production Notes

- Set `base_url` in `app/config/config.php` to the real HTTPS domain.
- Set `environment` to `production`.
- Update `app/config/database.php` with the production MySQL credentials.
- Make sure `public/uploads/images` and `public/uploads/videos` are writable.
- Import `database/schema.sql` first, then `database/seed.sql`.
- After first login, change the default admin password.
- Android should use the site root as API Base URL, for example `https://example.com`.

See `docs/deployment-checklist.md` and `docs/server-test-checklist.md` before deploying.
