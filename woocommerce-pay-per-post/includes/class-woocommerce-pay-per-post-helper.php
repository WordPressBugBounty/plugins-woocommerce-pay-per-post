<?php

use Elementor\Plugin;
use PRAMADILLO\Woocommerce_Pay_Per_Post_Restrict_Content;
use Pramadillo\PayForPost\Carbon\Carbon;
use Automattic\WooCommerce\Utilities\OrderUtil;
/**
 * Class Woocommerce_Pay_Per_Post_General
 */
class Woocommerce_Pay_Per_Post_Helper extends Woocommerce_Pay_Per_Post {
    /**
     * @var array
     */
    public static $protection_types = [
        'standard',
        'delay',
        'page-view',
        'expire'
    ];

    public static function is_protected( $post_id = null ) : array {
        if ( is_null( $post_id ) ) {
            $post_id = get_the_ID();
        }
        Woocommerce_Pay_Per_Post_Helper::logger( 'Post ID: ' . $post_id . ' - Woocommerce_Pay_Per_Post_Helper::is_protected() is called' );
        return self::get_post_protection( $post_id );
    }

    /**
     * A post's protection, as get_product_ids_by_post_id() returns it.
     *
     * set_product_ids() computes it on template_redirect for the post the
     * request is for, and keeps it in the global $product_ids. The global
     * answers for that post only: a listing renders other posts in the same
     * request, and a REST API or admin-ajax.php request has no global at all.
     *
     * @param int|null $post_id Defaults to the current post.
     *
     * @return array
     */
    public static function get_post_protection( $post_id = null ) : array {
        global $product_ids;
        if ( is_null( $post_id ) ) {
            $post_id = get_the_ID();
        }
        if ( isset( $product_ids['post_id'] ) && (int) $product_ids['post_id'] === (int) $post_id ) {
            return $product_ids;
        }
        return self::get_product_ids_by_post_id( $post_id );
    }

    /**
     * Check if a Post have a product related
     *
     * @param $post_id
     *
     * @return bool
     * @noinspection PhpUnused
     */
    public static function have_post_products( $post_id ) : bool {
        $cache_key = WC_PPP_SLUG . '_have_post_products' . $post_id;
        $result = wp_cache_get( $cache_key, WC_PPP_SLUG );
        if ( $result === false ) {
            $selected = (array) get_post_meta( $post_id, WC_PPP_SLUG . '_product_ids' );
            $product_ids = array_filter( $selected, function ( $item ) {
                return !empty( $item );
            } );
            wp_cache_set( $cache_key, $product_ids, WC_PPP_SLUG );
        }
        return $result;
    }

    /** @noinspection PhpUnusedParameterInspection
     * @noinspection PhpUnused
     */
    public static function get_protection_type( $id, $type = 'post', $meta = null ) : string {
        $delay_restriction_enable = false;
        $page_view_restriction_enable = false;
        $expire_restriction_enable = false;
        switch ( $type ) {
            case 'post':
            case 'elementor':
                //TODO this will need to be updated to account for types of protection on individual elements
                $delay_restriction_enable = (bool) get_post_meta( $id, WC_PPP_SLUG . '_delay_restriction_enable', true );
                $page_view_restriction_enable = (bool) get_post_meta( $id, WC_PPP_SLUG . '_page_view_restriction_enable', true );
                $expire_restriction_enable = (bool) get_post_meta( $id, WC_PPP_SLUG . '_expire_restriction_enable', true );
                break;
        }
        $protection = 'standard';
        // Mirrors get_product_ids_by_post_id(): an unlicensed install has no
        // handler for these types, so it must report standard protection.
        if ( !self::can_use_premium_protection() ) {
            return $protection;
        }
        if ( $delay_restriction_enable ) {
            $protection = 'delay';
        }
        if ( $page_view_restriction_enable ) {
            $protection = 'page-view';
        }
        if ( $expire_restriction_enable ) {
            $protection = 'expire';
        }
        return $protection;
    }

    /**
     * Whether the current user may view the given post.
     *
     * can_user_view_content() already answers "can this user see the content",
     * with the same polarity as this method — it does NOT return "should the
     * paywall be shown", which an earlier version of this docblock claimed.
     * The returned value has always been correct; the log line beneath it used
     * to print the negation, so a debug log could read "RETURNING - false" for
     * a user who was plainly reading the content. That cost real time on a
     * support ticket, so the value is now resolved once and both the log and
     * the return use it.
     *
     * Resolving it once also matters on its own: this used to call
     * can_user_view_content() twice — once to build the log message and once to
     * return — so every protected page ran the whole check chain, including the
     * purchase-date queries and any subscription lookups, twice.
     *
     * @param int|null $post_id         Post to check. Defaults to the current post.
     * @param bool     $track_page_view Whether this call should count as a page view.
     *
     * @return bool True when the user may view the content.
     */
    public static function has_access( $post_id = null, bool $track_page_view = true ) : bool {
        $restrict = new Woocommerce_Pay_Per_Post_Restrict_Content($post_id, $track_page_view);
        $has_access = $restrict->can_user_view_content();
        Woocommerce_Pay_Per_Post_Helper::logger( 'Post ID: ' . get_the_ID() . ' - Woocommerce_Pay_Per_Post_Helper::has_access  - RETURNING - ' . var_export( $has_access, true ) );
        return $has_access;
    }

    /**
     * Checks if the user has purchased a product associated with the post
     *
     * @param null $post_id
     * @param bool $track_page_view
     *
     * @return bool
     */
    public static function has_purchased( $post_id = null, bool $track_page_view = true ) : bool {
        $restrict = new Woocommerce_Pay_Per_Post_Restrict_Content($post_id, $track_page_view);
        return $restrict->check_if_purchased();
    }

    /**
     * @return string
     */
    public static function get_no_access_content() : string {
        $restrict = new Woocommerce_Pay_Per_Post_Restrict_Content();
        return $restrict->show_paywall( get_the_content() );
    }

    /**
     * @param $type
     *
     * @return bool|string
     */
    public static function protection_display_icon( $type ) {
        if ( in_array( $type, self::$protection_types, true ) ) {
            switch ( $type ) {
                case 'standard':
                    return '<span class="dashicons dashicons-post-status" title="Standard Purchase Protection" style="color:green"></span>';
                case 'delay':
                    return '<span class="dashicons dashicons-clock" title="Delay Protection" style="color:green"></span>';
                case 'page-view':
                    return '<span class="dashicons dashicons-visibility" title="Page View Protection" style="color:green"></span>';
                case 'expire':
                    return '<span class="dashicons dashicons-backup" title="Expiry Protection" style="color:green"></span>';
            }
        }
        return false;
    }

    /**
     * @return Carbon
     */
    public static function current_time() : Carbon {
        return Carbon::now();
    }

    /**
     * @return string
     */
    public static function date_time_format() : string {
        return get_option( 'date_format', true ) . ' ' . get_option( 'time_format', true );
    }

    public static function logger( $message, $context = null ) {
        $logger = new Woocommerce_Pay_Per_Post_Logger();
        if ( !is_array( $context ) ) {
            $context = [];
        }
        $logger->log( $message, $context );
    }

    public static function logger_uri() : string {
        $logger = new Woocommerce_Pay_Per_Post_Logger();
        return $logger->get_log_uri();
    }

    public static function logger_url() : string {
        $logger = new Woocommerce_Pay_Per_Post_Logger();
        return $logger->get_log_url();
    }

    public static function get_protected_post_types() {
        $custom_post_types = get_option( WC_PPP_SLUG . '_custom_post_types', [] );
        return ( empty( $custom_post_types ) ? [] : $custom_post_types );
    }

    public static function get_protected_post_types_args() : array {
        $custom_post_types = self::get_protected_post_types();
        if ( !is_array( $custom_post_types ) ) {
            $custom_post_types = explode( ',', $custom_post_types );
        }
        return [
            'orderby'     => 'post_date',
            'order'       => 'DESC',
            'nopaging'    => true,
            'post_status' => 'publish',
            'post_type'   => $custom_post_types,
        ];
    }

    /**
     * The meta query that makes a post "protected" for the protected posts
     * lists: it has stored protection IDs, or (Premium, with Elementor) an
     * Elementor element with protection turned on. get_protected_posts() and
     * the Protected Content screen share it, so they list the same posts.
     *
     * @return array A WP_Query meta_query.
     */
    public static function get_protected_posts_meta_query() : array {
        $meta_query = [[
            'key'     => WC_PPP_SLUG . '_product_ids',
            'value'   => '',
            'compare' => '!=',
        ]];
        return $meta_query;
    }

    public static function get_protected_posts( $args = null, $transient = 'posts', $bypass_transient = false ) : array {
        $transient = WC_PPP_SLUG . '_' . $transient;
        if ( is_null( $args ) ) {
            $args = self::get_protected_post_types_args();
            $args['meta_query'] = self::get_protected_posts_meta_query();
        }
        if ( $bypass_transient ) {
            Woocommerce_Pay_Per_Post_Helper::logger( 'Woocommerce_Pay_Per_Post_Helper::get_protected_posts() returning posts BYPASSING TRANSIENT' );
            $ppp_posts = get_posts( $args );
        } else {
            $ppp_posts = get_transient( $transient );
            Woocommerce_Pay_Per_Post_Helper::logger( 'Woocommerce_Pay_Per_Post_Helper::get_protected_posts() returning posts FROM ' . $transient . ' TRANSIENT' );
            if ( false === $ppp_posts ) {
                $ppp_posts = get_posts( $args );
                Woocommerce_Pay_Per_Post_Helper::logger( 'Woocommerce_Pay_Per_Post_Helper::get_protected_posts() returning posts FROM REGENERATING ' . $transient . ' TRANSIENT' );
                set_transient( $transient, $ppp_posts, apply_filters( 'wc_pay_per_post_posts_transient_time', HOUR_IN_SECONDS ) );
            }
        }
        return (array) $ppp_posts;
    }

    public static function get_posts_associated_with_product_id( $product_id ) : array {
        Woocommerce_Pay_Per_Post_Helper::logger( 'Product ID: ' . $product_id . ' - Woocommerce_Pay_Per_Post_Helper::get_posts_associated_with_product_id() Called' );
        $args = self::get_protected_post_types_args();
        $args['meta_query'] = [[
            'key'     => WC_PPP_SLUG . '_product_ids',
            'value'   => sprintf( '^%1$s$|s:%2$u:"%1$s";', $product_id, strlen( $product_id ) ),
            'compare' => 'REGEXP',
        ]];
        $posts = get_posts( apply_filters( 'wc_pay_per_post_woocommerce_email_args', $args ) );
        $protected_content = [];
        if ( $posts ) {
            $interface = 'default';
            switch ( $interface ) {
                case "default":
                default:
                    $protected_content = self::get_posts_associated_with_product_id_interface( $posts );
                    break;
                case "polylang":
                    // The interface is only ever set to polylang inside the
                    // premium block above, so the free build cannot reach this
                    // today. Guard it anyway: the handler is stripped there, and
                    // nothing else stops a future edit from setting the
                    // interface outside that block.
                    if ( !self::can_use_premium_protection() ) {
                        $protected_content = self::get_posts_associated_with_product_id_interface( $posts );
                        break;
                    }
                    $protected_content = self::get_posts_associated_with_product_id_interface_polylang__premium_only( $posts );
                    break;
            }
        }
        return $protected_content;
    }

    /**
     * Whether the premium protection types — delay, page-view and expire — may
     * run on this install.
     *
     * The protection type is derived from post meta, and meta survives a
     * downgrade. Without this gate a site that once held a licence keeps
     * classifying posts as delay/page-view/expire after dropping to the free
     * build, and the free build has no code left to service them: Freemius
     * strips every `__premium_only` method from the generated free version but
     * leaves the call sites intact, so `check_if_has_access()` reaches a
     * `Call to undefined method` fatal. Because `the_content` runs on archives
     * too, one such post takes down every page that lists it.
     *
     * The `is__premium_only()` nesting is deliberate, and is the opposite of
     * the choice made in `get_last_purchase_date()`. Freemius deletes the body
     * of these blocks, so in the free build this method reduces to a bare
     * `return false;` — which is exactly what is wanted here. A guard that has
     * to *survive* stripping must use `can_use_premium_code()` alone. See
     * `.claude/RELEASE_PROCESS.md`, "Freemius stripping".
     *
     * Unlicensed installs fall back to standard protection: the post stays
     * locked and access still requires the purchase. Falling back to
     * unprotected would publish paid content the moment a licence lapsed.
     *
     * @return bool True when delay/page-view/expire protection may be applied.
     */
    public static function can_use_premium_protection() : bool {
        return false;
    }

    public static function can_use_woocommerce_memberships() : bool {
        return false;
    }

    /**
     * Whether the WooCommerce Subscriptions integration should be used.
     *
     * Detection is by class, not by active plugin, because is_plugin_active()
     * is not available on the frontend. The side effect is that any plugin
     * shipping a WooCommerce Subscriptions compatibility layer — Flexible
     * Subscriptions by WP Desk declares its own WC_Subscriptions_Product, for
     * example — is indistinguishable from the real thing here.
     *
     * That matters because subscription products take a stricter path: in
     * Restrict_Content::check_if_purchased() a subscription product requires
     * BOTH a purchase and an active subscription, and is never fallen back to
     * as a regular purchase. On a store where subscription records are not
     * reliably created, customers who genuinely paid can be locked out, and
     * workarounds that fake the subscription answer end up disabling expire and
     * page-view protection as a side effect.
     *
     * Such a store can opt out and have PPP treat those products as ordinary
     * purchases, so purchase-plus-expiry behaves as configured: untick "Use
     * WooCommerce Subscriptions" under Settings > Integrations, or
     *
     *     add_filter( 'wc_pay_per_post_can_use_woocommerce_subscriptions', '__return_false' );
     *
     * The filter receives the setting and wins over it.
     *
     * @return bool
     */
    public static function can_use_woocommerce_subscriptions() : bool {
        $can_use = false;
        return (bool) apply_filters( 'wc_pay_per_post_can_use_woocommerce_subscriptions', $can_use );
    }

    /**
     * Whether the Paid Memberships Pro integration runs: Premium, and Paid
     * Memberships Pro active. Turn it off with:
     *
     *     add_filter( 'wc_pay_per_post_can_use_paid_memberships_pro', '__return_false' );
     *
     * The filter cannot turn it on without Premium.
     *
     * @return bool
     */
    public static function can_use_paid_membership_pro() : bool {
        return false;
    }

    public static function can_use_elementor() : bool {
        return false;
    }

    public static function md_array_diff( $arraya, $arrayb ) {
        foreach ( $arraya as $keya => $valuea ) {
            if ( in_array( $valuea, $arrayb ) ) {
                unset($arraya[$keya]);
            }
        }
        return $arraya;
    }

    public static function move_to_top( &$array, $key ) {
        $temp = [
            $key => $array[$key],
        ];
        unset($array[$key]);
        $array = $temp + $array;
    }

    protected static function get_posts_associated_with_product_id_interface( $posts ) : array {
        $protected_content = [];
        foreach ( $posts as $post ) {
            $protected_content[] = [
                'post_id'    => $post->ID,
                'post_title' => $post->post_title,
                'post_url'   => get_permalink( $post->ID ),
            ];
        }
        return $protected_content;
    }

    public static function locate_template( $template_name, $template_path = '', $default_path = '' ) {
        if ( !$template_path ) {
            $template_path = WC_PPP_TEMPLATE_PATH;
        }
        if ( !$default_path ) {
            $default_path = WC_PPP_PATH;
        }
        // Look within passed path within the theme - this is priority.
        $template = locate_template( [trailingslashit( $template_path ) . $template_name, $template_name] );
        // Get default template/.
        if ( !$template ) {
            $template = $default_path . $template_name;
        }
        // Return what we found.
        return apply_filters(
            'wc_pay_for_post_locate_template',
            $template,
            $template_name,
            $template_path
        );
    }

    public static function get_all_products() : array {
        $args = [
            'post_type'   => ['product'],
            'orderby'     => 'title',
            'post_status' => 'publish',
            'order'       => 'ASC',
            'nopaging'    => true,
        ];
        $products = get_posts( apply_filters( 'wc_pay_per_post_all_product_args', $args ) );
        $return = [];
        foreach ( $products as $product ) {
            $return[] = [
                'ID'         => $product->ID,
                'post_title' => $product->post_title,
            ];
        }
        return $return;
    }

    public static function get_virtual_products() : array {
        $args = [
            'post_type'   => ['product'],
            'post_status' => 'publish',
            'orderby'     => 'title',
            'order'       => 'ASC',
            'nopaging'    => true,
            'meta_query'  => [
                'relation' => 'OR',
                [
                    'key'     => '_downloadable',
                    'value'   => 'yes',
                    'compare' => '=',
                ],
                [
                    'key'     => '_virtual',
                    'value'   => 'yes',
                    'compare' => '=',
                ],
                [
                    'key'     => '_downloadable',
                    'value'   => '1',
                    'compare' => '=',
                ],
                [
                    'key'     => '_virtual',
                    'value'   => '1',
                    'compare' => '=',
                ],
            ],
        ];
        $products = get_posts( apply_filters( 'wc_pay_per_post_virtual_product_args', $args ) );
        $return = [];
        foreach ( $products as $product ) {
            $return[] = [
                'ID'         => $product->ID,
                'post_title' => $product->post_title,
            ];
        }
        return $return;
    }

    public static function get_products() {
        $only_show_virtual_products = (bool) get_option( WC_PPP_SLUG . '_only_show_virtual_products', false );
        if ( $only_show_virtual_products ) {
            return apply_filters( 'wc_pay_per_post_get_virtual_products', Woocommerce_Pay_Per_Post_Helper::get_virtual_products() );
        } else {
            return apply_filters( 'wc_pay_per_post_get_all_products', Woocommerce_Pay_Per_Post_Helper::get_all_products() );
        }
    }

    /**
     * What the paywall can sell for a post's protection.
     *
     * The stored protection IDs mix WooCommerce products with WooCommerce
     * Memberships plan IDs and Paid Memberships Pro level IDs, which no
     * product shortcode can show. A plan is replaced by the products that grant
     * it, and a level becomes a link to its Paid Memberships Pro checkout.
     * Everything else is kept as it was stored, in order.
     *
     * @param array|string $ids The post's protection IDs.
     *
     * @return array{product_ids: array, unlisted_product_ids: int[], pmpro_levels: object[], has_membership: bool}
     *               unlisted_product_ids are the plans' products that
     *               WooCommerce's [products] shortcode does not show (a
     *               variation, or a product hidden from the shop), which the
     *               paywall links to instead; they are in product_ids too.
     *               pmpro_levels holds only levels open to sign-ups.
     *               has_membership is true when any ID was a plan or a level.
     */
    public static function get_paywall_offer( $ids ) : array {
        $offer = [
            'product_ids'          => [],
            'unlisted_product_ids' => [],
            'pmpro_levels'         => [],
            'has_membership'       => false,
        ];
        $memberships = null;
        if ( self::can_use_woocommerce_memberships() && class_exists( '\\PRAMADILLO\\INTEGRATIONS\\WooCommerceMemberships' ) ) {
            $memberships = new \PRAMADILLO\INTEGRATIONS\WooCommerceMemberships();
        }
        $pmpro = null;
        if ( self::can_use_paid_membership_pro() && class_exists( '\\PRAMADILLO\\INTEGRATIONS\\PaidMembershipsPro' ) ) {
            $pmpro = new \PRAMADILLO\INTEGRATIONS\PaidMembershipsPro();
        }
        $ids = array_filter( (array) $ids, static function ( $id ) {
            return is_scalar( $id ) && '' !== trim( (string) $id );
        } );
        foreach ( $ids as $id ) {
            if ( $memberships && $memberships->is_plan( $id ) ) {
                $offer['has_membership'] = true;
                foreach ( $memberships->get_plan_product_ids( $id ) as $plan_product_id ) {
                    $offer['product_ids'][] = $plan_product_id;
                    if ( !self::product_list_shows( $plan_product_id ) ) {
                        $offer['unlisted_product_ids'][$plan_product_id] = $plan_product_id;
                    }
                }
                continue;
            }
            $level = ( $pmpro ? $pmpro->get_level( $id ) : null );
            if ( $level ) {
                $offer['has_membership'] = true;
                if ( $pmpro->allows_signups( $level ) ) {
                    $offer['pmpro_levels'][] = $level;
                }
                // Level IDs are not post IDs, so one can equal a real
                // product's ID; only then is it also sold as a product.
                if ( !wc_get_product( $id ) ) {
                    continue;
                }
            }
            $offer['product_ids'][] = $id;
        }
        // A product can be stored directly and also grant a plan.
        $unique = [];
        foreach ( $offer['product_ids'] as $id ) {
            $unique[(int) $id] = $id;
        }
        $offer['product_ids'] = array_values( $unique );
        $offer['unlisted_product_ids'] = array_values( $offer['unlisted_product_ids'] );
        return $offer;
    }

    /**
     * Whether WooCommerce's [products] shortcode shows a product. It lists
     * only products that appear in the shop, so not a variation, and not a
     * product whose catalog visibility is "Search results only" or "Hidden".
     *
     * @param int|string $product_id
     *
     * @return bool
     */
    protected static function product_list_shows( $product_id ) : bool {
        $product = wc_get_product( $product_id );
        return $product && !$product->is_type( 'variation' ) && in_array( $product->get_catalog_visibility(), ['visible', 'catalog'], true );
    }

    /**
     * The paywall's way in for membership protection that the product list
     * cannot show: a checkout link for each Paid Memberships Pro level, a link
     * to each plan product the list leaves out (a variation, or a product
     * hidden from the shop), or, when there is nothing at all to buy, a
     * members-only notice with a login link for visitors who are not logged
     * in.
     *
     * @param array $offer From get_paywall_offer().
     *
     * @return string HTML, empty when the product list already covers it.
     */
    public static function get_paywall_membership_offer( array $offer ) : string {
        $html = '';
        if ( !empty( $offer['pmpro_levels'] ) && class_exists( '\\PRAMADILLO\\INTEGRATIONS\\PaidMembershipsPro' ) ) {
            $pmpro = new \PRAMADILLO\INTEGRATIONS\PaidMembershipsPro();
            $html .= '<ul class="wc-ppp-membership-levels">';
            foreach ( $offer['pmpro_levels'] as $level ) {
                /* translators: %s: membership level name */
                $html .= '<li><a href="' . esc_url( $pmpro->get_checkout_url( $level->id ) ) . '">' . esc_html( sprintf( __( 'Join %s', 'wc_pay_per_post' ), $level->name ) ) . '</a></li>';
            }
            $html .= '</ul>';
        }
        $unlisted = [];
        foreach ( (array) ($offer['unlisted_product_ids'] ?? []) as $product_id ) {
            $product = wc_get_product( $product_id );
            if ( $product ) {
                /* translators: %s: product name, such as "Course - Gold" */
                $unlisted[] = '<li><a href="' . esc_url( $product->get_permalink() ) . '">' . esc_html( sprintf( __( 'Buy %s', 'wc_pay_per_post' ), $product->get_name() ) ) . '</a></li>';
            }
        }
        if ( $unlisted ) {
            $html .= '<ul class="wc-ppp-membership-products">' . implode( '', $unlisted ) . '</ul>';
        }
        if ( '' === $html && $offer['has_membership'] && empty( $offer['product_ids'] ) ) {
            $html .= '<p class="wc-ppp-members-only">' . esc_html__( 'This content is available to members only.', 'wc_pay_per_post' );
            if ( !is_user_logged_in() ) {
                $html .= ' <a href="' . esc_url( wp_login_url( get_permalink() ) ) . '">' . esc_html__( 'Already a member? Log in.', 'wc_pay_per_post' ) . '</a>';
            }
            $html .= '</p>';
        }
        return (string) apply_filters( 'wc_pay_per_post_paywall_membership_offer', $html, $offer );
    }

    /**
     * Remove every shortcode that mentions one of the tokens, for when they
     * would expand to nothing: [products ids=''] lists the whole catalogue.
     *
     * @param string   $content
     * @param string[] $tokens
     *
     * @return string
     */
    protected static function remove_shortcodes_using_tokens( string $content, array $tokens ) : string {
        return preg_replace_callback( '/' . get_shortcode_regex() . '/', static function ( $match ) use($tokens) {
            $uses = static function ( string $text ) use($tokens) : bool {
                foreach ( $tokens as $token ) {
                    if ( false !== strpos( $text, $token ) ) {
                        return true;
                    }
                }
                return false;
            };
            // [[escaped]] is text, not a shortcode.
            if ( '[' === $match[1] && ']' === $match[6] ) {
                return $match[0];
            }
            if ( $uses( $match[3] ) ) {
                return '';
            }
            // An enclosing shortcode, such as a members-only wrapper or a
            // page builder's row, keeps its own text: only the shortcodes
            // inside it that use a token go.
            if ( isset( $match[5] ) && '' !== $match[5] && $uses( $match[5] ) ) {
                return '[' . $match[2] . $match[3] . ']' . self::remove_shortcodes_using_tokens( $match[5], $tokens ) . '[/' . $match[2] . ']';
            }
            return $match[0];
        }, $content );
    }

    public static function replace_tokens( $paywall_content, $product_ids, $unfiltered_content = null ) {
        if ( is_archive() || is_home() || is_front_page() ) {
            //Get Product IDs
            //TODO this needs to be updated to newest methods that include Elementor
            $product_ids = get_post_meta( get_the_ID(), WC_PPP_SLUG . '_product_ids', true );
        }
        $offer = self::get_paywall_offer( $product_ids );
        $product_ids = $offer['product_ids'];
        if ( empty( $product_ids ) ) {
            $return_content = self::remove_shortcodes_using_tokens( (string) $paywall_content, ['{{product_id}}', '{{parent_id}}'] );
            $return_content = str_replace( ['{{product_id}}', '{{parent_id}}'], '', $return_content );
        } else {
            $return_content = str_replace( '{{product_id}}', implode( ',', $product_ids ), $paywall_content );
            $return_content = str_replace( '{{parent_id}}', wp_get_post_parent_id( reset( $product_ids ) ), $return_content );
        }
        // {{memberships}} places the membership way in; without it, the way
        // in follows the message so a message written before it existed
        // still offers one.
        $membership_offer = self::get_paywall_membership_offer( $offer );
        if ( false !== strpos( $return_content, '{{memberships}}' ) ) {
            $return_content = str_replace( '{{memberships}}', $membership_offer, $return_content );
        } elseif ( '' !== $membership_offer ) {
            $return_content .= "\n" . $membership_offer;
        }
        return $return_content;
    }

    /**
     * @param $method
     *
     * @return string
     */
    public static function carbon_add_method( $method ) : string {
        $methods = [
            'minute' => 'addMinutes',
            'hour'   => 'addHours',
            'day'    => 'addDays',
            'week'   => 'addWeeks',
            'month'  => 'addMonths',
            'year'   => 'addYears',
        ];
        if ( "" === $method ) {
            return $methods['day'];
        }
        return $methods[$method];
    }

    /**
     * @param $method
     *
     * @return string
     */
    public static function carbon_diff_method( $method ) : string {
        $methods = [
            'minute' => 'diffInMinutes',
            'hour'   => 'diffInHours',
            'day'    => 'diffInDays',
            'week'   => 'diffInWeeks',
            'month'  => 'diffInMonths',
            'year'   => 'diffInYears',
        ];
        if ( "" === $method ) {
            return $methods['day'];
        }
        return $methods[$method];
    }

    /** @noinspection PhpUnused */
    public static function recursive_array_search( $needle, array $haystack ) {
        $matches = [];
        $iterator = new RecursiveArrayIterator($haystack);
        $recursive = new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::SELF_FIRST);
        foreach ( $recursive as $key => $value ) {
            if ( $key === $needle ) {
                $matches[] = $value;
            }
        }
        $return = [];
        foreach ( $matches as $match ) {
            $return = array_merge( $return, $match );
        }
        return $return ?? false;
    }

    /**
     * Updated Function for 3.1.0
     *
     * @param $post_id
     *
     * @return array
     */
    public static function get_elementor_product_ids( $post_id ) : array {
        Woocommerce_Pay_Per_Post_Helper::logger( 'Post ID: ' . $post_id . ' - Woocommerce_Pay_Per_Post_Helper::get_elementor_product_ids() Called' );
        $product_ids = [];
        $elementor_data = get_post_meta( $post_id, '_elementor_data', true );
        $check_string = $elementor_data;
        if ( is_array( $elementor_data ) ) {
            $check_string = json_encode( $elementor_data );
        }
        if ( !is_null( $elementor_data ) && false !== strpos( $check_string, '"wc_pay_per_post_enable":"yes"' ) ) {
            Woocommerce_Pay_Per_Post_Helper::logger( 'Post ID: ' . $post_id . ' - Woocommerce_Pay_Per_Post_Helper::get_elementor_product_ids() - wc_pay_per_post_enable":"yes" FOUND' );
            //Uncomment this for full Elementor Data Debug.  Log file grows QUICK, so use sparingly.
            //Woocommerce_Pay_Per_Post_Helper::logger('Post ID: ' . $post_id . ' - Woocommerce_Pay_Per_Post_Helper::get_elementor_product_ids() - Elementor Data ' . print_r($elementor_data, true));
            $document = Plugin::$instance->documents->get( $post_id );
            //Loop Through all Elementor elements to check for protected elements.
            Plugin::$instance->db->iterate_data( $document->get_elements_data(), function ( $element_data ) use(&$product_ids, $post_id) {
                $element = Plugin::$instance->elements_manager->create_element_instance( $element_data );
                //Uncomment this for full Elementor Data Debug.  Log file grows QUICK, so use sparingly.
                //Woocommerce_Pay_Per_Post_Helper::logger('Post ID: ' . $post_id . ' - Woocommerce_Pay_Per_Post_Helper::get_elementor_product_ids() - $element ' . print_r($element, true));
                // If the widget/element isn't exist, like a plugin that creates a widget but deactivated
                if ( !$element ) {
                    return null;
                }
                $data = $element->get_data( 'settings' );
                //Uncomment this for full Elementor Data Debug.  Log file grows QUICK, so use sparingly.
                //Woocommerce_Pay_Per_Post_Helper::logger('Post ID: ' . $post_id . ' - Woocommerce_Pay_Per_Post_Helper::get_elementor_product_ids() - $data = $element->get_data(); ' . print_r($data, true));
                if ( isset( $data['wc_pay_per_post_enable'] ) && $data['wc_pay_per_post_enable'] === 'yes' ) {
                    if ( isset( $data['wc_pay_per_post_select_products'] ) && (array) count( $data['wc_pay_per_post_select_products'] ) > 0 ) {
                        $product_ids = $data['wc_pay_per_post_select_products'];
                    }
                }
            } );
            Woocommerce_Pay_Per_Post_Helper::logger( 'Post ID: ' . $post_id . ' - Woocommerce_Pay_Per_Post_Helper::get_elementor_product_ids() - ACTUAL $product_ids = ' . print_r( $product_ids, true ) );
        } else {
            Woocommerce_Pay_Per_Post_Helper::logger( 'Post ID: ' . $post_id . ' - Woocommerce_Pay_Per_Post_Helper::get_elementor_product_ids() - wc_pay_per_post_enable":"yes" NOT FOUND' );
        }
        return $product_ids;
    }

    public static function get_post_types() : array {
        $user_included_post_types = get_option( WC_PPP_SLUG . '_custom_post_types', [] );
        if ( empty( $user_included_post_types ) ) {
            $user_included_post_types = [];
        }
        return (array) $user_included_post_types;
    }

    /**
     * Roles that read every protected post without buying it (premium): the
     * "Other roles that skip the paywall" setting, then the
     * wc_pay_for_post_allowed_roles filter, which receives the setting.
     *
     * @return string[] Role slugs, as values.
     */
    public static function paywall_bypass_roles() : array {
        if ( !self::can_use_premium_protection() ) {
            return [];
        }
        $roles = self::sanitize_roles( get_option( WC_PPP_SLUG . '_paywall_bypass_roles', [] ) );
        return array_filter( (array) apply_filters( 'wc_pay_for_post_allowed_roles', $roles ), 'is_scalar' );
    }

    /**
     * Roles that see the Pay For Post panel or box in the editor: the "Who
     * can set protection" setting, then the
     * wc_pay_per_post_allowed_roles_for_meta_box filter. Empty means everyone
     * who can edit the post.
     *
     * The filter has always taken role slugs as keys, so the setting is passed
     * to it that way.
     *
     * @return array<string, true> Role slug => true.
     */
    public static function editor_panel_roles() : array {
        // A ticked role that has since been removed from the site restricts
        // nothing: if it were the only one, nobody would see the panel.
        $roles = array_intersect( self::sanitize_roles( get_option( WC_PPP_SLUG . '_editor_panel_roles', [] ) ), array_keys( wp_roles()->roles ) );
        $roles = array_fill_keys( $roles, true );
        return (array) apply_filters( 'wc_pay_per_post_allowed_roles_for_meta_box', $roles );
    }

    /**
     * A protection type's name, as the post editor box shows it: Buy once,
     * Free at first, Limited views or Time limited. Admin screens that name a
     * post's type use it, so the names match the editor everywhere.
     *
     * @param string $type standard, delay, page-view or expire.
     *
     * @return string The name, or the type itself if it is not one of the four.
     */
    public static function protection_type_label( string $type ) : string {
        $labels = [
            'standard'  => __( 'Buy once', 'wc_pay_per_post' ),
            'delay'     => __( 'Free at first', 'wc_pay_per_post' ),
            'page-view' => __( 'Limited views', 'wc_pay_per_post' ),
            'expire'    => __( 'Time limited', 'wc_pay_per_post' ),
        ];
        return $labels[$type] ?? $type;
    }

    /**
     * A list of role slugs from a settings form or the database. Unticking
     * every role stores an empty string.
     *
     * @param mixed $roles Submitted or stored value.
     *
     * @return string[]
     */
    public static function sanitize_roles( $roles ) : array {
        // A role registered on the site is kept exactly as it is spelled: a
        // plugin can register "Teacher", and sanitize_key() would store
        // "teacher", which no user has.
        $known = array_keys( wp_roles()->roles );
        $clean = [];
        foreach ( (array) $roles as $role ) {
            if ( !is_scalar( $role ) ) {
                continue;
            }
            $role = (string) $role;
            $clean[] = ( in_array( $role, $known, true ) ? $role : sanitize_key( $role ) );
        }
        return array_values( array_unique( array_filter( $clean, 'strlen' ) ) );
    }

    /**
     * Where the purchased content links on an order line show (premium):
     * "everywhere", "not_in_emails" or "off".
     *
     * @return string
     */
    public static function order_links_mode() : string {
        if ( !self::can_use_premium_protection() ) {
            return 'everywhere';
        }
        return self::sanitize_order_links_mode( get_option( WC_PPP_SLUG . '_order_links', 'everywhere' ) );
    }

    /**
     * A stored or submitted order_links_mode() value.
     *
     * @param mixed $mode Submitted or stored value.
     *
     * @return string One of order_links_mode()'s values; anything else is "everywhere".
     */
    public static function sanitize_order_links_mode( $mode ) : string {
        return ( in_array( $mode, ['everywhere', 'not_in_emails', 'off'], true ) ? $mode : 'everywhere' );
    }

    /**
     * Whether order lines list the content their product unlocks. The
     * wc_pay_per_post_show_item_meta filter receives the setting.
     *
     * Read when an order line is shown, not while the plugin loads, so the
     * filter works from a theme too.
     *
     * @return bool
     */
    public static function show_order_links() : bool {
        return (bool) apply_filters( 'wc_pay_per_post_show_item_meta', 'off' !== self::order_links_mode() );
    }

    /**
     * Whether those links are left out of emails. The
     * wc_pay_per_post_hide_item_meta_in_email filter receives the setting.
     *
     * @return bool
     */
    public static function hide_order_links_in_email() : bool {
        return (bool) apply_filters( 'wc_pay_per_post_hide_item_meta_in_email', 'not_in_emails' === self::order_links_mode() );
    }

    /**
     * Whether an email's order details are being rendered: WooCommerce's
     * order emails, or WooCommerce Subscriptions' own.
     *
     * @return bool
     */
    public static function is_rendering_email() : bool {
        return doing_action( 'woocommerce_email_order_details' ) || doing_action( 'woocommerce_subscriptions_email_order_details' );
    }

    /**
     * A text a setting can replace, escaped for HTML. Empty means the plugin's
     * own wording, which stays translatable.
     *
     * @param string $option   Option name without the plugin prefix.
     * @param string $fallback The plugin's wording, already escaped.
     *
     * @return string
     */
    public static function setting_text( string $option, string $fallback ) : string {
        $text = ( self::can_use_premium_protection() ? trim( (string) get_option( WC_PPP_SLUG . '_' . $option, '' ) ) : '' );
        return ( '' === $text ? $fallback : esc_html( $text ) );
    }

    public static function allowed_roles_for_meta_box() : bool {
        $allowed_roles = self::editor_panel_roles();
        // A network's super admins may have no role on the site at all.
        if ( is_multisite() && is_super_admin() ) {
            return true;
        }
        if ( count( $allowed_roles ) == 0 ) {
            $allow_meta = true;
        } else {
            $allow_meta = false;
            $user = wp_get_current_user();
            if ( !is_null( $user ) ) {
                $user_roles = $user->roles;
                foreach ( $user_roles as $role ) {
                    if ( array_key_exists( $role, $allowed_roles ) ) {
                        $allow_meta = true;
                    }
                }
            }
        }
        return $allow_meta;
    }

    /**
     * @return bool
     */
    public static function is_an_allowed_protected_post_type() : bool {
        return in_array( get_post_type(), self::get_post_types() );
    }

    /**
     * @param null $post_id
     *
     * @return array
     */
    public static function get_product_ids_by_post_id( $post_id = null ) : array {
        $bypass_allowed_protected_post_types = false;
        if ( is_null( $post_id ) ) {
            $bypass_allowed_protected_post_types = true;
            $post_id = get_the_ID();
        }
        Woocommerce_Pay_Per_Post_Helper::logger( 'Post ID: ' . $post_id . ' - Woocommerce_Pay_Per_Post_Helper::get_product_ids_by_post_id() Called' );
        if ( $bypass_allowed_protected_post_types && !self::is_an_allowed_protected_post_type() ) {
            $product_ids = [
                'product_ids'         => [],
                'section_product_ids' => [],
                'is_protected'        => false,
                'origin_type'         => '',
                'protection_type'     => '',
                'post_id'             => (int) $post_id,
            ];
            Woocommerce_Pay_Per_Post_Helper::logger( 'Post ID: ' . $post_id . ' - Woocommerce_Pay_Per_Post_Helper::get_product_ids_by_post_id() NOT in Protected Post Types Array.  Bailing.' );
            return $product_ids;
        }
        if ( self::can_use_elementor() ) {
            if ( Plugin::$instance->editor->is_edit_mode() || Plugin::$instance->preview->is_preview_mode() ) {
                $product_ids = [
                    'product_ids'         => '',
                    'section_product_ids' => '',
                    'is_protected'        => false,
                    'origin_type'         => '',
                    'protection_type'     => '',
                    'post_id'             => (int) $post_id,
                ];
                Woocommerce_Pay_Per_Post_Helper::logger( 'Post ID: ' . $post_id . ' - Woocommerce_Pay_Per_Post_Helper::get_product_ids_by_post_id() In Elementor Edit/Preview Mode.  Bailing.' );
                return $product_ids;
            }
        }
        $origin_type = 'post';
        $protection_type = '';
        $is_protected = false;
        $delay_restriction_enable = (bool) get_post_meta( $post_id, WC_PPP_SLUG . '_delay_restriction_enable', true );
        $page_view_restriction_enable = (bool) get_post_meta( $post_id, WC_PPP_SLUG . '_page_view_restriction_enable', true );
        $expire_restriction_enable = (bool) get_post_meta( $post_id, WC_PPP_SLUG . '_expire_restriction_enable', true );
        $standard_product_ids = get_post_meta( $post_id, WC_PPP_SLUG . '_product_ids', true );
        if ( is_array( $standard_product_ids ) ) {
            Woocommerce_Pay_Per_Post_Helper::logger( 'Post ID: ' . $post_id . ' - Woocommerce_Pay_Per_Post_Helper::get_product_ids_by_post_id() Standard Post Product IDs ', $standard_product_ids );
        } else {
            Woocommerce_Pay_Per_Post_Helper::logger( 'Post ID: ' . $post_id . ' - Woocommerce_Pay_Per_Post_Helper::get_product_ids_by_post_id() Standard Post Product IDs ' . print_r( $standard_product_ids, true ) );
        }
        if ( is_array( $standard_product_ids ) && count( $standard_product_ids ) > 0 && !empty( $standard_product_ids[0] ) ) {
            Woocommerce_Pay_Per_Post_Helper::logger( 'Post ID: ' . $post_id . ' - Woocommerce_Pay_Per_Post_Helper::get_product_ids_by_post_id() Product IDs $selected array contains items' );
            $is_protected = true;
            // Without a licence the delay/page-view/expire handlers do not
            // exist in the build, so the post must not be classified into one.
            // See can_use_premium_protection().
            $can_use_premium_protection = self::can_use_premium_protection();
            if ( $delay_restriction_enable && $can_use_premium_protection ) {
                $protection_type = 'delay';
                Woocommerce_Pay_Per_Post_Helper::logger( 'Post ID: ' . $post_id . ' - Woocommerce_Pay_Per_Post_Helper::get_product_ids_by_post_id() Protection Type: DELAY' );
            } else {
                if ( $page_view_restriction_enable && $can_use_premium_protection ) {
                    $protection_type = 'page-view';
                    Woocommerce_Pay_Per_Post_Helper::logger( 'Post ID: ' . $post_id . ' - Woocommerce_Pay_Per_Post_Helper::get_product_ids_by_post_id() Protection Type: PAGE VIEW' );
                } else {
                    if ( $expire_restriction_enable && $can_use_premium_protection ) {
                        $protection_type = 'expire';
                        Woocommerce_Pay_Per_Post_Helper::logger( 'Post ID: ' . $post_id . ' - Woocommerce_Pay_Per_Post_Helper::get_product_ids_by_post_id() Protection Type: EXPIRE' );
                    } else {
                        $protection_type = 'standard';
                        if ( !$can_use_premium_protection && ($delay_restriction_enable || $page_view_restriction_enable || $expire_restriction_enable) ) {
                            Woocommerce_Pay_Per_Post_Helper::logger( 'Post ID: ' . $post_id . ' - Woocommerce_Pay_Per_Post_Helper::get_product_ids_by_post_id() Premium protection configured but no licence — falling back to STANDARD' );
                        }
                        Woocommerce_Pay_Per_Post_Helper::logger( 'Post ID: ' . $post_id . ' - Woocommerce_Pay_Per_Post_Helper::get_product_ids_by_post_id() Protection Type: STANDARD' );
                    }
                }
            }
        }
        $elementor_product_ids = [];
        $return = [
            'product_ids'         => $standard_product_ids,
            'section_product_ids' => $elementor_product_ids,
            'is_protected'        => $is_protected,
            'origin_type'         => $origin_type,
            'protection_type'     => $protection_type,
            'post_id'             => (int) $post_id,
        ];
        Woocommerce_Pay_Per_Post_Helper::logger( 'Post ID: ' . $post_id . ' - Woocommerce_Pay_Per_Post_Helper::get_product_ids_by_post_id() Returned Product IDs array', $return );
        return $return;
    }

    /**
     * Determines if HOPS storage is enabled for WooCommerce
     * @return bool
     */
    public static function hops_enabled() {
        return OrderUtil::custom_orders_table_usage_is_enabled();
    }

    /**
     * Whether a request for $post_id counts as wp-admin, where the paywall is not
     * applied.
     *
     * is_admin() is true for every admin-ajax.php request, logged in or not, and
     * themes and plugins load posts for visitors over it (load more, infinite
     * scroll, quick view). So an AJAX request counts only for a user who can edit
     * the post, as when a page builder renders its editor preview. REST requests
     * never count.
     *
     * @param int|null $post_id
     *
     * @return bool
     */
    public static function is_wp_admin_request( $post_id ) : bool {
        if ( !is_admin() ) {
            return false;
        }
        $doing_ajax = ( function_exists( 'wp_doing_ajax' ) ? wp_doing_ajax() : defined( 'DOING_AJAX' ) && DOING_AJAX );
        return !$doing_ajax || $post_id && current_user_can( 'edit_post', $post_id );
    }

    /**
     * @return bool
     * @deprecated 3.3.0 Use Woocommerce_Pay_Per_Post_Helper::is_wp_admin_request(). The plugin no longer calls this.
     */
    public static function is_admin_request() {
        _deprecated_function( __METHOD__, '3.3.0', 'Woocommerce_Pay_Per_Post_Helper::is_wp_admin_request()' );
        return self::is_wp_admin_request( get_the_ID() );
    }

    public static function get_last_purchase_date( $post_id, $user_id = null ) {
        Woocommerce_Pay_Per_Post_Helper::logger( 'Post ID: ' . get_the_ID() . ' - Woocommerce_Pay_Per_Post_Helper/get_last_purchase_date - Called' );
        // This feature requires premium
        if ( !wcppp_freemius()->is__premium_only() || !wcppp_freemius()->can_use_premium_code() ) {
            return null;
        }
        if ( empty( $user_id ) ) {
            $user_id = get_current_user_id();
        }
        // Call the secure version from Restrict_Content class
        return \PRAMADILLO\Woocommerce_Pay_Per_Post_Restrict_Content::get_last_purchase_date__premium_only( $post_id, $user_id );
    }

    public static function customer_has_purchased_product( $user_email, $user_id, $product_id_or_variation_id ) {
        if ( empty( $product_id_or_variation_id ) || empty( $user_id ) ) {
            return false;
        }
        // Use WooCommerce's built-in function for versions outside 9.6.0 - 9.6.1
        if ( !defined( 'WC_VERSION' ) || version_compare( WC_VERSION, '9.6.0', '<' ) || version_compare( WC_VERSION, '9.6.1', '>' ) ) {
            return wc_customer_bought_product( $user_email, $user_id, $product_id_or_variation_id );
        }
        // If WooCommerce is specifically 9.6.0 or 9.6.1, use custom SQL query
        global $wpdb;
        $user_id = absint( $user_id );
        $product_id_or_variation_id = absint( $product_id_or_variation_id );
        // Get paid order statuses
        $statuses = array_map( fn( $status ) => "wc-{$status}", wc_get_is_paid_statuses() );
        $status_sql = "'" . implode( "','", $statuses ) . "'";
        // Preformat for SQL
        if ( \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled() ) {
            // HOPS enabled - Query modern order tables
            $order_table = \Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore::get_orders_table_name();
            $query = $wpdb->prepare(
                "\n        SELECT COUNT(*)\n        FROM {$wpdb->prefix}wc_order_product_lookup\n        LEFT JOIN {$order_table} AS orders ON orders.id={$wpdb->prefix}wc_order_product_lookup.order_id\n        LEFT JOIN {$wpdb->prefix}wc_customer_lookup ON {$wpdb->prefix}wc_customer_lookup.customer_id={$wpdb->prefix}wc_order_product_lookup.customer_id\n        WHERE \n        {$wpdb->prefix}wc_customer_lookup.user_id= %d \n        AND ({$wpdb->prefix}wc_order_product_lookup.product_id = %d OR {$wpdb->prefix}wc_order_product_lookup.variation_id = %d)\n        AND orders.status IN ({$status_sql})\n        ",
                $user_id,
                $product_id_or_variation_id,
                $product_id_or_variation_id
            );
        } else {
            // Legacy WooCommerce - Query posts/meta tables
            $query = $wpdb->prepare( "\n        SELECT COUNT(*) \n        FROM {$wpdb->prefix}posts AS orders\n        INNER JOIN {$wpdb->prefix}postmeta AS meta ON orders.ID = meta.post_id\n        INNER JOIN {$wpdb->prefix}woocommerce_order_items AS items ON orders.ID = items.order_id\n        INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS itemmeta ON items.order_item_id = itemmeta.order_item_id\n        WHERE orders.post_type = 'shop_order'\n        AND orders.post_status IN ({$status_sql})\n        AND meta.meta_key = '_customer_user'\n        AND meta.meta_value = %d\n        AND (\n            itemmeta.meta_key IN ('_product_id', '_variation_id')\n            AND itemmeta.meta_value = %d\n        )\n        ", $user_id, $product_id_or_variation_id );
        }
        return $wpdb->get_var( $query ) > 0;
    }

}
