# Use Pay For Post with Polylang
<!-- Short title: Polylang -->

**(Premium)**

On a multilingual site run with Polylang, each translation of a post is a separate post. This guide makes sure every translation is protected and that customers see their purchases in every language.

## Turn on Polylang support

1. Go to **Pay For Post > Settings > Integrations**. The **Use Polylang** setting appears in the Polylang row only while Polylang is active.
2. Tick **Use Polylang**.
3. Click **Save changes**.

**Expected result:** the `purchased` and `has_access` lists, and the purchased content links on orders, show each post in the visitor's current language with links to its other translations.

## Protect every translation

Protection is stored on each post, so a translation is not protected just because the original is.

1. Protect the original post with its products.
2. Open each translation and choose the same products.
3. Save each one.

Alternatively, in **Languages > Settings > Synchronization**, tick **Custom fields** so Polylang copies the Pay For Post settings to translations. Check one translation logged out afterwards to make sure it shows the paywall.

## Translate the paywall

The site-wide message is a single text. For a paywall in each language:

- give each translation its own message: under **Paywall message** in its **Pay For Post** panel or box, choose **A message for this post** and write it in that language; or
- use a translation plugin's string translation for the settings text, if it supports it.

## Common pitfalls

- **A translation shows in full.** It has no products. Open it and add them, or turn on custom field synchronization.
- **Products in other languages.** If you also translate WooCommerce products, choose the product in the matching language on each translation, or the same product on all of them. A customer who bought the English product does not have the French product, and the reverse.
