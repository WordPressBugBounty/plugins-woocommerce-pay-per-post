# Sell access with WooCommerce Subscriptions
<!-- Short title: WooCommerce Subscriptions -->

**(Premium)**

With WooCommerce Subscriptions active, a protected post stays open only while the customer's subscription is active. When the subscription is cancelled, expires or goes on hold, the paywall comes back.

This works with both kinds of subscription product:

- **classic subscription products**: the "Simple subscription" and "Variable subscription" product types;
- **subscription plans** (WooCommerce Subscriptions 9.0 and later): an ordinary product that can be bought once or on a plan.

## Protect a post with a subscription product

1. Create the subscription product, or add a plan to an ordinary product, and publish it.
2. Open the post in the editor.
3. Choose the product under **Products that unlock it**:
   - In the classic box, subscription products are grouped under **Subscription Products**.
   - In the block editor panel, type its name and choose it like any product.
4. Leave **Protection** on **Buy once**.
5. Save the post.

**Expected result:** a customer with an active subscription to the product sees the post. When the subscription ends, they see the paywall again.

## Which subscriptions count

| Subscription status | Access |
| --- | --- |
| Active | Yes |
| Pending cancellation (cancelled, but paid until the end of the term) | Yes |
| On hold, cancelled, expired, pending | No |

## Subscription plans (WooCommerce Subscriptions 9.0+)

A product with plans can be bought once or on a plan. Pay For Post decides per purchase:

- **Bought once:** access is permanent, like any purchase.
- **Bought on a plan:** access lasts while a subscription containing the product is active or pending cancellation.
- **Both:** the one-time purchase keeps access after the subscription ends.

If you later remove all plans from the product, it becomes an ordinary product again. Everyone who ever bought it, including lapsed subscribers, gets access.

## Mixing subscriptions and one-off products

A post can list a subscription product and an ordinary product. A customer who has an active subscription, **or** who bought the ordinary product, gets access. Use this to sell a single article alongside a monthly pass.

## Common pitfalls

- **Use Buy once.** Subscribers are not limited by delay, page views or expiry, so those settings do nothing for them. The subscription itself controls how long access lasts.
- **A classic subscription product needs both a paid order and an active subscription.** If a customer's subscription was created by hand without a paid order, they will not get access. Use the Access Diagnostic to see which part is missing.
- **Another plugin pretends to be WooCommerce Subscriptions.** Some subscription plugins load a compatibility layer that looks like WooCommerce Subscriptions. The Access Diagnostic warns when it finds one. If its subscription records are unreliable, paying customers can be locked out. Turn **Use WooCommerce Subscriptions** off under **Pay For Post > Settings > Integrations** so those products count as ordinary purchases.
- **Licence lapsed.** Without an active Premium licence, subscription products count as ordinary purchases: any paid order unlocks the post, whatever the subscription's status.
