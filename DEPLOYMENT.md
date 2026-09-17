# Deploying to cPanel via GitHub Actions

This app has no SSH access on its cPanel plan, so the pipeline pushes files over
**FTPS** and then calls a secret webhook (`POST /api/deploy/callback`) to run
migrations and rebuild caches on the server. Everything below is a **one-time**
manual setup; after that, every push to `master` deploys automatically.

## 1. PHP version

`composer.lock` currently locks packages that require **PHP 8.4.1+** (the CI
workflow builds with PHP 8.4). In cPanel → **MultiPHP Manager**, set this
domain's PHP version to **8.4** (or newer) — if 8.4 isn't offered, either ask
your host to enable it, or run `composer update` locally against whatever
version cPanel does offer and commit the resulting `composer.lock`.

## 2. Document root (do this first)

Laravel must serve from the `public/` folder, not the project root. In cPanel →
**Domains**, check whether you can set this domain/subdomain's **Document Root**
directly to a `public` subfolder (most cPanel accounts allow this even without
SSH — it's just a dropdown/text field, not a shell operation).

- **If yes (recommended):** point the document root at, e.g.,
  `/home/USER/exams-portal/public`, and have CI upload the whole repo to
  `/home/USER/exams-portal`. Set `FTP_SERVER_DIR` (secret, below) to
  `/exams-portal/`.
- **If you can't change the document root:** the whole app has to live inside
  `public_html`, which normally exposes `app/`, `vendor/`, `.env`, etc. to the
  web. Lock it down with the `.htaccess` in [Appendix A](#appendix-a-htaccess-when-the-whole-app-lives-in-public_html)
  below, and set `FTP_SERVER_DIR` to `/public_html/`.

## 3. Database

Create a MySQL database, user, and password in cPanel → **MySQL Databases**.
You'll put these in the server's `.env` in step 5.

## 4. Server directory structure & permissions

Before the first deploy, over FTP or File Manager, create these folders (CI's
`.gitignore` excludes them, so they won't be created by the file sync) and set
them group-writable (`755`, or `775` if PHP runs as a different user):

```
storage/framework/cache/data
storage/framework/sessions
storage/framework/views
storage/framework/testing
storage/logs
bootstrap/cache
```

## 5. Upload `.env` manually — once

CI **never** uploads `.env` (it's excluded in `deploy.yml`), so the server's
`.env` is managed by you, by hand, and survives every deploy. Upload it once via
File Manager or FTP, based on [.env.example](.env.example), with production
values for `APP_ENV`, `APP_DEBUG=false`, `APP_URL`, the DB credentials from
step 3, and a generated `DEPLOY_TOKEN`:

```sh
php artisan tinker --execute="echo Str::random(40);"
```

Save that token — you'll add it as a GitHub secret next.

## 6. GitHub repository secrets

In the GitHub repo → **Settings → Secrets and variables → Actions**, add:

| Secret            | Value                                                             |
|-------------------|--------------------------------------------------------------------|
| `FTP_SERVER`      | Your cPanel host/IP (from cPanel → FTP Accounts)                  |
| `FTP_USERNAME`    | An FTP account with access to the target directory                |
| `FTP_PASSWORD`    | That FTP account's password                                       |
| `FTP_SERVER_DIR`  | Path from step 2, e.g. `/exams-portal/` or `/public_html/`         |
| `APP_URL`         | The live URL, e.g. `https://api.yourdomain.com` (no trailing slash)|
| `DEPLOY_TOKEN`    | The token generated in step 5 — must match the server's `.env`     |

## 7. First deploy

Push to `master` (or run the workflow manually via **Actions → Deploy to cPanel →
Run workflow**). Watch the Actions tab — the first run uploads everything and
is slow; later runs only sync changed files.

If the final "Trigger post-deploy tasks" step fails, check:
- `DEPLOY_TOKEN` matches between the GitHub secret and the server's `.env`.
- `APP_URL` is reachable and routes through `public/index.php` (i.e. the
  document root is correct per step 2).

## Appendix A: `.htaccess` when the whole app lives in `public_html`

Only needed if you couldn't set a custom document root in step 2. Place this
at `public_html/.htaccess` — it blocks direct access to everything except
`public/` and lets Apache route requests into it:

```apacheconf
RewriteEngine On

RewriteCond %{REQUEST_URI} !^/public/
RewriteRule ^(.*)$ /public/$1 [L]
```

And add a second `.htaccess` inside every top-level folder that isn't
`public/` (`app`, `bootstrap`, `config`, `database`, `resources`, `routes`,
`storage`, `tests`, `vendor`) to deny access outright:

```apacheconf
Require all denied
```

This is a fallback, not the preferred setup — a real document-root change
(step 2) is simpler and harder to misconfigure.
