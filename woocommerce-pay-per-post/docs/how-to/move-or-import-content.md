# Move or import protected content
<!-- Short title: Move or import content -->

Pay For Post stores each post's protection in post meta, the custom fields attached to the post. Moving a whole site keeps it. Moving posts between sites, or creating them by import, needs care, because the meta refers to product IDs.

## Moving a whole site

A full migration (database and files together, with a migration plugin or your host's tools) keeps everything: settings, protection, orders and page views. Nothing extra is needed.

After the move:

1. Go to **Settings > Permalinks** and click **Save Changes**, so the My Account tab's address works.
2. Go to **Pay For Post > Need Help > Status and tools** and click **Refresh** beside **Refresh cached lists**.
3. Open a protected post logged out and check you see the paywall.

## Moving posts to another site

The WordPress exporter (**Tools > Export**) and importer copy post meta, including Pay For Post's. But a post's products are stored by **ID**, and product IDs are almost never the same on two sites.

1. Move the products first, and note each product's ID on the new site.
2. Import the posts.
3. Open each protected post and choose the right products again, or update the meta in bulk (see below).
4. Click **Refresh** beside **Refresh cached lists** on **Pay For Post > Need Help > Status and tools**.

If you skip step 3, posts may point at products that do not exist on the new site, or at the wrong products.

## Creating protection by import or code

Each protected post needs this meta:

| Meta key | Value |
| --- | --- |
| `wc_pay_per_post_product_ids` | An **array** of product IDs as strings, for example `["123","456"]`. WordPress stores it serialized. |

For Premium protection types, add the matching keys:

| Protection | Meta keys |
| --- | --- |
| Delay (**Free at first** in the editor) | `wc_pay_per_post_delay_restriction_enable` = `1`, `wc_pay_per_post_delay_restriction` = number, `wc_pay_per_post_delay_restriction_frequency` = `minute`, `hour`, `day`, `week`, `month` or `year` |
| Page view (**Limited views**) | `wc_pay_per_post_page_view_restriction_enable` = `1`, `wc_pay_per_post_page_view_restriction` = number of views; optional time frame: `wc_pay_per_post_page_view_restriction_enable_time_frame` = `1`, `wc_pay_per_post_page_view_restriction_time_frame` = number, `wc_pay_per_post_page_view_restriction_frequency` = unit |
| Expiry (**Time limited**) | `wc_pay_per_post_expire_restriction_enable` = `1`, `wc_pay_per_post_expire_restriction` = number, `wc_pay_per_post_expire_restriction_frequency` = unit |
| Per-post paywall message (**A message for this post**) | `wc_pay_per_post_restricted_content_override` = HTML |
| Status box (**Show customers the views or time they have left**) | `wc_pay_per_post_show_warnings` = `1` |

Set only one `_enable` key to `1`.

Example with WP-CLI:

```
wp post meta update 42 wc_pay_per_post_product_ids '["123","456"]' --format=json
```

Example in PHP:

```php
update_post_meta( 42, 'wc_pay_per_post_product_ids', array( '123', '456' ) );
```

After a bulk import, click **Refresh** beside **Refresh cached lists** on **Pay For Post > Need Help > Status and tools**, because saving through code does not clear them.

## Common pitfalls

- **A comma-separated string instead of an array.** `"123,456"` stored as text is not read correctly. Store an array.
- **Integers instead of strings.** Store `'123'`, not `123`. The lists and the product-page unlock list search for the ID in the stored text, which only matches strings.
- **Posts protected but missing from lists.** Clear transients, and check the post type is chosen in Settings.
- **Elementor element protection** is stored in Elementor's own page data (`_elementor_data`), not in Pay For Post meta. It moves with Elementor's export and import, but its product IDs need the same care.
- **Orders and page views** are what give customers access. Moving posts without orders means nobody on the new site has bought anything yet.
