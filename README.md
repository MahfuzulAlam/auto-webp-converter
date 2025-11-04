# Auto WebP Converter

A WordPress plugin that automatically converts uploaded images to WebP format during upload, reducing file sizes and improving website performance.

## Description

Auto WebP Converter automatically compresses and converts JPEG and PNG images to WebP format when they are uploaded to WordPress. The plugin uses Imagick library with GD fallback for maximum compatibility.

### Features

- **Automatic Conversion**: Converts images to WebP format during upload
- **Dual Library Support**: Uses Imagick with GD fallback
- **Smart Replacement**: Only replaces original images if WebP is smaller or similar size (10% tolerance)
- **Transparency Support**: Preserves PNG transparency during conversion
- **Directorist Integration**: Adds WebP support to Directorist file types
- **Admin Notices**: Displays helpful notices if required libraries are missing

## Requirements

- WordPress 5.0 or higher
- PHP 7.0 or higher
- One of the following PHP extensions:
  - Imagick (recommended)
  - GD with WebP support

## Installation

1. Download or clone this repository
2. Upload the plugin folder to `/wp-content/plugins/`
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Start uploading images - they will be automatically converted to WebP

## Usage

Once activated, the plugin works automatically. Simply upload images through the WordPress media library, and they will be converted to WebP format if:

- The image is a JPEG or PNG
- A supported image library (Imagick or GD) is available
- The WebP version is smaller or similar in size to the original

## Development

### Project Structure

```
auto-webp-converter/
├── auto-webp-converter.php  # Main plugin file
├── includes/
│   ├── class-auto-webp-converter.php  # Core plugin class
│   └── functions.php  # Hooks and support functions
├── README.md
└── .gitignore
```

### Hooks

The plugin provides the following WordPress hooks:

- `wp_handle_upload_prefilter` - Filters uploaded files before they are processed
- `directorist_supported_file_types_groups` - Adds WebP support to Directorist

## Security

- All user inputs are sanitized and validated
- Direct file access is prevented
- Nonce verification where applicable
- Proper escaping of output

## Coding Standards

This plugin follows:
- WordPress Coding Standards
- PHP 7.0+ compatibility
- PSR-12 compatible code style

## Changelog

### 1.0.0
- Initial release
- Automatic WebP conversion on upload
- Imagick and GD support
- Directorist integration

## Credits

Developed by [wpXplore](https://wpxplore.com/)

## License

GPL v2 or later

License URI: https://www.gnu.org/licenses/gpl-2.0.html

