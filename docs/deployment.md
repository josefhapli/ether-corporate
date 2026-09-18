# GitHub → GoDaddy

GitHub is the source of truth for website code. GoDaddy remains the production host. This branch does not deploy to the live site.

## Review and release flow
1. Make changes on a review branch and open a pull request against main.
2. GitHub Actions checks HTML links/assets and JavaScript syntax, then produces a ZIP review package named with the exact commit SHA. Download it from the workflow run's Artifacts section.
3. Review the preview and complete docs/content-to-finalize.md. Merge approved code into main.
4. Once launch content and the contact form are complete, prepare the production metadata and migration rules in another reviewed commit. Record a release tag and use the passing package for that exact version.
5. Back up the current GoDaddy document root, configuration and any affected data before replacing the corporate pages. Confirm the document root and existing WordPress routing rules in cPanel; those still need a migration-specific review.
6. Upload only the packaged public files using cPanel or the approved deployment connection. Preserve existing product files, especially /products/catalyst/ and its api/leads.php. Do not mirror-delete or replace the entire products directory.
7. Verify production URLs, HTTPS, navigation, mobile layouts, the contact submission and the existing Catalyst page/form. Record the deployed SHA and date.

## Current limitations
- ZIPs are review builds and intentionally retain noindex. They are not launch-ready packages.
- Contact now uses the corporate PHP endpoint. It stays disabled until private HubSpot configuration is installed; follow docs/contact-setup.md and verify notification delivery before launch.
- No production credentials are stored in the repository. No deployment workflow or hosting connection has been enabled.
- 404.html is provided; GoDaddy/Apache error routing is not yet configured.
- No .htaccess file is supplied; preserve the live one until its WordPress rules and redirects are reviewed.
- The package includes release-manifest.json (revision and file hashes). A locally modified checkout is marked working-copy; use clean committed builds for releases.

## Local checks
```
node --check script.js
node --check contact.js
php tests/contact_test.php
python3 scripts/check_site.py
python3 scripts/package_site.py
```

The scripts use only standard libraries. The deployed site needs no Node/Python runtime or build step.

## Rollback
Restore the backed-up corporate files and routing configuration if launch verification fails. Do not revert unrelated product application data or leads. Record the restored revision and reason.
