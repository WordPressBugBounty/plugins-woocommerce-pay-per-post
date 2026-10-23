<?php

use Elementor\Plugin;
use PRAMADILLO\Woocommerce_Pay_Per_Post_Restrict_Content;
/**
 * Class Woocommerce_Pay_Per_Post_Shortcodes
 */
class Woocommerce_Pay_Per_Post_Shortcodes {
    public static function register_shortcodes() {
        add_shortcode( 'woocommerce-payperpost', [__CLASS__, 'process_shortcode'] );
        add_shortcode( 'wc-pay-for-post', [__CLASS__, 'process_shortcode'] );
    }

    public static function process_shortcode( $atts ) {
        $post_id = get_the_ID();
        Woocommerce_Pay_Per_Post_Helper::logger( 'Post ID: ' . $post_id . ' - SHORTCODE: Woocommerce_Pay_Per_Post_Shortcodes/process_shortcode() called. - BACKTRACE - Called From - ' . print_r( debug_backtrace()[1]['function'], true ) );
        Woocommerce_Pay_Per_Post_Helper::logger( 'Post ID: ' . $post_id . ' - SHORTCODE: Woocommerce_Pay_Per_Post_Shortcodes/process_shortcode() supplied attributes ', $atts );
        $template = 'purchased';
        $orderby = 'date';
        $order = 'DESC';
        $bypass_transients = false;
        if ( isset( $atts['bypass_transients'] ) && ($atts['bypass_transients'] === 'TRUE' || $atts['bypass_transients'] === true || $atts['bypass_transients'] === 'true') ) {
            $bypass_transients = true;
        }
        if ( isset( $atts['template'] ) && array_key_exists( $atts['template'], self::available_templates() ) ) {
            $template = $atts['template'];
        }
        $custom_post_types = Woocommerce_Pay_Per_Post_Helper::get_protected_post_types();
        if ( !is_array( $custom_post_types ) ) {
            $custom_post_types = explode( ',', $custom_post_types );
        }
        $args = [
            'orderby'     => $orderby,
            'order'       => $order,
            'nopaging'    => true,
            'meta_query'  => [[
                'key'     => WC_PPP_SLUG . '_product_ids',
                'value'   => '',
                'compare' => '!=',
            ]],
            'post_status' => 'publish',
            'post_type'   => $custom_post_types,
        ];
        Woocommerce_Pay_Per_Post_Helper::logger( 'Post ID: ' . $post_id . ' - SHORTCODE: Woocommerce_Pay_Per_Post_Shortcodes/process_shortcode() query args ', $args );
        $get_ppp_args = apply_filters( 'wc_pay_per_post_args', $args );
        Woocommerce_Pay_Per_Post_Helper::logger( 'Post ID: ' . $post_id . ' - SHORTCODE: Woocommerce_Pay_Per_Post_Shortcodes/process_shortcode() query args AFTER FILTER ', $get_ppp_args );
        // Cache the result under the query it came from. The key used to be
        // built from a handful of attributes, which left out product_ids and
        // anything a wc_pay_per_post_args callback changes, so different lists
        // shared one entry and whichever rendered first was served to the rest
        // for up to an hour. Hashing the final args means two lists share an
        // entry only when they run the same query.
        //
        // The name keeps the wc_pay_per_post_ prefix, which is what the Tools
        // page and the post-save clear match on, and stays well inside the
        // 172-character transient name limit.
        $transient = 'shortcode_' . md5( wp_json_encode( $get_ppp_args ) );
        //			echo '<pre>'.print_r($get_ppp_args, true).'</pre>';
        $ppp_posts = Woocommerce_Pay_Per_Post_Helper::get_protected_posts( $get_ppp_args, $transient, $bypass_transients );
        Woocommerce_Pay_Per_Post_Helper::logger( 'Post ID: ' . $post_id . ' - SHORTCODE: Woocommerce_Pay_Per_Post_Shortcodes/process_shortcode() $ppp_posts contains ' . count( $ppp_posts ) . ' posts.' );
        Woocommerce_Pay_Per_Post_Helper::logger( 'Post ID: ' . $post_id . ' - SHORTCODE: Woocommerce_Pay_Per_Post_Shortcodes/process_shortcode() $ppp_posts contains ' . json_encode( $ppp_posts ) . ' posts.' );
        // Keyed off the final args, so a wc_pay_per_post_args callback that
        // changes the order still decides it.
        if ( isset( $get_ppp_args['orderby'] ) && 'title' === $get_ppp_args['orderby'] ) {
            $ascending = isset( $get_ppp_args['order'] ) && is_string( $get_ppp_args['order'] ) && 'ASC' === strtoupper( $get_ppp_args['order'] );
            $ppp_posts = self::sort_by_title( $ppp_posts, $ascending );
        }
        //			echo '<pre>'.print_r($ppp_posts, true).'</pre>';
        $atts = ( is_array( $atts ) ? $atts : [] );
        ob_start();
        switch ( $template ) {
            case 'has_access':
                self::shortcode_has_access( $template, $ppp_posts, $atts );
                break;
            case 'purchased':
                self::shortcode_purchased( $template, $ppp_posts, $atts );
                break;
            case 'purchased-datatables':
                self::shortcode_purchased_datatables( $template, $ppp_posts, $atts );
                break;
            case 'remaining':
                self::shortcode_remaining( $template, $ppp_posts, $atts );
                break;
            case 'all':
                self::shortcode_all( $template, $ppp_posts, $atts );
                break;
        }
        return ob_get_clean();
    }

    /**
     * Sort posts by title the way a reader would: naturally, so "Lesson 2"
     * comes before "Lesson 10", and ignoring case and accents. SQL's
     * ORDER BY post_title only got the last two right, so accents are folded
     * here to keep "Éclair" among the E's.
     *
     * Posts with the same title keep the order the query returned them in.
     *
     * @param WP_Post[] $posts
     * @param bool      $ascending
     *
     * @return WP_Post[]
     */
    private static function sort_by_title( array $posts, bool $ascending ) : array {
        $sortable = [];
        foreach ( array_values( $posts ) as $position => $post ) {
            $sortable[] = [remove_accents( ( isset( $post->post_title ) ? (string) $post->post_title : '' ) ), $position, $post];
        }
        usort( $sortable, static function ( $a, $b ) use($ascending) {
            $order = strnatcasecmp( $a[0], $b[0] );
            if ( !$ascending ) {
                $order = -$order;
            }
            return ( 0 !== $order ? $order : $a[1] - $b[1] );
        } );
        return array_column( $sortable, 2 );
    }

    /**
     * @param $template
     * @param $ppp_posts
     * @param array $atts
     */
    protected static function shortcode_purchased( $template, $ppp_posts, $atts = [] ) {
        $post_id = get_the_ID();
        Woocommerce_Pay_Per_Post_Helper::logger( 'Post ID: ' . $post_id . ' - SHORTCODE: Woocommerce_Pay_Per_Post_Shortcodes/shortcode_purchased() called. - BACKTRACE - Called From - ' . print_r( debug_backtrace()[1]['function'], true ) );
        $purchased = [];
        if ( is_user_logged_in() ) {
            foreach ( $ppp_posts as $post ) {
                if ( Woocommerce_Pay_Per_Post_Helper::has_purchased( $post->ID, false ) ) {
                    Woocommerce_Pay_Per_Post_Helper::logger( 'Post ID: ' . $post_id . ' - SHORTCODE: Woocommerce_Pay_Per_Post_Shortcodes/shortcode_purchased() checking if ' . $post->ID . ' has been purchased.  HAS BEEN PURCHASED' );
                    /** @noinspection PhpArrayWriteIsNotUsedInspection */
                    $purchased[] = $post;
                } else {
                    Woocommerce_Pay_Per_Post_Helper::logger( 'Post ID: ' . $post_id . ' - SHORTCODE: Woocommerce_Pay_Per_Post_Shortcodes/shortcode_purchased() checking if ' . $post->ID . ' HAS NOT BEEN PURCHASED' );
                }
            }
            /**
             * The posts a [woocommerce-payperpost] template is about to list:
             * already filtered to what this customer can see, and sorted.
             *
             * @param WP_Post[] $purchased
             * @param string    $template The template requested, before any Polylang variant.
             * @param array     $atts     The shortcode attributes.
             */
            $purchased = apply_filters(
                'wc_pay_per_post_shortcode_posts',
                $purchased,
                $template,
                $atts
            );
            require Woocommerce_Pay_Per_Post_Helper::locate_template( self::template_files()[$template], '', WC_PPP_PATH . 'public/partials/' );
        }
    }

    /**
     * @param $template
     * @param $ppp_posts
     * @param array $atts
     *
     * @noinspection PhpArrayAccessCanBeReplacedWithForeachValueInspection*/
    protected static function shortcode_purchased_datatables( $template, $ppp_posts, $atts = [] ) {
        $post_id = get_the_ID();
        Woocommerce_Pay_Per_Post_Helper::logger( 'Post ID: ' . $post_id . ' - SHORTCODE: Woocommerce_Pay_Per_Post_Shortcodes/shortcode_purchased_datatables() called. - BACKTRACE - Called From - ' . print_r( debug_backtrace()[1]['function'], true ) );
        $purchased = [];
        if ( is_user_logged_in() ) {
            foreach ( $ppp_posts as $post ) {
                if ( Woocommerce_Pay_Per_Post_Helper::has_purchased( $post->ID, false ) ) {
                    Woocommerce_Pay_Per_Post_Helper::logger( 'Post ID: ' . $post_id . ' - SHORTCODE: Woocommerce_Pay_Per_Post_Shortcodes/shortcode_purchased_datatables() checking if ' . $post->ID . ' has been purchased.  HAS BEEN PURCHASED' );
                    $purchased[] = $post;
                } else {
                    Woocommerce_Pay_Per_Post_Helper::logger( 'Post ID: ' . $post_id . ' - SHORTCODE: Woocommerce_Pay_Per_Post_Shortcodes/shortcode_purchased_datatables() checking if ' . $post->ID . ' HAS NOT BEEN PURCHASED' );
                }
            }
            // No Polylang switch here. It was written to swap to the plain
            // Polylang list, which drops the table, its search and its Category
            // and Publish Date columns: everything this template is chosen for.
            foreach ( $purchased as $key => $post ) {
                // Collect the names, then assign the whole array. WP_Post
                // answers unknown properties through __get(), which returns
                // a string, so appending to $post->product_terms directly
                // threw "[] operator not supported for strings" on every
                // purchase. Always set it: the template implodes it.
                $product_terms = [];
                $products = Woocommerce_Pay_Per_Post_Helper::get_product_ids_by_post_id( $post->ID );
                foreach ( array_filter( (array) $products['product_ids'] ) as $product ) {
                    $get_product_terms = get_the_terms( $product, 'product_cat' );
                    if ( !is_array( $get_product_terms ) ) {
                        continue;
                    }
                    foreach ( $get_product_terms as $product_term ) {
                        $product_terms[] = $product_term->name;
                    }
                }
                $purchased[$key]->product_terms = $product_terms;
            }
            $purchased = apply_filters(
                'wc_pay_per_post_shortcode_posts',
                $purchased,
                $template,
                $atts
            );
            require Woocommerce_Pay_Per_Post_Helper::locate_template( self::template_files()[$template], '', WC_PPP_PATH . 'public/partials/' );
        }
    }

    /**
     * @param $template
     * @param $ppp_posts
     * @param array $atts
     */
    protected static function shortcode_has_access( $template, $ppp_posts, $atts = [] ) {
        $post_id = get_the_ID();
        Woocommerce_Pay_Per_Post_Helper::logger( 'Post ID: ' . $post_id . ' - SHORTCODE: Woocommerce_Pay_Per_Post_Shortcodes/shortcode_has_access() called. - BACKTRACE - Called From - ' . print_r( debug_backtrace()[1]['function'], true ) );
        $purchased = [];
        if ( is_user_logged_in() ) {
            foreach ( $ppp_posts as $post ) {
                if ( Woocommerce_Pay_Per_Post_Helper::has_access( $post->ID, false ) ) {
                    Woocommerce_Pay_Per_Post_Helper::logger( 'Post ID: ' . $post_id . ' - SHORTCODE: Woocommerce_Pay_Per_Post_Shortcodes/shortcode_has_access() checking if ' . $post->ID . ' has access.  HAS Access' );
                    /** @noinspection PhpArrayWriteIsNotUsedInspection */
                    $purchased[] = $post;
                } else {
                    Woocommerce_Pay_Per_Post_Helper::logger( 'Post ID: ' . $post_id . ' - SHORTCODE: Woocommerce_Pay_Per_Post_Shortcodes/shortcode_has_access() checking if ' . $post->ID . ' has access.  DOES NOT HAVE Access' );
                }
            }
            $purchased = apply_filters(
                'wc_pay_per_post_shortcode_posts',
                $purchased,
                $template,
                $atts
            );
            require Woocommerce_Pay_Per_Post_Helper::locate_template( self::template_files()[$template], '', WC_PPP_PATH . 'public/partials/' );
        }
    }

    /**
     * @param $template
     * @param $ppp_posts
     * @param array $atts
     */
    protected static function shortcode_remaining( $template, $ppp_posts, $atts = [] ) {
        $remaining = [];
        if ( is_user_logged_in() ) {
            foreach ( $ppp_posts as $post ) {
                if ( !Woocommerce_Pay_Per_Post_Helper::has_access( $post->ID, false ) ) {
                    /** @noinspection PhpArrayWriteIsNotUsedInspection */
                    $remaining[] = $post;
                }
            }
            $remaining = apply_filters(
                'wc_pay_per_post_shortcode_posts',
                $remaining,
                $template,
                $atts
            );
            require Woocommerce_Pay_Per_Post_Helper::locate_template( self::template_files()[$template], '', WC_PPP_PATH . 'public/partials/' );
        }
    }

    /**
     * @param $template
     * @param $ppp_posts
     * @param array $atts
     */
    protected static function shortcode_all( $template, $ppp_posts, $atts = [] ) {
        $ppp_posts = apply_filters(
            'wc_pay_per_post_shortcode_posts',
            $ppp_posts,
            $template,
            $atts
        );
        require Woocommerce_Pay_Per_Post_Helper::locate_template( self::template_files()[$template], '', WC_PPP_PATH . 'public/partials/' );
    }

    /**
     * The templates a shortcode may ask for by name.
     */
    private static function available_templates() : array {
        return [
            'purchased'            => 'shortcode-purchased.php',
            'purchased-datatables' => 'shortcode-purchased-datatables.php',
            'has_access'           => 'shortcode-has_access.php',
            'all'                  => 'shortcode-all.php',
            'remaining'            => 'shortcode-remaining.php',
        ];
    }

    /**
     * Every partial the shortcode can render: the named templates plus the
     * Polylang variants it switches to by itself.
     *
     * The Polylang keys are kept out of available_templates() so that
     * template="purchased-polylang" is still not a name a shortcode can ask
     * for. They were once missing from this lookup entirely, so the lookup
     * returned null and the require loaded the partials directory: a fatal
     * wherever a purchased or has-access list rendered with Polylang support
     * enabled, the My Account tab included.
     */
    private static function template_files() : array {
        return self::available_templates() + [
            'purchased-polylang'  => 'shortcode-purchased-polylang.php',
            'has_access-polylang' => 'shortcode-has_access-polylang.php',
        ];
    }

}
