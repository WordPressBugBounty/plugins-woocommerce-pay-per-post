# Data storage reference

Where Pay For Post keeps its data: post meta, options, a database table and transients. Useful for imports, migrations, reporting and clean-up.

Pay For Post does not copy order data. Who bought what always comes from WooCommerce orders, read live, so changing an order's status changes access straight away.

## Post meta

Stored on each protected post.

| Key | Type | Meaning |
| --- | --- | --- |
| `wc_pay_per_post_product_ids` | array of strings | IDs that unlock the post: products, variations, and (Premium) WooCommerce Memberships plan IDs and Paid Memberships Pro level IDs. Stored serialized. An empty array or empty first item means not protected. |
| `wc_pay_per_post_delay_restriction_enable` | `1` or empty | Delay protection on (Premium) |
| `wc_pay_per_post_delay_restriction` | integer | Delay amount |
| `wc_pay_per_post_delay_restriction_frequency` | `minute`, `hour`, `day`, `week`, `month`, `year` | Delay unit |
| `wc_pay_per_post_page_view_restriction_enable` | `1` or empty | Page-view protection on (Premium) |
| `wc_pay_per_post_page_view_restriction` | integer | Views allowed per purchase |
| `wc_pay_per_post_page_view_restriction_enable_time_frame` | `1` or empty | Limit views to a window |
| `wc_pay_per_post_page_view_restriction_time_frame` | integer | Window length |
| `wc_pay_per_post_page_view_restriction_frequency` | unit | Window unit |
| `wc_pay_per_post_expire_restriction_enable` | `1` or empty | Expiry protection on (Premium) |
| `wc_pay_per_post_expire_restriction` | integer | Access length |
| `wc_pay_per_post_expire_restriction_frequency` | unit | Access unit |
| `wc_pay_per_post_restricted_content_override` | HTML | The post's own paywall message (Premium) |
| `wc_pay_per_post_show_warnings` | `1` or empty | Show views or time remaining (Premium) |
| `_ppp_document_settings_meta` | JSON | A copy of the block editor panel's settings, written by the panel. Not read when deciding access. |

Elementor element protection (Premium) is stored inside Elementor's `_elementor_data` meta, under each element's `wc_pay_per_post_enable`, `wc_pay_per_post_select_products`, `wc_pay_per_post_should_override_message` and `wc_pay_per_post_override_message` settings.

On pages with protected elements, Pay For Post deletes Elementor's `_elementor_element_cache` meta and stops Elementor from writing it again, so each visitor gets their own version of the page.

## Options

| Option | Default | Setting |
| --- | --- | --- |
| `wc_pay_per_post_restricted_content_default` | Default paywall message | Default paywall message |
| `wc_pay_per_post_custom_post_types` | `post`, `page` | Post types you can protect |
| `wc_pay_per_post_only_show_virtual_products` | off | Only offer virtual and downloadable products |
| `wc_pay_per_post_turn_off_comments_when_protected` | on | Who can comment on protected posts: Nobody |
| `wc_pay_per_post_turn_off_comments_public` | off | Who can comment on protected posts: Customers with access (Premium) |
| `wc_pay_per_post_allow_admins_access_to_protected_posts` | off | Administrators see everything |
| `wc_pay_per_post_block_editor_enabled` | on | Panel in the block editor sidebar |
| `wc_pay_per_post_enable_debugging` | off | Debug log |
| `wc_pay_per_post_delete_settings` | off | Delete all Pay For Post data when the plugin is deleted |
| `wc_pay_per_post_display_on_my_account` | off | Purchased Content tab (Premium) |
| `wc_pay_per_post_shortcodes_to_display_on_my_account` | both | Lists to show (Premium) |
| `wc_pay_per_post_my_account_sort_order` | `date_desc` | Sort the lists by (Premium) |
| `wc_pay_per_post_show_locks` | off | Lock icon on locked titles (Premium) |
| `wc_pay_per_post_enable_polylang` | off | Use Polylang (Premium) |

The plugin also keeps a few internal options with the same prefix, for example to record that a data upgrade is needed.

## Page-view table (Premium)

`{prefix}woocommerce_pay_per_post_pageviews`, created on activation.

| Column | Type | Meaning |
| --- | --- | --- |
| `id` | bigint | Row ID |
| `user_id` | bigint | The customer |
| `post_id` | bigint | The post viewed |
| `timestamp` | timestamp | When, in UTC |

One row per recorded view. Where the database allows it, rows are deleted automatically when the user or the post is deleted.

## Transients

All start with `wc_pay_per_post_`:

| Transient | Holds | Lifetime |
| --- | --- | --- |
| `wc_pay_per_post_posts` | All protected posts | 1 hour |
| `wc_pay_per_post_shortcode_{hash}` | The posts for one `[woocommerce-payperpost]` query | 1 hour |
| `wc_pay_per_post_dormant_premium_protection_count` | Count behind the "Premium protection paused" notice | 1 day |

Lifetimes can be changed with `wc_pay_per_post_posts_transient_time`. They are cleared when a post is saved in the editor (unless `wc_pay_per_post_clear_transients_on_post_edit` returns false) and by **Refresh cached lists** (**Pay For Post > Need Help > Status and tools**). Both clear the database copies only; with a persistent object cache, flush that cache too.

## Files

| Path | Holds |
| --- | --- |
| `wp-content/uploads/woocommerce-pay-per-post-logs/debug-<hash>.log` | The debug log, written only while debugging is on and the page URL contains `?wc-ppp-debug`. The hash comes from the site's security keys. The folder also holds an `.htaccess` that refuses web requests and empty index files. Versions up to 3.3.0 wrote `debug.log`; 3.4.0 deletes that file once |
| `wc_pay_per_post_log_secured` option | Set once the log folder has been protected and the old `debug.log` deleted |

## What is removed when

| Action | Removes |
| --- | --- |
| Deactivate the plugin | Nothing |
| Delete the plugin, with **Delete all Pay For Post data when the plugin is deleted** on | The page-view table, all `wc_pay_per_post_` options and post meta, transients, the debug log |
| Delete the plugin, with that setting off | Nothing |
| **Remove all protection** (**Need Help > Status and tools**) | All `wc_pay_per_post_` post meta, transients and page views. Settings stay. |
| **Reset page-view counts** (**Need Help > Status and tools**, Premium) | All page views |
