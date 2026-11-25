# Protect part of a post (inline protection)
<!-- Short title: Protect part of a post -->

**(Premium)**

Inline protection hides only the parts of a post you wrap in a shortcode. The rest of the post stays public. Use it to show an introduction to everyone and keep the paid part behind a paywall in the same post.

## Steps

1. Open the post in the editor.
2. Choose the post's products in the **Pay For Post** panel or box, as for a fully protected post. The inline shortcode uses these products and the post's protection type.
3. Wrap the paid part of the content in the shortcode:

   ```
   This introduction is free for everyone.

   [wc-pay-for-post-inline]
   This part is only for customers who bought the product.
   [/wc-pay-for-post-inline]
   ```

   In the block editor, put the opening and closing tags in **Shortcode** or **Paragraph** blocks, with the paid blocks between them. You can also put everything in one **Classic** block.
4. Save the post.

**Expected result:** visitors without access see the free part and, in place of the wrapped part, the paywall. Customers with access see the whole post.

Because the post contains the shortcode, the plugin does not also put a paywall over the whole post.

## Use a different message for the inline paywall

Add `paywall_message` to the shortcode:

```
[wc-pay-for-post-inline paywall_message="<p>Buy the full recipe: {{add_to_cart_link}}</p>"]
The paid part.
[/wc-pay-for-post-inline]
```

Without `paywall_message`, the inline paywall uses the post's own paywall message if it has one, otherwise the site-wide message from Settings.

Inside `paywall_message` you can use these tokens as well as the normal paywall tokens:

| Token | Replaced with |
| --- | --- |
| `{{products}}` | The WooCommerce products grid for the post's products. |
| `{{add_to_cart}}` | An add-to-cart button for each product, separated by commas. |
| `{{add_to_cart_link}}` | A text link "Add {product name} to Cart" for each product, separated by commas. |

Use single quotes inside the attribute if your HTML needs quotes, or keep the message to plain text and tokens. WordPress shortcode attributes cannot contain double quotes or square brackets.

## Several regions in one post

You can wrap more than one part of a post. Each region shows its own paywall to visitors without access.

## Common pitfalls

- **The post has no products.** The inline shortcode protects nothing unless the post itself has products.
- **Page-view posts.** Each inline region shown counts as one page view. A post with two regions spends two views per load. Prefer expiry or standard protection with inline regions.
- **Different messages in one post.** Once one region on the page sets `paywall_message`, later regions without it may reuse that message. Give every region on the page its own `paywall_message`, or none.
- **Elementor.** In the Elementor editor and preview, inline regions show their content so you can edit it. Check the live page logged out.
- **Licence lapsed.** Inline protection needs an active Premium licence. Without one, a post with inline regions is protected as a whole: buyers see all of it, everyone else gets the paywall instead of the free part. Before letting a licence lapse, replace inline shortcodes with whole-post protection if the free part should stay readable.
- **Excerpts.** An automatic excerpt can include the free part of the post. Write a manual excerpt if you need control.
