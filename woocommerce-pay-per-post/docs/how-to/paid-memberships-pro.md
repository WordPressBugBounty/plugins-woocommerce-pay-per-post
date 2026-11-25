# Protect content for Paid Memberships Pro members
<!-- Short title: Paid Memberships Pro -->

**(Premium)**

With Paid Memberships Pro active, you can protect a post with a membership level. Members of the level see the post. Everyone else sees the paywall with a link to join.

## Steps

1. Set up the level in **Memberships > Settings > Levels** in Paid Memberships Pro.
2. Open the post in the editor.
3. Choose the level under **Products that unlock it**. In the block editor's **Pay For Post** panel it is listed as "Paid Memberships Pro level: {level name}"; in the classic box, in the **PaidMembershipPro Membership Levels** group. You can also choose WooCommerce products.
4. Update the post.

**Expected result:** a logged-in member of the level sees the post. Visitors who are not members see the paywall with a "Join {level name}" link to the Paid Memberships Pro checkout for that level.

## Who gets access

- A member of any chosen level gets access, as long as Paid Memberships Pro says they hold the level.
- If you also chose WooCommerce products, a customer who bought one of them gets access too.
- Members are not limited by page views or expiry. Use **Buy once**.

## What the paywall shows

The paywall lists a "Join" link for each chosen level that is open to new sign-ups, in a list with the class `wc-ppp-membership-levels`. Levels closed to sign-ups are left out.

If no level is open and there is no product to buy, the paywall shows "This content is available to members only.", with "Already a member? Log in." for visitors who are not logged in.

Put the `{{memberships}}` token in your paywall message to choose where this appears. Without it, it is added after the message. The `{{product_id}}` token does not include levels; it lists only WooCommerce products.

## Common pitfalls

- **Paid Memberships Pro's own restrictions.** Paid Memberships Pro can also restrict posts by category or per post. Use one plugin per post, or visitors may see two different messages.
- **Licence lapsed.** Without an active Premium licence, levels are not checked. A post protected only by a level cannot be opened by anyone until the licence is active again.
