# Auto WebP Converter

A lightweight WordPress plugin that automatically compresses uploaded images and converts them to WebP format during upload — reducing file sizes and improving website performance with zero manual effort.

**Version:** 1.0.0 · **Author:** [wpXplore](https://wpxplore.com/) · **License:** GPL v2 or later

## Description

Auto WebP Converter intercepts image uploads *before* they reach the WordPress media library (via the `wp_handle_upload_prefilter` hook) and converts JPEG and PNG images to WebP on the fly. Because conversion happens pre-upload, only the WebP file is ever stored — no duplicate originals, no extra disk usage, and all generated thumbnail sizes are WebP too.

The plugin uses the Imagick PHP extension when available and automatically falls back to GD for maximum server compatibility.

### Features

- **Automatic Conversion** — JPEG and PNG uploads are converted to WebP during upload; no bulk-convert step or manual action needed
- **Pre-Upload Processing** — conversion happens on the temporary upload file, so only the WebP version is stored and all WordPress thumbnail sizes are generated from it
- **Dual Library Support** — uses Imagick (preferred) with automatic GD fallback
- **Configurable Quality** — set WebP compression quality (0–100, default 80) from the settings page
- **Selectable Image Types** — choose whether JPEG, PNG, or both should be converted
- **Smart Replacement** — the WebP file is only kept if it is smaller than (or within 10% of) the original size; otherwise the original is uploaded untouched
- **Metadata Stripping** — EXIF/metadata is stripped during Imagick conversion for smaller files
- **Transparency Support** — PNG alpha transparency is preserved
- **Directorist Integration** — adds `webp` to Directorist's supported image file types
- **Graceful Degradation** — if no image library is available, uploads proceed normally and an admin notice explains what's missing

## Requirements

- WordPress 5.0 or higher
- PHP 7.0 or higher
- One of the following PHP extensions:
  - Imagick (recommended)
  - GD with WebP support (`imagewebp`)

## Installation

1. Download or clone this repository
2. Upload the plugin folder to `/wp-content/plugins/`
3. Activate the plugin through the **Plugins** menu in WordPress
4. (Optional) Configure quality and allowed image types under **Settings → Auto WebP Converter**
5. Start uploading images — they will be automatically converted to WebP

## Usage

Once activated, the plugin works automatically. Upload images through the media library (or any upload flow that uses `wp_handle_upload`), and they will be converted to WebP if:

- The image type (JPEG/PNG) is enabled in settings
- A supported image library (Imagick or GD) is available
- The resulting WebP is smaller than, or within 10% of, the original file size

If any condition fails, the original file is uploaded unchanged — conversion never blocks an upload.

### Settings

Found under **Settings → Auto WebP Converter** (requires the `manage_options` capability):

| Setting | Default | Description |
| --- | --- | --- |
| Convert Quality | `80` | WebP compression quality (0–100). Higher = better quality, larger files. |
| Allow Image Type | JPEG, PNG | Which uploaded image types are converted. At least one must remain selected. |

Settings are stored in a single option: `wpxplore_awc_settings`.

## Development

### Project Structure

```
auto-webp-converter/
├── auto-webp-converter.php            # Main plugin file (constants, bootstrapping)
├── includes/
│   ├── class-auto-webp-converter.php  # Core converter class (Imagick/GD conversion logic)
│   ├── class-settings.php             # Settings page (Settings API)
│   └── functions.php                  # Hook registrations and helper functions
├── README.md
└── .gitignore
```

### Key Classes

- `WpXplore_Auto_WebP_Converter` — singleton core class: library detection, MIME/extension checks, Imagick and GD conversion, size comparison and file replacement
- `WpXplore_AWC_Settings` — registers the settings page, fields, sanitization, and provides static accessors (`get_settings()`, `get_setting()`)

### Hooks Used

- `wp_handle_upload_prefilter` — converts the uploaded file to WebP before WordPress processes it
- `directorist_supported_file_types_groups` — adds WebP to Directorist's supported image types
- `admin_notices` — warns when neither Imagick nor GD is available
- `admin_menu` / `admin_init` — registers the settings page and fields

### Conversion Flow

1. Upload is intercepted at `wp_handle_upload_prefilter`
2. Library availability and allowed image types are checked
3. Imagick conversion is attempted first; GD is the fallback
4. The WebP output is compared against the original size (10% tolerance)
5. If kept, the temp file is replaced and the file's name/MIME are rewritten to `.webp` / `image/webp`

## Security

- Direct file access is prevented (`ABSPATH` check in every file)
- Settings are sanitized via a `sanitize_callback` (quality clamped to 0–100, types whitelisted)
- Settings page is capability-gated (`manage_options`) and nonce-protected via the Settings API
- All output is escaped (`esc_html`, `esc_attr`)

## Coding Standards

This plugin follows:

- WordPress Coding Standards
- PHP 7.0+ compatibility
- PSR-12 compatible code style

## Changelog

### 1.0.0

- Initial release
- Automatic WebP conversion on upload (pre-upload, via `wp_handle_upload_prefilter`)
- Imagick support with GD fallback
- Settings page: conversion quality and allowed image types
- Smart size comparison — original kept if WebP isn't smaller
- PNG transparency preservation and metadata stripping
- Directorist WebP file type integration

## Credits

Developed by [wpXplore](https://wpxplore.com/)

## License

GPL v2 or later — https://www.gnu.org/licenses/gpl-2.0.html
