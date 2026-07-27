# Auto WebP Converter - User Guide

Welcome! This guide explains everything Auto WebP Converter does, every setting in the admin panel, and how to get the most out of it. No technical knowledge required.

---

## Why You Need This Plugin

### Your images are slowing your website down

Images are almost always the heaviest part of a web page. A typical photo uploaded from a phone or camera is 2 to 5 MB. Multiply that by every image on every page, and your visitors are downloading a lot of unnecessary data.

Slow pages cost you real results:

- **Visitors leave.** Most people abandon a page that takes more than 3 seconds to load.
- **Google ranks you lower.** Page speed and Core Web Vitals are official ranking factors, and heavy images directly hurt your SEO.
- **You pay more for hosting.** Bigger files mean more storage and more bandwidth on every single visit.

### The solution: WebP, smaller images with the same quality

**WebP** is a modern image format developed by Google. It makes image files **25-35% smaller than JPEG and PNG** with no visible difference in quality. Every modern browser supports it (Chrome, Safari, Firefox, Edge, all of them).

The problem? Converting every image by hand before uploading is tedious, and most optimization plugins keep *two copies* of every image, doubling your storage.

### What Auto WebP Converter does differently

Auto WebP Converter converts your images **automatically, at the moment you upload them**, before WordPress even stores the file. That means:

| ✅ Benefit | What it means for you |
| --- | --- |
| **Fully automatic** | Upload images exactly as you do now. No buttons to press, no bulk jobs to run. |
| **Faster pages** | Images are 25-35% smaller on average, so pages load noticeably quicker. |
| **Better SEO** | Smaller images improve your Core Web Vitals scores, which Google rewards. |
| **No wasted storage** | Only the WebP file is stored (unless you *choose* to keep originals). Every thumbnail WordPress creates is WebP too. |
| **Never makes things worse** | If the WebP version wouldn't be smaller, the plugin keeps your original. If anything goes wrong, your upload always succeeds. |
| **Directorist ready** | Built-in support for Directorist directory sites, including visitor-submitted listings. |

**In short:** install it once, and every image you upload from that day forward is automatically optimized. Set it and forget it.

---

## Requirements

- WordPress 5.0 or newer
- PHP 7.0 or newer
- One of these PHP image libraries on your server (almost all hosts have at least one):
  - **Imagick** (recommended)
  - **GD** with WebP support

> 💡 Not sure what your server has? Just activate the plugin. If neither library is available, a clear notice appears in your admin dashboard telling you what to ask your host for. Your uploads keep working normally either way.

---

## Getting Started (2 minutes)

1. Upload the plugin to `/wp-content/plugins/` (or install the ZIP via **Plugins → Add New → Upload Plugin**).
2. Click **Activate**.
3. Go to **Settings → Auto WebP Converter** to review your options (the defaults work great for most sites).
4. Upload an image to your Media Library. That's it! It arrives as a `.webp` file automatically.

**Quick test:** upload any JPEG photo, then look at it in the Media Library. The filename now ends in `.webp`, and the file size is dramatically smaller.

---

## The Settings Explained

All settings live under **Settings → Auto WebP Converter**. Here is every option, what it does, and when to change it.

---

### 1️⃣ Conversion Settings

#### Convert Quality

**What it does:** Controls how much your images are compressed, on a scale of 0 to 100. Higher numbers give better quality but bigger files; lower numbers give smaller files but more visible compression.

**Default:** `80`, the sweet spot for almost every website.

**Examples:**

| You run a… | Recommended quality | Why |
| --- | --- | --- |
| Blog or business site | **80** (default) | Great quality with big savings. Nobody will notice the difference. |
| Photography portfolio | **85-90** | Preserves fine detail in showcase images. |
| Directory or listings site | **70-80** | Listing photos stay sharp while pages load fast. |
| News/content site with many images | **65-75** | Maximum speed when pages contain dozens of images. |

> 💡 **Tip:** Changing this setting only affects *future* uploads. Try uploading the same photo at different quality values and compare. 80 vs. 90 is usually impossible to tell apart, but the file is much smaller.

#### Allow Image Type

**What it does:** Chooses which kinds of images get converted to WebP. Anything unchecked is uploaded exactly as-is.

**Default:** JPEG and PNG (the two most common formats, covering 95% of typical uploads).

**Available types:**

| Type | Check it if… | Good to know |
| --- | --- | --- |
| **JPEG** | ✅ Almost always: photos, camera uploads | The biggest space savings come from here. |
| **PNG** | ✅ Almost always: logos, screenshots, graphics | Transparency is fully preserved in the WebP version. |
| **GIF (non-animated)** | You upload static GIF images | **Animated GIFs are automatically detected and never converted**, so your animations are always safe. |
| **BMP** | You upload BMP files (rare, e.g. old scans) | Works on virtually all modern hosting. |
| **TIFF** | You upload TIFF files (rare, e.g. print graphics) | Requires the Imagick library on your server. |

**Example:** You run a design blog and upload lots of animated GIF memes plus JPEG photos. Keep **JPEG** and **PNG** checked and also check **GIF**. Your static GIFs get optimized, while your animated memes pass through untouched, still animating perfectly.

#### Convert Media Library Uploads

**What it does:** The master switch for regular uploads made through **Media → Add New** or the media picker in your posts and pages.

**Default:** ✅ On.

**When to turn it off:** Almost never. But if you want conversion *only* for specific situations (for example, only Directorist listing images), turn this off and enable just the contexts you want below.

**Example:** A real-estate directory wants property photos converted but wants brochures and floor plans (uploaded as PNG to the Media Library) left untouched. Solution: turn **off** Convert Media Library Uploads, and turn **on** only the Directorist options.

#### Keep Original Images

**What it does:** Decides whether your original file (the JPEG/PNG you actually uploaded) is kept alongside the converted WebP.

**Default:** ⬜ Off. Only the WebP file is kept, for maximum space savings.

**When enabled:** uploading `photo.jpg` gives you **two items in your Media Library**:

- `photo.webp` is the optimized version, used everywhere on your site
- `photo.jpg` is your untouched original, available whenever you need it

**Turn it ON if:**
- You sell or share original, full-quality files (photographers, stock sites)
- You want a safety copy of every original for future editing or print
- You're trying the plugin out and want the reassurance of keeping originals

**Leave it OFF if:**
- You want maximum storage savings (the whole point for most sites!)
- You don't have a reason to keep two copies of every image

> ⚠️ **Note:** With this on, WordPress stores both files *and* generates thumbnails for both, so storage use roughly doubles for those uploads. That's the trade-off for keeping originals.

---

### 2️⃣ Featured Image Settings

#### Post Types for Featured Images

**What it does:** Lets you enable conversion specifically for **featured images** uploaded while editing a post, page, or any custom content type. You'll see a checkbox for every content type on your site that supports featured images, for example **Post**, **Page**, or custom types added by your theme and plugins.

**Default:** ⬜ None selected.

**How it works:** When you set a featured image while editing (say) a blog post, the plugin checks whether "Post" is enabled here. If yes, the image is converted to WebP. If no, it uploads normally.

**Examples:**

- **A blogger** checks **Post** so every article's featured image is optimized. These images appear on the homepage, archives, and social shares, so keeping them small matters a lot.
- **An agency** checks **Post** and **Page** so hero images across the whole site are optimized.
- **A directory owner** leaves these unchecked and uses the dedicated Directorist options below instead.

> 💡 If "Convert Media Library Uploads" is already ON, most uploads are converted anyway. This setting matters most when you've turned that master switch off and want fine-grained control.

---

### 3️⃣ Directorist Settings

> This section only appears when the [Directorist](https://wordpress.org/plugins/directorist/) directory plugin is active. The plugin also automatically teaches Directorist to accept `.webp` files, with no configuration needed for that.

#### Directorist post type (Admin Panel)

**What it does:** When enabled, featured images you upload while editing a **listing in the WordPress admin** are converted to WebP.

**Default:** ⬜ Off.

**Example:** You manage a restaurant directory and add new restaurants from the admin dashboard. Enable this, and every restaurant photo you attach is automatically optimized. Your directory pages (which show dozens of listing images at once) load dramatically faster.

#### Add listing page (Frontend)

**What it does:** When enabled, images that **your visitors upload** through the public "Add Listing" form are converted to WebP.

**Default:** ⬜ Off.

**Why this is powerful:** You can't control what your users upload. Many will submit huge 4-8 MB phone photos. With this enabled, every user submission is automatically compressed before it ever hits your server's media library.

**Example:** Your business directory gets 50 new listings a week, each with 3 photos averaging 3 MB. That's around 450 MB of images per week. With conversion enabled at quality 80, that drops to roughly 130-150 MB, saving you gigabytes of storage every month while your listing pages stay fast.

---

## How It Works (the 30-second version)

1. You (or a visitor) upload an image.
2. **Before** WordPress stores it, the plugin converts it to WebP at your chosen quality.
3. The plugin compares sizes. **Only if the WebP is smaller** does it replace the original. If your image was already tiny or highly optimized, the original is kept instead.
4. WordPress stores the result and creates all its thumbnail sizes from it, so every size of every image is optimized too.
5. If "Keep Original Images" is on, your original is saved alongside and added to the Media Library.

**Built-in safety nets:**

- 🛡️ An upload is **never blocked or broken** by conversion. If anything fails, your original file uploads normally.
- 🎞️ **Animated GIFs are never converted**, so animations always survive.
- 📏 **Already-optimized images are respected.** The plugin never replaces a file with a bigger one.

---

## Frequently Asked Questions

**Will my existing images be converted?**
No. The plugin converts images at upload time only. Images already in your Media Library are untouched. (Bulk conversion of existing images is on our roadmap.)

**Do WebP images work in all browsers?**
Yes. Every modern browser (Chrome, Safari, Firefox, Edge, and mobile browsers) has supported WebP for years.

**Will I see a quality difference?**
At the default quality of 80, virtually never. WebP is simply more efficient at storing the same visual information.

**What happens to PNG transparency?**
It's fully preserved. Transparent logos and graphics stay transparent in WebP.

**I uploaded a small PNG icon and it stayed a PNG. Is that a bug?**
No, that's the smart size check working. Small, simple graphics sometimes compress better in their original format, so the plugin kept the better file.

**Can I get my original image back after conversion?**
Only if "Keep Original Images" was enabled when you uploaded it. Otherwise the original is not stored, so keep a local copy of anything irreplaceable, or enable that setting.

**Does it slow down uploading?**
Conversion adds a fraction of a second per image during upload. There is zero impact on your site's visitors, since nothing runs on your public pages.

**What if my server has neither Imagick nor GD?**
You'll see a clear notice in the dashboard, and all uploads continue working normally (just without conversion). Ask your host to enable one of the two. It's a standard, free component.

---

## Troubleshooting

| Problem | Solution |
| --- | --- |
| Images upload but aren't converted | Check **Settings → Auto WebP Converter**: is the image's type checked under *Allow Image Type*? Is the right context enabled (Media Library / Featured Images / Directorist)? |
| "Neither Imagick nor GD…" notice in dashboard | Ask your hosting provider to enable the **Imagick** PHP extension (or GD with WebP support). This takes them minutes. |
| A GIF wasn't converted | If it's animated, that's intentional: animations are protected. Static GIFs also need **GIF** checked in *Allow Image Type*. |
| A TIFF wasn't converted | TIFF requires the **Imagick** library. If your server only has GD, TIFF files upload unconverted. |
| Original file missing after upload | "Keep Original Images" was off at upload time. Enable it for future uploads. |
| Settings page looks unstyled | Hard-refresh the page (Cmd/Ctrl + Shift + R) to reload the stylesheet. |

---

## Recommended Setups

**🏠 "Just make my site faster" (most sites)**
Defaults: Quality 80, JPEG + PNG, Convert Media Library Uploads ON, everything else off. Done.

**📸 Photographer / creative portfolio**
Quality 85-90 · JPEG + PNG · Keep Original Images **ON**. Visitors get fast pages, you keep every original.

**📂 Directorist directory site**
Quality 75-80 · JPEG + PNG · Convert Media Library Uploads ON · both Directorist options **ON**. Admin-added and visitor-submitted listing photos are all optimized automatically.

**🗞️ High-volume content site**
Quality 70 · JPEG + PNG + GIF · Featured images enabled for **Post**. Maximum savings across thousands of images.

---

*Auto WebP Converter is developed by [wpXplore](https://wpxplore.com/). For technical documentation (hooks, file structure, developer details), see the plugin's README.md.*
