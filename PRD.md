# Product Requirements Document — Auto WebP Converter

| | |
| --- | --- |
| **Product** | Auto WebP Converter (WordPress plugin) |
| **Vendor** | wpXplore |
| **Current Version** | 1.0.0 |
| **Document Date** | July 28, 2026 |
| **Status** | Shipped (v1.0.0) — includes forward-looking roadmap |

---

## 1. Overview

### 1.1 Problem Statement

Images are typically the heaviest assets on a WordPress site. JPEG and PNG uploads inflate page weight, slow down load times, hurt Core Web Vitals (LCP in particular), and increase hosting/bandwidth costs. WebP delivers 25–35% smaller files at comparable visual quality, but most site owners:

- Don't know how (or forget) to convert images before uploading
- Rely on heavyweight optimization plugins that store duplicate copies of every image, bloating disk usage
- Use CDN-level conversion that adds cost and external dependencies

### 1.2 Solution

A "set and forget" plugin that converts JPEG/PNG images to WebP **at upload time, before WordPress stores the file**. Because conversion happens on the temporary upload file (`wp_handle_upload_prefilter`), only the WebP version ever exists in the media library — every thumbnail size WordPress generates is automatically WebP too. No duplicates, no bulk-conversion queues, no cron jobs, no external services.

### 1.3 Goals

1. Reduce uploaded image file sizes automatically with zero user effort
2. Never break or block an upload — conversion failures must degrade gracefully to the original file
3. Never make things worse — keep the original when WebP output isn't smaller
4. Work on the widest possible range of hosting environments (Imagick or GD)
5. Stay lightweight: no external services, no background processing, minimal settings

### 1.4 Non-Goals (v1.0)

- Bulk conversion of existing media library images
- Serving WebP conditionally to browsers via `<picture>`/`Accept` headers (all modern browsers support WebP; the file is simply stored as WebP)
- AVIF or other next-gen formats
- GIF/animated image conversion
- Image resizing or dimension optimization
- Keeping original files as backups

---

## 2. Target Users

| Persona | Needs |
| --- | --- |
| **Site owner / blogger** | Faster site without learning image optimization; installs and forgets |
| **Agency / freelance developer** | A dependable, no-bloat optimization layer to install on client sites |
| **Directory site operator (Directorist)** | WebP compatibility for listing image uploads (user-submitted content) |

---

## 3. Functional Requirements

### 3.1 Core Conversion (shipped)

| ID | Requirement | Status |
| --- | --- | --- |
| FR-1 | Intercept uploads via `wp_handle_upload_prefilter` and convert JPEG/PNG to WebP before storage | ✅ |
| FR-2 | Use Imagick when available; fall back to GD automatically | ✅ |
| FR-3 | Rename the file to `.webp` and set MIME to `image/webp` on successful conversion | ✅ |
| FR-4 | Keep the original file if the WebP output exceeds 110% of the original size (10% tolerance) | ✅ |
| FR-5 | Preserve PNG alpha transparency (GD path: `imagealphablending` + `imagesavealpha`) | ✅ |
| FR-6 | Strip image metadata during Imagick conversion to reduce file size | ✅ |
| FR-7 | On any failure (missing library, conversion error), pass the upload through unmodified | ✅ |
| FR-8 | Log conversion errors to the PHP error log for debugging | ✅ |

### 3.2 Settings (shipped)

| ID | Requirement | Status |
| --- | --- | --- |
| FR-9 | Settings page under **Settings → Auto WebP Converter**, gated by `manage_options` | ✅ |
| FR-10 | Configurable WebP quality, integer 0–100, default 80, clamped on save | ✅ |
| FR-11 | Checkboxes to enable/disable conversion per image type (JPEG, PNG); at least one must remain selected | ✅ |
| FR-12 | Settings stored in a single autoloaded option (`wpxplore_awc_settings`) with sane defaults via `wp_parse_args` | ✅ |

### 3.3 Compatibility & Admin UX (shipped)

| ID | Requirement | Status |
| --- | --- | --- |
| FR-13 | Add `webp` to Directorist's supported image file types (`directorist_supported_file_types_groups`) | ✅ |
| FR-14 | Show an admin error notice when neither Imagick nor GD is available | ✅ |
| FR-15 | All strings translatable under the `wpxplore-webp-converter` text domain | ✅ |

---

## 4. Non-Functional Requirements

| ID | Requirement |
| --- | --- |
| NFR-1 | **Compatibility:** WordPress ≥ 5.0, PHP ≥ 7.0; no hard dependency on any specific extension (degrades gracefully) |
| NFR-2 | **Performance:** conversion adds only per-upload cost (no cron, no queues, no frontend overhead); memory is freed after each conversion (`clear`/`destroy`/`imagedestroy`) |
| NFR-3 | **Reliability:** conversion is fail-open — an upload must never be rejected or corrupted by the plugin |
| NFR-4 | **Security:** `ABSPATH` guard in every file; Settings API sanitization callback; capability checks; escaped output; whitelisted setting values |
| NFR-5 | **Code quality:** WordPress Coding Standards, singleton core class, PSR-12-compatible style, PHPDoc throughout |
| NFR-6 | **Footprint:** no frontend assets, no database tables, one option row |

---

## 5. User Flows

### 5.1 Happy Path — Image Upload

1. User uploads `photo.jpg` (2.4 MB) via the media library
2. Plugin checks: library available? type enabled in settings? → yes
3. Imagick converts the temp file to WebP at the configured quality (default 80), stripping metadata
4. WebP (900 KB) ≤ 110% of original → temp file replaced; name becomes `photo.webp`, MIME `image/webp`
5. WordPress stores `photo.webp` and generates all thumbnail sizes as WebP
6. User sees a normal upload — just smaller

### 5.2 Fallback Paths

- **Imagick missing/fails** → GD attempts the conversion
- **Both libraries unavailable** → original uploads untouched; admin notice shown in wp-admin
- **WebP larger than original** (e.g., already-optimized PNG) → WebP discarded, original uploaded
- **Type disabled in settings** → upload passes through untouched

### 5.3 Configuration

1. Admin opens **Settings → Auto WebP Converter**
2. Adjusts quality and/or unchecks an image type
3. Saves — values are sanitized (quality clamped to 0–100, types whitelisted); confirmation notice shown

---

## 6. Technical Architecture

```
auto-webp-converter.php               → constants, file loading, init on plugins_loaded
includes/class-auto-webp-converter.php → WpXplore_Auto_WebP_Converter (singleton)
                                          • check_image_libraries()
                                          • is_supported_image()
                                          • convert_with_imagick() / convert_with_gd()
                                          • convert_to_webp()  ← main pipeline
includes/class-settings.php            → WpXplore_AWC_Settings (WP Settings API)
includes/functions.php                 → hook registrations (upload filter, Directorist filter)
```

**Key design decisions:**

- **Pre-upload conversion** (`wp_handle_upload_prefilter`) rather than post-upload: avoids duplicate files and makes thumbnails WebP for free. Trade-off: the original is not retained (accepted for v1; see roadmap).
- **Singleton + static settings accessors:** simple, stateless access from the upload filter without globals.
- **Fail-open everywhere:** every guard returns the unmodified `$file` array.

---

## 7. Success Metrics

| Metric | Target |
| --- | --- |
| Average file size reduction on converted JPEG/PNG uploads | ≥ 30% |
| Uploads broken/blocked by the plugin | 0 |
| Support tickets caused by missing-library confusion | Near zero (admin notice must be clear) |
| Installation-to-value time | < 1 minute (activate → next upload is optimized) |

---

## 8. Risks & Mitigations

| Risk | Impact | Mitigation |
| --- | --- | --- |
| Host lacks both Imagick and GD-with-WebP | No conversion happens | Admin notice; fail-open uploads (shipped). Roadmap: pre-flight check on the settings page showing library status |
| User needs the original (print/editing source) | Original is lost at upload | Documented behavior. Roadmap: optional "keep original" mode |
| GD builds without `imagewebp()` | GD fallback silently fails | `check_image_libraries()` currently checks GD generally; roadmap item to explicitly check `function_exists('imagewebp')` |
| Filename collision if `name.png` and `name.jpg` both upload | Both become `name.webp`; WordPress auto-suffixes | WordPress core handles uniqueness (`wp_unique_filename`) — no action needed |
| Very large images exhaust PHP memory during conversion | Failed conversion | Fail-open behavior preserves the upload; errors logged |

---

## 9. Roadmap (Proposed)

### v1.1 — Robustness

- Explicitly verify GD WebP support (`function_exists('imagewebp')`) in library detection
- Settings-page status panel: which library is active, WebP support confirmed
- Per-upload conversion stats in debug log (original vs. WebP size)

### v1.2 — Coverage & Control

- Bulk-convert existing media library images (with progress UI and batching)
- Optional "keep original file" mode
- Filter hooks for developers (`awc_quality`, `awc_skip_conversion`, etc.)

### v2.0 — Next-Gen Formats

- AVIF output with WebP fallback
- Per-image-type quality settings
- WP-CLI command for server-side batch conversion

---

## 10. Open Questions

1. Should animated GIF → animated WebP be in scope (Imagick supports it; GD does not)?
2. Should a maximum-dimensions resize option be bundled, or is that scope creep beyond "conversion"?
3. Is wordpress.org directory distribution planned? (Would require a `readme.txt` in WP plugin-repo format, tested-up-to headers, and i18n `.pot` generation.)
