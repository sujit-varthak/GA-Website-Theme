# GA-Website-Theme

Core-PHP frontend for GreatAndhra.com. No framework, no WordPress — plain PHP templates that render content fetched at request time from the GreatAndhra NestJS API.

## Structure

- `index.php`, `inner-page.php`, `box-office.php`, `list-page.php` — the four page templates (home, article, box office, listing/search)
- `css/`, `js/`, `images/`, `assets/` — static assets carried over from the original design
- `config.php` — API base URL, cache TTL, fallback images/title limits
- `inc/api-client.php` — HTTP calls to the API (with file-based caching) and the fire-and-forget view-count ping
- `inc/helpers.php` — small template helpers (truncation, image fallback, date formatting, escaping)
- `cache/` — generated at runtime, not committed

## Status

- **Homepage** (`index.php`): Big Story hero + related-articles list are dynamic (live API data)
- **Article page** (`inner-page.php`): fully dynamic — title, image, byline, body, category, tags; handles missing/invalid slugs and API downtime gracefully
- **Box office / listing pages**: still static, not yet converted

## Running locally

Requires PHP with `curl` and `openssl` enabled:

```
php -S localhost:8000
```

Then visit `http://localhost:8000/index.php`.

## API

Base URL is set in `config.php` (`GA_API_BASE_URL`). See that file for cache TTL and other tunables.
