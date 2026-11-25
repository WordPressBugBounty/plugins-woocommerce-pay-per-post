# Show customers their content in My Account
<!-- Short title: My Account tab -->

**(Premium)**

This guide adds a **Purchased Content** tab to the WooCommerce My Account page. Customers find everything they bought, and everything they can read, in one place.

## Turn the tab on

1. Go to **Pay For Post > Settings > Customers**.
2. Tick **On** beside **Purchased Content tab**.
3. Under **Lists to show**, choose what the tab shows:
   - **Content you have access to**: the "Content you have access to" list.
   - **Content purchased**: the "Content purchased" list.
   If you tick neither, both lists are shown.
4. Under **Sort the lists by**, choose the order (see below).
5. Click **Save changes**.

**Expected result:** My Account shows a **Purchased Content** tab directly below **Dashboard**. It opens at `https://example.com/my-account/purchased-content/`.

## What the two lists show

| List | Heading | Contains |
| --- | --- | --- |
| Has access | Content you have access to | Every protected post the customer can open right now, however they got access: a purchase, a subscription, a membership, or a post in its free delay window. |
| Purchased | Content purchased | Every protected post whose product the customer has bought, including posts whose expiry or page views have run out. |

Both lists are links to the posts. They include published posts of the post types chosen in Settings.

Posts unlocked only by a membership appear under "Content you have access to", not under "Content purchased".

## Sort order

**Sort the lists by** applies to both lists:

| Option | Order |
| --- | --- |
| Title A–Z | By title. Titles sort naturally, so "Lesson 2" comes before "Lesson 10", ignoring case and accents. |
| Title Z–A | The reverse. |
| Newest published first | By publish date, newest first. This is the default. |
| Oldest published first | By publish date, oldest first. |
| Menu order | By each post's **Order** value (Page Attributes), lowest first. |

## Common pitfalls

- **The tab shows "Page not found".** Go to **Settings > Permalinks** and click **Save Changes** to refresh WordPress's links. The plugin does this when you turn the tab on, but some hosts and caches need it again.
- **A post is missing.** Check that its post type is ticked under **Settings > Editor > Post types you can protect** and that it is published. Lists are cached for up to an hour; click **Refresh** beside **Refresh cached lists** on **Pay For Post > Need Help > Status and tools**.
- **The tab is empty for a paying customer.** The order must be Processing or Completed and belong to their account. Use the Access Diagnostic.
- **Rename the tab or the headings.** Fill in **Tab name** and the two headings on the same tab. Left empty, they keep the plugin's wording, which is translated.
