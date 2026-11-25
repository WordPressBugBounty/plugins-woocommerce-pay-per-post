# Sell one product that unlocks many posts
<!-- Short title: One product, many posts -->

This guide sells a bundle, course or pass: one WooCommerce product that unlocks a set of posts. There is no special bundle setting. You add the same product to each post.

## Steps

1. Create the product, for example "Gardening Course: all 12 lessons", and publish it.
2. Open the first lesson in the editor.
3. In the **Pay For Post** panel or box, choose the course product.
4. Save the post.
5. Repeat for every lesson.

**Expected result:** a customer who buys the course product can read every lesson. Visitors who have not bought it see the paywall on each lesson, offering the course product.

## Sell lessons singly as well

Give each lesson its own product **and** the course product. Choose both in the lesson's product list. A customer who bought either one can read the lesson.

## Show customers what they get

- On the product page, add `[wc-pay-for-post-product-unlocks]` to the product description to list every lesson the product unlocks (Premium). See "Show what a product unlocks on its product page".
- After purchase, the order confirmation, order emails and My Account order view list links to the unlocked lessons (Premium).
- The My Account **Purchased Content** tab lists everything a customer can read (Premium).
- On a page of your own, `[woocommerce-payperpost template="purchased" product_ids="123"]` lists the lessons a product unlocks that the customer has bought (`product_ids` is Premium).

## Give every lesson the same time limit (Premium)

Protection type is set per post. To sell, for example, 30 days of access to the whole course, choose **Time limited** with "Access lasts 30 days after purchase." on every lesson. Each lesson measures the 30 days from the customer's most recent qualifying order, so a repurchase restarts the clock on all of them.

## Common pitfalls

- **A lesson was missed.** The Protected Content screen (Premium) lists every protected post and its products, so you can check the set is complete.
- **New lessons do not show in lists straight away.** Lists are cached for up to an hour. Saving a post in the editor clears that cache. If you add products by import or code, click **Refresh** beside **Refresh cached lists** on **Pay For Post > Need Help > Status and tools**.
- **Variable products.** In Premium you can choose individual variations. A customer who bought a different variation of the same product does not get access. Add the parent product too if any variation should unlock the post.
