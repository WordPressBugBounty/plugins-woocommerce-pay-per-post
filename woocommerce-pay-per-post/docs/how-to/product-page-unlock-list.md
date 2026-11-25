# Show what a product unlocks on its product page
<!-- Short title: Product page unlock list -->

**(Premium)**

The `[wc-pay-for-post-product-unlocks]` shortcode lists every post a product unlocks. Put it on the product page so shoppers see what they are buying, and so customers who already bought can jump straight to the content.

## Steps

1. Go to **Products** and edit the product.
2. Add the shortcode to the product description or the short description:

   ```
   This bundle includes:
   [wc-pay-for-post-product-unlocks]
   ```

3. Update the product.

**Expected result:** the product page shows a bulleted list of linked post titles. Each item has the class `unlocked` if the visitor can open the post, or `locked` if they cannot.

The list is wrapped in `<ul class="wc-pay-for-post-unlocks-posts">`. Style `li.locked` and `li.unlocked` in your theme, for example with a padlock icon.

## Faster lists for large bundles

Checking access for each post takes time. On products that unlock 50 or more posts, turn the check off:

```
[wc-pay-for-post-product-unlocks show_status_classes="false"]
```

The list then has no `locked` or `unlocked` classes.

## Common pitfalls

- **Nothing appears.** The shortcode works only on a WooCommerce product page, where it knows which product it is on. It shows nothing on other pages, or when no published post uses the product.
- **Variable products.** The list includes posts protected by the parent product. Posts protected only by individual variations may not all be listed. Add the parent product to those posts, or list them by hand.
- **Draft posts** are not listed.
