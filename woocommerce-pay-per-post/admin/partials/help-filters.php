<div class="postbox wc-ppp-help-tab" id="wc-ppp-help-filters-tab" style="display:none;">
    <h2 class="hndle"><?php esc_attr_e( 'Available Filters', 'wc_pay_per_post' ); ?></h2>

    <div class="inside">
        <ul>
            <li><h4>wc_pay_per_post_args</h4>
                <ul>
                    <li><?php esc_attr_e( 'This filter allows you to override the WP Query arguments for the shortcodes.', 'wc_pay_per_post' ); ?></li>
                    <li><strong><?php esc_attr_e( 'Example', 'wc_pay_per_post' ); ?>:</strong>
                        <ul>
                            <li><pre>
<code>add_filter('wc_pay_per_post_args', 'my_theme_wc_ppp_args');

function my_theme_wc_ppp_args($args){
    $args['orderby'] = 'menu_order';
    return $args;
}
</code></pre>

                            </li>
                        </ul>
                    </li>
                </ul>
            </li>
            <li><h4>wc_pay_per_post_my_account_shortcode_atts</h4>
                <ul>
                    <li><?php esc_html_e( 'Premium. The shortcode attributes each section of the My Account Purchased Content tab uses, plus the section: has_access or purchased. The sort order chosen under Settings arrives as orderby and order; at the default order neither is set.', 'wc_pay_per_post' ); ?></li>
                    <li><strong><?php esc_attr_e( 'Example', 'wc_pay_per_post' ); ?>:</strong>
                        <ul>
                            <li><pre>
<code>add_filter(
    'wc_pay_per_post_my_account_shortcode_atts',
    'my_theme_my_account_atts', 10, 2
);

function my_theme_my_account_atts($atts, $section){
    if ( 'purchased' === $section ) {
        $atts['orderby'] = 'date';
        $atts['order']   = 'ASC';
    }
    return $atts;
}
</code></pre>

                            </li>
                        </ul>
                    </li>
                </ul>
            </li>
            <li><h4>wc_pay_per_post_shortcode_posts</h4>
                <ul>
                    <li><?php esc_html_e( 'The posts a [woocommerce-payperpost] list is about to show, already filtered to what the customer can see and sorted, plus the template and the shortcode attributes. Return the list in the order you want, or with posts removed.', 'wc_pay_per_post' ); ?></li>
                    <li><strong><?php esc_attr_e( 'Example', 'wc_pay_per_post' ); ?>:</strong>
                        <ul>
                            <li><pre>
<code>add_filter(
    'wc_pay_per_post_shortcode_posts',
    'my_theme_sort_by_lesson_number', 10, 3
);

function my_theme_sort_by_lesson_number($posts, $template, $atts){
    usort($posts, function ($a, $b) {
        $lesson_a = (int) get_post_meta($a-&gt;ID, 'lesson_number', true);
        $lesson_b = (int) get_post_meta($b-&gt;ID, 'lesson_number', true);
        return $lesson_a - $lesson_b;
    });
    return $posts;
}
</code></pre>

                            </li>
                        </ul>
                    </li>
                </ul>
            </li>
        </ul>
        <p><?php _e('This plugin has several different filters available for modifying various functions in the plugin. It is strongly recommended that you view all filters, attributes and descriptions by visiting <a href="https://pramadillo.com/documentation/filters-for-woocommerce-pay-per-post/" target="_blank">our filters documentation.</a></p>','wc_pay_per_post'); ?>

    </div>
</div>