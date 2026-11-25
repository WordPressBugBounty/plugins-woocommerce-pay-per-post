# Protect a post, page or custom post type
<!-- Short title: Protect a post or page -->

This guide puts any post, page or custom post type behind a paywall with one product, or with several products where any one of them unlocks it.

## Before you start

- Create the WooCommerce product or products first, and publish them. Only published products appear in the product list.
- For a custom post type, tick it under **Pay For Post > Settings > Editor > Post types you can protect** (see below).
- Guest checkout must be off.

## Protect with one product

1. Open the post, page or custom post in the editor.
2. In the block editor, open the **Pay For Post** panel in the editor's right-hand sidebar. In the classic editor, scroll to the **Pay For Post** box below the editor.
3. Under **Products that unlock it**, choose the product. In the block editor, type part of its name and choose it from the list; it shows as a token. In the classic box, choose it from the list.
4. Save or update the post.

**Expected result:** the panel or box shows **Protected**. Visitors who have not bought the product see the paywall in place of the content. A logged-in customer with a Processing or Completed order for the product sees the content.

## Protect with several products

Choose more than one product in the same list. A customer who has bought **any one** of them gets access. They do not need all of them.

Use this to:

- offer a single article and a bundle that both unlock the post;
- sell monthly and yearly passes that unlock the same content;
- (Premium) list each variation of a variable product separately.

The paywall's default message lists every product so the visitor can choose.

## Protect a custom post type

1. Go to **Pay For Post > Settings**.
2. Open the **Editor** tab. Under **Post types you can protect**, tick the post type. Every public post type on your site is listed.
3. Click **Save changes**.
4. Edit a post of that type. The Pay For Post panel or box now appears.

For the block editor panel to save its settings, the post type must support custom fields. If it does not, wp-admin shows the error "In order to utilize the Block Editor and Pay for Post on the ... custom post type(s), you must enable custom field support". Either add `custom-fields` support to the post type (ask your developer, or tick the option in the plugin that registers the post type), or turn off **Panel in the block editor sidebar** in **Settings > Editor** and use the classic box.

## Remove protection

Remove every product under **Products that unlock it** (in the block editor, click the x on each token) and save. The panel or box shows **Not protected**, and the post is public again.

## Common pitfalls

- **Your product is not in the list.** The list shows published products only. If **Only offer virtual and downloadable products** is on in **Settings > Editor**, ordinary physical products are hidden too. In the free version, variations are not listed; protect with the parent product or upgrade.
- **Only the content is protected.** The title, featured image, excerpt, custom fields and comments are not part of the content. Write excerpts that give nothing away, and see **Who can comment on protected posts** in **Settings > Paywall**.
- **The post shows in full in your theme.** Your theme or page builder may not use `the_content`. See the Template functions reference.
- **Removing a post type from Settings does not unprotect its posts.** It hides the panel. Posts of that type that already have products stay protected, and they drop out of `[woocommerce-payperpost]` lists.
- **Protection type.** New posts use **Buy once**: buy once, read forever. For time limits or view limits (Premium), see the guides for each protection type.
