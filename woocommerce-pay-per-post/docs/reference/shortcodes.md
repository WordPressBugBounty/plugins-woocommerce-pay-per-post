# Shortcodes reference

Every shortcode Pay For Post provides, with all attributes and their defaults.

| Shortcode | Purpose | Version |
| --- | --- | --- |
| `[woocommerce-payperpost]` | List protected posts | Free and Premium (some attributes Premium) |
| `[wc-pay-for-post]` | Same as `[woocommerce-payperpost]` | Free and Premium |
| `[wc-pay-for-post-inline]...[/wc-pay-for-post-inline]` | Protect part of a post | Premium |
| `[wc-pay-for-post-product-unlocks]` | List what a product unlocks, on its product page | Premium |
| `[wc-pay-for-post-status]` | Views or time remaining | Premium |

## [woocommerce-payperpost]

Lists published, protected posts of the post types chosen in Settings, each as a link.

| Attribute | Values | Default | Version |
| --- | --- | --- | --- |
| `template` | `purchased`, `has_access`, `remaining`, `all`, `purchased-datatables` | `purchased` | `purchased-datatables` needs Premium to show anything |
| `orderby` | `date`, `title`, `ID`, `menu_order` | `date` | Premium |
| `order` | `DESC`, `ASC` (either case) | `DESC` | Premium |
| `category_slug` | A post category slug | none | Premium |
| `tag` | A post tag slug | none | Premium |
| `product_ids` | Product IDs separated by commas | none | Premium |
| `bypass_transients` | `true` or `false` | `false` | Free and Premium |

Unknown values fall back to the default.

### Templates

| Template | Lists | Check used | Logged-out visitors |
| --- | --- | --- | --- |
| `purchased` | Posts whose product the visitor bought | Purchase only, ignoring expiry, page views and memberships | Nothing is output |
| `has_access` | Posts the visitor can open now | Full access check, without counting a page view | Nothing is output |
| `remaining` | Posts the visitor cannot open now | Full access check | Nothing is output |
| `all` | Every protected post, `li.locked` or `li.unlocked` | Full access check per post | Full list, all locked |
| `purchased-datatables` | Purchased posts as a searchable table: Name, Category, Publish Date | Purchase only | Nothing is output |

`purchased-datatables` loads the DataTables 1.13.1 script and stylesheet from `cdn.datatables.net`. The Category column shows the product categories of the post's products.

### Notes

- `orderby="title"` sorts naturally ("Lesson 2" before "Lesson 10"), ignoring case and accents. The sort happens after the query, so a developer's `wc_pay_per_post_args` callback that changes the order still wins.
- `product_ids` matches posts whose stored products include the ID. With Elementor, it also matches pages whose protected elements use it.
- The query results are cached for an hour (filter `wc_pay_per_post_posts_transient_time`). Access is still checked per visitor on every load. `bypass_transients="true"` skips the cache; use it only for testing.
- With Polylang support on (Premium), `purchased` and `has_access` use the Polylang versions of their templates.

Examples:

```
[woocommerce-payperpost]
[woocommerce-payperpost template="has_access" orderby="title" order="ASC"]
[woocommerce-payperpost template="all" category_slug="recipes" tag="vegan"]
[wc-pay-for-post template="remaining" product_ids="123,456"]
```

## [wc-pay-for-post-inline] (Premium)

Wraps part of a post's content. Visitors without access to the post see the paywall in place of the wrapped part.

| Attribute | Values | Default |
| --- | --- | --- |
| `paywall_message` | Text or HTML, with tokens | The post's own message, or the site-wide message |

Extra tokens inside `paywall_message`:

| Token | Replaced with |
| --- | --- |
| `{{products}}` | `[products]` grid of the post's products |
| `{{add_to_cart}}` | `[add_to_cart]` button per product, comma separated |
| `{{add_to_cart_link}}` | "Add {name} to Cart" link per product, comma separated |

The normal paywall tokens also work.

Behaviour:

- Uses the post's own products and protection type. The post must have products.
- A post containing the shortcode does not also get a whole-post paywall.
- The wrapped content is passed through WordPress's content filters (filter `wc_pay_per_post_apply_the_content_filter_to_inline_shortcode`).
- Shows its content in the Elementor editor and preview.
- On page-view posts, each region shown counts as a view.

```
[wc-pay-for-post-inline paywall_message="Members read on: {{add_to_cart_link}}"]
Paid content here.
[/wc-pay-for-post-inline]
```

## [wc-pay-for-post-product-unlocks] (Premium)

Lists the posts the current product unlocks. Works only on a WooCommerce product page.

| Attribute | Values | Default |
| --- | --- | --- |
| `show_status_classes` | `true` or `false` | `true` |

Output: `<ul class="wc-pay-for-post-unlocks-posts">` with an `<li>` per post. With status classes, each `<li>` has `locked` or `unlocked`. Set `show_status_classes="false"` on products that unlock many posts, to skip the access check per post.

For a variable product, the list includes posts protected by the parent product. Posts protected only by individual variations may be missing.

## [wc-pay-for-post-status] (Premium)

Prints the box that shows a customer their page views or time remaining. Pay For Post uses it itself when **Show customers the views or time they have left** (in the block editor, **Show the views left** or **Show the time left**) is on for a post. You rarely need to place it yourself; turn on that option instead.

| Attribute | Values | Default |
| --- | --- | --- |
| `template` | `pageview-status`, `expiration-status` | `pageview-status` |

It shows the figures from the most recent access check made in the request, so it only gives a correct result after the post's content has been checked.
