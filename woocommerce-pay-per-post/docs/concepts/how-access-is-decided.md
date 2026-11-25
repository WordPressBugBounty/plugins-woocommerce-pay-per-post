# How access is decided

Each time a protected post's content is shown, Pay For Post decides whether this visitor sees the content or the paywall. This page explains the order of the checks, so you can predict the answer for any visitor.

## The short version

A visitor sees the content if **any** of these is true:

1. The post is not protected.
2. The post is in its free delay window (Premium).
3. The visitor is an administrator and **Administrators see everything** is on.
4. The visitor has a role allowed to bypass the paywall by a developer filter (Premium).
5. The visitor is logged in, has a qualifying purchase of one of the post's products, and is within the post's page-view or expiry limits.
6. The visitor holds an active subscription or membership that the post accepts (Premium).

Otherwise they see the paywall.

## The checks in detail

### 1. Is the post protected?

A post is protected when at least one product, plan or level is chosen for it. The protection type comes from the post's settings: standard, delay, page view or expiry. Without an active Premium licence, every protected post is treated as standard.

### 2. Is the paywall active yet? (Premium, delay posts)

On a delay post, the paywall does not apply until the delay has passed since the publish date. During the window, everyone sees the content, logged in or not.

### 3. Administrators

Administrators see the paywall like anyone else, unless **Administrators see everything** is on in **Settings > Access**. Then they see everything. On multisite, this applies to super admins only.

### 4. Allowed roles (Premium)

Roles ticked in **Other roles that skip the paywall** under **Settings > Access** always see content (Premium). No roles are ticked by default.

### 5. Purchase

The visitor must be logged in. Access is tied to a WordPress account, never to a browser or a guest order.

They must have a qualifying purchase of one of the post's products. See "What counts as a purchase".

Then the protection type applies:

| Type | Extra condition |
| --- | --- |
| Standard | None |
| Delay | None after the window; the purchase is what counts |
| Page view | Views since the latest qualifying order are within the allowance, and, if set, the time window since that order has not closed |
| Expiry | The time since the latest qualifying order's paid date is less than the access length |

### 6. Subscriptions and memberships (Premium)

If the post accepts a subscription product, a WooCommerce Memberships plan or a Paid Memberships Pro level, the visitor gets access by holding an active subscription or membership. See "Subscriptions and memberships".

Page-view and expiry limits do not apply to access granted this way.

## When several products are chosen

Any one is enough. A customer does not need to buy every product on the post.

## What the decision covers

The decision applies to the post's **content**: the text and blocks in the editor. Other parts of the post are not protected:

- the title;
- the featured image;
- a manual excerpt;
- custom fields and their output;
- comments, which have their own settings.

See "Where protection applies" for listings, search, feeds and the REST API.

## The same rules everywhere

The paywall, the content lists, the lock icons, the body classes, the product unlock list and the Access Diagnostic all use this same decision. If the Access Diagnostic says a customer has access, the paywall agrees.
