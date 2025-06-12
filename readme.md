# KROM Manual Translation Plugin - README

## Description

The KROM Manual Translation Plugin provides a simple, manual translation system for WordPress sites. It allows you to add languages, create translations for specific texts, and display a language switcher on your site.

---

## Installation

1. Download the plugin zip file.
2. In your WordPress admin area, go to **Plugins > Add New**.
3. Click **Upload Plugin** and select the zip file.
4. Activate the plugin after installation.

---

## Setup and Usage

### 1. Managing Languages

1. Go to the WordPress admin area and find **KROM Translations** in the menu.
2. Under the **Manage Languages** section:
   - Enter the language code (e.g., `en`, `id`, `fr`).
   - Enter the language name (e.g., `English`, `Indonesian`, `French`).
   - Click **Add Language** to save the language.

### 2. Adding Translations

1. In the **Add New Translation** section:
   - Select the target language from the dropdown.
   - Enter the original text (in your default language).
   - Enter the translation for the selected language.
   - Click **Add Translation** to save the translation.
2. View your existing translations organized by language in the **Existing Translations** section.

### 3. Using the Language Switcher

You can display the language switcher on your site using one of the following methods:

#### Option 1: Shortcode

Add the language switcher anywhere in your content using the shortcode:

```php
[krom_language_switcher class="custom-class"]
```

You can add a CSS class to style the switcher:

#### Option 2: Template Function

Add the language switcher in your theme files (header.php, footer.php, etc.):

#### Option 3: Widget

Use the KROM Language Switcher widget in any widget area through the WordPress Customizer or **Appearance > Widgets**.

### 4. Translating Content

To ensure text is translated:

For static text in your theme or plugins, use:
For user-entered content, the plugin will automatically look for and translate any content that matches entries in your translations database.

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
