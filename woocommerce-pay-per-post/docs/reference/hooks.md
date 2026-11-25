# Hooks reference

Every filter and action Pay For Post provides, with its arguments and default. This page is for developers.

## Where to put your code

Most hooks can go in a child theme's `functions.php` or a small custom plugin. Two are read while the plugin loads, before the theme, and must go in a **must-use plugin** (a PHP file in `wp-content/mu-plugins/`):

- `wcppp_is_active`
- `wc_pay_for_post_the_content_priority`

## Filters that have a setting

Some filters have a setting on **Pay For Post > Settings** too. The filter receives the setting as its value, and what it returns is used, so a filter overrides the setting. Where one is active, the Settings screen says so under that setting. See the Settings reference.

| Filter | Setting |
| --- | --- |
| `wc_pay_for_post_allowed_roles` | Access > Other roles that skip the paywall |
| `wc_pay_for_post_show_warnings_position` | Paywall > Views or time remaining box |
| `wc_pay_per_post_enable_javascript_expiration_refresh` | Paywall > Reload when access runs out |
| `wc_pay_per_post_show_item_meta`, `wc_pay_per_post_hide_item_meta_in_email` | Customers > Purchased content links on orders |
| `wc_pay_per_post_purchased_content_title` | Customers > Heading above the links |
| `wc_pay_per_post_my_account_tab_title`, `wc_pay_per_post_my_account_has_access_title`, `wc_pay_per_post_my_account_purchased_title` | Customers > Tab name and the two headings |
| `wc_pay_per_post_allowed_roles_for_meta_box` | Editor > Who can set protection |
| `wc_pay_per_post_can_use_woocommerce_subscriptions` | Integrations > Use WooCommerce Subscriptions |

Hooks marked (Premium) run only with an active Premium licence.

## Turning protection on and off

### wcppp_is_active

Whether the plugin runs at all. Return `false` to switch every protection off site-wide. Must-use plugin only.

| Argument | Default |
| --- | --- |
| `bool $active` | `true` |

```php
add_filter( 'wcppp_is_active', '__return_false' );
```

### wc_pay_for_post_the_content_priority

The priority of the plugin's `the_content` filter. Must-use plugin only.

| Argument | Default |
| --- | --- |
| `int $priority` | `99` |

### wc_pay_per_post_is_admin_call

Whether a request counts as wp-admin, in which case the paywall is skipped and the original content returned. Applied by both the post-level engine and the Elementor element checks.

| Argument | Meaning |
| --- | --- |
| `bool $is_admin_call` | `true` in wp-admin; for AJAX requests, only when the user can edit the post |
| `int $post_id` | The post being checked |

Return `false` to evaluate access as on the front end from wp-admin code, as the Access Diagnostic does.

### wc_pay_per_post_force_bypass_paywall

Overrides the access decision at the point of showing content. **The value means different things in two places:**

| Where | Value passed | `true` means |
| --- | --- | --- |
| Whole-post protection (`the_content`) | `! can_user_view_content()` | Show the paywall |
| Inline shortcode (Premium) | `can_user_view_content()` | Show the content |

A callback that should apply to both must check which context it is in. Prefer `wc_pay_for_post_allowed_roles` or `wcppp_is_active` where they fit.

### wc_pay_for_post_allowed_roles (Premium)

User roles that bypass every paywall.

| Argument | Default |
| --- | --- |
| `string[] $roles` | The roles ticked in **Other roles that skip the paywall**; `[]` when none |

List role slugs as values:

```php
add_filter( 'wc_pay_for_post_allowed_roles', function ( $roles ) {
    $roles[] = 'editor';
    return $roles;
} );
```

### wc_pay_for_post_is_frontend_edit

Whether the request is a front-end page builder's edit mode. When `true`, logged-in administrators and authors see the original content instead of the paywall. Defaults to `true` when the URL contains `et_fb` (Divi) or `fl_builder` (Beaver Builder).

| Argument | Meaning |
| --- | --- |
| `bool $is_frontend_edit` | Detected edit mode |

The filter is also passed two fixed extra values, `10` and `1`, which carry no information.

### wc_pay_for_post_show_paywall_in_archives

Affects when the plugin skips filtering content outside the main loop. Default `true`. Its effect is narrow; to change what archives show, use excerpts or the template functions.

### wc_pay_per_post_force_elementor_full_page_protection (Premium)

Return `true` to also apply whole-post protection to Elementor's own content output, for Elementor layouts that bypass `the_content`.

| Argument | Default |
| --- | --- |
| `bool $force` | `false` |

## Paywall content

### wc_pay_for_post_override_paywall_content

The post's own paywall message before the plugin decides whether to use it. A non-empty return replaces the site-wide message for this post.

| Argument | Meaning |
| --- | --- |
| `string $message` | The post's own paywall message (**A message for this post**), or empty |

Also passed two fixed extra values, `10` and `1`. The inline shortcode's `paywall_message` uses this filter internally.

### wc_pay_per_post_paywall_membership_offer (Premium)

The HTML the `{{memberships}}` token places (or that is appended to the message).

| Argument | Meaning |
| --- | --- |
| `string $html` | Paid Memberships Pro "Join" links, "Buy" links to plan products the product list leaves out (in a list with the class `wc-ppp-membership-products`), the members-only notice, or empty |
| `array $offer` | From `get_paywall_offer()`: `product_ids`, `unlisted_product_ids` (plan products that are variations or hidden from the shop), `pmpro_levels`, `has_membership` |

### wc_pay_per_post_modify_excerpt (Premium)

The text for `{{excerpt}}`.

| Argument | Meaning |
| --- | --- |
| `string $excerpt` | The manual excerpt, or the first 55 words of the content |
| `string $manual_excerpt` | The manual excerpt, or empty |
| `string $content` | The post content |

### wc_pay_per_post_inline_shortcode_product_link_name (Premium)

The link text for `{{add_to_cart_link}}` in the inline shortcode.

| Argument | Meaning |
| --- | --- |
| `string $text` | `Add {name} to Cart` |
| `string $name` | The product name |

### wc_pay_per_post_apply_the_content_filter_to_inline_shortcode (Premium)

Whether content inside `[wc-pay-for-post-inline]` is passed through `the_content` filters. Default `true`.

### wc_pay_per_post_paywall_icon (Premium)

The lock shown before protected post titles when **Lock icon on locked titles** is on.

| Argument | Default |
| --- | --- |
| `string $html` | `<span>&#x1F512;</span>` (a padlock) |

## Status box and expiry

### wc_pay_for_post_show_warnings_position (Premium)

Where the views or time remaining box goes. `'top'` or anything else for the bottom. Receives the **Views or time remaining box** setting: `'top'` (the default) or `'bottom'`.

### wc_pay_per_post_enable_javascript_expiration_refresh (Premium)

Whether an expiry-protected post reloads itself when access runs out. Receives the **Reload when access runs out** setting, on by default.

### wc_pay_per_post_override_purchase_date_sql (Premium)

The SQL that reads the paid date of the order expiry and page views are measured from.

| Argument | Meaning |
| --- | --- |
| `string $sql` | The prepared query |
| `int $order_id` | The order found |
| `int $post_id` | The post |
| `array $product_ids` | The post's products |

## Page views (Premium)

### wc_pay_per_post_enable_pageview_fix

Whether the view being served counts against the allowance. Default `true`: an allowance of 3 serves exactly 3 views. Return `false` to allow one extra view.

### wc_pay_per_post_disable_in_the_loop

Return `true` to record views even when the content is not shown inside the main loop. For themes that print content outside the loop. Default `false`.

### wc_pay_per_post_page_view_action_threshold

How many views before the allowance runs out the `wc_pay_per_post_page_view_threshold_reached` action fires. Default `3`.

### Action: wc_pay_per_post_page_view_threshold_reached

Fires when a customer's recorded views of a post reach the allowance minus the threshold. Use it to send a "running low" email.

| Argument | Meaning |
| --- | --- |
| `int $user_id` | The customer |
| `int $post_id` | The post |
| `int $total_page_views` | Their recorded views of the post, all time |
| `int $allowed_page_views` | The post's allowance |

The count is all-time, not only since the latest purchase.

## Content lists

### wc_pay_per_post_args

The `get_posts()` arguments for a `[woocommerce-payperpost]` list, after all attributes are applied.

| Argument | Meaning |
| --- | --- |
| `array $args` | Query arguments |

### wc_pay_per_post_shortcode_posts

The posts a list template is about to render, after access filtering and sorting.

| Argument | Meaning |
| --- | --- |
| `WP_Post[] $posts` | The posts |
| `string $template` | The template requested (`purchased`, `has_access`, `remaining`, `all`, `purchased-datatables`) |
| `array $atts` | The shortcode attributes |

### wc_pay_per_post_enable_last_purchase_date (Premium)

Whether posts in the `purchased` list get a `last_purchase_date` property. Default `true`. Return `false` on large stores to save a query per post.

### wc_pay_per_post_posts_transient_time

How long cached lists last, in seconds. Default `HOUR_IN_SECONDS`. Also used, with a default of `DAY_IN_SECONDS`, for the cached count behind the "Premium protection paused" notice.

### wc_pay_per_post_woocommerce_email_args

The `get_posts()` arguments used to find the posts a product unlocks, for order links and the product unlock list.

## My Account tab (Premium)

| Filter | Argument | Default |
| --- | --- | --- |
| `wc_pay_per_post_my_account_tab_title` | `string $title` | **Tab name**, or `Purchased Content` |
| `wc_pay_per_post_my_account_has_access_title` | `string $title` | **Heading above the content they have access to**, or `Content you have access to` |
| `wc_pay_per_post_my_account_purchased_title` | `string $title` | **Heading above the content they purchased**, or `Content purchased` |
| `wc_pay_per_post_my_account_show_has_access` | `bool $show` | From the setting |
| `wc_pay_per_post_my_account_show_purchased` | `bool $show` | From the setting |

### wc_pay_per_post_my_account_shortcode_atts

The attributes each section passes to `[woocommerce-payperpost]`.

| Argument | Meaning |
| --- | --- |
| `array $atts` | `template`, plus `orderby` and `order` when a sort order other than the default is chosen |
| `string $section` | `has_access` or `purchased` |

### Actions: wc_pay_per_post_before_my_account_shortcode, wc_pay_per_post_after_my_account_shortcode

Fire before and after the tab's lists. No arguments.

## Orders (Premium)

### wc_pay_per_post_show_item_meta

Whether order lines list the content their product unlocks. Receives `false` when **Purchased content links on orders** is **Off**, otherwise `true`. Read for each order line, so it works from a theme.

### wc_pay_per_post_hide_item_meta_in_email

Return `true` to leave those lists out of emails. Receives `true` when **Purchased content links on orders** is **Not in emails**, otherwise `false`. The email then shows the line's normal WooCommerce details.

### wc_pay_per_post_purchased_content_title

The heading above the list, escaped. Receives **Heading above the links**, or `Access Your Purchased Content` when it is empty.

## Editor and admin

### wc_pay_per_post_allowed_roles_for_meta_box

Limits the editor panel and box to certain roles. Receives the roles ticked in **Who can set protection**; `[]`, the default, means everyone who can edit the post. List role slugs as **keys**:

```php
add_filter( 'wc_pay_per_post_allowed_roles_for_meta_box', function () {
    return array( 'administrator' => true, 'editor' => true );
} );
```

### wc_pay_per_post_allowed_roles_for_user_profile_edit (Premium)

Roles that see the page-view table on user profiles. Default `[ 'administrator' ]`, as values.

### wc_pay_per_post_clear_transients_on_post_edit

Whether saving a post clears the plugin's cached lists. Default `true`.

### Product lists in the editor

| Filter | Filters |
| --- | --- |
| `wc_pay_per_post_all_product_args` | `get_posts()` arguments for all products |
| `wc_pay_per_post_virtual_product_args` | `get_posts()` arguments for virtual and downloadable products |
| `wc_pay_per_post_get_all_products` | The resulting product list |
| `wc_pay_per_post_get_virtual_products` | The resulting virtual product list |

### wc_pay_per_post_protected_content_per_page (Premium)

How many posts a page of the Protected Content screen lists. Default `20`, limited to between 1 and 100.

### wc_pay_per_post_diagnostic_order_limit

How many orders the Access Diagnostic lists. Default `50`.

## Integrations

### wc_pay_per_post_can_use_woocommerce_subscriptions (Premium)

Whether the WooCommerce Subscriptions integration is on. Receives `true` when the `WC_Subscriptions_Product` class exists and **Use WooCommerce Subscriptions** is on. Return `false` to treat subscription products as ordinary purchases, for example when another plugin imitates WooCommerce Subscriptions.

### wc_pay_per_post_can_use_paid_memberships_pro (Premium)

Whether the Paid Memberships Pro integration is on. Receives `true` when Paid Memberships Pro is active. Return `false` to turn the integration off. It cannot turn the integration on without an active Premium licence.

### wc_pay_per_post_is_subscription_plan_product (Premium)

Whether a product counts as sold on WooCommerce Subscriptions 9.0+ plans.

| Argument | Meaning |
| --- | --- |
| `bool $is_plan` | Whether the product has plans |
| `int $product_id` | The product |

Return `false` to treat it as an ordinary product (any paid order unlocks). Return `true` to keep the plan rule for a product whose plans were removed after customers subscribed.

## Templates

### wc_pay_for_post_locate_template

The path of a template file.

| Argument | Meaning |
| --- | --- |
| `string $template` | The path found |
| `string $template_name` | The file name |
| `string $template_path` | The theme folder searched (`woocommerce-pay-per-post/`) |

## Other actions

### wcppp_freemius_loaded

Fires once the licensing SDK has loaded.

## Hooks that currently have no effect

These appear in the templates but do not change the output. Override the template instead.

- `wc_pay_per_post_shortcode_purchased_no_posts`
- `wc_pay_per_post_shortcode_has_access_no_posts`
- `wc_pay_per_post_shortcode_remaining_no_posts`
- `wc_pay_per_post_languages_title`
