# Installing Pay For Post with WooCommerce
<!-- Short title: Installation -->

This guide installs the free version or the Premium version and checks that everything is ready. It takes a few minutes.

Before you start, check the Requirements page: WordPress 6.6 or later, WooCommerce 6.9 or later, PHP 7.4 or later, and guest checkout turned off.

## Install the free version

1. In wp-admin, go to **Plugins > Add New Plugin**.
2. Search for **Pay For Post with WooCommerce**.
3. Click **Install Now**, then **Activate**.

## Install the Premium version

The Premium version is a separate download that replaces the free one. Your licence is managed through Freemius.

1. Download the Premium zip from your account email or your Freemius customer dashboard.
2. Go to **Plugins > Add New Plugin > Upload Plugin**.
3. Choose the zip and click **Install Now**, then **Activate**.
4. When asked, enter your licence key.

If the free version is already active, the Premium version takes its place. Your settings and protected posts are kept, because both versions read the same settings.

## What activation does

When the plugin is activated it:

- checks that WooCommerce is active, and stops with an error if it is not;
- sets the protected post types to **post** and **page**, unless you already chose post types in an earlier install;
- (Premium) creates the page-view table `{prefix}woocommerce_pay_per_post_pageviews` if it does not exist.

Deactivating the plugin removes nothing. Your settings, protected posts and page-view records stay in the database.

## Find the plugin's screens

Pay For Post adds a **Pay For Post** menu to wp-admin, near the bottom. It contains:

| Screen | What it is for |
| --- | --- |
| Settings | All plugin settings, and a preview of the paywall. |
| Protected Content (Premium) | Your protected posts and what unlocks them, with each post's buyers on request. |
| Need Help | Check a customer's access (the Access Diagnostic), the site status and maintenance tools, the debug log while it is on, the docs, and what's new. |

Only users who can manage options (normally administrators) see this menu.

## Check your setup

1. Go to **WooCommerce > Settings > Accounts & Privacy** and confirm guest checkout is off.
2. Go to **Pay For Post > Need Help > Status and tools**. Under **Status** check the versions, that guest checkout is off, and (Premium) that **Page-view table** says **Present**.
3. Open **Pay For Post > Settings** and review the **Default paywall message** on the **Paywall** tab. This is the paywall visitors see.

You are ready to protect your first post. See "Protect your first post in five minutes".

## Uninstalling

Deleting the plugin from the **Plugins** screen removes its data only if you turned on **Delete all Pay For Post data when the plugin is deleted** under **Pay For Post > Settings > Advanced** first. Nothing is deleted on deactivation; the data is removed when the plugin is deleted.

When that option is on, deleting the plugin removes:

- the page-view table;
- every option whose name starts with `wc_pay_per_post_`;
- every post meta field whose key starts with `wc_pay_per_post_`, which removes the protection from every post;
- the plugin's cached data;
- the debug log file.

This cannot be undone. Take a backup first.
