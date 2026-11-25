# Show purchased content links on orders and emails
<!-- Short title: Links on orders and emails -->

**(Premium)**

When a customer buys a product that unlocks content, each line of their order can list links to the posts that product unlocks. They see the links on the order confirmation page, in order emails, and when they view the order in My Account.

This is on by default.

## What customers see

Under each product that unlocks content:

> **Access Your Purchased Content**
> - Spring Planting Guide
> - Summer Pruning Guide

Each title links to the post and opens in a new tab.

Products that unlock nothing show their normal order details.

## Choose where the links appear

1. Go to **Pay For Post > Settings > Customers**.
2. Under **Purchased content links on orders**, choose:
   - **Everywhere**: on the order pages and in emails.
   - **Not in emails**: on the order confirmation page and in My Account only. Useful when one product unlocks a long list, such as a subscription to everything on the site.
   - **Off**: orders show only WooCommerce's own item details.
3. Click **Save changes**.

## Change the heading

Fill in **Heading above the links** on the same tab and save. Left empty, it reads "Access Your Purchased Content", translated into the site's language.

For other changes, override the `woocommerce-order-receipt.php` template. See "Override the plugin's templates".

## For developers

The `wc_pay_per_post_show_item_meta`, `wc_pay_per_post_hide_item_meta_in_email` and `wc_pay_per_post_purchased_content_title` filters receive these settings and override them. They work from a theme's `functions.php`. See the Hooks reference.

## Common pitfalls

- **Other item details are missing.** On lines whose product unlocks content, the content links replace WooCommerce's usual item details, such as variation attributes or add-on choices. Where the links are left out, in emails with **Not in emails** or everywhere with **Off**, those details come back.
- **The links appear before payment.** The links are shown on the order whatever its status. Customers can only open the posts once the order is Processing or Completed.
- **Polylang.** With Polylang support on, the links use the Polylang version of the template.
