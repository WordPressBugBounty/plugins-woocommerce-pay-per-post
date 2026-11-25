# Where protection applies

Pay For Post protects a post's content wherever WordPress shows it through its normal content output. This page lists the places a post can appear and what visitors without access see in each.

## Places the paywall replaces the content

| Where | What visitors without access see |
| --- | --- |
| The single post page | The paywall |
| Blog index, category, tag, author and date archives | The paywall, if your theme shows full content in listings |
| Search results | The paywall, if your theme shows full content |
| RSS and Atom feeds | The paywall in the item content |
| The REST API (`/wp-json/wp/v2/posts`) | The paywall in `content.rendered` |
| A theme or plugin that loads posts with AJAX for visitors | The paywall |
| wp-admin, including the editor | The content, to users who can edit the post |
| The **Preview** of a post | The same as the front end: the paywall, unless the previewing user has access |

## Excerpts

| Excerpt type | Shown to everyone? |
| --- | --- |
| Manual excerpt (typed in the Excerpt box) | Yes, exactly as written |
| Automatic excerpt (WordPress builds it from the content) | It is built from what the visitor would see, so visitors without access get the start of the paywall text |

Write a manual excerpt for each protected post that describes it without giving the paid part away. In Premium, the `{{excerpt}}` token can show it inside the paywall.

## What is never protected

- The post title.
- The featured image.
- Custom fields, unless your template checks access (see "Protect content in themes that do not use the_content").
- Attachments and media files. Their URLs work for anyone who has them. Protect downloadable files with WooCommerce's downloadable products instead.
- The post's existence: protected posts still appear in listings, search results, sitemaps and menus.

## Search

WordPress searches the stored post content, so a protected post can appear in search results for words in its paid part. The result shows the title and excerpt, not the paid text. If that matters, exclude protected posts from search with a search plugin.

## Comments

By default, **Who can comment on protected posts** in **Settings > Paywall** is set to **Nobody**, which closes comments on protected posts for everyone and hides the comment count. **Everyone** leaves comments open. In Premium, **Customers with access** opens comments for customers with access only. See the Settings reference.

## Page builders

| Builder | Behaviour |
| --- | --- |
| Block editor | Fully supported |
| Elementor | Whole-page protection through the post's products; element protection (Premium) inside Elementor. The editor and preview always show content. |
| Divi, Beaver Builder | Supported through the normal content output. In their front-end editing modes, administrators and authors see the content. |
| Others | Supported if they print content through `the_content`. Otherwise use the template functions. |

## Requests from other systems

- **Headless sites** reading the REST API get the paywall unless the request is authenticated as a user with access.
- **Email newsletters** built from your feed get the paywall text for protected posts.
- **Translation and SEO plugins** that read the raw content from the database see the full text; that is outside what Pay For Post controls.
