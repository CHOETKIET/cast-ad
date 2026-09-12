# iCHI-COOL Play Webapp

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
ICHICOOL_DB_HOST
ICHICOOL_DB_PORT
ICHICOOL_DB_SOCKET
ICHICOOL_DB_NAME
ICHICOOL_DB_USER
ICHICOOL_DB_PASS
```

For local MySQL through a socket, set `ICHICOOL_DB_SOCKET=/tmp/mysql.sock`.
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
