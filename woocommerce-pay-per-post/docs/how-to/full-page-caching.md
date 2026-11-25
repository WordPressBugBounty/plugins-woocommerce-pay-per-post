# Use Pay For Post with full-page caching
<!-- Short title: Full-page caching -->

A full-page cache saves one copy of a page and serves it to everyone. Pay For Post shows different content to different people, so a cache that ignores who is logged in will serve the wrong version: buyers see the paywall, or the paywall goes missing.

This guide sets up caching so protected content works.

## The rule

**Never serve a cached page to a logged-in user.**

Everyone who can have access is logged in, because access is tied to an account. Visitors who are not logged in always see the paywall, so a cached copy of the paywall is safe for them.

## Steps

1. Open your caching plugin's or host's cache settings.
2. Turn on the option that skips the cache for logged-in users. Common names are "Don't cache pages for logged-in users", "Cache logged-in users: off" or "Bypass cache for logged-in users".
3. Make sure the WooCommerce pages (cart, checkout, My Account) are excluded. Most caches do this already.
4. Clear the whole cache.
5. Test:
   - Open a protected post in a private window, logged out. You should see the paywall.
   - Log in as a customer who bought the product and open it again. You should see the full post.
   - Log in as a customer who did not buy it. You should see the paywall.

## Pages to keep out of the cache entirely

Some pages vary per customer even when shown to logged-out visitors, or must update straight away. Exclude them from the cache:

- the page with `[woocommerce-payperpost]` lists, if any list is visible to logged-out visitors;
- product pages using `[wc-pay-for-post-product-unlocks]` with status classes, if you style locked and unlocked items;
- delay-protected posts, if you need the paywall to appear exactly on time (a cached copy keeps showing the free version until it expires).

## Server and CDN caches

Caches in front of WordPress (Varnish, Nginx FastCGI cache, Cloudflare APO and similar) need the same rule. They usually detect logged-in users by the `wordpress_logged_in_` cookie and skip the cache when it is present. Ask your host to confirm.

Pay For Post does not send no-cache headers, so the cache's own rules decide what is stored.

## Object caches

A persistent object cache (Redis, Memcached) is fine. One thing to know: **Refresh cached lists** (**Pay For Post > Need Help > Status and tools**) clears the plugin's cached lists from the database only. With a persistent object cache, flush the object cache as well, or wait for the lists to expire (an hour by default).

## Common pitfalls

- **A buyer still sees the paywall after paying.** Their browser or the cache may hold an old copy. Ask them to reload, and check that logged-in users bypass the cache.
- **Logged-out visitors see the full post.** A cached copy was made while someone with access was logged in, or the cache shares pages across users. Clear the cache and turn on the logged-in bypass.
- **Page views are not counted.** A cached page never reaches WordPress, so the view is not recorded.
- **Expiry countdowns do not reload.** The automatic reload when access expires only runs on uncached pages.
