# Protect Elementor pages and elements
<!-- Short title: Elementor -->

You can protect a page built with Elementor in two ways:

- **The whole page**, with the Pay For Post panel or box, exactly as for any other post. This works in the free and Premium versions.
- **Single elements** (a section, column or widget), from inside Elementor. This is **(Premium)**.

## Protect the whole page

1. Edit the page in WordPress (not in Elementor).
2. Choose the products in the **Pay For Post** panel or box.
3. Update the page.

**Expected result:** visitors without access see the paywall in place of the Elementor content.

If the page still shows in full, your theme or Elementor template may render the content without going through WordPress's normal content filter. A developer can turn on the `wc_pay_per_post_force_elementor_full_page_protection` filter, which applies the paywall to Elementor's own content output:

```php
add_filter( 'wc_pay_per_post_force_elementor_full_page_protection', '__return_true' );
```

## Protect one element (Premium)

1. Open the page in Elementor.
2. Select the section, column or widget to protect.
3. Open the **Pay for Post** tab in the element's panel, then the **Pay for Post (BETA V3)** section.
4. Turn on **Enable Pay for Post**.
5. Under **Select Products**, choose the products that unlock this element. WooCommerce Memberships plans are listed here too.
6. Optionally, turn on **Override Restricted Content Message?** and write a **Restricted Content Message** for this element. Without it, the element uses the site-wide message from Settings.
7. Click **Update**.

**Expected result:** visitors without access see the paywall in place of that element. The rest of the page is visible to everyone. Customers who bought one of the element's products see the element.

Each element can have its own products. A page can mix free elements and elements sold by different products.

The paywall in place of an element has the class `wc-ppp-protected`, so you can style it.

## How element protection differs from post protection

- Elements use standard protection only: buy once, see forever. Delay, page view and expiry apply only to whole posts.
- Paid Memberships Pro levels cannot be chosen for elements.
- A page with only element protection has no paywall over the whole page. In `[woocommerce-payperpost]` lists it is included through its element products, and it always appears in the `has_access` list, because the page itself is open.

## Common pitfalls

- **You see everything while editing.** In the Elementor editor and preview, protection is off so you can work on the page. Check the live page in a private window.
- **Elementor and Elementor Pro versions.** Keep them on matching versions. A mismatch can break the site before Pay For Post is involved.
- **Element caching.** Elementor's element cache stores one copy of a page for everyone. Pay For Post turns it off for pages that contain protected elements, so each visitor gets the right version. Other page caches still need logged-in users excluded.
- **Shortcodes in the element message.** If a WooCommerce shortcode shows as plain text in an element's paywall, use a plain message with a link to the product instead.
- **Licence lapsed.** Without an active Premium licence, element protection is not applied.
