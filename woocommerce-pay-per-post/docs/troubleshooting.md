# Troubleshooting

Find your symptom below. Start with the Access Diagnostic on **Pay For Post > Need Help > Check a customer** whenever a particular customer is involved: it answers most questions in one step.

## A customer paid but sees the paywall

Run the Access Diagnostic with their account email and the page URL. Then check, in order:

1. **The order status.** It must be Processing or Completed. On hold, Pending payment and Failed do not count. Bank transfer and cheque orders stay On hold until you confirm payment.
2. **The order's account.** The order must belong to the customer's account. If they checked out as a guest or with another account, edit the order and set **Customer** to their account. Turn guest checkout off to prevent it.
3. **The product.** The order must contain a product chosen on the post. Customers often buy a similar product. The diagnostic lists any other products they bought beside the ones that unlock the page.
4. **Variations (Premium).** If the post lists one variation, other variations of the same product do not count.
5. **Expiry or page views (Premium).** Their access may have ended. The diagnostic says so.
6. **Subscriptions (Premium).** For a subscription product, the subscription must be Active or Pending cancellation.
7. **Caching.** If the diagnostic says **Has access**, a page cache is serving them an old copy. See below.

## Logged-out visitors can see the full post

1. **Check the post has products.** Open it in the editor and look at the Pay For Post panel or box.
2. **Check for a page cache.** Clear your caching plugin's, host's and CDN's caches, then test again in a private window. Make sure logged-in users are never served cached pages, so a cached copy made by someone with access cannot reach visitors.
3. **Check your theme.** Some themes and builders print content without WordPress's `the_content` filter. Switch briefly to a default theme such as Twenty Twenty-Five on a staging site. If the paywall appears, see "Protect content in themes that do not use the_content".
4. **Check for code that turns protection off.** The Access Diagnostic warns if protection is switched off site-wide.
5. **Elementor element-only pages.** A page with element protection but no products in the Pay For Post panel has no whole-page paywall. Only the protected elements are hidden.

## I can see the full post but customers cannot, or the reverse

Administrators see the paywall unless **Administrators see everything** is on in **Settings > Access**. Test as a customer in a private window. See "What administrators and editors see".

## Membership or subscription customers are refused (Premium)

- **WooCommerce Memberships or Paid Memberships Pro:** check the customer's membership is active in that plugin, and that the plan or level is chosen on the post.
- **WooCommerce Subscriptions:** check the subscription is Active or Pending cancellation. For a classic subscription product, there must also be a paid order for it.
- **Another subscription plugin:** the diagnostic warns if a plugin is imitating WooCommerce Subscriptions. See "Subscriptions and memberships".
- **Licence:** subscription and membership checks need an active Premium licence.

## A membership plan or level disappeared from a post

Before 3.4.0 the block editor panel listed products only, so any change made in it removed a post's plans and levels. From 3.4.0 the panel lists them as the classic meta box does. A plan or level removed that way is not restored: choose it again in either place.

## Page views run out too fast (Premium)

- Every reload counts as a view.
- Each `[wc-pay-for-post-inline]` region on the post counts as a view.
- Check the user's views on their profile page in wp-admin, and delete any you want to give back.

## Expiry ends too soon (Premium)

- Expiry measures from the **most recent** qualifying order's paid date.
- An order with no paid date counts as expired. This happens with orders marked Completed by hand.
- An order matched only by billing email cannot supply the date. Attach it to the customer's account.
- Short expiries (minutes or hours) can be offset by your time zone on some sites. Test them first.

## A post is missing from a content list or the My Account tab

1. Check the post is published.
2. Check its post type is ticked under **Settings > Editor > Post types you can protect**.
3. Check it has products.
4. Click **Refresh** beside **Refresh cached lists** on **Pay For Post > Need Help > Status and tools**. If you use a persistent object cache, flush it too.
5. For `purchased`, check the customer actually bought one of its products. Posts opened by membership appear only under "Content you have access to".

## The My Account tab shows "Page not found" (Premium)

Go to **Settings > Permalinks** and click **Save Changes**.

## The block editor panel is missing

- Check the post type is ticked under **Settings > Editor > Post types you can protect**.
- Check **Panel in the block editor sidebar** is on in **Settings > Editor**. If it is off, look for the box below the editor.
- For custom post types, look for a notice about custom field support.
- A developer may have limited the panel to certain roles.

## A product is missing from the product list

- Only published products are listed.
- **Only offer virtual and downloadable products** (Settings > Editor) hides physical products.
- Variations need Premium.

## The paywall shows a shortcode as text, or the wrong products

- Check the shortcode's spelling and quotes.
- Use `{{product_id}}` in `[products ids='{{product_id}}']`, not fixed IDs.
- In an Elementor element's message, shortcodes may not run; use a link instead.

## The site is slow on pages with long content lists

- Use `category_slug`, `tag` or `product_ids` (Premium) to keep lists short.
- On product pages with large bundles, use `[wc-pay-for-post-product-unlocks show_status_classes="false"]`.
- On large stores, a developer can turn off `last_purchase_date` with the `wc_pay_per_post_enable_last_purchase_date` filter.

## Collecting information for support

1. Run the Access Diagnostic and click **Copy for a support reply**.
2. Copy your system report from **Tools > Site Health > Info > Copy site info to clipboard**.
3. If asked for a log: turn on **Debug log** in **Settings > Advanced**, open the problem page with `?wc-ppp-debug` added to its address (for example `https://example.com/spring-planting-guide/?wc-ppp-debug`), then open the log under **Pay For Post > Need Help > Debug log**. Afterwards, click **Turn off and delete** there to turn debugging off and delete the log in one step.
