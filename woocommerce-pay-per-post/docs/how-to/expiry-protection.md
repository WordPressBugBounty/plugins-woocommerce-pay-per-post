# Sell time-limited access (expiry protection)
<!-- Short title: Time-limited access -->

**(Premium)**

Expiry protection gives a customer access for a set time after they buy. When the time is up, the paywall returns and they must buy again.

## Steps

1. Open the post in the editor and choose its products.
2. Under **Protection**, choose **Time limited**. Then fill in "Access lasts [number] [unit] after purchase." The unit is minutes, hours, days, weeks, months or years. This is the same in the block editor panel and the classic box.
3. Optionally, show customers their order date, expiry date and time remaining: tick **Show customers the views or time they have left** in the classic box, or turn on **Show the time left** in the panel.
4. Save the post.

**Expected result:** with 2 days, a customer who paid on Monday at 10:00 can read the post until Wednesday at 10:00.

## How the time is measured

- The clock starts at the **paid date** of the customer's most recent Processing or Completed order on their account. Buying again restarts it.
- Time is counted in whole units. With 1 month, access lasts until one full calendar month has passed.
- If the customer has the post open when access ends, the page reloads itself and shows the paywall. Turn **Reload when access runs out** off under **Pay For Post > Settings > Paywall** to stop it.

## Common pitfalls

- **Access ends at once for old orders.** Adding a 30-day expiry to a post whose customers bought months ago locks them all out. Tell them first, or use a new post.
- **An order with no paid date.** An order set to Completed by hand without a payment may have no paid date. The plugin then treats access as expired. Have the customer buy again, or record the payment on the order.
- **An order matched only by billing email.** It unlocks standard posts but gives expiry no start date, so the paywall shows. The order must belong to the customer's account.
- **Members and subscribers.** Customers who get access through a membership or an active subscription are not limited by expiry. To end access when a subscription lapses, use WooCommerce Subscriptions with standard protection instead.
- **Dates in the status box** are shown in UTC, not in your site's time zone.
