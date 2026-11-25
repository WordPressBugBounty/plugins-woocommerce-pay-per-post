# Requirements

Pay For Post with WooCommerce sells access to WordPress pages, posts and custom post types through WooCommerce. This page lists what your site needs before you install it.

This page describes version 3.4.0.

## Minimum versions

| Software | Minimum | Tested up to |
| --- | --- | --- |
| WordPress | 6.6 | 7.1 |
| WooCommerce | 6.9 | 11.2.1 |
| PHP | 7.4 | not stated |
| Elementor and Elementor Pro (optional, Premium integration) | none stated | Elementor 4.3.4, Elementor Pro 4.3.1 |

WooCommerce must be installed and active. WordPress will not activate Pay For Post without it, and the plugin stops activation with the message "Please install and Activate WooCommerce." if WooCommerce is missing.

Sites below WordPress 6.6 or WooCommerce 6.9 keep the version of Pay For Post they already have. WordPress does not offer them version 3.4.0 or later.

## Guest checkout must be off

Access to protected content belongs to a WordPress user account. A visitor who is not logged in never has access, whatever they bought.

If a customer checks out as a guest, they have no account to log in to, so they cannot see what they paid for. Turn guest checkout off:

1. Go to **WooCommerce > Settings > Accounts & Privacy**.
2. Clear **Allow customers to place orders without an account**.
3. Make sure customers can create an account during checkout.
4. Click **Save changes**.

The Access Diagnostic on **Pay For Post > Need Help > Check a customer** warns you when guest checkout is on.

## Your theme must use the_content

Pay For Post hides content by filtering WordPress's `the_content` output. Almost every theme and the block editor use it. If your theme or page builder prints post content another way, the content is not protected until you add the template functions described in the Template functions reference.

## Optional integrations (Premium)

The Premium version works with these plugins when they are active:

- WooCommerce Subscriptions, including the subscription plans added in WooCommerce Subscriptions 9.0
- WooCommerce Memberships
- Paid Memberships Pro
- Elementor (element-level protection)
- Polylang (purchased content in every language)

Each one is detected automatically. You do not need to switch it on, except Polylang support, which has its own setting.

## Hosting notes

- The Premium page-view protection stores views in its own database table, `{prefix}woocommerce_pay_per_post_pageviews`. It is created when the plugin is activated. If it is missing, **Pay For Post > Need Help > Status and tools** offers **Create the page-view table**.
- Full-page caches must not serve cached pages to logged-in customers. See the How-to guide "Use Pay For Post with full-page caching".
