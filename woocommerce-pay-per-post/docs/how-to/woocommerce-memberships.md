# Protect content for WooCommerce Memberships members
<!-- Short title: WooCommerce Memberships -->

**(Premium)**

With WooCommerce Memberships active, you can protect a post with a membership plan as well as, or instead of, a product. Active members of the plan see the post. Everyone else sees the paywall, which offers the products that grant the plan.

## Steps

1. Set up the membership plan in **WooCommerce > Memberships > Membership Plans**. To sell it, set the plan's access to "product(s) purchase" and choose the products that grant it.
2. Open the post in the editor.
3. Choose the plan under **Products that unlock it**. In the block editor's **Pay For Post** panel it is listed as "Membership plan: {plan name}"; in the classic box, in the **Membership Levels** group. You can choose products too.
4. Update the post.

**Expected result:** a logged-in customer with an active membership in the plan sees the post. Visitors without it see the paywall, which lists the published products that grant the plan.

## Who gets access

- **Members:** an active member of any chosen plan gets access. "Active" is as WooCommerce Memberships defines it; expired, cancelled and paused memberships do not count.
- **Buyers:** if you also chose products, a customer who bought one of them gets access, member or not.
- Members are not limited by page views or expiry. Use **Buy once**.

## What the paywall shows

`{{product_id}}` in the paywall message lists the products that grant the plan, so the default `[products ids='{{product_id}}']` lets visitors buy their way in.

WooCommerce's `[products]` shortcode leaves out variations and products whose catalog visibility is "Search results only" or "Hidden". When a plan is granted by one of those, the paywall adds a "Buy {product name}" link to its product page, in the same place as the members-only notice below.

If the plan has no published products that grant it, the paywall shows "This content is available to members only.", with "Already a member? Log in." for visitors who are not logged in. Place this with the `{{memberships}}` token, or let it follow your message.

See "Customise the paywall message".

## Common pitfalls

- **WooCommerce Memberships' own restrictions.** WooCommerce Memberships can also restrict content itself. Use one plugin to restrict a given post, not both, or visitors may see two different messages.
- **Licence lapsed.** Without an active Premium licence, plans are not checked. A post protected only by a plan cannot be opened by anyone until the licence is active again.
