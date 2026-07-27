# Auto WebP Converter

A lightweight WordPress plugin that automatically compresses uploaded images and converts them to WebP format during upload — with fine-grained control over *where* conversion applies: the Media Library, featured images per post type, and Directorist listing uploads (admin and frontend).

**Version:** 1.1.0 · **Author:** [wpXplore](https://wpxplore.com/) · **License:** GPL v2 or later

## Description

Auto WebP Converter intercepts image uploads *before* they reach the WordPress media library (via the `wp_handle_upload_prefilter` hook) and converts JPEG and PNG images to WebP on the fly. Because conversion happens pre-upload, only the WebP file is ever stored — no duplicate originals, no extra disk usage, and all generated thumbnail sizes are WebP too.

The plugin is **context-aware**: it detects where an upload is coming from (Media Library, a post edit screen, or the Directorist frontend Add Listing page) and applies conversion only where you've enabled it.

The plugin uses the Imagick PHP extension when available and automatically falls back to GD for maximum server compatibility.

### Features

- **Automatic Conversion** — JPEG and PNG uploads are converted to WebP during upload; no bulk-convert step or manual action needed
- **Pre-Upload Processing** — conversion happens on the temporary upload file, so only the WebP version is stored and all WordPress thumbnail sizes are generated from it
- **Context-Aware Rules** — enable/disable conversion independently for:
  - direct Media Library uploads (on by default)
  - featured images, per post type (any post type with thumbnail support)
  - Directorist featured images uploaded from the admin panel
  - Directorist frontend Add Listing page uploads
- **Dual Library Support** — uses Imagick (preferred) with automatic GD fallback
- **Configurable Quality** — set WebP compression quality (0–100, default 80)
- **Selectable Image Types** — choose which of JPEG, PNG, GIF, BMP, and TIFF get converted (JPEG + PNG by default; TIFF requires Imagick)
- **Animated GIF Protection** — animated GIFs are detected and never converted, so animations are never lost
- **Keep Originals (optional)** — keep the uploaded original (e.g. JPEG) next to the converted WebP file *and* register it as its own Media Library attachment; when off, only the WebP is kept
- **Smart Replacement** — the WebP file is only kept if it is smaller than (or within 10% of) the original size; otherwise the original is uploaded untouched
- **Metadata Stripping** — EXIF/metadata is stripped during Imagick conversion for smaller files
- **Transparency Support** — PNG alpha transparency is preserved
- **Directorist Integration** — dedicated integration class: adds `webp` to Directorist's supported image types, detects the Add Listing page, and supports Directorist post types (`at_biz_dir` and other `at_*` types)
- **Polished Settings UI** — custom-styled settings page with sectioned options
- **Graceful Degradation** — if no image library is available, uploads proceed normally and an admin notice explains what's missing

## Requirements

- WordPress 5.0 or higher
- PHP 7.0 or higher
- One of the following PHP extensions:
  - Imagick (recommended)
  - GD with WebP support (`imagewebp`)
- [Directorist](https://wordpress.org/plugins/directorist/) (optional — only needed for the Directorist integration features)

## Installation

1. Download or clone this repository
2. Upload the plugin folder to `/wp-content/plugins/`
3. Activate the plugin through the **Plugins** menu in WordPress
4. Configure conversion rules under **Settings → Auto WebP Converter**
5. Start uploading images — they will be automatically converted to WebP wherever enabled

## Usage

Once activated, uploads are converted to WebP when **all** of the following hold:

- The upload context is enabled in settings (see below)
- The image type (JPEG, PNG, GIF, BMP, or TIFF) is enabled in settings
- The image is not an animated GIF (animations are never converted)
- A supported image library is available for that format (TIFF needs Imagick; BMP needs Imagick or PHP 7.2+ GD)
- The resulting WebP is smaller than, or within 10% of, the original file size

If any condition fails, the original file is uploaded unchanged — conversion never blocks an upload.

### Settings

Found under **Settings → Auto WebP Converter** (requires the `manage_options` capability). Settings are stored in a single option: `wpxplore_awc_settings`.

**Conversion Settings**

| Setting | Default | Description |
| --- | --- | --- |
| Convert Quality | `80` | WebP compression quality (0–100). Higher = better quality, larger files. |
| Allow Image Type | JPEG, PNG | Which uploaded image types are converted: JPEG, PNG, GIF (non-animated), BMP, TIFF (Imagick only). At least one must remain selected. |
| Keep Original Images | Off | Keep the uploaded original next to the WebP file (e.g. `photo.webp` + `photo.jpg`). Both appear in the Media Library — the WebP as the main upload, the original as a separate attachment with its own thumbnails. |
| Convert Media Library Uploads | On | Convert images uploaded directly through the Media Library. |

**Featured Image Settings**

| Setting | Default | Description |
| --- | --- | --- |
| Post Types for Featured Images | None | Per-post-type checkboxes (all post types with thumbnail support). When enabled, featured images uploaded from that post type's edit screen are converted. |

**Directorist Settings** *(shown only when Directorist is active)*

| Setting | Default | Description |
| --- | --- | --- |
| Directorist post type (Admin Panel) | Off | Convert featured images uploaded from Directorist post type edit screens in wp-admin. |
| Add listing page (Frontend) | Off | Convert images uploaded from the frontend Add Listing page. |

### How upload context is detected

- **Frontend uploads** are converted only when they come from the Directorist Add Listing page (detected via the configured page ID and, for AJAX uploads, the HTTP referer) and that option is enabled.
- **Admin uploads** are classified as featured-image uploads when a post edit context is detectable (via the `post_id` request parameter, the current admin screen, or the referer's `post`/`post_type` query args); otherwise they're treated as Media Library uploads.

> **Note:** referer-based detection is a best-effort heuristic — browsers or proxies that strip the `Referer` header can prevent frontend/AJAX context detection, in which case the upload passes through unconverted.

## Development

### Project Structure

```
auto-webp-converter/
├── auto-webp-converter.php            # Main plugin file (constants, bootstrapping)
├── assets/
│   └── admin-styles.css               # Settings page styles
├── includes/
│   ├── class-auto-webp-converter.php  # Core converter class (Imagick/GD conversion logic)
│   ├── class-settings.php             # Settings page (WP Settings API)
│   ├── class-directorist.php          # Directorist integration (detection, settings, file types)
│   └── functions.php                  # Upload routing, context detection, hook registrations
├── README.md                          # Technical documentation (this file)
├── DOCUMENTATION.md                   # End-user guide (settings, examples, FAQ)
├── PRD.md
├── SECURITY_AND_STANDARDS_REPORT.md
└── .gitignore
```

### Key Classes

- `WpXplore_Auto_WebP_Converter` — singleton core class: library detection, MIME/extension checks, Imagick and GD conversion, size comparison and file replacement
- `WpXplore_AWC_Settings` — settings page, fields, sanitization; static accessors (`get_settings()`, `get_setting()`)
- `WpXplore_AWC_Directorist` — singleton integration class: Directorist active-check (single site + multisite), Directorist settings section, post type discovery, Add Listing page detection, WebP file type support

### Hooks Used

- `wp_handle_upload_prefilter` — routes the upload through frontend/admin context handlers, then converts if the context is enabled
- `directorist_supported_file_types_groups` — adds WebP to Directorist's supported image types
- `admin_notices` — warns when neither Imagick nor GD is available
- `admin_menu` / `admin_init` / `admin_enqueue_scripts` — settings page, fields, and styles

### Conversion Flow

1. Upload is intercepted at `wp_handle_upload_prefilter`
2. Upload context is resolved (frontend Directorist / featured image / Media Library) and checked against settings
3. Library availability and allowed image types are checked; file array and paths are validated
4. Imagick conversion is attempted first; GD is the fallback
5. The WebP output is compared against the original size (10% tolerance)
6. If kept, the temp file is replaced and the file's name/MIME are rewritten to `.webp` / `image/webp` (name passed through `sanitize_file_name()`)

## Security

- Direct file access is prevented (`ABSPATH` check in every file)
- Settings are sanitized via a `sanitize_callback` (quality clamped to 0–100, types and post types whitelisted against real values)
- Settings page is capability-gated (`manage_options`) and nonce-protected via the Settings API
- Request data (`$_POST`, `$_GET`, `$_SERVER`) is unslashed and sanitized (`absint`, `sanitize_key`, `sanitize_text_field`, `esc_url_raw`)
- File arrays and paths are validated (existence, readability) before processing
- File deletion uses `wp_delete_file()`; rewritten filenames pass through `sanitize_file_name()`
- All output is escaped (`esc_html`, `esc_attr`)
- Conversion errors are caught as `\Throwable` (never fatals an upload); logging is gated behind `WP_DEBUG`
- Library detection verifies actual WebP capability (`imagewebp()` for GD, the WEBP delegate for Imagick)
- The missing-library admin notice is shown only to users with `manage_options`

### Known limitations

- Referer-based context detection can miss when the `Referer` header is stripped (upload still succeeds, just unconverted)

## Coding Standards

This plugin follows:

- WordPress Coding Standards (naming prefixes `wpxplore_awc_` / `WpXplore_AWC_` / `AWC_`, tabs, strict comparisons, PHPDoc throughout)
- PHP 7.0+ compatibility
- PSR-12 compatible code style

## Changelog

### 1.1.0

- **New:** GIF (non-animated), BMP, and TIFF added to convertible image types — each individually selectable in settings
- **New:** animated GIF detection — animated GIFs always pass through unconverted so animation is never lost
- **New:** "Keep Original Images" option — keeps the uploaded original next to the converted WebP file and registers it as its own Media Library attachment (restored via `wp_handle_upload`, registered via `add_attachment`)
- **Fix:** settings-page notices were unreadable under dark admin color schemes (text color now explicit) and "Settings saved" appeared twice (duplicate custom notice removed)
- **Fix:** toggle-switch thumb was misaligned — wp-admin's checkbox checkmark styles are now fully suppressed
- GIF transparency preserved via palette-to-truecolor conversion; GD BMP support guarded for PHP < 7.2

### 1.0.1

- **Fix:** GD builds without WebP support could fatal during upload — library detection now requires `imagewebp()`, and Imagick detection verifies the WEBP delegate (`Imagick::queryFormats`)
- **Fix:** corrupt/unreadable PNGs could crash the GD path on PHP 8 — transparency handling now runs after the image-creation failure check
- **Fix:** conversion methods now catch `\Throwable` (not just `Exception`) so engine errors can never break an upload
- **Fix:** stray temporary `.webp` file was left behind when the size check or copy failed — temp output is now always cleaned up
- Error logging is gated behind `WP_DEBUG`; missing-library notice restricted to administrators; WebP quality clamped defensively at conversion time
- Redesigned settings page: modern hero header, card layout, toggle switches, pill checkboxes, focus-visible states, reduced-motion support
- Removed duplicate field labels on the settings page; stylesheet is cache-busted on change

### 1.0.0

- Automatic WebP conversion on upload (pre-upload, via `wp_handle_upload_prefilter`)
- Imagick support with GD fallback
- Context-aware conversion rules: Media Library toggle, featured images per post type, Directorist admin + frontend
- Dedicated Directorist integration class with Add Listing page detection
- Settings page with custom styling: quality, allowed image types, and per-context toggles
- Smart size comparison — original kept if WebP isn't smaller
- PNG transparency preservation and metadata stripping
- Security hardening: input sanitization, file validation, `wp_delete_file()`, `sanitize_file_name()`

## Credits

Developed by [wpXplore](https://wpxplore.com/)

## License

GPL v2 or later — https://www.gnu.org/licenses/gpl-2.0.html
