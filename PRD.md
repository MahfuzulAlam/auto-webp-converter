# Product Requirements Document — Auto WebP Converter

| | |
| --- | --- |
| **Product** | Auto WebP Converter (WordPress plugin) |
| **Vendor** | wpXplore |
| **Current Version** | 1.1.0 |
| **Document Date** | July 28, 2026 |
| **Status** | Shipped (v1.1.0) — includes forward-looking roadmap |

---

## 1. Overview

### 1.1 Problem Statement

Images are typically the heaviest assets on a WordPress site. JPEG and PNG uploads inflate page weight, slow down load times, hurt Core Web Vitals (LCP in particular), and increase hosting/bandwidth costs. WebP delivers 25–35% smaller files at comparable visual quality, but most site owners:

- Don't know how (or forget) to convert images before uploading
- Rely on heavyweight optimization plugins that store duplicate copies of every image, bloating disk usage
- Use CDN-level conversion that adds cost and external dependencies
- Need conversion applied *selectively* — e.g. only for listing images, not every upload — which bulk optimizers don't support

### 1.2 Solution

A "set and forget" plugin that converts JPEG/PNG images to WebP **at upload time, before WordPress stores the file**. Because conversion happens on the temporary upload file (`wp_handle_upload_prefilter`), only the WebP version ever exists in the media library — every thumbnail size WordPress generates is automatically WebP too. No duplicates, no bulk-conversion queues, no cron jobs, no external services.

Unlike blanket optimizers, conversion is **context-aware**: administrators choose exactly which upload contexts convert — direct Media Library uploads, featured images per post type, Directorist admin uploads, and Directorist frontend Add Listing uploads.

### 1.3 Goals

1. Reduce uploaded image file sizes automatically with zero user effort
2. Give site owners per-context control instead of all-or-nothing conversion
3. Never break or block an upload — conversion failures must degrade gracefully to the original file
4. Never make things worse — keep the original when WebP output isn't smaller
5. Work on the widest possible range of hosting environments (Imagick or GD)
6. First-class Directorist support, including user-submitted frontend listings

### 1.4 Non-Goals

- Bulk conversion of existing media library images
- Serving WebP conditionally to browsers via `<picture>`/`Accept` headers (the file is simply stored as WebP; all modern browsers support it)
- AVIF or other next-gen formats
- Animated image conversion (animated GIFs are deliberately passed through untouched)
- Image resizing or dimension optimization

---

## 2. Target Users

| Persona | Needs |
| --- | --- |
| **Site owner / blogger** | Faster site without learning image optimization; installs and forgets |
| **Agency / freelance developer** | A dependable, no-bloat optimization layer with per-context control for client sites |
| **Directory site operator (Directorist)** | WebP conversion for listing images — both admin-curated and user-submitted from the frontend — plus WebP compatibility in Directorist's upload validation |

---

## 3. Functional Requirements

### 3.1 Core Conversion (shipped)

| ID | Requirement | Status |
| --- | --- | --- |
| FR-1 | Intercept uploads via `wp_handle_upload_prefilter` and convert JPEG/PNG to WebP before storage | ✅ |
| FR-2 | Use Imagick when available; fall back to GD automatically | ✅ |
| FR-3 | Rename the file to `.webp` (via `sanitize_file_name()`) and set MIME to `image/webp` on success | ✅ |
| FR-4 | Keep the original file if the WebP output exceeds 110% of the original size (10% tolerance) | ✅ |
| FR-5 | Preserve PNG alpha transparency | ✅ |
| FR-6 | Strip image metadata during Imagick conversion | ✅ |
| FR-7 | On any failure (missing library, invalid file array, unreadable path, conversion error), pass the upload through unmodified — errors caught as `\Throwable` | ✅ |
| FR-8 | Log conversion errors to the PHP error log when `WP_DEBUG` is enabled | ✅ |
| FR-8a | Library detection verifies real WebP capability (`imagewebp()` for GD; WEBP delegate for Imagick) | ✅ (v1.0.1) |

### 3.2 Context-Aware Conversion Rules (shipped)

| ID | Requirement | Status |
| --- | --- | --- |
| FR-9 | Route each upload by context: frontend vs. admin; within admin, featured-image vs. Media Library | ✅ |
| FR-10 | Media Library toggle: convert direct Media Library uploads (default: on) | ✅ |
| FR-11 | Featured images: per-post-type opt-in covering every post type with thumbnail support | ✅ |
| FR-12 | Detect admin upload context via `post_id` request parameter, current admin screen, or referer `post`/`post_type` query args | ✅ |
| FR-13 | Frontend uploads convert only from the Directorist Add Listing page, and only when enabled | ✅ |

### 3.3 Settings (shipped)

| ID | Requirement | Status |
| --- | --- | --- |
| FR-14 | Settings page under **Settings → Auto WebP Converter**, gated by `manage_options`, with custom admin styling | ✅ |
| FR-15 | Configurable WebP quality, integer 0–100, default 80, clamped on save | ✅ |
| FR-16 | Checkboxes to enable/disable conversion per image type — JPEG, PNG, GIF (non-animated), BMP, TIFF; at least one must remain selected; defaults JPEG + PNG | ✅ (expanded in 1.1.0) |
| FR-16a | Animated GIFs are detected (frame-count check) and always pass through unconverted | ✅ (v1.1.0) |
| FR-16b | Optional "Keep Original Images": the uploaded original is kept next to the WebP file (same basename, original extension) and registered as its own Media Library attachment with generated metadata; stash/restore/registration failure never blocks the upload | ✅ (v1.1.0) |
| FR-16c | Format capability is engine-aware: TIFF converts only via Imagick; BMP requires Imagick or GD on PHP 7.2+; unsupported combinations pass through | ✅ (v1.1.0) |
| FR-17 | All settings in a single option (`wpxplore_awc_settings`); every value sanitized/whitelisted on save (image types validated against the convertible-types list, post types against registered public post types) | ✅ |

### 3.4 Directorist Integration (shipped)

| ID | Requirement | Status |
| --- | --- | --- |
| FR-18 | Dedicated integration class that activates only when Directorist is active (single-site and multisite network activation both detected) | ✅ |
| FR-19 | Add `webp` to Directorist's supported image file types (`directorist_supported_file_types_groups`) | ✅ |
| FR-20 | Admin toggle: convert featured images for Directorist post types (`at_biz_dir` + discovered `at_*` types with thumbnail support) | ✅ |
| FR-21 | Frontend toggle: convert uploads from the Add Listing page (detected via configured page ID; referer match for AJAX uploads) | ✅ |
| FR-22 | Directorist settings section renders only when Directorist is active | ✅ |

### 3.5 Admin UX (shipped)

| ID | Requirement | Status |
| --- | --- | --- |
| FR-23 | Admin error notice when neither Imagick nor GD (with WebP) is available, shown only to users with `manage_options` | ✅ |
| FR-24 | Settings styles enqueued only on the plugin's own settings screen | ✅ |
| FR-25 | All strings translatable under the `wpxplore-webp-converter` text domain | ✅ |

---

## 4. Non-Functional Requirements

| ID | Requirement |
| --- | --- |
| NFR-1 | **Compatibility:** WordPress ≥ 5.0, PHP ≥ 7.0; no hard dependency on any specific extension or on Directorist (degrades gracefully) |
| NFR-2 | **Performance:** conversion adds only per-upload cost (no cron, no queues, no frontend overhead); memory is freed after each conversion; admin CSS loads only on the plugin's settings page |
| NFR-3 | **Reliability:** conversion is fail-open — an upload must never be rejected or corrupted by the plugin |
| NFR-4 | **Security:** `ABSPATH` guard in every file; Settings API nonce + sanitize callback; capability checks; escaped output; request data unslashed and sanitized; file paths validated; `wp_delete_file()` and `sanitize_file_name()` for file operations |
| NFR-5 | **Code quality:** WordPress Coding Standards, consistent `wpxplore_awc_`/`WpXplore_AWC_`/`AWC_` prefixes, singleton pattern, strict comparisons, PHPDoc throughout |
| NFR-6 | **Footprint:** no frontend assets, no database tables, one option row, one admin-only stylesheet |

---

## 5. User Flows

### 5.1 Happy Path — Media Library Upload

1. User uploads `photo.jpg` (2.4 MB) via the media library
2. Plugin routes the upload: admin context, no post edit screen → Media Library rules; toggle is on
3. Checks pass: library available, JPEG enabled, file readable
4. Imagick converts the temp file to WebP at the configured quality (default 80), stripping metadata
5. WebP (900 KB) ≤ 110% of original → temp file replaced; name becomes `photo.webp`, MIME `image/webp`
6. WordPress stores `photo.webp` and generates all thumbnail sizes as WebP

### 5.2 Featured Image Upload

1. Admin enables "Post" under **Post Types for Featured Images**
2. While editing a post, they upload a featured image
3. Plugin detects the post edit context (post ID → post type `post`) → conversion applies
4. Uploads from post types *not* enabled pass through untouched

### 5.3 Directorist Frontend Listing

1. Admin enables **Add listing page (Frontend)** in Directorist settings section
2. A visitor submits a listing with photos from the Add Listing page
3. Plugin confirms the upload originates from that page (page ID / referer) → images convert to WebP
4. Directorist accepts the `.webp` files because the plugin registered WebP in its supported types

### 5.4 Fallback Paths

- **Imagick missing/fails** → GD attempts the conversion
- **Both libraries unavailable** → original uploads untouched; admin notice shown in wp-admin
- **WebP larger than original** (e.g., already-optimized PNG) → WebP discarded, original uploaded
- **Context or type disabled in settings** → upload passes through untouched
- **Referer stripped on frontend AJAX upload** → context undetectable; upload proceeds unconverted

---

## 6. Technical Architecture

```
auto-webp-converter.php                → constants, file loading, init on plugins_loaded
includes/class-auto-webp-converter.php → WpXplore_Auto_WebP_Converter (singleton)
                                          • check_image_libraries()
                                          • is_supported_image()
                                          • convert_with_imagick() / convert_with_gd()
                                          • convert_to_webp()  ← conversion pipeline
includes/class-settings.php            → WpXplore_AWC_Settings (WP Settings API)
                                          • quality / allowed types / media-library toggle
                                          • featured-image post type checkboxes
                                          • sanitize_settings() whitelist validation
includes/class-directorist.php         → WpXplore_AWC_Directorist (singleton)
                                          • active-plugin detection (site + network)
                                          • Directorist settings section
                                          • get_post_types() / add-listing page detection
                                          • WebP file type registration
includes/functions.php                 → upload routing layer
                                          • wpxplore_awc_handle_upload() → frontend/admin split
                                          • featured-image vs. media-library dispatch
                                          • wpxplore_awc_get_upload_context_post_type()
includes/admin-styles.css              → settings page styling
```

**Key design decisions:**

- **Pre-upload conversion** (`wp_handle_upload_prefilter`) rather than post-upload: avoids duplicate files and makes thumbnails WebP for free. Trade-off: the original is not retained (accepted for v1; see roadmap).
- **Routing layer in functions.php, conversion in the core class:** context policy (should this upload convert?) is fully separated from mechanism (how to convert).
- **Best-effort context detection:** post ID → admin screen → referer, in that order of reliability. Referer-based detection is a heuristic; failures degrade to "no conversion," never to a broken upload.
- **Directorist isolation:** all Directorist logic lives in one class that self-disables when Directorist is inactive; core plugin has no hard dependency.
- **Fail-open everywhere:** every guard returns the unmodified `$file` array.

---

## 7. Success Metrics

| Metric | Target |
| --- | --- |
| Average file size reduction on converted JPEG/PNG uploads | ≥ 30% |
| Uploads broken/blocked by the plugin | 0 |
| Directorist frontend listings with converted images (when enabled) | ≥ 95% (referer-dependent) |
| Support tickets caused by missing-library confusion | Near zero (admin notice must be clear) |
| Installation-to-value time | < 1 minute (activate → next upload is optimized) |

---

## 8. Risks & Known Issues

| # | Risk / Issue | Impact | Status / Mitigation |
| --- | --- | --- | --- |
| 1 | GD builds without WebP support caused a fatal error during upload | Violated the fail-open guarantee on affected hosts | **Fixed in 1.0.1:** detection requires `imagewebp()` (and the Imagick WEBP delegate); conversion catches `\Throwable` |
| 2 | GD PNG path could crash before its failure guard (transparency calls on a `false` resource; `TypeError` on PHP 8) | Fatal error on malformed PNG uploads on GD-only hosts | **Fixed in 1.0.1:** transparency calls moved after the failure check; verified with a corrupt-PNG regression test |
| 3 | Referer-based detection (Directorist frontend, admin AJAX context) fails when the `Referer` header is stripped | Feature silently inactive for some users; upload still succeeds | Documented; roadmap: nonce/field-based detection via Directorist form hooks |
| 4 | Host lacks both Imagick and GD-with-WebP | No conversion happens | Admin notice (admins only); fail-open uploads. Roadmap: library status panel on settings page |
| 5 | User needs the original (print/editing source) | Original is lost at upload | **Fixed in 1.1.0:** optional "Keep Original Images" setting keeps the original beside the WebP in uploads |
| 6 | `$_POST['post_id']` read without nonce verification (context sniffing only) | PHPCS/plugin-check warning; no injection risk (value only feeds `get_post()`) | **Addressed in 1.0.1:** `phpcs:ignore` annotations with justification |
| 7 | `error_log()` calls flagged by WordPress Plugin Check | Directory-review friction | **Fixed in 1.0.1:** logging gated behind `WP_DEBUG` |
| 8 | Text domain (`wpxplore-webp-converter`) ≠ plugin slug (`auto-webp-converter`) | Blocks translation loading on wordpress.org | Align before any directory submission |
| 9 | Very large images exhaust PHP memory during conversion | Failed conversion | Fail-open behavior preserves the upload; errors logged under `WP_DEBUG` |

---

## 9. Roadmap (Proposed)

### v1.0.1 — Hotfixes ✅ (shipped July 28, 2026)

- Verify WebP capability in library detection (GD `imagewebp()`, Imagick WEBP delegate); catch `\Throwable` in conversion methods
- Fix PNG transparency calls running before the `imagecreatefrompng()` failure check
- Always clean up temporary `.webp` output; clamp quality at conversion time
- Gate `error_log()` behind `WP_DEBUG`; add `phpcs:ignore` justifications; admin notice restricted to `manage_options`
- Redesigned settings page (modern card layout, toggle switches, pill checkboxes)

### v1.1 — Robustness & Compliance

- Settings-page status panel: which library is active, WebP support confirmed
- Align text domain with plugin slug; add `readme.txt` for wordpress.org
- Replace referer-based Directorist frontend detection with form-hook/nonce-based detection
- Per-upload conversion stats in debug log (original vs. WebP size)

### v1.1.0 — Formats & Originals ✅ (shipped July 28, 2026)

- GIF (non-animated), BMP, and TIFF added as selectable image types with engine-aware capability handling
- Animated GIF detection — animations always preserved
- "Keep Original Images" option — original kept alongside the WebP in the uploads folder
- Admin UI fixes: notice readability on dark admin schemes, duplicate save notice removed, toggle-switch alignment

### v1.2 — Coverage & Control

- Bulk-convert existing media library images (with progress UI and batching)
- Developer filter hooks (`awc_quality`, `awc_skip_conversion`, `awc_enabled_contexts`, etc.)
- Backup management: view/restore/prune kept originals from the settings page

### v2.0 — Next-Gen Formats

- AVIF output with WebP fallback
- Per-image-type and per-context quality settings
- WP-CLI command for server-side batch conversion

---

## 10. Open Questions

1. Should animated GIF → animated WebP be in scope (Imagick supports it; GD does not)?
2. Should a maximum-dimensions resize option be bundled, or is that scope creep beyond "conversion"?
3. Is wordpress.org directory distribution planned? (Requires `readme.txt`, tested-up-to headers, text domain alignment, and i18n `.pot` generation.)
4. Should other frontend upload sources (e.g. WooCommerce, Dokan, generic `media_handle_upload` calls) get their own context toggles like Directorist?
