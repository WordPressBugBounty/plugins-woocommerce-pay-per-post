# Templates reference

The template files Pay For Post renders, the variables each receives, and how the plugin finds an override. For a step-by-step guide, see "Override the plugin's templates".

## Lookup order

For a template file `{file}`, the plugin uses the first that exists:

1. `{child theme}/woocommerce-pay-per-post/{file}`
2. `{parent theme}/woocommerce-pay-per-post/{file}`
3. `{child theme}/{file}`
4. `{parent theme}/{file}`
5. `wp-content/plugins/woocommerce-pay-per-post/public/partials/{file}`

The result passes through the `wc_pay_for_post_locate_template` filter, with the found path, the file name and the theme folder name (`woocommerce-pay-per-post/`).

## Content list templates

Used by `[woocommerce-payperpost]` and the My Account tab.

| File | Template name | Variables |
| --- | --- | --- |
| `shortcode-purchased.php` | `purchased` | `$purchased` WP_Post[], `$atts` array, `$template` string |
| `shortcode-has_access.php` | `has_access` | `$purchased` WP_Post[] (posts the visitor can open), `$atts`, `$template` |
| `shortcode-remaining.php` | `remaining` | `$remaining` WP_Post[], `$atts`, `$template` |
| `shortcode-all.php` | `all` | `$ppp_posts` WP_Post[], `$atts`, `$template` |
| `shortcode-purchased-datatables.php` | `purchased-datatables` (Premium) | `$purchased` WP_Post[], each with `product_terms` (product category terms) |
| `shortcode-purchased-polylang.php` | `purchased` with Polylang support (Premium) | `$purchased` |
| `shortcode-has_access-polylang.php` | `has_access` with Polylang support (Premium) | `$purchased` |

Extra properties on posts in the `purchased` lists (Premium):

| Property | Type | Meaning |
| --- | --- | --- |
| `expiration_status` | bool | `true` when the post uses expiry protection and the visitor's access has run out |
| `last_purchase_date` | date object or null | The paid date of the visitor's most recent qualifying order; absent when `wc_pay_per_post_enable_last_purchase_date` returns false |

The lists are already filtered to the visitor and sorted before the template runs. The `wc_pay_per_post_shortcode_posts` filter can change them first.

Default markup:

| Template | Container | List |
| --- | --- | --- |
| purchased, has_access | `div.wc-ppp-purchased-container` | `ul#what-ever-you-want` |
| remaining | `div.wc-ppp-remaining-container` | `ul` |
| all | `div.wc-ppp-posts-container` | `ul` with `li.locked` / `li.unlocked` |

## Order templates (Premium)

| File | Used for | Variables |
| --- | --- | --- |
| `woocommerce-order-receipt.php` | Purchased content links under an order line, in order views and emails | `$purchased`: array of `['post_id' => int, 'post_title' => string, 'post_url' => string]`; `$item`: WC_Order_Item_Product; `$formatted_meta`: the item's original meta HTML |
| `woocommerce-order-receipt-polylang.php` | The same, with Polylang support | As above, each entry with `translations` |

Default markup: `div.ppp-purchased-content` with a heading (**Heading above the links** in Settings, filter `wc_pay_per_post_purchased_content_title`) and `ul.ppp-purchased-content__list`.

## Status templates (Premium)

Shown at the top or bottom of a post, as **Views or time remaining box** under **Settings > Paywall** says, when **Show customers the views or time they have left** (in the block editor, **Show the views left** or **Show the time left**) is on for the post.

| File | Variables |
| --- | --- |
| `pageview-status.php` | `$user_info['last_purchase_date']` date, `$user_info['total_page_views']` int (including the current view), `$user_info['end_date']` date (only with a time frame), `$number_of_allowed_pageviews` |
| `expiration-status.php` | `$user_info['last_purchase_date']` date, `$user_info['expiration_date']` date, `$user_info['time_remaining']` array: `human` (for example "2 days from now"), `difference` (number), `frequency` (unit) |

Dates are Carbon date objects in UTC. The default templates format them with your site's date and time format settings: `$date->format( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) )`.

Default markup: `div.wc-ppp-pageview-status-container` or `div.wc-ppp-expiration-status-container`, each with an "Order Information" heading and a list.

## Not templates

- **The paywall** is built from the **Default paywall message** setting or the post's override. Change it there, or with the `wc_pay_for_post_override_paywall_content` filter.
- **The My Account tab headings** come from filters. See the Hooks reference.
- **The product unlock list** markup is built in code.
