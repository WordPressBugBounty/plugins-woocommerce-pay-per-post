# How time limits are measured

**(Premium)**

Delay, page-view and expiry protection all depend on time. This page explains the starting points, the units and the edge cases, so you can predict exactly when access starts and ends.

## Starting points

| Protection | Measured from |
| --- | --- |
| Delay | The post's publish date |
| Expiry | The paid date of the customer's most recent qualifying order for one of the post's products |
| Page-view time window | The same paid date as expiry |
| Page-view count | Views recorded after that paid date |

"Most recent qualifying order" means the newest Processing or Completed order, placed by the customer's account, that contains one of the post's products. A newer order replaces an older one, so buying again starts afresh.

## Units

You can choose minutes, hours, days, weeks, months or years.

Time is counted in **whole units**:

- Expiry of 2 days: access while fewer than 2 full days have passed. It ends exactly 48 hours after payment.
- Expiry of 1 month: access until one calendar month has passed, so from 15 March to 15 April.
- Delay of 1 week: the post is free until 7 full days after publishing.

## Expiry

Access lasts while the time since payment is less than the length you set. At the end:

- the customer sees the paywall on their next visit;
- if they have the post open, it reloads itself and shows the paywall;
- the `purchased` content list still includes the post (in Premium, with `expiration_status` set), while `has_access` drops it.

## Page views

The customer may view the post as many times as the allowance, counting the current view. With an allowance of 3, views 1, 2 and 3 show the post and view 4 shows the paywall.

With a time window, views are also limited to the window that opens at payment. The window is fixed, not rolling: when it closes, access ends even if views are left.

Only full views of the single post count. Listings, archives, search results and content lists do not.

## Delay

The post is free to everyone until the delay has passed since its publish date. It does not matter when a visitor first sees it.

## Time zones

- Order dates and page views are stored and compared in UTC.
- The post publish date used for delay is in your site's time zone, while the current time is in UTC. On sites far from UTC, delay can end a few hours early or late. Choose days or longer when that matters.
- On sites that do not use High-Performance Order Storage, the paid date is read as WooCommerce stores it. Very short expiry times (minutes or hours) can be offset by your site's distance from UTC. Test short expiries on your own site before selling them.
- Dates shown in the status box are in UTC.

## Without a Premium licence

Time limits are not applied. Delay, page-view and expiry posts behave as standard posts until the licence is active again. Their settings are kept.
