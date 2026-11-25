# Template functions reference

PHP functions for themes and plugins. All are static methods on the `Woocommerce_Pay_Per_Post_Helper` class, available once plugins have loaded.

Guard calls so your theme keeps working if the plugin is deactivated:

```php
if ( class_exists( 'Woocommerce_Pay_Per_Post_Helper' ) ) {
    // ...
}
```

## Access

### has_access( $post_id = null, $track_page_view = true )

Whether the current visitor may see a post's content. Runs every check the paywall runs: protection, delay, administrator setting, roles, purchase, protection type limits, subscriptions and memberships.

| Parameter | Type | Default | Meaning |
| --- | --- | --- | --- |
| `$post_id` | int or null | current post | The post to check. |
| `$track_page_view` | bool | `true` | On a page-view-protected post, record this as a view when access is granted on a single post's main loop. Pass `false` when you only want to know the answer. |

Returns `bool`.

```php
if ( Woocommerce_Pay_Per_Post_Helper::has_access( get_the_ID(), false ) ) {
    echo '<a class="download" href="...">Download the PDF</a>';
}
```

### has_purchased( $post_id = null, $track_page_view = true )

Whether the current visitor has a qualifying purchase of one of the post's products. Only the purchase: ignores delay, expiry, page views, administrator access, roles and memberships. Use it to tell "bought but expired" apart from "never bought".

Returns `bool`.

### get_no_access_content()

The paywall HTML for the current post: the post's own message or the site-wide one, with tokens replaced and shortcodes run, wrapped in `div.wc_ppp_paywall`.

Returns `string`.

```php
if ( ! Woocommerce_Pay_Per_Post_Helper::has_access() ) {
    echo Woocommerce_Pay_Per_Post_Helper::get_no_access_content();
}
```

### get_last_purchase_date( $post_id, $user_id = null ) (Premium)

The paid date of the user's most recent Processing or Completed order, on their account, that contains one of the post's products. Expiry and page views are measured from this date.

| Parameter | Default |
| --- | --- |
| `$post_id` | required |
| `$user_id` | current user |

Returns a Carbon date object in UTC. Returns a date in 1982 when there is no such order or the order has no paid date. Returns `null` without Premium.

### customer_has_purchased_product( $email, $user_id, $product_id )

Whether a user bought a product: a paid order on their account or with their billing email. A wrapper for WooCommerce's `wc_customer_bought_product()` that also works around a fault in WooCommerce 9.6.0 and 9.6.1. Returns `false` when `$user_id` is empty.

Returns `bool`.

## Protection data

### is_protected( $post_id = null ) and get_post_protection( $post_id = null )

The same function under two names. Returns an array:

| Key | Type | Meaning |
| --- | --- | --- |
| `is_protected` | bool | Whether the post has products set at post level, or (Premium) protected Elementor elements |
| `protection_type` | string | `standard`, `delay`, `page-view` or `expire`; empty when not protected. Without Premium, always `standard`. A post with protected Elementor elements reports `standard`. |
| `product_ids` | array | IDs chosen for the post: products, variations, and (Premium) plan or level IDs |
| `section_product_ids` | array | Product IDs from Elementor element protection on the post |
| `origin_type` | string | `post`, or `elementor` when the protection comes from Elementor elements |
| `post_id` | int | The post the answer is for |

```php
$protection = Woocommerce_Pay_Per_Post_Helper::is_protected( 42 );
if ( $protection['is_protected'] && 'expire' === $protection['protection_type'] ) {
    // ...
}
```

### get_paywall_offer( $ids )

What a paywall for these protection IDs can sell. Use this, not the raw IDs, when you build your own paywall: protection IDs can include membership plan and level IDs.

Returns an array:

| Key | Meaning |
| --- | --- |
| `product_ids` | Product IDs a visitor can buy, including the products that grant chosen WooCommerce Memberships plans (Premium) |
| `pmpro_levels` | Paid Memberships Pro level objects open to sign-ups (Premium) |
| `has_membership` | `true` when any ID is a plan or level |

### get_posts_associated_with_product_id( $product_id )

The published posts, of enabled post types, protected by a product. Returns an array of `['post_id', 'post_title', 'post_url']` arrays.

### get_protected_posts( $args = null, $transient = 'posts', $bypass_transient = false )

All published protected posts of enabled post types, as WP_Post objects. Cached in a transient named `wc_pay_per_post_{$transient}` for an hour (filter `wc_pay_per_post_posts_transient_time`). Pass your own `get_posts()` arguments in `$args` and a unique `$transient` name, or `true` for `$bypass_transient` to skip the cache.

### get_products()

The products offered in the editor's product list: all published products (Premium adds variations), or only virtual and downloadable ones when that setting is on. Each item is `['ID', 'post_title']`.

### replace_tokens( $content, $product_ids, $unfiltered_content = null )

Replaces the paywall tokens in `$content` for these protection IDs. `$unfiltered_content` is used for `{{excerpt}}`. Returns `string`.

## Request

### is_wp_admin_request( $post_id )

Whether the request counts as wp-admin for protection: `true` in wp-admin, except AJAX requests, which count only for a user who can edit the post. Protection is not applied to such requests.

## The access engine

`\PRAMADILLO\Woocommerce_Pay_Per_Post_Restrict_Content` is the class behind `has_access()`. Build it with the post ID:

```php
$restrict = new \PRAMADILLO\Woocommerce_Pay_Per_Post_Restrict_Content( $post_id, false );
$restrict->can_user_view_content(); // bool, the same as has_access()
$restrict->show_paywall( $content ); // the paywall HTML
$restrict->is_expired( $post_id );   // bool, true when expiry access has run out (Premium)
```

The second constructor argument is whether to record a page view.

## Body classes (Premium)

On a protected post, `<body>` gets:

| Class | When |
| --- | --- |
| `ppp-protected` | Always |
| `ppp-has-access` | The visitor can see the content |
| `ppp-has-no-access` | The visitor sees the paywall |

On archive pages the classes describe the first post in the list.

## Deprecated

| Function | Use instead |
| --- | --- |
| `Woocommerce_PayPerPost::has_access()` | `Woocommerce_Pay_Per_Post_Helper::has_access()` |
| `Woocommerce_PayPerPost::get_no_access_content()` | `Woocommerce_Pay_Per_Post_Helper::get_no_access_content()` |
| `Woocommerce_Pay_Per_Post_Helper::is_admin_request()` (deprecated in 3.3.0) | `Woocommerce_Pay_Per_Post_Helper::is_wp_admin_request( $post_id )` |

Deprecated functions still work and log a notice when `WP_DEBUG` is on.
