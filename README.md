# Ether corporate website

A responsive corporate website built with plain HTML, CSS and JavaScript. GitHub is the source of truth; GoDaddy remains the production host.

## Review status

The approved homepage is saved at commit `bb3053f`. The `site-expansion` branch adds the core interior pages for review. The live GoDaddy site has not been modified.

Available pages:
- Home
- Expertise — six capability sections
- Case Studies — National Geographic, Marriott and Nestlé listing structure
- Products — Catalyst, Open Air and Wish You Were Here
- Ideas — listing and a permanent rapid-prototyping article
- Ether Gov — mission and capabilities structure
- About Ether
- Contact — corporate inquiry form and PHP handler
- 404 page

All pages retain `noindex` while content is being finalized. Case-study narratives, government credentials and legal copy are pending. The contact page and PHP handler are built; The dedicated HubSpot form is published; end-to-end submission and notification delivery are verified. Private GoDaddy configuration remains pending. The sample article and expanded copy are review drafts.

See [content to finalize](docs/content-to-finalize.md) and the [GitHub-to-GoDaddy release process](docs/deployment.md).

## Local preview and checks

```sh
php -S 127.0.0.1:8765 -t .
node --check script.js
node --check contact.js
php tests/contact_test.php
python3 scripts/check_site.py
python3 scripts/package_site.py
```

Open http://127.0.0.1:8765/. There are no package installs or build dependencies. Python is used only for local checks/packaging; the deployed website needs no Python or Node runtime. The contact endpoint requires PHP 8.1+ with cURL.

GitHub Actions runs the checks and produces a versioned review ZIP with a revision/file-hash manifest. Only public website files are packaged. Repository files, screenshots and development scripts are excluded. No automatic GoDaddy deployment is enabled.

## Design

- Blue `#006699`, orange `#ff6633`, light `#dedede`, dark `#333333`.
- Libre Baskerville headings, Inter body/subheads/eyebrows; Georgia and Arial/Helvetica fallbacks.
- Locally hosted Google Fonts and licenses in `assets/`.
- Real stock photography with no added color filters; neutral dark overlays keep text readable.
- Homepage header starts transparent and turns white after scrolling its own height; interior-page headers stay white.
- Hero: three slides, five-second crossfade, manual controls and pause/play. Keyboard interaction/manual selection stops rotation. Hover, offscreen position and background tabs suspend the timer. Reduced-motion users start paused without transitions. Inactive slides are inert.

## Editing

Each page is a normal `index.html` inside its route folder, with shared `styles.css` and `script.js`. Update the shared header/footer consistently when editing navigation; page-specific links use clean folder URLs. New Ideas articles can follow `ideas/rapid-prototyping/index.html`, with a new title, description, canonical URL and listing entry. Verify any author/date metadata before publishing.

Keep `/products/catalyst/` outside this project's deployment package. Its existing application and PHP lead handler must be preserved when publishing corporate pages.

## Asset credits

Logo: supplied `ether_brand_final.png`.

Photographs are illustrative stock, not representations of Ether staff, clients or completed engagements. All selected images were listed as free under the [Unsplash License](https://unsplash.com/license), checked September 16, 2026.

- Vitaly Gariev: [café transaction](https://unsplash.com/photos/a-customer-receives-a-coffee-at-a-coffee-shop-a641UgoV9Yw)
- Annie Spratt: [team working at laptops](https://unsplash.com/photos/group-of-people-using-laptop-computer-QckxruozjRg)
- Yuliia Kucherenko: [people in a library](https://unsplash.com/photos/a-group-of-people-sitting-at-desks-in-a-library-yEB_tCgb-gk)

Fonts: Libre Baskerville and Inter via Google Fonts. License files are in `assets/font-licenses/`.

See [contact setup and pending verification](docs/contact-setup.md).
