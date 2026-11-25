# What administrators and editors see
<!-- Short title: What admins see -->

Pay For Post treats administrators like any other visitor on the front end, unless you choose otherwise. This catches many site owners out when they first test. This page explains what each kind of user sees, and how to test what customers see.

## By default

| User | wp-admin and the editor | The front end |
| --- | --- | --- |
| Administrator | Full content | The paywall, unless they bought a product |
| Editor, author | Full content in posts they can edit | The paywall, unless they bought a product |
| Customer, subscriber | No access to wp-admin content | The paywall, unless they bought a product |
| Visitor not logged in | No access | The paywall |

## Let administrators see everything

Turn on **Administrators see everything** in **Pay For Post > Settings > Access**.

- On a single site, this applies to administrators.
- On multisite, it applies to super admins only, not to administrators of individual sites.

With it on, administrators also see comments on protected posts when Premium's **Who can comment on protected posts** is set to **Customers with access**.

## Let other roles see everything (Premium)

Tick roles such as Editor or Shop manager in **Other roles that skip the paywall** under **Pay For Post > Settings > Access**. Users with those roles read every protected post without buying it.

Developers can also change the list with the `wc_pay_for_post_allowed_roles` filter, which receives the ticked roles:

```php
add_filter( 'wc_pay_for_post_allowed_roles', function ( $roles ) {
    $roles[] = 'editor';
    return $roles;
} );
```

## Page builder editing

- **Elementor:** the editor and preview always show protected content, for anyone using them.
- **Divi and Beaver Builder:** in their front-end editing modes, administrators and authors see the content.

## Testing what customers see

Your own view is not a reliable test, because of the settings above and because a page cache may treat you differently. Instead:

1. Open the post in a private browser window, not logged in. You should see the paywall.
2. Create a test customer account with a test email such as `customer@example.com`, place an order for the product, and mark it Processing or Completed.
3. Log in as that customer in the private window and open the post. You should see the content.
4. To answer "can this real customer see this page?" without logging in as them, use the Access Diagnostic on **Pay For Post > Need Help > Check a customer**.

## The editor panel

Everyone who can edit a post sees the Pay For Post panel or box. **Who can set protection** under **Settings > Editor** narrows it to the roles you tick.
