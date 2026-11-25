# Use the block editor panel or the classic meta box
<!-- Short title: Block editor or classic box -->

Pay For Post settings for a post can be edited in two places: a **Pay For Post** panel in the block editor's sidebar, or the **Pay For Post** box below the editor. This guide explains which one you see, how to switch, and what each can do.

## Which one you see

| Your setup | Where the settings are |
| --- | --- |
| Block editor, with **Panel in the block editor sidebar** on (the default) | The **Pay For Post** panel in the editor's right-hand sidebar, on the **Post** or **Page** tab. Nothing from Pay For Post shows below the editor. |
| Block editor, with **Panel in the block editor sidebar** off | The **Pay For Post** box at the bottom of the editor. |
| Classic editor (the Classic Editor plugin) | The **Pay For Post** box below the editor. |

Both appear only on post types ticked under **Pay For Post > Settings > Editor > Post types you can protect**.

Before 3.4.0 the box was called **Pay For Post with WooCommerce**, and it had tabs.

## Switch between them

1. Go to **Pay For Post > Settings > Editor**.
2. Tick or untick **On** beside **Panel in the block editor sidebar**.
3. Click **Save changes**.

Settings you already saved are kept when you switch. Both places read and write the same data.

## What each can do

The panel and the box have the same layout, with no tabs. From top to bottom:

1. **The top line.** A **Protected** or **Not protected** badge, and a sentence saying what a visitor gets, such as "Each purchase allows 3 views. Everyone else sees the paywall." For administrators, on a post that has been saved, it also has two links:
   - **Preview the paywall** opens **Settings > Paywall** with the paywall preview for this post.
   - **Check a customer** opens **Need Help > Check a customer** with this post filled in, so only the customer's email is left to enter.
2. **Products that unlock it.** The products, plans and levels that unlock the post. Any one of them unlocks it. A **Create a product** link opens a new product.
3. **Protection.** One choice of four: **Buy once**, **Free at first** (Premium), **Limited views** (Premium) or **Time limited** (Premium). The chosen type's settings appear under the choices.
4. **Paywall message.** **Your default message** or (Premium) **A message for this post**.

Where they differ:

| Feature | Block editor panel | Classic box |
| --- | --- | --- |
| Choose products, plans and levels | A token field: type to search, choose from the list. Each choice shows as a token with an x to remove it. Membership plans show as "Membership plan: …" and Paid Memberships Pro levels as "Paid Memberships Pro level: …". | A searchable list, grouped into **Products** and, while their plugins are active, **Membership Levels**, **Subscription Products** and **PaidMembershipPro Membership Levels**. |
| Number of views (Premium) | A field labelled **Views per purchase** | "Each purchase allows [number] views." |
| Count views within a period (Premium) | The **Count only the views within a period** toggle | The **Count only the views within a period of the purchase** checkbox |
| Show views or time left (Premium) | The **Show the views left** or **Show the time left** toggle | The **Show customers the views or time they have left** checkbox |
| Per-post paywall message (Premium) | The start of the message and an **Edit message** button, which opens a window to write it | The message editor, right in the box |

## The block editor panel

1. Open the post and the **Post** tab of the right-hand sidebar.
2. Expand **Pay For Post**.
3. Under **Products that unlock it**, type part of a product name and choose it from the list. Repeat for each product. Click the x on a token to remove it.
4. (Premium) Under **Protection**, choose the type and fill in the sentence that appears under it. For **Limited views**, turn on **Count only the views within a period** to measure views within a time after the last purchase.
5. (Premium) For **Limited views** or **Time limited**, turn on **Show the views left** or **Show the time left** to show customers what they have left at the top of the post.
6. (Premium) Under **Paywall message**, choose **A message for this post** and click **Edit message**. Write the message in the window (HTML and shortcodes work), use **Insert** to add a token, and click **Done**. **Cancel** closes the window without keeping your changes.
7. Save the post.

The panel's protection changes are saved with the post. If you save without touching the panel, the post's protection stays as it was.

## The classic box

1. Scroll to the **Pay For Post** box below the editor.
2. Under **Products that unlock it**, choose one or more products from the list. Type to search it.
3. (Premium) Under **Protection**, choose the type and fill in the sentence that appears under it:
   - **Free at first**: "Free to everyone for [number] [unit] after it is published."
   - **Limited views**: "Each purchase allows [number] views." Tick **Count only the views within a period of the purchase** to add "Within [number] [unit] of their last purchase."
   - **Time limited**: "Access lasts [number] [unit] after purchase."
4. (Premium) For **Limited views** or **Time limited**, tick **Show customers the views or time they have left**.
5. (Premium) Under **Paywall message**, choose **A message for this post** and write it in the editor that appears. The table of tokens below it has an **Insert** button for each.
6. Update the post.

Units are minutes, hours, days, weeks, months or years.

Only one protection type can be chosen. Choosing one replaces the others.

## Common pitfalls

- **The panel is missing.** Check the post type is chosen in Settings. Custom post types also need custom field support for the panel to save; wp-admin shows a notice when it is missing. If **Who can set protection** under **Settings > Editor** has roles ticked, only those roles see the panel.
- **An entry reads "#123 (not available)".** The post is protected by a product, plan or level that neither list can show: a product that is no longer published or is hidden by the virtual-only setting, or a plan or level whose plugin is inactive or whose Premium licence has lapsed. It stays selected, and saving the post keeps it. Remove it from the list if the post should no longer use it.
- **A product is missing from the list.** Only published products are listed. If **Only offer virtual and downloadable products** is on in **Settings > Editor**, other products are hidden.
- **Some choices are locked.** In the free version, **Free at first**, **Limited views**, **Time limited** and **A message for this post** are shown but locked. Each has a **Premium** badge that links to the plans.
- **The box says the protection is paused.** The post is set to a Premium protection type while Premium is not active. It is sold as **Buy once** until Premium is active again. Its settings are kept.
- **The per-post message is not below the block editor any more.** With the panel on, it is edited from the panel: choose **A message for this post** and click **Edit message**.
