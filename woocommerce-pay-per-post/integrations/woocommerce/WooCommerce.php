<?php

namespace PRAMADILLO\INTEGRATIONS;

use Woocommerce_Pay_Per_Post_Helper;
class WooCommerce {
    /**
     * The order the My Account lists have always used: the shortcode's own
     * default, newest publish date first.
     */
    const MY_ACCOUNT_SORT_ORDER_DEFAULT = 'date_desc';

    private $available_templates;

    public function __construct() {
        $this->available_templates = [
            'woocommerce-order-receipt' => 'woocommerce-order-receipt.php',
        ];
    }

    /**
     * The sort orders offered for the My Account lists, keyed by the value
     * stored in the wc_pay_per_post_my_account_sort_order option. Each is a
     * pair of the shortcode's own orderby/order attributes.
     *
     * Most recently purchased is deliberately absent: every post unlocked by
     * one subscription shares a purchase date, and looking the date up costs a
     * query per post.
     */
    public static function my_account_sort_orders() : array {
        return [
            'title_asc'  => [
                'label'   => __( 'Title A–Z', 'wc_pay_per_post' ),
                'orderby' => 'title',
                'order'   => 'ASC',
            ],
            'title_desc' => [
                'label'   => __( 'Title Z–A', 'wc_pay_per_post' ),
                'orderby' => 'title',
                'order'   => 'DESC',
            ],
            'date_desc'  => [
                'label'   => __( 'Newest published first', 'wc_pay_per_post' ),
                'orderby' => 'date',
                'order'   => 'DESC',
            ],
            'date_asc'   => [
                'label'   => __( 'Oldest published first', 'wc_pay_per_post' ),
                'orderby' => 'date',
                'order'   => 'ASC',
            ],
            'menu_order' => [
                'label'   => __( 'Menu order', 'wc_pay_per_post' ),
                'orderby' => 'menu_order',
                'order'   => 'ASC',
            ],
        ];
    }

    /**
     * Settings API sanitize callback: anything unrecognised becomes the default.
     *
     * @param mixed $value The submitted or stored value.
     */
    public static function sanitize_my_account_sort_order( $value ) : string {
        $value = ( is_string( $value ) ? $value : '' );
        return ( array_key_exists( $value, self::my_account_sort_orders() ) ? $value : self::MY_ACCOUNT_SORT_ORDER_DEFAULT );
    }

    /**
     * The store's chosen sort order, or the default when none (or an unknown
     * one) is stored.
     */
    public static function get_my_account_sort_order() : string {
        return self::sanitize_my_account_sort_order( get_option( WC_PPP_SLUG . '_my_account_sort_order', self::MY_ACCOUNT_SORT_ORDER_DEFAULT ) );
    }

    /**
     * @param $formatted_meta
     * @param $item
     *
     * @return string
     * @noinspection PhpUnused
     */
    public function get_purchased_content_by_product_id( $formatted_meta, $item ) : string {
        Woocommerce_Pay_Per_Post_Helper::logger( 'WooCommerce/get_purchased_content_by_product_id() called' );
        if ( $item->get_variation_id() > 0 ) {
            $purchased = Woocommerce_Pay_Per_Post_Helper::get_posts_associated_with_product_id( $item->get_variation_id() );
        } else {
            $purchased = Woocommerce_Pay_Per_Post_Helper::get_posts_associated_with_product_id( $item->get_product_id() );
        }
        $template = 'woocommerce-order-receipt';
        if ( !empty( $purchased ) ) {
            ob_start();
            require Woocommerce_Pay_Per_Post_Helper::locate_template( $this->available_templates[$template], '', WC_PPP_PATH . 'public/partials/' );
            return ob_get_clean();
        }
        return $formatted_meta;
    }

    public static function hide_item_meta_from_email( $css ) : string {
        return $css . '.ppp-purchased-content {display:none;}';
    }

}
