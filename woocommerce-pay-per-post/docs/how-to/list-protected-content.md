# List protected content with a shortcode
<!-- Short title: List protected content -->

The `[woocommerce-payperpost]` shortcode lists protected posts on any page. You choose a template: what the customer bought, what they can read, what they have not bought yet, or everything. `[wc-pay-for-post]` is the same shortcode under a shorter name.

## Steps

1. Create or edit a page, for example "My Library".
2. Add a **Shortcode** block and enter one of the examples below.
3. Publish the page.

**Expected result:** a bulleted list of post titles linking to the posts.

## Templates

| Shortcode | Lists | Logged-out visitors see |
| --- | --- | --- |
| `[woocommerce-payperpost template="purchased"]` | Posts whose product the customer bought. This is the default when `template` is left out. | Nothing |
| `[woocommerce-payperpost template="has_access"]` | Posts the customer can open right now. | Nothing |
| `[woocommerce-payperpost template="remaining"]` | Protected posts the customer cannot open yet. | Nothing |
| `[woocommerce-payperpost template="all"]` | Every protected post, each marked with a `locked` or `unlocked` CSS class. | The full list, all locked |
| `[woocommerce-payperpost template="purchased-datatables"]` | (Premium) Purchased posts in a searchable, sortable table with Name, Category and Publish Date columns. | Nothing |

When a logged-in customer has nothing to show, `purchased` says "You have not purchased any protected posts.", `has_access` says "You do not have access to any protected posts." and `remaining` says "Sorry there are no posts remaining to be purchased." To change these texts, override the template.

"Purchased" and "has access" differ. A customer whose 30-day access has expired still appears to have purchased the post, but no longer has access. A member who never bought anything has access but has purchased nothing.

## Filter and sort the list (Premium)

| Attribute | Values | Example |
| --- | --- | --- |
| `orderby` | `date` (default), `title`, `ID`, `menu_order` | `orderby="title"` |
| `order` | `DESC` (default) or `ASC` | `order="ASC"` |
| `category_slug` | A post category slug | `category_slug="recipes"` |
| `tag` | A post tag slug | `tag="vegan"` |
| `product_ids` | Product IDs, separated by commas. Lists only posts protected by these products. | `product_ids="123,456"` |

Examples:

```
[woocommerce-payperpost template="has_access" orderby="title" order="ASC"]
[woocommerce-payperpost template="all" category_slug="recipes"]
[woocommerce-payperpost template="purchased" product_ids="123"]
```

Sorting by title is natural: "Lesson 2" comes before "Lesson 10", ignoring case and accents.

## Style the lists

Each template wraps its list in a container you can style:

| Template | Container class |
| --- | --- |
| purchased | `.wc-ppp-purchased-container` |
| has_access | `.wc-ppp-purchased-container` (the same class as purchased) |
| remaining | `.wc-ppp-remaining-container` |
| all | `.wc-ppp-posts-container`, with `li.locked` and `li.unlocked` |

For a different layout, override the template in your theme. See "Override the plugin's templates".

## Common pitfalls

- **New posts are missing.** The list of protected posts is cached for up to an hour. Saving a post clears it. After imports, go to **Pay For Post > Need Help > Status and tools** and click **Refresh** beside **Refresh cached lists**. While testing, add `bypass_transients="true"` to the shortcode, and remove it afterwards.
- **A post type is missing.** Only post types ticked under **Settings > Editor > Post types you can protect** are listed.
- **Drafts and private posts** are never listed.
- **Free version.** `orderby`, `order`, `category_slug`, `tag` and `product_ids` are ignored without Premium; the list is sorted newest first.
- **Page caches.** These lists differ per customer. Do not cache the page for logged-in users.
- **Large sites.** The `all`, `has_access` and `remaining` templates check access for every protected post on each page load. On sites with hundreds of protected posts, use `category_slug`, `tag` or `product_ids` to keep lists short.
