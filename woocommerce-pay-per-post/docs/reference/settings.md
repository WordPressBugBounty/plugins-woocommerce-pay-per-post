# Settings reference

Every setting on **Pay For Post > Settings**, what it does, and its default. The settings are in six tabs: **Paywall**, **Access**, **Customers** (Premium), **Editor**, **Integrations** and **Advanced**. Each setting has a title and a description, with an **On** checkbox beside it where it is a yes-or-no choice. Click **Save changes** at the bottom after changing any of them; one save covers every tab.

The option name is given for developers. Every option name starts with `wc_pay_per_post`.

## Paywall

| Setting | Default | Version | What it does |
| --- | --- | --- | --- |
| Default paywall message | A heading "Oops, Restricted Content", a short apology, and `[products ids='{{product_id}}']` | Free and Premium | The paywall shown on every protected post that has no message of its own. Accepts HTML, shortcodes and tokens; click a token to insert it. Option: `wc_pay_per_post_restricted_content_default`. |
| Preview on a post | | Free and Premium | Enter a post ID or URL and click **Preview** to see the message on that post as a visitor without access sees it, unsaved changes included. Saves nothing. The **Preview the paywall** link at the top of a post's **Pay For Post** panel or box opens this with that post. |
| Who can comment on protected posts | Nobody | Free and Premium | **Everyone**: comments work as normal. **Customers with access** (Premium): only customers who can open the post can comment, and administrators when **Administrators see everything** is on. **Nobody**: comments are closed and the comment count hidden on every protected post, for everyone, customers who have paid included. Options: `wc_pay_per_post_turn_off_comments_when_protected` (on for Nobody and for Customers with access) and `wc_pay_per_post_turn_off_comments_public` (on for Customers with access). Customers with access keeps the first on too, so that if the licence lapses, comments close instead of opening to everyone. |
| Lock icon on locked titles | Off | Premium | Puts a lock beside the title of each protected post the visitor cannot open, in post lists and loops. Change the icon with the `wc_pay_per_post_paywall_icon` filter. Option: `wc_pay_per_post_show_locks`. |
| Views or time remaining box | Above the content | Premium | Where the box goes on page-view and expiry posts that show it: **Above the content** or **Below the content**. A post shows it when **Show customers the views or time they have left** (in the block editor, **Show the views left** or **Show the time left**) is on in its editor. Option: `wc_pay_per_post_status_box_position` (`top`, `bottom`). Filter: `wc_pay_for_post_show_warnings_position`. |
| Reload when access runs out | On | Premium | An expiry post a customer has open reloads itself the moment their access ends, so the paywall appears. Off: they keep reading until they leave the page. Option: `wc_pay_per_post_expiry_reload`. Filter: `wc_pay_per_post_enable_javascript_expiration_refresh`. |

Tokens: `{{product_id}}`, `{{parent_id}}`, and in Premium `{{excerpt}}` and `{{memberships}}`. See the Paywall tokens reference. A post can replace the message with its own (Premium).

## Access

| Setting | Default | Version | What it does |
| --- | --- | --- | --- |
| Administrators see everything | Off | Free and Premium | Lets administrators read every protected post without buying it. On multisite, only network super admins. Off by default, so administrators see the paywall like customers. Option: `wc_pay_per_post_allow_admins_access_to_protected_posts`. |
| Other roles that skip the paywall | None | Premium | Tick the roles, such as Editor or Shop manager, that read every protected post without buying it. Administrators are set by **Administrators see everything**. Option: `wc_pay_per_post_paywall_bypass_roles`. Filter: `wc_pay_for_post_allowed_roles`. |

The tab's heading also shows whether WooCommerce guest checkout is on. It must be off: access belongs to a customer account, and a guest has none. While it is on, the Access tab, Need Help's Start view and Status and tools show it in red, with a link to turn it off in WooCommerce.

## Customers (Premium)

What customers see after they buy. Text fields left empty use the plugin's own wording, which is translated.

### Orders and emails

| Setting | Default | What it does |
| --- | --- | --- |
| Purchased content links on orders | Everywhere | Under each product that unlocks content, a list of links to that content. **Everywhere**: on the order confirmation page, in My Account > Orders and in order emails. **Not in emails**: on the order pages only, useful when one product unlocks a long list. **Off**: orders show only WooCommerce's own item details. Option: `wc_pay_per_post_order_links` (`everywhere`, `not_in_emails`, `off`). Filters: `wc_pay_per_post_show_item_meta`, `wc_pay_per_post_hide_item_meta_in_email`. |
| Heading above the links | Access Your Purchased Content | Option: `wc_pay_per_post_order_links_heading`. Filter: `wc_pay_per_post_purchased_content_title`. |

### My Account

| Setting | Default | What it does |
| --- | --- | --- |
| Purchased Content tab | Off | Adds a **Purchased Content** tab below **Dashboard** in My Account, at `/my-account/purchased-content/`. Option: `wc_pay_per_post_display_on_my_account`. |
| Lists to show | Both | **Content you have access to** and **Content purchased**. Ticking neither shows both. Option: `wc_pay_per_post_shortcodes_to_display_on_my_account`. |
| Sort the lists by | Newest published first | Order of both lists on the tab: Title A–Z, Title Z–A, Newest published first, Oldest published first, Menu order. Titles sort naturally, so "Lesson 2" comes before "Lesson 10". Option: `wc_pay_per_post_my_account_sort_order` (`title_asc`, `title_desc`, `date_desc`, `date_asc`, `menu_order`). |
| Tab name | Purchased Content | Option: `wc_pay_per_post_my_account_tab_title`. Filter: `wc_pay_per_post_my_account_tab_title`. |
| Heading above the content they have access to | Content you have access to | Option: `wc_pay_per_post_my_account_has_access_title`. Filter: `wc_pay_per_post_my_account_has_access_title`. |
| Heading above the content they purchased | Content purchased | Option: `wc_pay_per_post_my_account_purchased_title`. Filter: `wc_pay_per_post_my_account_purchased_title`. |

The last four show while **Purchased Content tab** is on.

## Editor

| Setting | Default | Version | What it does |
| --- | --- | --- | --- |
| Post types you can protect | Post and Page | Free and Premium | Tick each post type that gets the Pay For Post panel or box, and whose posts appear in content lists. Every public post type on the site is listed. Option: `wc_pay_per_post_custom_post_types`. |
| Panel in the block editor sidebar | On | Free and Premium | On: the **Pay For Post** panel in the block editor sidebar. Off: the classic **Pay For Post** box below the block editor. Option: `wc_pay_per_post_block_editor_enabled`. |
| Only offer virtual and downloadable products | Off | Free and Premium | Limits the product lists in the editor to virtual and downloadable products. Useful on stores that also sell physical goods. Option: `wc_pay_per_post_only_show_virtual_products`. |
| Who can set protection | Everyone who can edit a post | Free and Premium | Tick roles to show Pay For Post's panel or box only to them. With none ticked, everyone who can edit a post sees it. Ticking roles without Administrator hides it from administrators too. Only roles that can edit posts are listed. A user outside the ticked roles cannot change a post's protection, even from an editor opened before the setting changed. A ticked role that has since been removed from the site is ignored, and on multisite network super admins always see it. Option: `wc_pay_per_post_editor_panel_roles`. Filter: `wc_pay_per_post_allowed_roles_for_meta_box`. |

Unticking a post type hides the panel and removes its posts from lists. Posts of that type that already have products stay protected. A post type saved earlier that is no longer public stays listed, ticked.

Custom post types need custom field support for the block editor panel to save. wp-admin shows a notice naming any ticked post type that lacks it.

## Integrations

The plugins Pay For Post works with, detected automatically. Each shows its status:

| Status | Meaning |
| --- | --- |
| Active | Installed and in use. |
| Installed, not in use | Installed, but Pay For Post is not using it: Polylang with **Use Polylang** off, or WooCommerce Subscriptions with **Use WooCommerce Subscriptions** off. |
| Premium | Installed, but the integration needs Premium. |
| Not installed | The plugin is not active on this site. |

| Integration | What it does | Setting |
| --- | --- | --- |
| WooCommerce Subscriptions | Protect posts with subscription products. Access lasts while the subscription is active or pending cancellation. | **Use WooCommerce Subscriptions** (Premium, shown while WooCommerce Subscriptions or a plugin imitating it is active, default on): off makes subscription products count as ordinary purchases. Turn it off only when another plugin imitates WooCommerce Subscriptions and its subscription records can't be relied on. Option: `wc_pay_per_post_use_woocommerce_subscriptions`. Filter: `wc_pay_per_post_can_use_woocommerce_subscriptions`. |
| WooCommerce Memberships | Protect posts with a membership plan. Access ends if the membership expires, is paused or is cancelled. | None |
| Paid Memberships Pro | Protect posts with a membership level. Access ends if the membership expires or is cancelled. | None |
| Elementor | A Protection tab on every Elementor widget, to lock part of a page. | None |
| Polylang | Lists purchased content in shortcodes and emails in every language. | **Use Polylang** (Premium, shown while Polylang is active): shows list and order links in the visitor's language with links to other translations. Option: `wc_pay_per_post_enable_polylang`. |

## Advanced

| Setting | Default | Version | What it does |
| --- | --- | --- | --- |
| Debug log | Off | Free and Premium | Records how the paywall decides, on pages you open with `?wc-ppp-debug=true` added to the address. Shows a reminder notice in wp-admin while on. The log can include customers' email addresses and orders, so turn this off and delete the log when you are done. The log is written to `wp-content/uploads/woocommerce-pay-per-post-logs/debug-<hash>.log`; the hash comes from your site's security keys, so the address can't be guessed, and the folder refuses web requests on Apache. While a log exists, **View or delete the log** opens it on **Pay For Post > Need Help > Debug log**. Option: `wc_pay_per_post_enable_debugging`. |
| Delete all Pay For Post data when the plugin is deleted | Off | Free and Premium | When on, **deleting** the plugin from the Plugins screen removes its settings, all post protection, page-view records, cached data and the debug log, so every protected post becomes free to read. Deactivating removes nothing. Orders and products are kept. Cannot be undone. Option: `wc_pay_per_post_delete_settings`. |

## Settings and filters

Each setting that names a filter is the value that filter receives, so code using the filter has the last word. Where such code is active, the setting says so under its description.

## Settings that are not on this page

- A post's products and protection type: in the post editor. See the Editor fields reference.
- How long lists are cached, and other developer options: developer filters. See the Hooks reference.
