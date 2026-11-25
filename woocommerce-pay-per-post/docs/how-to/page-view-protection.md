# Limit how many times a customer can view a post (page-view protection)
<!-- Short title: Limit page views -->

**(Premium)**

Page-view protection lets a purchase pay for a set number of views. After the last allowed view, the customer sees the paywall and must buy again.

## Steps

1. Open the post in the editor and choose its products.
2. Under **Protection**, choose **Limited views**. Then enter the number of views: "Each purchase allows [number] views." In the block editor panel the field is labelled **Views per purchase**.
3. Optionally, limit the views to a time window. Classic box: tick **Count only the views within a period of the purchase**. Block editor: turn on **Count only the views within a period**. Then fill in "Within [number] [unit] of their last purchase."
4. Optionally, show customers their count: tick **Show customers the views or time they have left** in the classic box, or turn on **Show the views left** in the panel.
5. Save the post.

**Expected result:** with 3 views, the customer reads the post on loads 1, 2 and 3, and sees the paywall on load 4.

## How views are counted

- Only views since the customer's most recent qualifying order count. Buying the product again starts a fresh allowance.
- A view is recorded when a logged-in customer with access opens the post itself. Seeing it in a blog listing, an archive or search results does not count.
- Every reload counts as a view.
- Views are stored in the plugin's page-view table, not in the order. Administrators can see and delete a customer's views on that user's profile in wp-admin.

## The optional time window

The window opens at the customer's most recent qualifying order and closes after the amount you set. It is **not** a rolling window.

For example, with 5 views over 7 days:

- the customer may view the post up to 5 times in the 7 days after buying;
- after the 7 days, the paywall shows even if views are left;
- to read again, they buy again, which opens a new 7-day window with 5 new views.

## Common pitfalls

- **Inline regions spend views too.** On a page-view post, each `[wc-pay-for-post-inline]` region shown on the page counts as a view. A post with two inline regions spends two views per load. Avoid inline regions on page-view posts.
- **The order is not on the customer's account.** The allowance and the time window are measured from the newest Processing or Completed order placed by the customer's account. An order matched only by billing email unlocks the post but gives no start date, so the window is treated as closed. Attach the order to the customer's account. The Access Diagnostic shows which order was used.
- **Members and subscribers.** Customers who get access through a membership or an active subscription are not limited by views.
- **Page caches.** A cached page cannot count views. Exclude logged-in users from the cache.
