# Customise the paywall message

The paywall is what visitors without access see in place of the post content. You write it once for the whole site, and (Premium) you can replace it on any post. This guide shows how, and how to use the tokens that fill in each post's products.

## Change the site-wide message

1. Go to **Pay For Post > Settings**.
2. Edit **Default paywall message** on the **Paywall** tab. It is a normal WordPress editor, so you can add headings, links, images and shortcodes.
3. Click **Save changes**.

The default message is:

```
<h1>Oops, Restricted Content</h1>
<p>We are sorry but this post is restricted to folks that have purchased this page.</p>
[products ids='{{product_id}}']
```

**Expected result:** every protected post without its own message shows the new text.

## Give one post its own message (Premium)

1. Open the post in the editor.
2. Under **Paywall message** in the **Pay For Post** panel or box, choose **A message for this post**.
3. Write the message:
   - Classic box: write it in the message editor that appears. Click **Insert** beside a token in the table below it to add that token.
   - Block editor: click **Edit message**. Write the message in the window that opens (HTML and shortcodes work), click **Insert** beside a token to add it, then click **Done**.
4. Save the post.

To go back to the site-wide message, choose **Your default message** and save the post. That deletes the post's own message.

To check the result, save the post and click **Preview the paywall** at the top of the panel or box. It opens **Settings > Paywall** with the preview for this post.

## Tokens

Tokens are placeholders that are replaced when the paywall is shown.

| Token | Replaced with | Version |
| --- | --- | --- |
| `{{product_id}}` | The IDs of the products that unlock this post, separated by commas. | Free and Premium |
| `{{parent_id}}` | The parent product ID of the first product. Use it when you protect with a variation and want to show the whole variable product. | Free and Premium |
| `{{excerpt}}` | The post's excerpt: the manual excerpt if there is one, otherwise the first 55 words of the content. | Premium |
| `{{memberships}}` | The way in for membership protection: a "Join" link for each Paid Memberships Pro level, or a members-only notice. | Premium |

Use the product tokens inside WooCommerce shortcodes. Some useful ones:

```
[products ids='{{product_id}}']
[add_to_cart id='{{product_id}}']
[product_page id='{{parent_id}}']
```

`[add_to_cart]` takes one product. If a post has several products, use `[products]`.

## Show a teaser of the post (Premium)

Put `{{excerpt}}` at the top of the message:

```
{{excerpt}}
<h2>Want to keep reading?</h2>
[products ids='{{product_id}}']
```

Write a manual excerpt on posts where the first 55 words give too much away.

## Membership-protected posts (Premium)

When a post is protected by a WooCommerce Memberships plan, `{{product_id}}` lists the products that grant the plan, so visitors can buy their way in. WooCommerce's `[products]` shortcode does not show a variation or a product hidden from the shop, so for those the paywall adds a "Buy {product name}" link to the product's page.

When a post is protected by a Paid Memberships Pro level, or by a membership with nothing to buy, the paywall adds the membership way in:

- a list of "Join {level name}" links to Paid Memberships Pro checkout, for each level that is open to sign-ups; or
- the notice "This content is available to members only.", with "Already a member? Log in." for visitors who are not logged in.

Put `{{memberships}}` where you want that to appear. Without the token, it is added after your message.

If a post has nothing to sell, shortcodes that use `{{product_id}}` or `{{parent_id}}` are removed so they cannot list your whole catalogue.

## Style the paywall

The paywall is wrapped in:

```html
<div class="wc_ppp_paywall wc_ppp_paywall_123"> ... </div>
```

where 123 is the post ID. Target `.wc_ppp_paywall` in your theme's CSS, or `.wc_ppp_paywall_123` for one post.

## Common pitfalls

- **The message shows the raw shortcode.** Check the shortcode name and quotes. WooCommerce shortcodes need WooCommerce active.
- **All your products appear on the paywall.** A `[products]` shortcode with a fixed `ids` list, or with no `ids`, shows those products, not the post's. Use `{{product_id}}`.
- **Extra blank lines or paragraphs.** The message is run through WordPress's paragraph formatting. Keep shortcodes on their own lines.
- **Different text per language.** See "Use Pay For Post with Polylang".
