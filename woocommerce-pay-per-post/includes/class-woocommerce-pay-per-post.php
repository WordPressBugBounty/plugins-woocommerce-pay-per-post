<?php

class Woocommerce_Pay_Per_Post {
    protected $loader;

    public function __construct() {
        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    public function run() {
        $this->loader->run();
    }

    private function define_admin_hooks() {
        $plugin_admin = new Woocommerce_Pay_Per_Post_Admin();
        // Initialize integrations at early 'init' priority (1)
        // This ensures all plugins are loaded and their classes are available
        // but fires before most other init hooks
        add_action( 'init', [$plugin_admin, 'init_integrations'], 1 );
        // Once per site, on any request: the log written up to 3.3.0 can be read
        // by anyone who knows its address, debugging on or off.
        add_action( 'init', ['Woocommerce_Pay_Per_Post_Logger', 'secure_log_directory'] );
        $this->loader->add_action( 'init', $plugin_admin, 'admin_init' );
        $this->loader->add_action( 'admin_menu', $plugin_admin, 'add_plugin_options' );
        $this->loader->add_action( 'admin_notices', $plugin_admin, 'admin_notices' );
        $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
        $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
        $this->loader->add_filter( 'admin_body_class', $plugin_admin, 'admin_body_class' );
        $this->loader->add_action( 'wp_ajax_wc_ppp_preview_paywall', $plugin_admin, 'ajax_preview_paywall' );
        $this->loader->add_action( 'add_meta_boxes', $plugin_admin, 'meta_box' );
        $this->loader->add_action( 'save_post', $plugin_admin, 'save_meta_box' );
        $this->loader->add_action( 'edit_attachment', $plugin_admin, 'save_meta_box' );
        $this->loader->add_action(
            'in_plugin_update_message-woocommerce-pay-per-post/woocommerce-pay-per-post.php',
            $plugin_admin,
            'prefix_plugin_update_message',
            10
        );
        foreach ( $plugin_admin->get_post_types() as $post_type ) {
            $this->loader->add_action(
                'manage_' . $post_type . '_posts_custom_column',
                $plugin_admin,
                'manage_custom_column',
                10,
                2
            );
            $this->loader->add_filter( 'manage_' . $post_type . '_posts_columns', $plugin_admin, 'manage_columns' );
            $this->loader->add_filter( 'manage_edit-' . $post_type . '_sortable_columns', $plugin_admin, 'sortable_columns' );
        }
        $this->loader->add_filter(
            'plugin_action_links_woocommerce-pay-per-post/woocommerce-pay-per-post.php',
            $plugin_admin,
            'plugin_settings_link',
            10,
            2
        );
    }

    private function define_public_hooks() {
        $plugin_public = new Woocommerce_Pay_Per_Post_Public();
        $this->loader->add_action( 'init', $plugin_public, 'init' );
        $this->loader->add_action( 'template_redirect', $plugin_public, 'set_product_ids' );
        $this->loader->add_action( 'template_redirect', $plugin_public, 'should_disable_comments' );
        $this->loader->add_filter(
            'the_content',
            $plugin_public,
            'restrict_content',
            apply_filters( 'wc_pay_for_post_the_content_priority', 99 )
        );
    }

    private function load_dependencies() {
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-woocommerce-pay-per-post-loader.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-woocommerce-pay-per-post-i18n.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-woocommerce-pay-per-post-helper.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-woocommerce-pay-per-post-protection-checks.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-woocommerce-pay-per-post-logger.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-woocommerce-pay-per-post-deprecated.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-woocommerce-pay-per-post-admin.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-woocommerce-pay-per-post-debug.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-woocommerce-pay-per-post-tools.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-woocommerce-pay-per-post-access-diagnostic.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-woocommerce-pay-per-post-protected-content.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-woocommerce-pay-per-post-docs.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'integrations/woocommerce/WooCommerce.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-woocommerce-pay-per-post-public.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-woocommerce-pay-per-post-restrict-content.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-woocommerce-pay-per-post-shortcodes.php';
        $this->loader = new Woocommerce_Pay_Per_Post_Loader();
    }

    private function set_locale() {
        $plugin_i18n = new Woocommerce_Pay_Per_Post_i18n();
        $this->loader->add_action( 'init', $plugin_i18n, 'load_plugin_textdomain' );
    }

}
