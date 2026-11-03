# Administrator security and deployment

No default administrator account is created by the seed. Sample posts have no author until assigned. Never publish passwords, password hashes, hosting credentials, database exports, or private configuration.

## Rotate an existing live administrator

Sign in privately to InfinityFree and open phpMyAdmin for this application's database. Do not change the hosting or database user's password: this task concerns the application's `users` table.

Run `tools/New-AdminPasswordHash.ps1` locally in PowerShell. It prompts with hidden input, hashes through local PHP over stdin, and copies only the hash to your clipboard. It never puts the password in command arguments, SQL, source files, or console output. Use a unique password saved in your own password manager. Avoid clipboard history/sync for hashes and clear the clipboard afterward.

In phpMyAdmin, identify the exact `users` row with the intended username and role `admin`. Edit only that row's `password_hash`; paste the generated hash without selecting an SQL transformation function. Confirm and submit this change yourself. Verify exactly one row changed. Never replace it with plaintext, MD5, SHA1, or a MySQL PASSWORD() value. Test the new login privately, and confirm the previous default no longer works with a single controlled attempt. The updated auth.php binds administrator sessions to the current database password hash and role on every protected request: deploying it invalidates legacy sessions, and subsequent password rotations revoke existing sessions. Until this update is deployed, changing the hash alone does not revoke sessions.

## New installations

After importing schema and seed, generate a unique hash privately. Pipe the hash on stdin to `php tools/create-admin.php YOUR_USERNAME` using a trusted local shell. This CLI-only tool creates one administrator and refuses to overwrite an existing username. Do not upload CLI tools to public hosting. On hosting without CLI, use phpMyAdmin to insert one `users` row with the unique username, PHP-generated hash, and `admin` role privately.

## Rate-limit configuration

Generate a separate 32-byte random key, encoded as 64 lowercase hexadecimal characters, in a file outside the web document root. Restrict filesystem access to the account and PHP process. Set the absolute file path through `CROP_LOGIN_HMAC_KEY_FILE` or the private `includes/config.php` array entry `login_hmac_key_file`. No key value belongs in Git. Existing Windows installs retain their legacy private key path for compatibility.

On InfinityFree, files outside htdocs are restricted. Instead set `login_hmac_key` in the existing private PHP config array, or use the server environment variable `CROP_LOGIN_HMAC_KEY` if supported. Deploy `includes/.htaccess` with `Require all denied` first, and verify direct HTTP requests to files under `/includes/` are denied before adding the secret. PHP includes continue to work. Keep the actual configuration untracked and preserve the existing database configuration. A direct environment key takes precedence over private config, and either takes precedence over a key file. Generate the key on the server or through a trusted local tool without printing or publishing it.

Before deploying auth.php, confirm the selected private key configuration works on the host. Do not disable rate limiting to work around missing configuration. The `admin_login_attempts` table and MySQL GET_LOCK/RELEASE_LOCK support must also be present. The additive migration in `sql/admin_login_attempts.sql` creates the table on new installations; inspect an existing table for the required columns and indexes. Missing security dependencies deliberately make administrator login unavailable, while public pages remain independent of those login checks.

## Repository cleanup and validation

Rotate the live password before publishing changes. Back up the existing repository privately. Scan the index, every branch and tag, commit messages, and history for known exposed values without printing them. A normal commit does not remove earlier exposures. Rewriting shared history and force-pushing require the owner's explicit approval. After rewriting, collaborators must use the cleaned history and avoid merging old commits. Review forks, pull request references, releases, artifacts, and caches; coordinate with GitHub support where necessary. Old credentials must remain invalid even if an external copy survives.

Deploy only reviewed application files. Keep `.git`, `sql`, tools, backups, and private configuration out of the served document root. Check public pages, prediction requests, administrator login, CSRF rejection, wrong-role denial, failed-login limiting, logout, and session invalidation after deployment. Never call the repository or live service safe until these checks and public history verification complete.
