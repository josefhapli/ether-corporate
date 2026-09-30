# GitHub → GoDaddy

GitHub stores the website source; GoDaddy serves production at https://www.etherstudios.net/. The redesign was deployed September 26, 2026. GitHub pushes do not automatically change production.

## Build and verify

```sh
node --check script.js
node --check contact.js
php -l api/contact.php
php -l api/contact-lib.php
php tests/contact_test.php
python3 scripts/package_site.py --release
```

Build from a clean committed checkout. The ZIP contains only public website files and a manifest with the full revision and SHA-256 file hashes. Its filename includes a content hash. `launch-candidate` identifies packaging mode, not deployment approval. GitHub Actions validates and retains the package as a build artifact; generated ZIPs remain outside Git history.

## Deploy

1. Verify the exact package and manifest, and back up overwritten production files and routing outside the public root.
2. Install only manifest-listed files. `scripts/install_release.py` verifies source hashes, creates a private backup and rollback script, and checks installed hashes. Run with umask 022 so public directories are traversable.
3. Preserve WordPress files, private contact configuration, unrelated hosted domains and all existing product applications. Never mirror-delete or replace the entire products directory. `/products/catalyst/` and its lead handler are outside this package.
4. Verify HTTPS, all corporate pages and assets, homepage redirects, custom 404 responses, contact endpoint behavior and Catalyst preservation.
5. Record the deployed revision and backup location privately. To roll back, run the backup's generated `rollback.py`; do not revert unrelated application data.

Root `.htaccess` scopes corporate homepage and error routing to Ether's domain. `api/.htaccess` selects PHP 8.3 for the corporate API. Contact credentials and rate-limit storage remain outside the public root and repository.

## Verified production state

All 30 directly served public files match the release; HTML comparison accounts for GoDaddy's injected monitoring scripts. Missing routes return 404, explicit index.php visits redirect to the homepage, and Catalyst's response matches its predeployment baseline. Contact rejects unsupported GET requests. Dedicated HubSpot submission and email delivery were verified September 18; that test predates deployment.
