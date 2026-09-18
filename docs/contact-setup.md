# Corporate inquiry form

## Built and tested locally
- `/contact/`: Name, Email, Organization (optional), Message; no marketing subscription.
- `/api/contact.php`: same PHP → HubSpot Forms API approach as Catalyst, with a dedicated corporate form.
- Input and request-size validation, a honeypot, exact Origin allowlist, and a locked private rate limiter (5 attempts / 15 minutes / IP).
- No form contents or HubSpot response bodies written to logs; rate files contain timestamps only and use keyed IP hashes.
- Errors retain the typed message. No success response until HubSpot returns 2xx. Transport timeouts are reported as unconfirmed delivery, never automatically retried.

## HubSpot configured September 18, 2026
- Published **Ether Corporate Inquiry**, portal `247280970`, form `4d984f04-b21b-4cee-8e26-5d7597f495dd`.
- Contact properties: `firstname` (required, website label Name), `email` (required), `company` (Organization, optional), `message` (What are you working on?, required).
- Notification recipient: `josef@etherstudios.net`, matching Catalyst. Contact-owner notifications are off. Catalyst was not modified.
- No marketing opt-in field or follow-up email was added. The website sends inquiry data only.
- Publication verified in HubSpot. One authorized test submission was received on September 18, 2026 at 2:01 PM EDT. All four fields and the complete message were verified in HubSpot (submission `dcbd9d25-d38d-4408-a74e-9ba066ce5f45`). Josef confirmed successful receipt of the notification email.

## GoDaddy setup before launch
- PHP 8.1+ and cURL required. Confirm the hosting PHP version.
- Copy `docs/contact-config.example.php` to `~/ether-private/contact-config.php` OUTSIDE `public_html`. Do not upload docs or tests into the web root. Use permissions 0600.
- Create `~/ether-private/rate-limit` (0700); populate the form GUID and random salt; enable only after field mapping and notifications are verified.
- With the endpoint at `public_html/api/contact.php`, the default private path is `~/ether-private/contact-config.php`. Set `ETHER_CONTACT_CONFIG` if hosting uses a different layout.
- Purge rate-limit JSON files older than one day during routine server maintenance; the files contain no raw IP addresses or inquiry content.
- Preserve `/products/catalyst/` and its handler. Do not change its configuration.

## End-to-end verification complete
The local website submitted an authorized, clearly labeled test inquiry. All four fields were verified in HubSpot and Josef confirmed notification receipt. Repeat this check from GoDaddy after deployment. Finish the website privacy notice before public launch.

## Local preview
Use `php -S 127.0.0.1:8765 -t .` to serve the site and handler. Without a private config, valid submissions return 503 with an honest "not connected yet" message and the email fallback; nothing is sent to HubSpot. Never add a fake-success switch to the production endpoint.

Run `php tests/contact_test.php` for isolated validation, payload, delivery-status and rate-limit checks. Tests use synthetic data and stubbed transport; no external submissions.
