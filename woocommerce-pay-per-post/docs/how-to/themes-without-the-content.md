# Protect content in themes that do not use the_content
<!-- Short title: Themes without the_content -->

Pay For Post puts the paywall in place of a post's content through WordPress's `the_content` filter. Nearly every theme uses it. Some custom themes, page builders and plugins print content another way, for example from a custom field or with `get_the_content()` on its own. On those, protected posts show in full.

This guide shows a developer how to add the paywall to such a template.

## Check whether you need this

1. Protect a post.
2. Open it logged out in a private window.
3. If you see the paywall, you do not need this guide. If you see the full post, read on.

Also rule out a page cache first: see "Use Pay For Post with full-page caching".

## Wrap the content in your template

In the template file that prints the post (often `single.php`, a template part, or a custom field output), wrap the content in an access check:

```php
<?php
if ( Woocommerce_Pay_Per_Post_Helper::has_access() ) {
    // The visitor may see the post. Print it however your theme does.
    echo get_post_meta( get_the_ID(), 'my_custom_body', true );
} else {
    // Show the paywall.
    echo Woocommerce_Pay_Per_Post_Helper::get_no_access_content();
}
?>
```

`has_access()` checks the current post. Pass a post ID to check another post: `has_access( 42 )`.

`get_no_access_content()` returns the paywall for the current post, with the post's own message or the site-wide one, and the tokens filled in.

## Page-view posts

On a page-view-protected post, `has_access()` counts the visit as a page view when it grants access inside the main loop of a single post page. Call it once per page load. To check access without counting a view, pass `false` as the second argument:

```php
Woocommerce_Pay_Per_Post_Helper::has_access( get_the_ID(), false );
```

Use the counting form for the check that decides whether to print the post, and the non-counting form anywhere else, such as a sidebar or a menu.

## Protect custom fields too

Pay For Post protects only the post content. If your template prints paid material from custom fields (a download link, a video URL), wrap those in the same check.

## Archive and listing templates

In a loop of several posts, pass each post's ID and do not count views:

```php
while ( have_posts() ) {
    the_post();
    $can_read = Woocommerce_Pay_Per_Post_Helper::has_access( get_the_ID(), false );
    // Show a lock icon, a "Buy" button, or a teaser.
}
```

## Common pitfalls

- **Old function names.** `Woocommerce_PayPerPost::has_access()` and `Woocommerce_PayPerPost::get_no_access_content()` still work but are deprecated. Use the `Woocommerce_Pay_Per_Post_Helper` versions.
- **The plugin is inactive.** Guard calls with `class_exists( 'Woocommerce_Pay_Per_Post_Helper' )` so your theme does not break if the plugin is deactivated.
- **Double paywalls.** If your template calls these functions and also uses `the_content`, the paywall can appear twice. Use one or the other.
- **See the Template functions reference** for every function and its arguments.
