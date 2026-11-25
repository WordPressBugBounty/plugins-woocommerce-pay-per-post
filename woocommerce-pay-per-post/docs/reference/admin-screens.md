# Admin screens reference

Pay For Post adds a **Pay For Post** menu to wp-admin with three screens: **Settings**, **Protected Content** (Premium) and **Need Help**. Only users who can manage options (administrators, normally) see it. This page describes each screen and the plugin's other additions to wp-admin.

Before 3.4.0 the menu also had What's New, Getting Started, Documentation, Debug and Tools. They are now part of Need Help, and links and bookmarks to them open the new place:

| Old screen | Opens |
| --- | --- |
| What's New | Need Help, What's new |
| Getting Started | Need Help, Docs, "Protect your first post in five minutes" |
| Documentation | Need Help, Docs |
| Debug | Need Help, Check a customer |
| Tools | Need Help, Status and tools |

## Settings

All plugin settings, one tab per section: **Paywall**, **Access**, **Customers** (Premium), **Editor**, **Integrations** and **Advanced**. Each setting has a title and a description, with an **On** checkbox beside it where it is a yes-or-no choice. One **Save changes** button at the bottom saves every tab. Integrations lists the plugins Pay For Post works with and the status of each. Paywall can preview a post's paywall with the message as you have it, before you save. See the Settings reference for each setting.

## Protected Content (Premium)

Your protected posts and what unlocks them, 20 to a page, newest first. Only published posts of the post types chosen in Settings are listed.

- **Search** matches post titles. WordPress searches for each word separately, so a two-word search finds titles containing both words anywhere.
- **Post type** narrows the list to one type. It appears when more than one post type is enabled.
- **Previous** and **Next** move between pages. The line above the table says which posts you are looking at, such as "1–20 of 3,000 protected posts".

| Column | Shows |
| --- | --- |
| Post | The title, linked to its editor, with its ID and a **View** link. |
| Protection | **Buy once**, **Free at first**, **Limited views** or **Time limited**, the names the post editor uses. |
| Protects | **Whole post**, or **Elementor sections** when the protection is set on Elementor elements. |
| Unlocked by | The products that unlock it, linked to each product, and any WooCommerce Memberships plan or Paid Memberships Pro level. A product, plan or level that no longer exists shows as "not available". When more than six unlock a post, the rest are behind "+N more". |
| Buyers | **Show buyers**, or a note when nothing can be listed (see below). |

### Buyers

Click **Show buyers** to list a post's buyers under its row. They are loaded only when you ask, so the screen stays quick however many orders the store has. Click again to hide them.

A buyer is a customer with a paid order (an order status WooCommerce counts as paid, such as Processing or Completed) for a product that unlocks the post, including any variation of a variable product. Orders in both of WooCommerce's order storage types are found. The list shows the 50 customers who ordered most recently, and says how many there are in all:

| Column | Shows |
| --- | --- |
| Customer | Their name, linked to their profile. A guest order shows "No account: a guest checkout, so no access"; an account deleted since the order shows "Account deleted, so no access". A guest order placed with an account's email address is listed under that account. |
| Email | Their email address: the account's, or the order's billing email for a guest. |
| Paid orders | How many of their paid orders contain one of the products, linked to the customer's orders. |
| Latest order | The most recent of those orders and its date, linked to the order. |
| Check access | Opens the Access Diagnostic on **Need Help > Check a customer** for this customer and post. Customers without an account have no button. |

Below the list are links to search the orders screen for each product, for buyers beyond the first 50.

Being a buyer is not the same as having access. Expiry, page views, subscriptions and memberships can each end access a purchase gave, and a membership can give access without a purchase. Use **Check access** to see whether a customer can open the post now.

The Buyers column shows instead:

- "Memberships: buyers are not listed" when only membership plans or levels unlock the post. Their members are listed in WooCommerce Memberships or Paid Memberships Pro. When a post has both products and memberships, its buyers list says that members are not included.
- "No products" when nothing that still exists unlocks the post.

Links from earlier versions that open a post's buyers (`&product_id=` followed by the post's ID) still work: they open a page listing that post's buyers.

Developers can change the page size with `wc_pay_per_post_protected_content_per_page` (see the Hooks reference).

## Need Help

Views, shown as tabs in this order: **Start**, **Check a customer**, **Status and tools**, **Debug log**, **Docs** and **What's new**. The Debug log tab appears only while debug logging is on or a log is left to delete.

### Start

The view that opens first. Three panels side by side:

| Panel | What it does |
| --- | --- |
| A customer can't get in | The Access Diagnostic form: enter a customer email and a page address (or post ID) and click **Check access**. The answer opens on Check a customer. |
| Find an answer | Searches these docs, with links to the most popular pages. |
| Ask us | **1. Copy status report**, then **2. Contact support**, so your message includes your site's details. |

Below them, **Your site** summarises the status (see Status and tools), and a **New in** line sums up what the installed version added (the full list is under What's new).

While WooCommerce guest checkout is on, a red bar across the top says so, with **Turn it off in WooCommerce**: customers who check out as guests get no access. While the debug log is on, a warning bar across the top offers **View log** and **Turn off and delete**, which turns debug logging off and deletes the log in one step.

### Check a customer

The Access Diagnostic. Enter **Customer email** and **Page URL or post ID**, then click **Check access**. The result shows:

- **Has access** or **No access**, with the reason in plain language. When the customer's order for the product has not been paid, or was cancelled or refunded, the reason names that order and its status.
- **Copy for a support reply**, and **Open order #N** when one order explains the answer.
- **How the paywall decided**: each check, numbered, in the order the paywall runs them. Every check runs; when the answer is No access, the ones that failed are marked **Kept them out**.
- **Their orders**: every order found for the customer, with how many were found and how many count.
- The products that unlock this page, and any other products they bought.
- Folded sections: **Page setup**, **Evidence**, **Environment** and **The report as text**.

See "Find out why a customer can or cannot see a page (Access Diagnostic)".

### Status and tools

| Panel | What it does |
| --- | --- |
| Status | The Pay For Post, WordPress, WooCommerce and PHP versions, which order storage WooCommerce uses, whether guest checkout is on, the debug log, (Premium) whether the page-view table exists, and the integrations detected. **Copy report** copies it as text. |
| Tools | Refresh cached lists, the debug log and, when needed, the page-view table. |
| Can't be undone | Reset page-view counts and Remove all protection. |

| Tool | What it does |
| --- | --- |
| Refresh cached lists | Click **Refresh** to delete the plugin's cached lists. They rebuild on the next page load. Safe at any time. |
| Debug log | Shows the log's size, with **View** (opens the Debug log view) and **Delete**. Logging is turned on under **Settings > Advanced > Debug log**. |
| Create the page-view table (Premium) | Appears only when the table page-view protection records visits in is missing. |
| Reset page-view counts (Premium) | **Reset page views…** deletes every recorded page view for every customer. Everyone's page-view allowance starts again. Cannot be undone. |
| Remove all protection | **Remove all protection…** unlinks every product from every post (all post meta starting with `wc_pay_per_post_`) and clears page views and cached data, so every protected post becomes free to read. Settings, orders and products are kept. Cannot be undone. Take a backup first. |

### Debug log

The debug log itself: whether logging is on, the log's size and where it is on the server, and the log (the most recent 64 KB). **Copy** copies it, **Delete** deletes it, and while logging is on **Turn off and delete** turns logging off and deletes the log in one step. With no log yet, it says how to make one.

### Docs

This documentation, for the version you have installed. The first page lists every guide and reference page, with a search box. A page shows the list beside it, links to the pages it mentions, and links to the previous and next page. Searching finds pages containing every word you type.

### What's new

The changelog for the four most recent versions.

## Post list column

Post lists for enabled post types get a **Pay For Post** column. Protected posts show a green icon for their protection type. Hover over it for its name:

| Icon | Hover text |
| --- | --- |
| Pin | Standard Purchase Protection |
| Clock | Delay Protection |
| Eye | Page View Protection |
| Clock with an arrow | Expiry Protection |

## User profile: page views (Premium)

Each user's profile page in wp-admin has a **Pay For Post with WooCommerce Page Views** table listing their recorded views of page-view-protected posts: ID, page or post name, and time. Click **Delete** to remove a view, which gives the customer that view back.

By default only administrators see it. Developers can change the roles with `wc_pay_per_post_allowed_roles_for_user_profile_edit`. Deleting a view also needs permission to edit that user.

## Notices

| Notice | Meaning |
| --- | --- |
| Debugging is on | **Debug log** is on in **Settings > Advanced**. Turn it off when you are done. |
| Custom field support | A post type chosen in Settings cannot save the block editor panel's settings. Add custom field support or turn off **Panel in the block editor sidebar** in **Settings > Editor**. |
| Premium protection paused | Some posts use delay, page-view or expiry protection, but the Premium licence is not active. Those posts are protected as standard purchases until it is. |
| WooCommerce required | Pay For Post needs WooCommerce active. |
