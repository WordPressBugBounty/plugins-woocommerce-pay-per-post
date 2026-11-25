# Override the plugin's templates
<!-- Short title: Override templates -->

The content lists, the order receipt links and the status boxes are built from small PHP template files. You can copy any of them into your theme and change the markup, without editing the plugin. Your copy survives plugin updates.

This guide is for someone comfortable editing theme files. Use a child theme, so a theme update does not remove your changes.

## Steps

1. Find the template in the plugin, in `wp-content/plugins/woocommerce-pay-per-post/public/partials/`. The list is below.
2. Create a folder named `woocommerce-pay-per-post` in your child theme, for example `wp-content/themes/my-child-theme/woocommerce-pay-per-post/`.
3. Copy the template file into that folder, keeping its file name.
4. Edit your copy.
5. Reload a page that uses it.

**Expected result:** the plugin uses your copy instead of its own.

The plugin looks for the file in this order:

1. `your-theme/woocommerce-pay-per-post/{file}`
2. `your-theme/{file}`
3. the plugin's own copy.

Child themes are searched before parent themes.

## Templates you can override

| File | Used by | Variables available |
| --- | --- | --- |
| `shortcode-purchased.php` | `[woocommerce-payperpost template="purchased"]` and the My Account "Content purchased" list | `$purchased` (array of posts), `$atts`, `$template` |
| `shortcode-has_access.php` | `template="has_access"` and the My Account "Content you have access to" list | `$purchased` (array of posts the customer can open) |
| `shortcode-remaining.php` | `template="remaining"` | `$remaining` (array of posts) |
| `shortcode-all.php` | `template="all"` | `$ppp_posts` (array of posts) |
| `shortcode-purchased-datatables.php` | `template="purchased-datatables"` (Premium) | `$purchased`, each post with `product_terms` |
| `shortcode-purchased-polylang.php` | The purchased list with Polylang support on (Premium) | `$purchased` |
| `shortcode-has_access-polylang.php` | The has-access list with Polylang support on (Premium) | `$purchased` |
| `woocommerce-order-receipt.php` | Links to purchased content on orders and order emails (Premium) | `$purchased` (array of `post_id`, `post_title`, `post_url`), `$item` (the order item) |
| `woocommerce-order-receipt-polylang.php` | The same, with Polylang support on (Premium) | As above |
| `expiration-status.php` | The status box on expiry posts (Premium) | `$user_info`: `last_purchase_date`, `expiration_date`, `time_remaining` (`human`, `difference`, `frequency`) |
| `pageview-status.php` | The status box on page-view posts (Premium) | `$user_info`: `last_purchase_date`, `end_date` (with a time frame), `total_page_views`; and `$number_of_allowed_pageviews` |

The posts in `$purchased`, `$remaining` and `$ppp_posts` are WordPress post objects. In Premium, posts in the purchased list also carry:

- `$post->expiration_status`: `true` if the customer's expiry access has run out;
- `$post->last_purchase_date`: the date of their most recent qualifying order, as a date object, unless a developer turned it off with `wc_pay_per_post_enable_last_purchase_date`.

Dates in `$user_info` are date objects in UTC. Format them with `->format()`; the default templates use your site's date and time formats.

## Example: show "Expired" in the purchased list (Premium)

In your copy of `shortcode-purchased.php`, inside the loop:

```php
<li>
    <a href="<?php echo esc_url( get_permalink( $post->ID ) ); ?>"><?php echo esc_html( $post->post_title ); ?></a>
    <?php if ( ! empty( $post->expiration_status ) ) : ?>
        <span class="expired">Expired</span>
    <?php endif; ?>
</li>
```

## Use a different folder

A developer can point the plugin at another location with the `wc_pay_for_post_locate_template` filter. See the Hooks reference.

## Common pitfalls

- **Your copy is ignored.** Check the folder name is exactly `woocommerce-pay-per-post` and the file name matches.
- **Your copy breaks after an update.** Plugin updates can change a template. When a release notes a template change, compare your copy with the new one.
- **Escape output.** Use `esc_html()` and `esc_url()` for titles and links, as the default templates do.
- **The paywall itself is not a template.** Change it in **Settings > Paywall > Default paywall message**, or per post.
