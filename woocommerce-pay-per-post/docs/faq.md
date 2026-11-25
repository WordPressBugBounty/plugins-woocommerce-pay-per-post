# Frequently asked questions

## Getting started

### Do customers need an account?

Yes. Access is tied to a WordPress account. Turn guest checkout off in WooCommerce so every buyer has one.

### Which versions of WordPress, WooCommerce and PHP do I need?

WordPress 6.6 or later, WooCommerce 6.9 or later and PHP 7.4 or later.

### Does it work with any payment gateway?

Yes. Pay For Post looks at the order status, not the gateway. Access starts when the order is Processing or Completed.

### Can I protect pages and custom post types, not just posts?

Yes. Posts and pages are on by default. Add custom post types under **Settings > Editor > Post types you can protect**.

### What is the difference between free and Premium?

The free version sells permanent access to whole posts. Premium adds time limits, view limits, delayed paywalls, inline protection, the My Account tab, per-post paywall messages, and the subscription, membership, Elementor and Polylang integrations. See "Free and Premium compared".

## Selling

### Can one product unlock many posts?

Yes. Choose the same product on each post. See "Sell one product that unlocks many posts".

### Can a post be unlocked by several products?

Yes. Choose them all. Buying any one is enough.

### Can I sell access for a limited time?

Yes, with expiry protection (Premium), or with a subscription through WooCommerce Subscriptions (Premium).

### Can I sell a set number of views?

Yes, with page-view protection (Premium).

### Can I make new posts free for a while?

Yes, with delay protection (Premium).

### Can I protect only part of a post?

Yes, with the `[wc-pay-for-post-inline]` shortcode (Premium), or with Elementor element protection (Premium).

### Does the product have to be virtual?

No, but virtual products skip shipping, which suits content. The editor can be set to list only virtual and downloadable products.

### Can I sell content in bundles with other WooCommerce products?

Yes. Any WooCommerce product can unlock a post, including products that also ship something.

## Access

### What happens when an order is refunded or cancelled?

Access ends as soon as the order leaves Processing or Completed.

### What happens when a subscription is cancelled?

Access continues while it is Pending cancellation, and ends when the subscription is cancelled or expires (Premium).

### Can I give someone access without them paying?

Create an order for them in **WooCommerce > Orders > Add order** with the product, assign it to their account and set it to Completed. For expiry and page-view posts, record a paid date too, by marking the order paid.

### Why do I, the administrator, see the paywall?

Administrators see what customers see, unless **Administrators see everything** is on in **Settings > Access**. See "What administrators and editors see".

### Do members of my membership plugin get access?

With Premium, yes, for WooCommerce Memberships plans and Paid Memberships Pro levels chosen on the post.

## Content and SEO

### Is the title or featured image protected?

No. Only the content is replaced. Titles, featured images and manual excerpts are visible to everyone.

### Can search engines index protected posts?

They see what a logged-out visitor sees: the title, the excerpt and the paywall. The paid content is not served to them.

### Are media files protected?

No. Images and files in the Media Library can be opened by anyone who has the link. Sell files with WooCommerce downloadable products.

### Does it work with feeds and the REST API?

Yes. Both get the paywall in place of protected content for visitors without access.

## Technical

### Does it work with page caching?

Yes, if logged-in users are never served cached pages. See "Use Pay For Post with full-page caching".

### Does it support WooCommerce High-Performance Order Storage?

Yes.

### Does it work with multisite?

Yes. Each site has its own settings. "Administrators see everything" applies to super admins on multisite.

### My theme shows the full post. Why?

Your theme may not use WordPress's `the_content` filter. See "Protect content in themes that do not use the_content".

### Can I change the look of lists and status boxes?

Yes. Copy the templates into your theme. See "Override the plugin's templates".

### What happens to my data if I deactivate the plugin?

Nothing is deleted. Data is removed only when you delete the plugin with **Delete all Pay For Post data when the plugin is deleted** on in **Settings > Advanced**.

### What happens if my Premium licence lapses?

Your settings are kept. Delay, page-view and expiry posts behave as standard posts. Inline protection, the My Account tab, the integrations and Elementor element protection stop until the licence is active again. See "Free and Premium compared".
