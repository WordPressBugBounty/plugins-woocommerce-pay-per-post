# Editor fields reference

Every field Pay For Post adds to the post editor, in the block editor panel, the classic meta box and Elementor, with the post meta each one saves. Meta keys are given for developers and importers.

## Where the fields appear

- Only on post types ticked in **Settings > Editor > Post types you can protect**.
- Only for users who can edit the post. **Who can set protection** under **Settings > Editor** can narrow this to certain roles.
- The block editor panel appears when **Panel in the block editor sidebar** is on in **Settings > Editor**; otherwise the classic meta box appears below the editor.

## Block editor panel: "Pay For Post"

In the **Post** (or **Page**) tab of the editor's right-hand sidebar.

| Field | Version | Values | Saves to |
| --- | --- | --- | --- |
| Protected / Not protected badge, and the sentence beside it | Free and Premium | Shows what a visitor gets. Not a field. | |
| Preview the paywall | Free and Premium | Link, for administrators on a saved post. Opens **Settings > Paywall** with the preview for this post. | |
| Check a customer | Free and Premium | Link, for administrators on a saved post. Opens **Need Help > Check a customer** with this post filled in. | |
| Products that unlock it | Free and Premium | Token field: type to search, choose from the list, click the x on a token to remove it. Published products. Premium also lists variations, WooCommerce Memberships plans ("Membership plan: …") and Paid Memberships Pro levels ("Paid Memberships Pro level: …"), each only while its plugin is active. | `wc_pay_per_post_product_ids` |
| Create a product | Free and Premium | Link to **Products > Add New Product**. | |
| Protection | Free and Premium; all but **Buy once** need Premium | **Buy once**, **Free at first**, **Limited views**, **Time limited** | `wc_pay_per_post_delay_restriction_enable`, `wc_pay_per_post_page_view_restriction_enable`, `wc_pay_per_post_expire_restriction_enable` |
| Free to everyone for ... after it is published. | Premium | Number and unit (default days). Shown for **Free at first**. | `wc_pay_per_post_delay_restriction`, `wc_pay_per_post_delay_restriction_frequency` |
| Views per purchase | Premium | Number of views each purchase allows. Shown for **Limited views**. | `wc_pay_per_post_page_view_restriction` |
| Count only the views within a period | Premium | On or off; with "Within ... of their last purchase." (number and unit) | `wc_pay_per_post_page_view_restriction_enable_time_frame`, `wc_pay_per_post_page_view_restriction_time_frame`, `wc_pay_per_post_page_view_restriction_frequency` |
| Access lasts ... after purchase. | Premium | Number and unit. Shown for **Time limited**. | `wc_pay_per_post_expire_restriction`, `wc_pay_per_post_expire_restriction_frequency` |
| Show the views left / Show the time left | Premium | On or off. Shown for **Limited views** and **Time limited**. | `wc_pay_per_post_show_warnings` |
| Paywall message | Free and Premium; **A message for this post** needs Premium | **Your default message** or **A message for this post**. The second shows the start of the message and an **Edit message** button, which opens a window with a text box (HTML and shortcodes work), the tokens with **Insert** buttons, and **Cancel** and **Done**. | `wc_pay_per_post_restricted_content_override` |

Units are minutes, hours, days, weeks, months and years. They are stored as `minute`, `hour`, `day`, `week`, `month` and `year`.

The panel saves products and protection with the post only when you change something in it. A save that does not touch the panel leaves the post's protection as it was. The paywall message (Premium) is saved with every save.

## Classic meta box: "Pay For Post"

Below the editor, in the classic editor or when **Panel in the block editor sidebar** is off. Before 3.4.0 it was called "Pay For Post with WooCommerce" and had tabs. It has the same top line, with the badge, the sentence and the two links, as the panel.

| Field | Version | Values | Saves to |
| --- | --- | --- | --- |
| Products that unlock it | Free and Premium | Searchable, grouped list. Free: **Products**. Premium adds variations, **Membership Levels** (WooCommerce Memberships plans), **Subscription Products** and **PaidMembershipPro Membership Levels**, each group only while its plugin is active. Each option shows the name and ID. | `wc_pay_per_post_product_ids` |
| Create a product | Free and Premium | Link to add a product. | |
| Protection | Free and Premium; all but **Buy once** need Premium | **Buy once**, **Free at first**, **Limited views**, **Time limited** | `wc_pay_per_post_delay_restriction_enable`, `wc_pay_per_post_page_view_restriction_enable`, `wc_pay_per_post_expire_restriction_enable` |
| Free to everyone for ... after it is published. | Premium | Whole number and unit (default days) | `wc_pay_per_post_delay_restriction`, `wc_pay_per_post_delay_restriction_frequency` |
| Each purchase allows ... views. | Premium | Whole number | `wc_pay_per_post_page_view_restriction` |
| Count only the views within a period of the purchase | Premium | Checkbox | `wc_pay_per_post_page_view_restriction_enable_time_frame` |
| Within ... of their last purchase. | Premium | Whole number and unit | `wc_pay_per_post_page_view_restriction_time_frame`, `wc_pay_per_post_page_view_restriction_frequency` |
| Access lasts ... after purchase. | Premium | Whole number and unit | `wc_pay_per_post_expire_restriction`, `wc_pay_per_post_expire_restriction_frequency` |
| Show customers the views or time they have left | Premium | Checkbox. Shown for **Limited views** and **Time limited**. | `wc_pay_per_post_show_warnings` |
| Paywall message | Free and Premium; **A message for this post** needs Premium | **Your default message** or **A message for this post**. The second shows an HTML editor and a table of tokens, each with an **Insert** button. | `wc_pay_per_post_restricted_content_override` |

Choosing **Buy once** stores none of the three `_enable` keys as on. Only one protection type can be chosen.

## The paywall message choice

Choosing **Your default message** and saving the post deletes the post's own message, in either editor. The choice itself is not stored: a post uses its own message whenever `wc_pay_per_post_restricted_content_override` is not empty, as before 3.4.0.

## The free version

The Premium choices (**Free at first**, **Limited views**, **Time limited** and **A message for this post**) are shown but locked. Each has a **Premium** badge that links to the plans, with a note ending "Available with Premium". The free version saves only the products; other stored values are left as they are.

If a post is set to a Premium protection type while Premium is not active, the box or panel says that type is paused and the post is sold as **Buy once** until Premium is active again.

## Elementor element controls (Premium)

On any section, column or widget: **Pay for Post** tab, **Pay for Post (BETA V3)** section.

| Control | Values |
| --- | --- |
| Enable Pay for Post | Yes or no |
| Select Products | Products, and WooCommerce Memberships plans while that plugin is active |
| Override Restricted Content Message? | Yes or no |
| Restricted Content Message | HTML. Shown only when the override is on. |

These are saved in Elementor's page data, not in Pay For Post meta.

## How the protection type is decided

From the stored values:

1. No products stored: the post is not protected.
2. Delay enabled: delay (**Free at first**).
3. Otherwise page view enabled: page view (**Limited views**).
4. Otherwise expiry enabled: expiry (**Time limited**).
5. Otherwise: standard (**Buy once**).

Steps 2 to 4 apply only with an active Premium licence. Without one, the post is standard.
