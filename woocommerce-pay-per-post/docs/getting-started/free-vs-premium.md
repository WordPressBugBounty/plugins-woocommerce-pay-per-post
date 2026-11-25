# Free and Premium compared

Pay For Post with WooCommerce comes in a free version on WordPress.org and a Premium version sold through pramadillo.com. Throughout this documentation, features that need Premium are marked **(Premium)**.

## What both versions do

| Feature | Free | Premium |
| --- | --- | --- |
| Protect posts, pages and custom post types with one or more WooCommerce products | Yes | Yes |
| One product unlocks many posts | Yes | Yes |
| Standard protection: buy the product, read the post | Yes | Yes |
| One site-wide paywall message with `{{product_id}}` and `{{parent_id}}` tokens | Yes | Yes |
| Any WooCommerce shortcode in the paywall message | Yes | Yes |
| Block editor sidebar panel and classic meta box | Yes | Yes |
| `[woocommerce-payperpost]` lists with the `purchased`, `has_access`, `remaining` and `all` templates | Yes | Yes |
| Template functions for themes that do not use `the_content` | Yes | Yes |
| Access Diagnostic ("why can't this customer see this page?") | Yes | Yes |
| Turn comments off on protected posts | Yes | Yes |
| Let administrators see all protected content | Yes | Yes |

## What Premium adds

| Feature | Premium |
| --- | --- |
| Delay protection: the post is free for a set time after publishing, then the paywall appears | Yes |
| Page-view protection: a purchase allows a set number of views | Yes |
| Expiry protection: a purchase gives access for a set time | Yes |
| Show customers their views or time remaining on the post | Yes |
| A different paywall message for each post | Yes |
| `{{excerpt}}` and `{{memberships}}` tokens | Yes |
| Inline protection of part of a post with `[wc-pay-for-post-inline]` | Yes |
| "Purchased Content" tab in WooCommerce My Account, with a sort order | Yes |
| Links to unlocked content on the order confirmation, order emails and My Account order view | Yes |
| Product-page list of what a product unlocks: `[wc-pay-for-post-product-unlocks]` | Yes |
| `orderby`, `order`, `category_slug`, `tag` and `product_ids` on `[woocommerce-payperpost]` | Yes |
| The `purchased-datatables` list template (searchable table) | Yes |
| Protect with product variations | Yes |
| Lock icon beside protected post titles | Yes |
| Comments only for customers with access | Yes |
| `ppp-protected`, `ppp-has-access` and `ppp-has-no-access` body classes | Yes |
| Let chosen user roles bypass the paywall | Yes |
| WooCommerce Subscriptions, including 9.0+ subscription plans | Yes |
| WooCommerce Memberships | Yes |
| Paid Memberships Pro | Yes |
| Elementor element-level protection | Yes |
| Polylang support | Yes |
| Protected Content screen listing each protected post and its buyers | Yes |
| Page-view history on each user's profile, with delete | Yes |

Premium also includes a 7-day trial and priority support.

## If a Premium licence lapses

Your settings stay in the database. Posts set to delay, page-view or expiry protection keep their settings but are protected as standard purchases:

- visitors still have to buy the product;
- the delay window, the page-view allowance and the expiry date stop being applied;
- the original settings resume as soon as the licence is active again.

wp-admin shows a warning naming how many posts are affected.

Other Premium features stop working while the licence is inactive. That includes the inline shortcode, the My Account tab, the integrations with subscription and membership plugins, and Elementor element protection. A post with inline regions is protected as a whole, so visitors who have not bought it no longer see its free part. A post protected only by a membership plan or level cannot be opened by anyone while the integration is inactive. Check posts that rely on them before you let a licence lapse.
