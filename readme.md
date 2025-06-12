# KROM Manual Translation Plugin

## Description

The KROM Manual Translation Plugin provides a simple yet powerful manual translation system for WordPress sites. It allows site administrators to add languages, create translations for specific text strings, and display a language switcher on their site. No automated translation services are used - you have complete control over all translated content.

---

## Installation

1. Upload the `krom-manual-translation` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure languages and translations through the "Krom Translation" admin menu

---

## Setup and Configuration

### Managing Languages

1. Go to **Krom Translation > Settings** in your WordPress admin area
2. Set your default language (e.g., English)
3. Select which languages you want to make available on your site
4. Save your settings

### Adding Translations

1. Navigate to **Krom Translation > Translations** 
2. Add new text items by providing:
   - A text ID/key (or leave empty for auto-hashing)
   - The text content in each configured language
3. Click "Save Translations" to update the translation database

### Using the Language Switcher

Add a language switcher to your site using one of these methods:

#### Shortcode

Add the language switcher anywhere in your content using the shortcode:

```php
[krom_language_switcher class="custom-class"]
```

You can add a CSS class to style the switcher.

#### Template Function

Add the language switcher in your theme files (header.php, footer.php, etc.):

```php
<?php krom_language_switcher(['class' => 'custom-class']); ?>
```

#### Widget

Use the KROM Language Switcher widget in any widget area through the WordPress Customizer or **Appearance > Widgets**.

---

## Features

- Multiple Languages Support: Add and manage any number of languages
- Simple Translation Interface: Easy-to-use admin interface for adding translations
- Language Switcher: Display a language switcher with proper hreflang attributes
- Fixed Header Detection: Language switcher can detect when your theme's header becomes fixed
- Shortcode Support: Add the language switcher anywhere using shortcodes
- Flexible Implementation: Use with any WordPress theme or plugin

---

## Styling the Language Switcher

The language switcher has the following HTML structure:

```html
<div class="krom-language-switcher">
  <ul>
    <li class="active"><a href="?lang=en">English</a></li>
    <li><a href="?lang=fr">French</a></li>
    <li><a href="?lang=id">Indonesian</a></li>
  </ul>
</div>
```

You can add custom CSS to style this according to your site's design.

When a fixed header is detected, the class `header-is-fixed` is added to the language switcher automatically.

---

## Technical Notes

- Translations are stored in the WordPress options table
- Language preferences are stored in user sessions and/or URL parameters
- The plugin uses WordPress nonces for security
- The hreflang attribute is automatically set according to the language mapping

---

## Troubleshooting

- Language switcher not showing: Ensure you've added languages in the admin area
- Translations not working: Verify you've added translations and are using the correct text string
- Fixed header detection not working: Make sure your theme adds a 'fixed' class to the header element

---

## Need Help?

If you need assistance with this plugin, please contact support.
