# Make a post free for a while, then paid (delay protection)
<!-- Short title: Free for a while, then paid -->

**(Premium)**

Delay protection keeps a new post open to everyone for a set time after its publish date. When the time is up, the paywall appears and readers must buy one of the post's products.

## Steps

1. Open the post in the editor and choose its products.
2. Under **Protection**, choose **Free at first**. Then fill in "Free to everyone for [number] [unit] after it is published." The unit is minutes, hours, days, weeks, months or years. This is the same in the block editor panel and the classic box.
3. Save the post.

**Expected result:** with 2 weeks, the post is free to everyone for two weeks after its publish date. From then on, only customers who bought a product see it.

## How the time is measured

- The clock starts at the post's **publish date**, as shown in the editor. Changing the publish date moves the window.
- Time is counted in whole units. With 2 weeks, the post is free until two full weeks have passed.
- The window is the same for every visitor. It does not depend on when they first visited.

## Common pitfalls

- **Old posts are locked straight away.** If you add a 2-week delay to a post published a month ago, the window has already passed.
- **Delay-window posts appear in "has access" lists.** While the post is free, the `has_access` list template includes it for every logged-in visitor. That is correct: everyone has access during the window.
- **Times near the boundary.** The publish date is in your site's time zone and the current time is in UTC. On sites far from UTC, the paywall can appear some hours earlier or later than the exact boundary. Use days or longer if precision matters.
- **Licence lapsed.** Without an active Premium licence the delay is not applied and the paywall shows from the start.
