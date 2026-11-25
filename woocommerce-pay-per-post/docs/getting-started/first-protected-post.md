# Protect your first post in five minutes
<!-- Short title: Your first protected post -->

This guide puts one post behind a paywall and checks that it works. You need Pay For Post installed, WooCommerce set up to take payments, and guest checkout turned off.

## Step 1: Create the product

The product is what customers buy to unlock the post.

1. Go to **Products > Add New Product**.
2. Give it a name, for example "Full article: Spring Planting Guide".
3. Set a regular price.
4. In **Product data**, tick **Virtual**. There is nothing to ship.
5. Click **Publish**.

## Step 2: Write the post

1. Go to **Posts > Add New Post**.
2. Write the post as usual. Everything in the editor will be hidden from visitors who have not bought the product.
3. Optionally, write an excerpt. Visitors without access see the excerpt in your blog listing, so keep it free of paid content.

## Step 3: Connect the product to the post

In the block editor:

1. Open the **Post** tab of the editor's right-hand sidebar.
2. Find the **Pay For Post** panel.
3. Under **Products that unlock it**, type part of your product's name and choose it from the list.
4. Leave **Protection** on **Buy once**.
5. Click **Publish** or **Save**.

In the classic editor, or with "Panel in the block editor sidebar" turned off:

1. Scroll to the **Pay For Post** box below the editor.
2. Under **Products that unlock it**, choose your product.
3. Leave **Protection** on **Buy once**.
4. Click **Publish** or **Update**.

## Step 4: Check it works

1. Open the post in a private browser window, where you are not logged in.
2. You should see the paywall: a heading "Oops, Restricted Content", a short message, and your product with an add-to-cart button.
3. Log in as a test customer, buy the product, and complete the order. If the payment method leaves the order "On hold" or "Pending payment", change it to "Processing" or "Completed".
4. Open the post again as that customer. You should see the full post.

## What you should see

- Visitors who are not logged in, and logged-in customers who have not bought the product, see the paywall in place of the post content.
- The customer who bought it sees the full post, as long as their order is Processing or Completed.
- The post's title, featured image and excerpt stay visible to everyone.

## Common pitfalls

- **You still see the full post.** Administrators see the paywall like anyone else, unless **Administrators see everything** is on in **Settings > Access**. If the content is visible to a logged-out visitor, your theme may not use `the_content`, or a page cache is serving an old copy. See Troubleshooting.
- **The customer paid but sees the paywall.** Check that the order is Processing or Completed and belongs to their account, not a guest checkout. The Access Diagnostic on **Pay For Post > Need Help > Check a customer** answers this in one step.
- **The post type is missing the panel.** Only the post types ticked under **Settings > Editor > Post types you can protect** get the panel. Post and page are chosen by default.

## Next steps

- Change the paywall text: see "Customise the paywall message".
- Sell a bundle: see "Sell one product that unlocks many posts".
- Limit access by time or views (Premium): see the protection type guides.
