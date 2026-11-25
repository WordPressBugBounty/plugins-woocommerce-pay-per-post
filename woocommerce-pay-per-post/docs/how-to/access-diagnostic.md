# Find out why a customer can or cannot see a page (Access Diagnostic)
<!-- Short title: Check a customer's access -->

When a customer says "I paid but I can't see the page", the Access Diagnostic gives you the answer in one step. You enter the customer's email address and the page, and it tells you whether they have access and why.

It is in the free and Premium versions.

## Steps

1. Go to **Pay For Post > Need Help > Check a customer**. (The **A customer can't get in** panel on the Start view is the same form.) From a saved post, you can also click **Check a customer** at the top of its **Pay For Post** panel or box: the page is filled in, so only the email is left.
2. Enter:
   - **Customer email**: the email address of the customer's WordPress account.
   - **Page URL or post ID**: the full address of the page they are trying to open, for example `https://example.com/spring-planting-guide/`. If the address does not work, enter the post's numeric ID instead.
3. Click **Check access**.

**Expected result:** **Has access** or **No access**, with the customer's email and the page title, followed by the reason in plain language. When the customer's order for the product has not been paid, or was cancelled or refunded, the reason names that order and its status.

## How it works

The diagnostic runs the same protection checks as a real visit, as if the customer opened the page. It does not have its own copy of the rules, so its answer matches what the customer sees.

It does not change anything:

- it does not spend one of the customer's page views;
- it does not log you in as the customer;
- it does not change orders or settings.

## What the report contains

| Section | What it tells you |
| --- | --- |
| Has access or No access | The verdict and its reason, for example "No access: the customer DID purchase, but the access window has closed." |
| Copy for a support reply | Copies a plain-text summary to paste into your reply. |
| Open order #N | Opens the order, when one order explains the answer. |
| Warnings | Site problems that commonly cause refusals (see below). |
| How the paywall decided | Each check the plugin ran, numbered, in the order the paywall runs them, and its result. Every check runs, whatever the earlier ones answered. When the answer is No access, the checks that failed are marked **Kept them out**. |
| Their orders | The customer's orders, found by account **and** by billing email, so guest orders and orders on a second account appear too. The heading says how many were found and how many count. Each order shows its status, which account it belongs to, and whether it counts. A star marks a product that unlocks this page. |
| Products | The products that unlock this page, and any other products the customer bought. Those other products are often the answer: the customer bought a similar product that is not on the page. |
| Page setup (folded) | The page's protection settings, and each product that unlocks it with its type and whether it is a subscription or membership. |
| Evidence (folded) | The facts behind the verdict: whether the page is protected, its protection type, which products the customer bought, the purchase the expiry or page views are measured from, page views used, active subscription and membership. |
| Environment (folded) | Plugin, WooCommerce and integration status. |
| The report as text (folded) | The whole report as plain text, the same text **Copy for a support reply** copies. |

## Warnings you may see

- **Guest checkout is enabled.** Shown in red, with **Turn it off in WooCommerce**. Customers who check out as guests have no account, so they get no access.
- **A subscription compatibility layer.** Another plugin is posing as WooCommerce Subscriptions. Products it marks as subscriptions need an active subscription record, not just a paid order.
- **The subscriptions integration is switched off** for a recurring product, so the product counts as an ordinary purchase.
- **This page cannot be bought.** It is protected only by membership plans or levels that nothing sells and that are closed to sign-ups.
- **Protection is switched off site-wide** by code on the site.

## Common answers and what to do

| Reason | What to do |
| --- | --- |
| No WordPress user found with that email | The customer checked out as a guest or used another email. Find their order, create or find their account, and assign the order to it. |
| Has not purchased any of the products | Check the order exists, is Processing or Completed, and belongs to this account. Check whether they bought a different product (see the other products they bought, listed under the products that unlock the page). |
| The access window has closed | Expiry is working. The customer needs to buy again. |
| Has used their page-view allowance | Page views are working. Delete views from the customer's user profile (Premium) if you want to give more. |
| No active subscription | The subscription is on hold, cancelled or expired. |
| The page is not protected by Pay For Post | Something else is hiding the content: a membership plugin, the theme or a cache. |

## Common pitfalls

- **Use the customer's account email.** Billing emails are matched in the purchase history, but the lookup itself needs the account's email.
- **The page shows differently in the customer's browser.** If the diagnostic says **Has access** but the customer sees the paywall, a page cache is the usual cause. See "Use Pay For Post with full-page caching".
- **Large stores.** Purchase history shows the newest 50 orders. A developer can raise this with the `wc_pay_per_post_diagnostic_order_limit` filter.
