# GA-Website-Theme

Core-PHP frontend for GreatAndhra.com. No framework, no WordPress — plain PHP templates that render content fetched at request time from the GreatAndhra NestJS API.

## Structure

- `index.php` — homepage: Big Story hero, trending/opinion/reviews/etc. category sections, Editor's Pick, Talk of the Town, Featured, USA Movie Schedules, Movie Rankings, Top Trending Topics
- `inner-page.php` — article detail page
- `list-page.php` — category and tag listing page (numbered pagination)
- `box-office.php` — Movies-category listing + Movie Rankings tables (This Week Top Five, All Time Top Films, USA Box Office)
- `advertisement.php` — full-page roadblock ad, shown once per cookie window before any page renders (see `ga_maybe_show_roadblock_ad()`)
- `clear-cache.php` — hits `?key=<GA_CACHE_CLEAR_KEY>` to wipe `cache/` on demand instead of waiting out the TTL
- `.htaccess` — clean-URL rewriting (see below) — **requires Apache with `mod_rewrite`**
- `config.php` — API base URL, cache TTLs, category/route tables, fallback images, title-truncation limits, roadblock ad settings
- `inc/api-client.php` — all HTTP calls to the backend API, with file-based caching and stale-cache fallback on failure
- `inc/helpers.php` — template helpers: truncation, image fallback, date formatting, escaping, URL building/resolution for categories and tags
- `css/`, `js/`, `images/`, `assets/` — static assets carried over from the original design
- `html-files/` — original fully-static HTML templates, kept as a design reference (not served)
- `cache/` — generated at runtime, not committed

## URL scheme

Clean URLs are rewritten by `.htaccess` to the PHP scripts, which read the real path from `PATH_INFO`:

| URL | Handled by | Notes |
|---|---|---|
| `/{id}/{category}/{subcategory?}/{title-slug}` | `inner-page.php` | `id` is the article's numeric shortId or full UUID |
| `/{category-path}` (e.g. `/politics`, `/movies/gossip`) | `list-page.php` | resolved against `GA_CATEGORY_ROUTES` in `config.php` |
| `/tag/{slug}` | `list-page.php` | resolved by scanning the full tag list (`ga_find_tag_by_slug()`) — the backend has no server-side slug filter |
| `/box-office` | `box-office.php` | |

Old-style links (`inner-page.php/{id}/...`, `box-office.php`, `?categoryId=...`, `?tagId=...`, the earlier `tag/{id}/{slug}` scheme) all 301-redirect to the current clean form — nothing that's ever been linked externally breaks.

**Local dev note:** PHP's built-in server (`php -S`) does not read `.htaccess`, so clean URLs won't resolve correctly with it alone — test against a real Apache (or equivalent) setup, or expect to use old-style/query-string URLs when using the built-in server.

## Status

All four page templates are fully dynamic (live API data, graceful fallback to stale cache or an "unavailable" state on API failure). Nothing left on static/hardcoded content.

## Running locally

Requires PHP with `curl` and `openssl` enabled, and Apache with `mod_rewrite` for the clean URLs to work:

```
php -S localhost:8000
```

Then visit `http://localhost:8000/index.php` (or serve through Apache pointed at this directory to get the real clean-URL behavior).

## Caching

File-based, in `cache/` (auto-created). `GA_CACHE_TTL` (articles/homepage) and `GA_TAGS_CACHE_TTL` (full tag list, changes far less often) are set in `config.php`. On API failure, a stale cache entry is served if one exists; otherwise the page shows a graceful "content unavailable" state. Force an immediate refresh with `clear-cache.php?key=<GA_CACHE_CLEAR_KEY>`.

## API

Base URL is `GA_API_BASE_URL` in `config.php`. Endpoints in use:

- `GET /api/public/homepage` — aggregate: hero/trending/category sections, Editor's Pick source data, Talk of the Town, Featured, USA Movie Schedules, Movie Rankings, trending tags
- `GET /api/public/articles` — filterable by `categoryId`, `includeChildren`, `tagId`; paginated via `take`/`skip`; returns `{ items, total }`
- `GET /api/public/articles/:id` — article detail, accepts either the shortId or the full UUID
- `POST /api/public/articles/:id/view` — fire-and-forget view-count ping
- `GET /api/public/tags` — full tag list (no server-side filtering — `/tag/{slug}` resolution scans this client-side)
