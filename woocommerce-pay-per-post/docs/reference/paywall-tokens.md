# Paywall tokens reference

Tokens are placeholders in the paywall message that are replaced when the paywall is shown. They work in the site-wide **Default paywall message**, a post's own message (**A message for this post** under **Paywall message** in the editor), an Elementor element's **Restricted Content Message**, and the inline shortcode's `paywall_message`.

## Paywall tokens

| Token | Replaced with | Version |
| --- | --- | --- |
| `{{product_id}}` | The IDs of the products that can be bought to unlock the post, separated by commas. | Free and Premium |
| `{{parent_id}}` | The parent product ID of the first product, or `0` if it has no parent. | Free and Premium |
| `{{excerpt}}` | The post's manual excerpt, or the first 55 words of its content. | Premium |
| `{{memberships}}` | The membership way in: Paid Memberships Pro "Join" links, or a members-only notice. Empty when there is nothing extra to show. | Premium |

### {{product_id}} in detail

The list contains the products a visitor can buy:

- products and variations chosen for the post;
- (Premium) for each WooCommerce Memberships plan chosen, the plan's published products that grant it;
- duplicates removed.

Paid Memberships Pro levels are not products and are left out.

When the list is empty, any shortcode in the message that contains `{{product_id}}` or `{{parent_id}}` is removed, so `[products ids='']` cannot list your whole catalogue.

Use it inside WooCommerce shortcodes:

```
[products ids='{{product_id}}']
[products ids='{{product_id}}' columns='2']
```

### {{memberships}} in detail (Premium)

| Situation | Output |
| --- | --- |
| The post has Paid Memberships Pro levels open to sign-ups | `<ul class="wc-ppp-membership-levels">` with a "Join {level name}" link to each level's checkout |
| The post has a membership, and nothing can be bought or joined | `<p class="wc-ppp-members-only">This content is available to members only.</p>`, plus "Already a member? Log in." for visitors who are not logged in |
| Anything else | Nothing |

If the message does not contain `{{memberships}}`, the output is added after the message. Developers can change it with the `wc_pay_per_post_paywall_membership_offer` filter.

### {{excerpt}} in detail (Premium)

- A manual excerpt is used exactly as written.
- Otherwise, the first 55 words of the post content, with tags removed.
- Developers can change it with the `wc_pay_per_post_modify_excerpt` filter.
- Without Premium, `{{excerpt}}` is not replaced and appears as typed. Remove it from the message if you move to the free version.

## Inline-only tokens (Premium)

Only in the `paywall_message` attribute of `[wc-pay-for-post-inline]`:

| Token | Replaced with |
| --- | --- |
| `{{products}}` | The output of `[products ids="..."]` for the post's products |
| `{{add_to_cart}}` | An `[add_to_cart]` button per product, comma separated |
| `{{add_to_cart_link}}` | A link per product reading "Add {product name} to Cart" (filter `wc_pay_per_post_inline_shortcode_product_link_name`), comma separated |

## Order of processing

1. Tokens are replaced.
2. Shortcodes are run.
3. WordPress paragraph formatting is applied.
4. The result is wrapped in `<div class="wc_ppp_paywall wc_ppp_paywall_{post ID}">`.

Elementor element messages get step 1, then are placed in the page as they are.
