# Subscriptions and memberships

**(Premium)**

Pay For Post can grant access through recurring products and memberships as well as one-off purchases. This page explains how the three integrations decide, and how they combine with ordinary products.

## The three integrations

| Integration | You choose on the post | Access while |
| --- | --- | --- |
| WooCommerce Subscriptions, classic products | A Simple or Variable subscription product | The customer bought it **and** a subscription to one of the post's products is active or pending cancellation |
| WooCommerce Subscriptions 9.0+ plans | An ordinary product that has plans | Bought once: always. Bought on a plan: while a subscription containing the product is active or pending cancellation |
| WooCommerce Memberships | A membership plan | The customer is an active member of the plan |
| Paid Memberships Pro | A membership level | The customer holds the level |

Each integration is used only while its plugin is active and the Premium licence is active.

## Mixing products, subscriptions and memberships

A post can list any mix. The customer needs **one** way in:

- a qualifying purchase of an ordinary product on the post; or
- an active subscription to a subscription product on the post; or
- an active membership in a plan or level on the post.

For example, a post protected by "Single article" (an ordinary product) and "Monthly pass" (a subscription) opens for anyone who bought the article or holds an active pass.

## Limits do not apply to members and subscribers

Page-view and expiry limits measure from a purchase. Access through an active subscription or membership is not limited by them. Use **Buy once** on posts sold by subscription or membership, and let the subscription or membership decide how long access lasts.

## Subscription statuses

| Status | Access |
| --- | --- |
| Active | Yes |
| Pending cancellation | Yes, until the paid term ends |
| On hold | No |
| Cancelled | No |
| Expired | No |
| Pending | No |

When a subscription comes back to Active (for example, after a failed payment is retried), access returns.

## Subscription plans in detail

WooCommerce Subscriptions 9.0 and later can sell an ordinary product on a plan. Pay For Post looks at each purchase:

- A purchase made **on a plan** counts only while a subscription containing the product is active or pending cancellation. Pay For Post recognises a plan purchase from the order line's plan, or from the order belonging to a subscription that contains the product.
- A **one-time** purchase of the same product counts for good.

If you remove every plan from a product, it becomes an ordinary product: everyone who bought it, lapsed subscribers included, gets access. This matches what happens when a classic subscription product is changed to a simple product.

## Memberships and the paywall

The paywall offers whatever can be bought or joined:

- WooCommerce Memberships plans: the published products that grant the plan appear in `{{product_id}}`.
- Paid Memberships Pro levels: "Join" links for levels open to sign-ups, through `{{memberships}}`.
- Nothing to buy or join: a members-only notice, with a login link for visitors who are not logged in.

## Imitation subscription plugins

Some subscription plugins provide a compatibility layer that makes them look like WooCommerce Subscriptions. Pay For Post detects WooCommerce Subscriptions by its classes, so it cannot tell them apart. If that plugin's subscription records are unreliable, paying customers can be refused. The Access Diagnostic warns about this. Turning **Use WooCommerce Subscriptions** off under **Settings > Integrations** makes those products count as ordinary purchases.

## Without a Premium licence

- Subscription products count as ordinary purchases: any paid order unlocks the post.
- Membership plans and levels are not checked. A post protected only by a plan or level cannot be opened.
