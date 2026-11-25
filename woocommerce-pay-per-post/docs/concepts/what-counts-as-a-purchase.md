# What counts as a purchase

A customer gets access to a protected post by buying one of its products. This page explains exactly which orders count.

## The rule

An order counts when all of these are true:

- it contains one of the post's products (or the chosen variation);
- its status is **Processing** or **Completed**;
- it belongs to the customer's WordPress account, or its billing email matches the account's email.

The customer must be logged in when they visit the post.

## Order statuses

| Status | Counts? |
| --- | --- |
| Processing | Yes |
| Completed | Yes |
| On hold | No |
| Pending payment | No |
| Failed | No |
| Cancelled | No |
| Refunded | No |
| Draft (checkout not finished) | No |

Custom order statuses added by other plugins count only if that plugin marks them as paid in WooCommerce (the `woocommerce_order_is_paid_statuses` filter). Even then, expiry and page views measure only from Processing and Completed orders.

Changing an order's status changes access straight away. Refunding or cancelling an order removes access.

Some payment methods, such as bank transfer or cheque, leave orders On hold until you confirm payment. Customers cannot read the post until you move the order to Processing or Completed.

## Accounts and guest orders

Access needs a WordPress account. Turn guest checkout off in **WooCommerce > Settings > Accounts & Privacy**, so every buyer has one.

If a customer checked out as a guest before you turned it off:

1. Ask them to create an account with the same email address, or create one for them.
2. Edit the order and set its **Customer** to that account.

An order matched only by billing email unlocks standard posts, but expiry and page-view posts need the order on the customer's account, because the date they measure from is read from the account's orders.

## Variations (Premium)

You can protect a post with a single variation, such as "Annual" of a "Membership pass" product. Then only orders for that variation count. Choose the parent product as well if any variation should count.

## Which order sets the date

Expiry and page views are measured from the customer's **most recent** Processing or Completed order, on their account, that contains one of the post's products. The date used is the order's **paid date**.

- A newer order restarts the clock, even if an older order is still within its time.
- An order with no paid date (for example, one marked Completed by hand without payment) is treated as having no date, and access is treated as expired.

## Subscriptions (Premium)

With WooCommerce Subscriptions, a paid order is not enough for a subscription product: the subscription itself must be active. See "Subscriptions and memberships".

## Checking a customer

The Access Diagnostic on **Pay For Post > Need Help > Check a customer** lists the customer's orders, which ones count, and why the others do not.
