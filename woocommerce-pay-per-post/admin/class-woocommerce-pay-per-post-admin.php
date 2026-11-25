<?php

use PRAMADILLO\Woocommerce_Pay_Per_Post_Restrict_Content;
use PRAMADILLO\INTEGRATIONS\Elementor;
use PRAMADILLO\INTEGRATIONS\PaidMembershipsPro;
use PRAMADILLO\INTEGRATIONS\WooCommerceMemberships;
use PRAMADILLO\INTEGRATIONS\WooCommerceSubscriptions;
class Woocommerce_Pay_Per_Post_Admin {
    public $integrations;

    private $allowed_restriction_frequency = [
        'minute',
        'hour',
        'day',
        'week',
        'month',
        'year'
    ];

    public function __construct() {
        // Integrations are initialized in init_integrations() which is called from admin_init
        // This ensures other plugins (WooCommerce Subscriptions, Memberships, etc.) are fully loaded
    }

    /**
     * Initialize integrations after all plugins are loaded.
     * Called from admin_init hook to ensure WooCommerce extensions are available.
     */
    public function init_integrations() {
        // Only initialize once
        if ( $this->integrations !== null ) {
            return;
        }
        $this->integrations = [];
    }

    /** @noinspection PhpUnused */
    public function admin_init() {
        //Register Options
        $this->options_init();
        //Load Block Editor
        $this->load_block_editor();
    }

    public function load_block_editor() {
        if ( get_option( WC_PPP_SLUG . '_block_editor_enabled', true ) ) {
            if ( Woocommerce_Pay_Per_Post_Helper::allowed_roles_for_meta_box() ) {
                $this->check_requirements_for_selected_custom_post_types();
                $this->wp_editor_integration_register_post_meta();
                add_action( 'enqueue_block_editor_assets', [$this, 'enqueue_wp_editor_integration_scripts'] );
            }
        }
    }

    /**
     * Register the "custom field support" notice.
     *
     * This only registers the callback — the post types are inspected later, when
     * the notice is actually rendered. That distinction is the whole point.
     *
     * This method runs on `init` at priority 10, which is also where most custom
     * post types are registered. Asking `post_type_supports()` here reads a post
     * type that frequently does not exist yet, and an unregistered post type
     * reports no support for anything — so the plugin warned that custom fields
     * were disabled on post types that had them enabled all along. On this very
     * site PPP is callback 106 of 190 on `init:10`, with WooCommerce Memberships
     * (156) and Elementor (183) registering post types after it.
     *
     * `admin_notices` fires long after `init` has completed, so by then every
     * post type is registered and the answer is the real one.
     *
     * @see Woocommerce_Pay_Per_Post_Admin::custom_post_type_support_notice()
     */
    public function check_requirements_for_selected_custom_post_types() {
        add_action( 'admin_notices', [$this, 'custom_post_type_support_notice'] );
    }

    /**
     * Warn about selected post types that do not support custom fields.
     *
     * The Block Editor integration stores protection settings in post meta, which
     * requires custom-field support on the post type.
     */
    public function custom_post_type_support_notice() {
        $failed = [];
        foreach ( $this->get_post_types() as $post_type ) {
            if ( false === post_type_supports( $post_type, 'custom-fields' ) ) {
                $failed[] = $post_type;
            }
        }
        if ( count( $failed ) === 0 ) {
            return;
        }
        $message = 'In order to utilize the Block Editor and Pay for Post on the <code>' . implode( ', ', $failed ) . '</code> custom post type(s), you must enable <a href="https://developer.wordpress.org/reference/functions/add_post_type_support/" target="_blank">custom field support</a> for the custom post types or utilize disable Block Editor Integration in the <a href="' . admin_url() . '/admin.php?page=wc_pay_per_post-settings">Settings</a>';
        echo '<div class="notice notice-error is-dismissible"><p>' . $message . '</p></div>';
    }

    /**
     * Save the protection settings from the block editor.
     *
     * The block editor saves the post over REST, then posts this plugin's meta
     * box (admin/partials/meta-box.php) to post.php?meta-box-loader=1, which
     * reaches this method through save_meta_box(). The Pay For Post sidebar puts
     * its settings in that meta box's `wc_pay_per_post_document_settings` field,
     * and only when one of its controls changes. An empty field means the sidebar
     * was not touched, so the post's protection is left as it is.
     *
     * This used to rebuild the protection from the `_ppp_document_settings_meta`
     * post meta on every block editor save. The sidebar writes that meta only on a
     * change too, so a post protected any other way — the classic editor, the
     * meta box with Block Editor Integration off, an import, or before the sidebar
     * existed — lost all its products on its first block editor save, even one
     * that only fixed the title. A post with meta left over from an earlier
     * sidebar edit went back to those settings instead. A stored value cannot
     * tell a change made in this save from one made months ago; the posted field
     * can.
     *
     * @param int $post_id The post being saved.
     */
    public function edit_post_block_editor( $post_id ) {
        $ppp_document_settings_meta = $this->posted_document_settings();
        if ( null === $ppp_document_settings_meta ) {
            return;
        }
        $protected_ids = [];
        foreach ( $ppp_document_settings_meta->product_ids as $data ) {
            if ( isset( $data->value ) ) {
                $protected_ids[] = $data->value;
            }
        }
        $protected_ids = $this->sanitize_product_ids( $protected_ids );
        update_post_meta( $post_id, WC_PPP_SLUG . '_product_ids', $protected_ids );
    }

    /**
     * The Pay For Post sidebar's settings, as posted with the meta box.
     *
     * @return object|null Null when the sidebar posted nothing, because it was not
     *                     touched, or posted something without a product list. Either
     *                     way the caller must leave the post's protection alone.
     */
    private function posted_document_settings() {
        $posted = $_POST[WC_PPP_SLUG . '_document_settings'] ?? '';
        if ( !is_string( $posted ) || '' === $posted ) {
            return null;
        }
        $settings = json_decode( wp_unslash( $posted ) );
        if ( !is_object( $settings ) || !isset( $settings->product_ids ) || !is_array( $settings->product_ids ) ) {
            return null;
        }
        return $settings;
    }

    /**
     * The post's product IDs, as strings, for the sidebar to preselect.
     *
     * The sidebar matches them against its product options, whose IDs are
     * strings, with a strict includes(). IDs stored as integers, by an import or
     * other code writing the meta directly, showed as unselected, and the next
     * change made in the sidebar saved the post without them.
     *
     * @param int $post_id The post being edited.
     *
     * @return string[]
     */
    private function product_ids_for_sidebar( $post_id ) : array {
        return $this->normalize_protection_ids( get_post_meta( $post_id, WC_PPP_SLUG . '_product_ids', true ) );
    }

    /**
     * Protection IDs as unique numeric strings, the form every option value in
     * the classic meta box and the sidebar takes, so a strict comparison with
     * them matches however the IDs were stored.
     *
     * @param mixed $ids Stored protection IDs.
     *
     * @return string[]
     */
    private function normalize_protection_ids( $ids ) : array {
        $ids = array_filter( (array) $ids, 'is_numeric' );
        return array_values( array_unique( array_map( 'strval', $ids ) ) );
    }

    /**
     * WooCommerce Memberships plans and Paid Memberships Pro levels a post can be
     * protected by, as ID => label: the ones the classic meta box lists above
     * its products. Empty unless the integration is active (premium).
     *
     * @return array<string, string>
     */
    private function membership_protection_options() : array {
        $options = [];
        $sources = [
            'woocommerce-memberships' => __( 'Membership plan: %1$s - [#%2$s]', 'wc_pay_per_post' ),
            'paid-memberships-pro'    => __( 'Paid Memberships Pro level: %1$s - [#%2$s]', 'wc_pay_per_post' ),
        ];
        foreach ( $sources as $integration => $label ) {
            if ( !isset( $this->integrations[$integration] ) ) {
                continue;
            }
            $levels = $this->integrations[$integration]->get_membership_levels();
            if ( !is_array( $levels ) ) {
                continue;
            }
            foreach ( $levels as $level ) {
                $options[(string) $level->id] = sprintf( $label, $level->name, $level->id );
            }
        }
        return $options;
    }

    /**
     * Label for a protection ID the post has but no option lists: a product that
     * is no longer listed, or a plan or level whose plugin is inactive or whose
     * licence lapsed. It is still shown, selected, so saving the post keeps it.
     *
     * @param string $id Protection ID.
     *
     * @return string
     */
    private function unlisted_protection_label( string $id ) : string {
        /* translators: %s: product, plan or level ID */
        return sprintf( __( '#%s (not available)', 'wc_pay_per_post' ), $id );
    }

    public function enqueue_wp_editor_integration_scripts() {
        if ( 'post' === WP_Screen::get()->base ) {
            global $post;
            $id = $post->ID;
            $selected_product_ids = $this->product_ids_for_sidebar( $id );
            $delay_restriction_enable = get_post_meta( $id, WC_PPP_SLUG . '_delay_restriction_enable', true );
            $delay_restriction = get_post_meta( $id, WC_PPP_SLUG . '_delay_restriction', true );
            $delay_restriction_frequency = get_post_meta( $id, WC_PPP_SLUG . '_delay_restriction_frequency', true );
            $page_view_restriction_enable = get_post_meta( $id, WC_PPP_SLUG . '_page_view_restriction_enable', true );
            $page_view_restriction = get_post_meta( $id, WC_PPP_SLUG . '_page_view_restriction', true );
            $page_view_restriction_frequency = get_post_meta( $id, WC_PPP_SLUG . '_page_view_restriction_frequency', true );
            $page_view_restriction_enable_time_frame = get_post_meta( $id, WC_PPP_SLUG . '_page_view_restriction_enable_time_frame', true );
            $page_view_restriction_time_frame = get_post_meta( $id, WC_PPP_SLUG . '_page_view_restriction_time_frame', true );
            $expire_restriction_enable = get_post_meta( $id, WC_PPP_SLUG . '_expire_restriction_enable', true );
            $expire_restriction = get_post_meta( $id, WC_PPP_SLUG . '_expire_restriction', true );
            $expire_restriction_frequency = get_post_meta( $id, WC_PPP_SLUG . '_expire_restriction_frequency', true );
            $show_warnings = get_post_meta( $id, WC_PPP_SLUG . '_show_warnings', true );
            $wc_products = Woocommerce_Pay_Per_Post_Helper::get_products();
            // The sidebar offers what the classic meta box offers: membership plans
            // and levels, then products. It drops any stored ID it has no option
            // for on its next change, so each of those is listed too.
            $this->init_integrations();
            $sidebar_options = $this->membership_protection_options();
            foreach ( $wc_products as $product ) {
                $sidebar_options += [
                    (string) $product['ID'] => $product['post_title'] . ' - [#' . $product['ID'] . ']',
                ];
            }
            foreach ( $selected_product_ids as $selected_product_id ) {
                $sidebar_options += [
                    $selected_product_id => $this->unlisted_protection_label( $selected_product_id ),
                ];
            }
            $productIds = array_map( 'strval', array_keys( $sidebar_options ) );
            $productTitles = array_values( $sidebar_options );
            $box = $this->meta_box_view( $id, [
                'delay'     => $delay_restriction_enable,
                'page-view' => $page_view_restriction_enable,
                'expire'    => $expire_restriction_enable,
            ] );
            // Use the manifest wp-scripts generates alongside the bundle. Passing
            // null for both arguments, as this once did, meant the script declared no
            // dependencies (so its load order relative to the editor packages it needs
            // was luck) and carried no version at all — the URL never changed between
            // plugin releases, so a browser or CDN holding an old copy had no signal to
            // refetch it. The manifest's version is a content hash, so it changes
            // exactly when the bundle does.
            $asset_file = plugin_dir_path( __FILE__ ) . 'wp-editor-integration/build/index.asset.php';
            $asset = ( file_exists( $asset_file ) ? require $asset_file : [
                'dependencies' => [],
                'version'      => WC_PPP_VERSION,
            ] );
            wp_enqueue_script(
                WC_PPP_SLUG . '_wp-editor-integration-js',
                plugin_dir_url( __FILE__ ) . 'wp-editor-integration/build/index.js',
                $asset['dependencies'] ?? [],
                $asset['version'] ?? WC_PPP_VERSION,
                true
            );
            wp_enqueue_style( WC_PPP_SLUG . '_admin' );
            wp_localize_script( WC_PPP_SLUG . '_wp-editor-integration-js', 'wpEditorIntegrationObj', [
                'selected_product_ids'                    => $selected_product_ids,
                'productIds'                              => $productIds,
                'productTitles'                           => $productTitles,
                'adminUrl'                                => get_admin_url(),
                'wcppp_freemius_upgrade_url'              => $box['upgrade_url'],
                'can_use_premium_code'                    => $box['premium'],
                'protection_type'                         => $box['type'],
                'stored_protection_type'                  => $box['stored_type'],
                'frequencies'                             => $box['frequencies'],
                'preview_url'                             => $box['preview_url'],
                'check_url'                               => $box['check_url'],
                'settings_url'                            => admin_url( 'admin.php?page=' . WC_PPP_SLUG . '-settings&tab=paywall' ),
                'summaries'                               => self::meta_box_summaries(),
                'restricted_content_override'             => [
                    'text' => ( $box['premium'] ? (string) get_post_meta( $id, WC_PPP_SLUG . '_restricted_content_override', true ) : '' ),
                ],
                'has_own_message'                         => '' !== trim( (string) get_post_meta( $id, WC_PPP_SLUG . '_restricted_content_override', true ) ),
                'delay_restriction_enable'                => $delay_restriction_enable,
                'delay_restriction'                       => $delay_restriction,
                'delay_restriction_frequency'             => $delay_restriction_frequency,
                'page_view_restriction_enable'            => $page_view_restriction_enable,
                'page_view_restriction'                   => $page_view_restriction,
                'page_view_restriction_frequency'         => $page_view_restriction_frequency,
                'page_view_restriction_enable_time_frame' => $page_view_restriction_enable_time_frame,
                'page_view_restriction_time_frame'        => $page_view_restriction_time_frame,
                'expire_restriction_enable'               => $expire_restriction_enable,
                'expire_restriction'                      => $expire_restriction,
                'expire_restriction_frequency'            => $expire_restriction_frequency,
                'show_warnings'                           => $show_warnings,
                'post_types'                              => Woocommerce_Pay_Per_Post_Helper::get_post_types(),
            ] );
        }
    }

    public function wp_editor_integration_register_post_meta() {
        register_meta( 'post', '_ppp_document_settings_meta', [
            'auth_callback' => static function ( $allowed, $meta_key, $post_id ) {
                return current_user_can( 'edit_post', $post_id );
            },
            'default'       => '',
            'show_in_rest'  => true,
            'single'        => true,
            'type'          => 'string',
        ] );
    }

    /**
     * Admin page hooks for the plugin's own screens, keyed by slug. Assets and
     * the app-shell body class are limited to these.
     *
     * @var array<string, string>
     */
    protected $page_hooks = [];

    /**
     * Admin page slugs that the redesign folded into other screens, and where
     * each one lives now. Documentation, support replies and customers'
     * bookmarks link to them, so each is still registered (hidden from the
     * menu) and redirects.
     *
     * @return array<string, string> Slug => path and query relative to admin_url().
     */
    public static function legacy_page_redirects() : array {
        return [
            WC_PPP_SLUG                      => 'admin.php?page=' . WC_PPP_SLUG . '-settings',
            WC_PPP_SLUG . '-whats-new'       => 'admin.php?page=' . WC_PPP_SLUG . '-support&view=whats-new',
            WC_PPP_SLUG . '-getting-started' => 'admin.php?page=' . WC_PPP_SLUG . '-support&view=docs&doc=getting-started%2Ffirst-protected-post',
            WC_PPP_SLUG . '-help'            => 'admin.php?page=' . WC_PPP_SLUG . '-support&view=docs',
            WC_PPP_SLUG . '-debug'           => 'admin.php?page=' . WC_PPP_SLUG . '-support&view=check',
            WC_PPP_SLUG . '-tools'           => 'admin.php?page=' . WC_PPP_SLUG . '-support&view=tools',
        ];
    }

    /**
     * The URL a folded-in screen's slug now lives at, or '' for any other slug.
     *
     * @param string $slug Admin page slug.
     *
     * @return string
     */
    public static function legacy_page_redirect_url( string $slug ) : string {
        $redirects = self::legacy_page_redirects();
        return ( isset( $redirects[$slug] ) ? admin_url( $redirects[$slug] ) : '' );
    }

    /** @noinspection PhpUnused */
    public function add_plugin_options() {
        $this->page_hooks[WC_PPP_SLUG] = add_menu_page(
            __( 'Pay For Post with WooCommerce', 'wc_pay_per_post' ),
            'Pay For Post',
            'manage_options',
            WC_PPP_SLUG,
            [$this, 'create_options_page'],
            'dashicons-cart',
            99
        );
        $this->page_hooks[WC_PPP_SLUG . '-settings'] = add_submenu_page(
            WC_PPP_SLUG,
            __( 'Pay For Post Settings', 'wc_pay_per_post' ),
            __( 'Settings', 'wc_pay_per_post' ),
            'manage_options',
            WC_PPP_SLUG . '-settings',
            [$this, 'create_options_page']
        );
        $this->page_hooks[WC_PPP_SLUG . '-support'] = add_submenu_page(
            WC_PPP_SLUG,
            __( 'Pay For Post: Need Help', 'wc_pay_per_post' ),
            __( 'Need Help', 'wc_pay_per_post' ),
            'manage_options',
            WC_PPP_SLUG . '-support',
            [$this, 'create_support_page']
        );
        // The folded-in screens, registered but hidden from the menu. A GET
        // redirects before any output (redirect_legacy_page()); a POST from a
        // form that was open when the plugin updated renders Need Help, which
        // handles it.
        foreach ( array_keys( self::legacy_page_redirects() ) as $slug ) {
            if ( WC_PPP_SLUG !== $slug ) {
                $this->page_hooks[$slug] = add_submenu_page(
                    '',
                    __( 'Pay For Post: Need Help', 'wc_pay_per_post' ),
                    '',
                    'manage_options',
                    $slug,
                    [$this, 'create_support_page']
                );
            }
            if ( !empty( $this->page_hooks[$slug] ) ) {
                add_action( 'load-' . $this->page_hooks[$slug], [$this, 'redirect_legacy_page'] );
            }
        }
        if ( !empty( $this->page_hooks[WC_PPP_SLUG . '-settings'] ) ) {
            add_action( 'load-' . $this->page_hooks[WC_PPP_SLUG . '-settings'], [$this, 'maybe_delete_log_from_link'] );
        }
        remove_submenu_page( WC_PPP_SLUG, WC_PPP_SLUG );
    }

    /**
     * Send a request for a folded-in screen to where that screen lives now.
     *
     * Runs on the page's load- hook, before any output. Only GET requests
     * move: redirecting a POST would turn it into a GET and drop what was
     * submitted.
     */
    public function redirect_legacy_page() {
        if ( 'GET' !== ($_SERVER['REQUEST_METHOD'] ?? 'GET') ) {
            return;
        }
        $slug = ( isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '' );
        $url = self::legacy_page_redirect_url( $slug );
        if ( '' !== $url ) {
            wp_safe_redirect( $url );
            exit;
        }
    }

    /**
     * Delete the debug log from the link earlier versions printed on the
     * Settings and Debug pages. That handler ran inside the page, after output
     * had started, so its redirect never happened.
     */
    public function maybe_delete_log_from_link() {
        if ( isset( $_GET['delete_log_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['delete_log_nonce'] ) ), 'delete_log' ) && current_user_can( 'manage_options' ) ) {
            $log = new Woocommerce_Pay_Per_Post_Logger();
            $log->delete_log_file();
            wp_safe_redirect( admin_url( 'admin.php?page=' . WC_PPP_SLUG . '-support&view=tools' ) );
            exit;
        }
    }

    /**
     * Whether an admin screen is one of the plugin's own.
     *
     * @param string|null $hook_suffix The screen's hook; the current screen when null.
     *
     * @return bool
     */
    public function is_plugin_screen( $hook_suffix = null ) : bool {
        if ( null === $hook_suffix ) {
            $screen = ( function_exists( 'get_current_screen' ) ? get_current_screen() : null );
            $hook_suffix = ( $screen ? $screen->id : '' );
        }
        return '' !== (string) $hook_suffix && in_array( (string) $hook_suffix, array_filter( $this->page_hooks ), true );
    }

    /**
     * A small inline icon for the app shell. Decorative: status is always
     * spelled out in words beside it.
     *
     * @param string $name check | cross | dash | warn | info | lock.
     *
     * @return string SVG markup.
     */
    public static function icon( string $name ) : string {
        $paths = [
            'check' => 'M4.5 8.2 2.3 6l-1 1 3.2 3.2L11 3.7l-1-1z',
            'cross' => 'M3.2 2.1 6 4.9l2.8-2.8 1.1 1.1L7.1 6l2.8 2.8-1.1 1.1L6 7.1 3.2 9.9 2.1 8.8 4.9 6 2.1 3.2z',
            'dash'  => 'M2 5h8v2H2z',
            'warn'  => 'M5 2h2v5H5zm0 6h2v2H5z',
            'info'  => 'M5 5h2v5H5zm0-3h2v2H5z',
            'lock'  => 'M3.6 5.4V4a2.4 2.4 0 1 1 4.8 0v1.4h.6a.6.6 0 0 1 .6.6v4.2a.6.6 0 0 1-.6.6H3a.6.6 0 0 1-.6-.6V6a.6.6 0 0 1 .6-.6zm1.2 0h2.4V4a1.2 1.2 0 1 0-2.4 0z',
        ];
        return '<svg class="wcppp-icon" viewBox="0 0 12 12" aria-hidden="true" focusable="false"><path d="' . esc_attr( $paths[$name] ?? $paths['info'] ) . '"/></svg>';
    }

    /**
     * Mark the plugin's screens, so the app shell's styles apply to them only.
     *
     * @param string $classes Space-separated body classes.
     *
     * @return string
     */
    public function admin_body_class( $classes ) {
        if ( $this->is_plugin_screen() ) {
            $classes .= ' wcppp-app-screen';
        }
        return $classes;
    }

    /** @noinspection PhpUnused */
    public function enqueue_scripts( $hook_suffix = '' ) {
        wp_register_script(
            WC_PPP_SLUG . '_admin',
            plugin_dir_url( __FILE__ ) . 'js/wc-ppp-admin.js',
            ['jquery'],
            WC_PPP_VERSION
        );
        wp_register_script(
            WC_PPP_SLUG . '_select2',
            plugin_dir_url( __FILE__ ) . 'js/select2.min.js',
            [],
            '4.0.6'
        );
        wp_register_script(
            WC_PPP_SLUG . '_app',
            plugin_dir_url( __FILE__ ) . 'js/wc-ppp-app.js',
            [],
            WC_PPP_VERSION,
            true
        );
        if ( $this->is_plugin_screen( $hook_suffix ) ) {
            wp_enqueue_script( WC_PPP_SLUG . '_app' );
            wp_localize_script( WC_PPP_SLUG . '_app', 'wcPppApp', [
                'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
                'previewNonce' => wp_create_nonce( 'wc_ppp_preview_paywall' ),
                'i18n'         => [
                    'unsaved'       => __( 'You have unsaved changes', 'wc_pay_per_post' ),
                    'saved'         => __( 'No unsaved changes', 'wc_pay_per_post' ),
                    'copied'        => __( 'Copied', 'wc_pay_per_post' ),
                    'copyFailed'    => __( 'Press Ctrl+C (Cmd+C on a Mac) to copy', 'wc_pay_per_post' ),
                    'rendering'     => __( 'Rendering the paywall…', 'wc_pay_per_post' ),
                    'rendered'      => __( 'Preview updated.', 'wc_pay_per_post' ),
                    'previewError'  => __( 'The preview could not be rendered.', 'wc_pay_per_post' ),
                    'needPost'      => __( 'Enter a post ID or URL to preview the paywall on.', 'wc_pay_per_post' ),
                    'deleteData'    => __( 'With this on, deleting Pay For Post from the Plugins screen also removes every post\'s protection, so those posts become free to read. Turn it on?', 'wc_pay_per_post' ),
                    'showBuyers'    => __( 'Show buyers', 'wc_pay_per_post' ),
                    'hideBuyers'    => __( 'Hide buyers', 'wc_pay_per_post' ),
                    'loadingBuyers' => __( 'Loading buyers…', 'wc_pay_per_post' ),
                    'buyersError'   => __( 'The buyers could not be loaded. Reload the page and try again.', 'wc_pay_per_post' ),
                ],
            ] );
        }
    }

    /** @noinspection PhpUnused */
    public function enqueue_styles( $hook_suffix = '' ) {
        wp_register_style(
            WC_PPP_SLUG . '_admin',
            plugin_dir_url( __FILE__ ) . 'css/wc-ppp-admin.css',
            [],
            WC_PPP_VERSION
        );
        wp_register_style(
            WC_PPP_SLUG . '_select2',
            plugin_dir_url( __FILE__ ) . 'css/select2.min.css',
            [],
            WC_PPP_VERSION
        );
        wp_register_style(
            WC_PPP_SLUG . '_app',
            plugin_dir_url( __FILE__ ) . 'css/wc-ppp-app.css',
            [],
            WC_PPP_VERSION
        );
        if ( $this->is_plugin_screen( $hook_suffix ) ) {
            wp_enqueue_style( WC_PPP_SLUG . '_app' );
        }
    }

    public function ajax_post_types() : array {
        $post_types = [];
        foreach ( get_post_types( [
            'public' => true,
        ] ) as $post_type ) {
            $post_types[] = $post_type;
        }
        return $post_types;
    }

    public function options_init() {
        register_setting( WC_PPP_SLUG . '_settings', WC_PPP_SLUG . '_restricted_content_default' );
        register_setting( WC_PPP_SLUG . '_settings', WC_PPP_SLUG . '_custom_post_types' );
        register_setting( WC_PPP_SLUG . '_settings', WC_PPP_SLUG . '_only_show_virtual_products' );
        register_setting( WC_PPP_SLUG . '_settings', WC_PPP_SLUG . '_turn_off_comments_when_protected', [
            'sanitize_callback' => [__CLASS__, 'sanitize_comments_when_protected'],
        ] );
        register_setting( WC_PPP_SLUG . '_settings', WC_PPP_SLUG . '_allow_admins_access_to_protected_posts' );
        register_setting( WC_PPP_SLUG . '_settings', WC_PPP_SLUG . '_enable_debugging' );
        register_setting( WC_PPP_SLUG . '_settings', WC_PPP_SLUG . '_delete_settings' );
        register_setting( WC_PPP_SLUG . '_settings', WC_PPP_SLUG . '_block_editor_enabled', [
            'type'    => 'boolean',
            'default' => 1,
        ] );
        register_setting( WC_PPP_SLUG . '_settings', WC_PPP_SLUG . '_editor_panel_roles', [
            'type'              => 'array',
            'default'           => [],
            'sanitize_callback' => [Woocommerce_Pay_Per_Post_Helper::class, 'sanitize_roles'],
        ] );
    }

    public function create_options_page() {
        if ( !current_user_can( 'manage_options' ) ) {
            wp_die( esc_html( 'You do not have sufficient permissions to access this page.' ) );
        }
        $restricted_content_default = get_option( WC_PPP_SLUG . '_restricted_content_default', _x( Woocommerce_Pay_Per_Post_Restrict_Content::RESTRICT_CONTENT_DEFAULT_MESSAGE, 'Default restricted content', 'wc_pay_per_post' ) );
        $custom_post_types = get_option( WC_PPP_SLUG . '_custom_post_types', [] );
        $custom_post_types = ( empty( $custom_post_types ) ? [] : $custom_post_types );
        if ( !is_array( $custom_post_types ) ) {
            $custom_post_types = explode( ',', $custom_post_types );
        }
        $turn_off_comments_when_protected = (bool) get_option( WC_PPP_SLUG . '_turn_off_comments_when_protected', true );
        // Premium settings. Without a licence they are not registered, so the
        // page shows them as unavailable and posts nothing for them.
        $premium = false;
        $turn_off_comments_public = false;
        $display_on_my_account = false;
        $shortcodes_to_display_on_my_account = [];
        $my_account_sort_orders = [];
        $my_account_sort_order = '';
        $enable_polylang = false;
        $show_locks = false;
        $paywall_bypass_roles = [];
        $allowed_roles = [];
        $status_box_position = 'top';
        $expiry_reload = true;
        $order_links = 'everywhere';
        $custom_texts = [];
        $use_woocommerce_subscriptions = true;
        $allow_admins_access_to_protected_posts = (bool) get_option( WC_PPP_SLUG . '_allow_admins_access_to_protected_posts', false );
        $enable_debugging = (bool) get_option( WC_PPP_SLUG . '_enable_debugging', false );
        $delete_settings = (bool) get_option( WC_PPP_SLUG . '_delete_settings', false );
        $block_editor_enabled = (bool) get_option( WC_PPP_SLUG . '_block_editor_enabled' );
        $available_post_types = $this->ajax_post_types();
        $only_show_virtual_products = (bool) get_option( WC_PPP_SLUG . '_only_show_virtual_products', false );
        $editor_panel_roles = Woocommerce_Pay_Per_Post_Helper::sanitize_roles( get_option( WC_PPP_SLUG . '_editor_panel_roles', [] ) );
        $role_names = array_map( 'translate_user_role', wp_roles()->get_names() );
        $subscriptions_installed = class_exists( 'WC_Subscriptions_Product' );
        $guest_checkout_enabled = 'yes' === get_option( 'woocommerce_enable_guest_checkout' );
        $debug_log = self::debug_log_info();
        $integration_tiles = $this->integration_tiles( $premium, $enable_polylang );
        $polylang_installed = function_exists( 'pll_current_language' );
        $upgrade_url = ( wcppp_freemius()->is_not_paying() && !wcppp_freemius()->is_trial() ? wcppp_freemius()->get_upgrade_url() : '' );
        $settings_tabs = self::settings_tabs();
        $settings_tab = ( isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : '' );
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- which tab to show.
        $settings_tab = ( isset( $settings_tabs[$settings_tab] ) ? $settings_tab : 'paywall' );
        require plugin_dir_path( __FILE__ ) . 'partials/settings.php';
    }

    /**
     * The Settings screen's tabs, in order. Every tab's fields are in the one
     * form, so Save changes stores all of them whichever tab is showing.
     *
     * @return array<string, string> Tab => label.
     */
    public static function settings_tabs() : array {
        return [
            'paywall'      => __( 'Paywall', 'wc_pay_per_post' ),
            'access'       => __( 'Access', 'wc_pay_per_post' ),
            'my-account'   => __( 'Customers', 'wc_pay_per_post' ),
            'editor'       => __( 'Editor', 'wc_pay_per_post' ),
            'integrations' => __( 'Integrations', 'wc_pay_per_post' ),
            'advanced'     => __( 'Advanced', 'wc_pay_per_post' ),
        ];
    }

    /**
     * What the Integrations section says about each supported plugin.
     *
     * Detection only: a class or function check per plugin, nothing that reads
     * posts, products or orders.
     *
     * @param bool $premium         Whether premium code may run.
     * @param bool $enable_polylang The Polylang setting.
     *
     * @return array<string, array{name: string, description: string, installed: bool, active: bool, state: string}>
     */
    protected function integration_tiles( bool $premium, bool $enable_polylang ) : array {
        $integrations = (array) $this->integrations;
        $tiles = [
            'woocommerce-subscriptions' => [
                'name'        => 'WooCommerce Subscriptions',
                'description' => __( 'Protect posts with subscription products. Access lasts while the subscription is active or pending cancellation. A product sold on subscription plans unlocks for good when it was bought one-time.', 'wc_pay_per_post' ),
                'installed'   => class_exists( 'WC_Subscriptions_Product' ),
            ],
            'woocommerce-memberships'   => [
                'name'        => 'WooCommerce Memberships',
                'description' => __( 'Protect posts with a membership plan. Access ends if the membership expires, is paused or is cancelled.', 'wc_pay_per_post' ),
                'installed'   => function_exists( 'wc_memberships' ),
            ],
            'paid-memberships-pro'      => [
                'name'        => 'Paid Memberships Pro',
                'description' => __( 'Protect posts with a membership level. Access ends if the membership expires or is cancelled.', 'wc_pay_per_post' ),
                'installed'   => function_exists( 'pmpro_hasMembershipLevel' ),
            ],
            'elementor'                 => [
                'name'        => 'Elementor',
                'description' => __( 'A Protection tab on every Elementor widget, to lock part of a page.', 'wc_pay_per_post' ),
                'installed'   => class_exists( '\\Elementor\\Plugin' ),
            ],
            'polylang'                  => [
                'name'        => 'Polylang',
                'description' => __( 'Lists purchased content in shortcodes and emails in every language.', 'wc_pay_per_post' ),
                'installed'   => function_exists( 'pll_current_language' ),
            ],
        ];
        foreach ( $tiles as $key => $tile ) {
            if ( 'polylang' === $key ) {
                $active = $premium && $tile['installed'] && $enable_polylang;
            } else {
                $active = $premium && isset( $integrations[$key] );
            }
            if ( !$tile['installed'] ) {
                $state = 'missing';
            } elseif ( !$premium ) {
                $state = 'needs-premium';
            } elseif ( $active ) {
                $state = 'active';
            } else {
                $state = 'off';
            }
            $tiles[$key]['active'] = $active;
            $tiles[$key]['state'] = $state;
        }
        return $tiles;
    }

    /**
     * The debug log's location and size. One file check, no database.
     *
     * @return array{path: string, url: string, exists: bool, size: int}
     */
    public static function debug_log_info() : array {
        $path = Woocommerce_Pay_Per_Post_Helper::logger_uri();
        $exists = '' !== $path && file_exists( $path );
        return [
            'path'   => $path,
            'url'    => Woocommerce_Pay_Per_Post_Helper::logger_url(),
            'exists' => $exists,
            'size'   => ( $exists ? (int) filesize( $path ) : 0 ),
        ];
    }

    /**
     * The Protected Content screen (Premium): protected posts a page at a time,
     * and a post's buyers only when asked (ajax_protected_content_buyers(), or
     * ?product_id=<post ID> without JavaScript, the link earlier versions used).
     * See Woocommerce_Pay_Per_Post_Protected_Content for the rule it keeps.
     */
    public function create_protected_content_page() {
        if ( !current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'wc_pay_per_post' ) );
        }
        $wcppp_content = new Woocommerce_Pay_Per_Post_Protected_Content($this);
        $wcppp_request = Woocommerce_Pay_Per_Post_Protected_Content::request( $_GET );
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only filters.
        $wcppp_buyers = null;
        if ( $wcppp_request['post'] ) {
            $wcppp_buyers_post = $wcppp_content->protected_post( $wcppp_request['post'] );
            if ( $wcppp_buyers_post ) {
                $wcppp_buyers = $wcppp_content->buyers( $wcppp_buyers_post );
            }
        }
        if ( null === $wcppp_buyers ) {
            $wcppp_query = $wcppp_content->query( $wcppp_request );
            $wcppp_rows = $wcppp_content->rows( $wcppp_query->posts );
        }
        require plugin_dir_path( __FILE__ ) . 'partials/protected-content.php';
    }

    /**
     * The buyers of one protected post's products, as HTML for the Protected
     * Content screen's "Show buyers" control.
     */
    public function ajax_protected_content_buyers() {
        check_ajax_referer( Woocommerce_Pay_Per_Post_Protected_Content::BUYERS_NONCE, 'nonce' );
        if ( !current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [
                'message' => __( 'You are not allowed to list buyers.', 'wc_pay_per_post' ),
            ], 403 );
        }
        if ( !Woocommerce_Pay_Per_Post_Helper::can_use_premium_protection() ) {
            wp_send_json_error( [
                'message' => __( 'The Protected Content screen needs Premium.', 'wc_pay_per_post' ),
            ], 403 );
        }
        $content = new Woocommerce_Pay_Per_Post_Protected_Content($this);
        $post = $content->protected_post( ( isset( $_POST['post'] ) ? absint( $_POST['post'] ) : 0 ) );
        if ( !$post ) {
            wp_send_json_error( [
                'message' => __( 'That post is not protected.', 'wc_pay_per_post' ),
            ], 404 );
        }
        $wcppp_buyers = $content->buyers( $post );
        ob_start();
        require plugin_dir_path( __FILE__ ) . 'partials/protected-content-buyers.php';
        $html = (string) ob_get_clean();
        wp_send_json_success( [
            'html'  => $html,
            'total' => $wcppp_buyers['total'],
            'shown' => count( $wcppp_buyers['buyers'] ),
        ] );
    }

    /**
     * The Need Help screen (slug -support, which earlier versions called
     * Support). Five views:
     *
     * - start: a customer check, the docs and contact, and the site's status;
     * - check: the access diagnostic;
     * - tools: status and maintenance tools;
     * - log:   the debug log, with its tab shown while logging is on or a
     *          log is left to delete;
     * - docs:  the documentation in docs/;
     * - whats-new: recent releases.
     *
     * It replaces the Debug, Tools, Getting Started, Documentation and What's
     * New screens, whose slugs redirect here.
     */
    public function create_support_page() {
        if ( !current_user_can( 'manage_options' ) ) {
            wp_die( esc_html( 'You do not have sufficient permissions to access this page.' ) );
        }
        $support_notices = $this->handle_support_actions();
        // Access diagnostic. This evaluates access AS ANOTHER USER, so it is
        // gated on manage_options in addition to the nonce.
        $diagnostic_email = '';
        $diagnostic_url = '';
        $diagnostic_result = null;
        if ( isset( $_POST['wc_ppp_access_diagnostic_nonce'] ) && wp_verify_nonce( $_POST['wc_ppp_access_diagnostic_nonce'], 'wc_ppp_access_diagnostic' ) && current_user_can( 'manage_options' ) ) {
            $diagnostic_email = sanitize_email( wp_unslash( $_POST['wc_ppp_diagnostic_email'] ?? '' ) );
            $raw_url = trim( (string) wp_unslash( $_POST['wc_ppp_diagnostic_url'] ?? '' ) );
            // A bare post ID is an accepted input — it is the fallback when a URL
            // will not resolve. It has to be checked BEFORE esc_url_raw(), which
            // turns "1010" into "http://1010" rather than rejecting it.
            $diagnostic_url = ( ctype_digit( $raw_url ) ? $raw_url : esc_url_raw( $raw_url ) );
            $diagnostic_result = Woocommerce_Pay_Per_Post_Access_Diagnostic::run( $diagnostic_email, $diagnostic_url );
        } elseif ( isset( $_GET['post'] ) ) {
            // "Check a customer" in the post editor box opens this with the post
            // filled in, so only the email is left to type.
            $diagnostic_url = (string) absint( $_GET['post'] );
        }
        $support_views = self::support_views();
        $support_view = ( isset( $_GET['view'] ) ? sanitize_key( wp_unslash( $_GET['view'] ) ) : 'start' );
        $support_view = ( 'diagnose' === $support_view ? 'check' : $support_view );
        // 3.4.0 development builds.
        $support_view = ( 'tools' === $support_view && isset( $_GET['log'] ) ? 'log' : $support_view );
        // Likewise.
        if ( !isset( $support_views[$support_view] ) ) {
            $support_view = 'start';
        }
        if ( null !== $diagnostic_result ) {
            $support_view = 'check';
        }
        $premium = Woocommerce_Pay_Per_Post_Helper::can_use_premium_protection();
        $enable_debugging = (bool) get_option( WC_PPP_SLUG . '_enable_debugging', false );
        $debug_log = self::debug_log_info();
        $page_view_table = ( $premium ? Woocommerce_Pay_Per_Post_Debug::page_view_table_exists() : null );
        $status_rows = $this->support_status_rows(
            $premium,
            $enable_debugging,
            $debug_log,
            $page_view_table
        );
        $changelog = ( in_array( $support_view, ['start', 'whats-new'], true ) ? self::readme_changelog( ( 'start' === $support_view ? 1 : 4 ) ) : [] );
        $show_log_tab = $enable_debugging || $debug_log['exists'];
        $log_tail = ( 'log' === $support_view && $debug_log['exists'] ? self::read_log_tail( $debug_log['path'] ) : '' );
        // Docs: one page, a search, or the index. The page must be one of the
        // files in docs/; anything else shows the index.
        $is_docs = 'docs' === $support_view;
        $doc_slug = ( $is_docs && isset( $_GET['doc'] ) ? sanitize_text_field( wp_unslash( $_GET['doc'] ) ) : '' );
        $doc_query = ( $is_docs && isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '' );
        $doc = ( '' !== $doc_slug ? Woocommerce_Pay_Per_Post_Docs::page( $doc_slug ) : null );
        $doc_query = ( $doc ? '' : $doc_query );
        $doc_results = ( '' !== $doc_query ? Woocommerce_Pay_Per_Post_Docs::search( $doc_query ) : [] );
        $doc_sections = ( $is_docs ? Woocommerce_Pay_Per_Post_Docs::sections() : [] );
        require plugin_dir_path( __FILE__ ) . 'partials/support.php';
    }

    /**
     * The Need Help screen's views, in tab order.
     *
     * @return array<string, string> View => label.
     */
    public static function support_views() : array {
        return [
            'start'     => __( 'Start', 'wc_pay_per_post' ),
            'check'     => __( 'Check a customer', 'wc_pay_per_post' ),
            'tools'     => __( 'Status and tools', 'wc_pay_per_post' ),
            'log'       => __( 'Debug log', 'wc_pay_per_post' ),
            'docs'      => __( 'Docs', 'wc_pay_per_post' ),
            'whats-new' => __( 'What\'s new', 'wc_pay_per_post' ),
        ];
    }

    /**
     * The Settings screen's "Who can comment on protected posts" choice, from
     * the two settings it is made of: everyone, customers (with access) or
     * nobody.
     *
     * @param bool $premium         Whether the premium setting applies.
     * @param bool $when_protected  _turn_off_comments_when_protected.
     * @param bool $public          _turn_off_comments_public.
     *
     * @return string
     */
    public static function comments_mode( bool $premium, bool $when_protected, bool $public ) : string {
        if ( $premium && $public ) {
            return 'customers';
        }
        return ( $when_protected ? 'nobody' : 'everyone' );
    }

    /**
     * The comments choice posted with the Settings form, when it differs from
     * the one the form was shown with. The form's script sets the two
     * settings as the choice changes; this does the same when the script did
     * not run. An unchanged choice leaves both settings exactly as posted.
     *
     * @return string Empty when unchanged or not posted.
     */
    private static function changed_comments_mode() : string {
        // phpcs:disable WordPress.Security.NonceVerification.Missing -- options.php checks the nonce.
        if ( !isset( $_POST['option_page'], $_POST['wc_ppp_comments_mode'], $_POST['wc_ppp_comments_mode_shown'] ) || WC_PPP_SLUG . '_settings' !== $_POST['option_page'] ) {
            return '';
        }
        $mode = sanitize_key( wp_unslash( $_POST['wc_ppp_comments_mode'] ) );
        $shown = sanitize_key( wp_unslash( $_POST['wc_ppp_comments_mode_shown'] ) );
        // phpcs:enable
        return ( in_array( $mode, ['everyone', 'customers', 'nobody'], true ) && $mode !== $shown ? $mode : '' );
    }

    /**
     * "Customers with access" keeps this on too, so that comments close again
     * if the licence lapses.
     *
     * @param mixed $value
     *
     * @return mixed
     */
    public static function sanitize_comments_when_protected( $value ) {
        $mode = self::changed_comments_mode();
        return ( '' === $mode ? $value : (( 'everyone' === $mode ? '' : '1' )) );
    }

    /**
     * @param mixed $value
     *
     * @return mixed
     */
    public static function sanitize_comments_public( $value ) {
        $mode = self::changed_comments_mode();
        return ( '' === $mode ? $value : (( 'customers' === $mode ? '1' : '' )) );
    }

    /**
     * A Need Help view's address.
     *
     * @param string $view A key of support_views().
     *
     * @return string
     */
    public static function support_url( string $view = 'start' ) : string {
        $url = admin_url( 'admin.php?page=' . WC_PPP_SLUG . '-support' );
        return ( 'start' === $view ? $url : add_query_arg( 'view', $view, $url ) );
    }

    /**
     * The diagnostic's checks as a checklist, in the order the engine runs them.
     *
     * Presentation only. The answers are the engine's own, captured by the
     * access diagnostic; this adds a plain-language name for each check and
     * says whether its answer helps the customer in, keeps them out, or does
     * not apply.
     *
     * @param array<string, bool> $checks Check name => the engine's answer, in run order.
     *
     * @return array<int, array{check: string, label: string, answer: bool, detail: string, state: string}>
     *         state is pass (holds, on the way in), fail (keeps them out) or skip (does not apply).
     */
    public static function diagnostic_checklist( array $checks ) : array {
        $has = static function ( string $check ) use($checks) : bool {
            return !empty( $checks[$check] );
        };
        // label, detail when true, detail when false, state when true, state when false.
        $map = [
            'check_if_logged_in'                                              => [
                __( 'Logged in to an account', 'wc_pay_per_post' ),
                __( 'Access belongs to an account, so this comes first.', 'wc_pay_per_post' ),
                __( 'Without an account nothing can be unlocked.', 'wc_pay_per_post' ),
                'pass',
                'fail'
            ],
            'check_if_protected'                                              => [
                __( 'Page is protected', 'wc_pay_per_post' ),
                __( 'At least one product, plan or level unlocks it.', 'wc_pay_per_post' ),
                __( 'Nothing protects it, so everyone can read it.', 'wc_pay_per_post' ),
                'pass',
                'skip'
            ],
            'check_if_should_show_paywall'                                    => [
                __( 'Paywall is due on this visit', 'wc_pay_per_post' ),
                __( 'Nothing holds the paywall back.', 'wc_pay_per_post' ),
                __( 'The page\'s delay has not run out, so the content shows for now.', 'wc_pay_per_post' ),
                'pass',
                'skip'
            ],
            'check_if_admin_call'                                             => [
                __( 'Request from wp-admin', 'wc_pay_per_post' ),
                __( 'Requests from wp-admin always see the content.', 'wc_pay_per_post' ),
                __( 'Checked as a visit to the page itself.', 'wc_pay_per_post' ),
                'pass',
                'skip'
            ],
            'check_if_purchased'                                              => [
                __( 'Bought a product that unlocks it', 'wc_pay_per_post' ),
                __( 'A paid order holds one of the page\'s products.', 'wc_pay_per_post' ),
                __( 'No paid order holds one of the page\'s products.', 'wc_pay_per_post' ),
                'pass',
                'fail'
            ],
            'check_if_admin_user_have_access'                                 => [
                __( 'Administrator who sees everything', 'wc_pay_per_post' ),
                __( 'The "Administrators see everything" setting lets them in.', 'wc_pay_per_post' ),
                __( 'Not an administrator covered by that setting.', 'wc_pay_per_post' ),
                'pass',
                'skip'
            ],
            'check_if_user_role_has_access'                                   => [
                __( 'Role that skips the paywall', 'wc_pay_per_post' ),
                __( 'Their role is set to skip the paywall (Settings > Access, or the wc_pay_for_post_allowed_roles filter).', 'wc_pay_per_post' ),
                __( 'No role of theirs skips the paywall.', 'wc_pay_per_post' ),
                'pass',
                'skip'
            ],
            'check_if_has_access'                                             => [
                __( 'Access still valid', 'wc_pay_per_post' ),
                __( 'Within the page\'s access window or page-view allowance, if it has one.', 'wc_pay_per_post' ),
                __( 'Not bought, or the access window or page-view allowance is used up.', 'wc_pay_per_post' ),
                'pass',
                'fail'
            ],
            'check_if_post_contains_subscription_products'                    => [
                __( 'Unlocked by a subscription', 'wc_pay_per_post' ),
                __( 'An active subscription is needed as well as the purchase.', 'wc_pay_per_post' ),
                __( 'No subscription products protect it.', 'wc_pay_per_post' ),
                'pass',
                'skip'
            ],
            'check_if_post_contains_membership_products'                      => [
                __( 'Unlocked by a membership plan', 'wc_pay_per_post' ),
                __( 'A WooCommerce Memberships plan protects it.', 'wc_pay_per_post' ),
                __( 'No membership plan protects it.', 'wc_pay_per_post' ),
                'pass',
                'skip'
            ],
            'check_if_is_member'                                              => [
                __( 'Active membership', 'wc_pay_per_post' ),
                __( 'They hold an active membership in a plan that unlocks it.', 'wc_pay_per_post' ),
                __( 'No active membership in a plan that unlocks it.', 'wc_pay_per_post' ),
                'pass',
                ( $has( 'check_if_post_contains_membership_products' ) ? 'fail' : 'skip' )
            ],
            'check_if_is_subscriber'                                          => [
                __( 'Active subscription', 'wc_pay_per_post' ),
                __( 'A subscription containing the product is active or pending cancellation.', 'wc_pay_per_post' ),
                __( 'No active subscription contains the product.', 'wc_pay_per_post' ),
                'pass',
                ( $has( 'check_if_post_contains_subscription_products' ) ? 'fail' : 'skip' )
            ],
            'check_if_post_contains_paid_memberships_pro_membership_products' => [
                __( 'Unlocked by a Paid Memberships Pro level', 'wc_pay_per_post' ),
                __( 'A membership level protects it.', 'wc_pay_per_post' ),
                __( 'No membership level protects it.', 'wc_pay_per_post' ),
                'pass',
                'skip'
            ],
            'check_if_is_paid_memberships_pro_member'                         => [
                __( 'Paid Memberships Pro member', 'wc_pay_per_post' ),
                __( 'They hold a level that unlocks it.', 'wc_pay_per_post' ),
                __( 'They hold no level that unlocks it.', 'wc_pay_per_post' ),
                'pass',
                ( $has( 'check_if_post_contains_paid_memberships_pro_membership_products' ) ? 'fail' : 'skip' )
            ],
        ];
        $rows = [];
        foreach ( $checks as $check => $answer ) {
            $check = (string) $check;
            $answer = (bool) $answer;
            $entry = $map[$check] ?? [
                $check,
                '',
                '',
                'pass',
                'skip'
            ];
            $rows[] = [
                'check'  => $check,
                'label'  => $entry[0],
                'answer' => $answer,
                'detail' => ( $answer ? $entry[1] : $entry[2] ),
                'state'  => ( $answer ? $entry[3] : $entry[4] ),
            ];
        }
        return $rows;
    }

    /**
     * Run a Status and tools button, if one was pressed.
     *
     * Each keeps the nonce it had on the Debug and Tools screens, and the
     * screen's manage_options capability is checked again here.
     *
     * @return array<int, array{type: string, message: string}> Notices to show.
     */
    protected function handle_support_actions() : array {
        $notices = [];
        if ( 'POST' !== ($_SERVER['REQUEST_METHOD'] ?? '') || !current_user_can( 'manage_options' ) ) {
            return $notices;
        }
        // create_table__premium_only() is stripped from the free build. The
        // button that posts this nonce is only rendered for licensed installs,
        // and the licence check keeps a stray POST from reaching it there.
        if ( isset( $_POST['wc_ppp_create_table_nonce'] ) && wp_verify_nonce( $_POST['wc_ppp_create_table_nonce'], 'wc_ppp_create_table' ) && Woocommerce_Pay_Per_Post_Helper::can_use_premium_protection() ) {
            require_once WC_PPP_PATH . '/includes/class-woocommerce-pay-per-post-activator.php';
            Woocommerce_Pay_Per_Post_Activator::create_table__premium_only();
            $notices[] = [
                'type'    => 'success',
                'message' => __( 'The page-view table was created.', 'wc_pay_per_post' ),
            ];
        }
        if ( isset( $_POST['wc_ppp_delete_transients_nonce'] ) && wp_verify_nonce( $_POST['wc_ppp_delete_transients_nonce'], 'wc_ppp_delete_transients' ) ) {
            Woocommerce_Pay_Per_Post_Debug::delete_transients();
            $notices[] = [
                'type'    => 'success',
                'message' => __( 'Cached lists cleared. They are rebuilt the next time they are shown.', 'wc_pay_per_post' ),
            ];
        }
        if ( isset( $_POST['wc_ppp_reset_data_nonce'] ) && wp_verify_nonce( $_POST['wc_ppp_reset_data_nonce'], 'wc_ppp_reset_data' ) ) {
            Woocommerce_Pay_Per_Post_Tools::reset_data();
            $notices[] = [
                'type'    => 'success',
                'message' => __( 'All protection was removed. Every post is now free to read.', 'wc_pay_per_post' ),
            ];
        }
        if ( isset( $_POST['wc_ppp_reset_page_views_nonce'] ) && wp_verify_nonce( $_POST['wc_ppp_reset_page_views_nonce'], 'wc_ppp_reset_page_views' ) ) {
            Woocommerce_Pay_Per_Post_Tools::reset_page_views();
            $notices[] = [
                'type'    => 'success',
                'message' => __( 'Page-view counts were reset. Every customer has their full allowance again.', 'wc_pay_per_post' ),
            ];
        }
        if ( isset( $_POST['wc_ppp_delete_log_nonce'] ) && wp_verify_nonce( $_POST['wc_ppp_delete_log_nonce'], 'wc_ppp_delete_log' ) ) {
            Woocommerce_Pay_Per_Post_Tools::delete_log();
            $notices[] = [
                'type'    => 'success',
                'message' => __( 'The debug log was deleted.', 'wc_pay_per_post' ),
            ];
        }
        // "Turn off and delete" on the debug log warning: the setting and the
        // log it wrote, in one step, so nothing keeps recording afterwards.
        if ( isset( $_POST['wc_ppp_debug_off_nonce'] ) && wp_verify_nonce( $_POST['wc_ppp_debug_off_nonce'], 'wc_ppp_debug_off' ) ) {
            update_option( WC_PPP_SLUG . '_enable_debugging', '' );
            Woocommerce_Pay_Per_Post_Tools::delete_log();
            $notices[] = [
                'type'    => 'success',
                'message' => __( 'Debug logging is off and the log was deleted.', 'wc_pay_per_post' ),
            ];
        }
        // Moving post meta from version 1. No screen has offered this form
        // since What's New lost its button, but a POST is still honoured. The
        // query runs only when it is asked for.
        if ( isset( $_POST['wc_ppp_upgrade_nonce'] ) && wp_verify_nonce( $_POST['wc_ppp_upgrade_nonce'], 'wc_ppp_upgrade' ) ) {
            $custom_post_types = get_option( WC_PPP_SLUG . '_custom_post_types', [] );
            $custom_post_types = ( empty( $custom_post_types ) ? [] : $custom_post_types );
            if ( !is_array( $custom_post_types ) ) {
                $custom_post_types = explode( ',', $custom_post_types );
            }
            $this->upgrade_database( new WP_Query([
                'post_type' => $custom_post_types,
                'meta_key'  => 'woocommerce_ppp_product_id',
                'nopaging'  => true,
            ]) );
        }
        return $notices;
    }

    /**
     * The status table on Need Help. Option reads, class checks, one check that the
     * page-view table exists and the debug log's size: nothing that grows with
     * the store.
     *
     * @param bool      $premium          Whether premium code may run.
     * @param bool      $enable_debugging The debug setting.
     * @param array     $debug_log        From debug_log_info().
     * @param bool|null $page_view_table  Whether the page-view table exists; null when not checked.
     *
     * @return array<int, array{label: string, value: string, state: string}>
     */
    protected function support_status_rows(
        bool $premium,
        bool $enable_debugging,
        array $debug_log,
        $page_view_table
    ) : array {
        $versions = Woocommerce_Pay_Per_Post_Access_Diagnostic::versions();
        $rows = [
            [
                'label' => __( 'Pay For Post', 'wc_pay_per_post' ),
                'value' => $versions['Pay For Post'] . ' ' . (( $premium ? __( 'Premium', 'wc_pay_per_post' ) : __( 'Free', 'wc_pay_per_post' ) )),
                'state' => '',
            ],
            [
                'label' => __( 'WordPress', 'wc_pay_per_post' ),
                'value' => (string) $versions['WordPress'],
                'state' => '',
            ],
            [
                'label' => __( 'WooCommerce', 'wc_pay_per_post' ),
                'value' => (string) $versions['WooCommerce'],
                'state' => '',
            ],
            [
                'label' => __( 'PHP', 'wc_pay_per_post' ),
                'value' => (string) $versions['PHP'],
                'state' => '',
            ],
            [
                'label' => __( 'Order storage', 'wc_pay_per_post' ),
                'value' => ( Woocommerce_Pay_Per_Post_Helper::hops_enabled() ? __( 'HPOS', 'wc_pay_per_post' ) : __( 'Legacy post tables', 'wc_pay_per_post' ) ),
                'state' => '',
            ]
        ];
        $guest_checkout = 'yes' === get_option( 'woocommerce_enable_guest_checkout' );
        $rows[] = [
            'label' => __( 'Guest checkout', 'wc_pay_per_post' ),
            'value' => ( $guest_checkout ? __( 'On: guests get no access', 'wc_pay_per_post' ) : __( 'Off', 'wc_pay_per_post' ) ),
            'state' => ( $guest_checkout ? 'err' : 'ok' ),
        ];
        $log_value = ( $enable_debugging ? __( 'On', 'wc_pay_per_post' ) : __( 'Off', 'wc_pay_per_post' ) );
        if ( $debug_log['exists'] ) {
            $log_value .= ' · ' . size_format( $debug_log['size'], 1 );
        }
        $rows[] = [
            'label' => __( 'Debug log', 'wc_pay_per_post' ),
            'value' => $log_value,
            'state' => ( $enable_debugging ? 'warn' : '' ),
        ];
        if ( null !== $page_view_table ) {
            $rows[] = [
                'label' => __( 'Page-view table', 'wc_pay_per_post' ),
                'value' => ( $page_view_table ? __( 'Present', 'wc_pay_per_post' ) : __( 'Missing', 'wc_pay_per_post' ) ),
                'state' => ( $page_view_table ? 'ok' : 'err' ),
            ];
        }
        $detected = [];
        foreach ( $this->integration_tiles( $premium, (bool) get_option( WC_PPP_SLUG . '_enable_polylang', false ) ) as $tile ) {
            if ( $tile['installed'] ) {
                $detected[] = $tile['name'] . (( $tile['active'] ? '' : ' (' . __( 'not in use', 'wc_pay_per_post' ) . ')' ));
            }
        }
        $rows[] = [
            'label' => __( 'Integrations', 'wc_pay_per_post' ),
            'value' => ( $detected ? implode( ', ', $detected ) : __( 'None detected', 'wc_pay_per_post' ) ),
            'state' => '',
        ];
        return $rows;
    }

    /**
     * The end of the debug log, read as text.
     *
     * The Debug screen used include() to print the log, which runs it as PHP.
     * This reads it, and only the last part, so a large log cannot stall the
     * screen.
     *
     * @param string $path  Log file.
     * @param int    $bytes How much of the end to read.
     *
     * @return string
     */
    public static function read_log_tail( string $path, int $bytes = 65536 ) : string {
        if ( '' === $path || !is_readable( $path ) ) {
            return '';
        }
        $size = (int) filesize( $path );
        $handle = fopen( $path, 'rb' );
        if ( !$handle ) {
            return '';
        }
        if ( $size > $bytes ) {
            fseek( $handle, -$bytes, SEEK_END );
        }
        $text = (string) fread( $handle, $bytes );
        fclose( $handle );
        // Drop the partial line a mid-file start leaves.
        if ( $size > $bytes && false !== ($newline = strpos( $text, "\n" )) ) {
            $text = substr( $text, $newline + 1 );
        }
        return $text;
    }

    /**
     * Recent releases from README.txt's changelog, for What's New.
     *
     * @param int $limit How many releases.
     *
     * @return array<int, array{version: string, items: array<int, array{kind: string, premium: bool, text: string}>}>
     */
    public static function readme_changelog( int $limit = 4 ) : array {
        $readme = ( is_readable( WC_PPP_PATH . 'README.txt' ) ? (string) file_get_contents( WC_PPP_PATH . 'README.txt' ) : '' );
        $parts = explode( '== Changelog ==', $readme, 2 );
        if ( count( $parts ) < 2 ) {
            return [];
        }
        // Stop at the next top-level section, if there is one.
        $log = preg_split( '/^==\\s/m', $parts[1], 2 )[0];
        if ( !preg_match_all(
            '/^=\\s*([^=\\r\\n]+?)\\s*=\\s*$/m',
            $log,
            $headings,
            PREG_OFFSET_CAPTURE
        ) ) {
            return [];
        }
        $kinds = [
            'SECURITY'    => __( 'Security', 'wc_pay_per_post' ),
            'BUG FIX'     => __( 'Fixed', 'wc_pay_per_post' ),
            'FIX'         => __( 'Fixed', 'wc_pay_per_post' ),
            'NEW'         => __( 'New', 'wc_pay_per_post' ),
            'ENHANCEMENT' => __( 'Improved', 'wc_pay_per_post' ),
            'IMPROVEMENT' => __( 'Improved', 'wc_pay_per_post' ),
            'CHANGE'      => __( 'Changed', 'wc_pay_per_post' ),
            'CHANGED'     => __( 'Changed', 'wc_pay_per_post' ),
            'TWEAK'       => __( 'Changed', 'wc_pay_per_post' ),
        ];
        $releases = [];
        $count = count( $headings[0] );
        for ($i = 0; $i < $count && count( $releases ) < $limit; $i++) {
            $start = $headings[0][$i][1] + strlen( $headings[0][$i][0] );
            $end = ( $i + 1 < $count ? $headings[0][$i + 1][1] : strlen( $log ) );
            $body = substr( $log, $start, $end - $start );
            $items = [];
            foreach ( preg_split( '/\\r?\\n/', $body ) as $line ) {
                $line = trim( $line );
                if ( 0 !== strpos( $line, '*' ) ) {
                    continue;
                }
                $line = trim( substr( $line, 1 ) );
                $kind = '';
                $premium = false;
                if ( preg_match( '/^([A-Z][A-Z ]+?)\\s*(\\(PREMIUM\\))?\\s+-\\s+(.*)$/', $line, $m ) ) {
                    $kind = $kinds[trim( $m[1] )] ?? ucfirst( strtolower( trim( $m[1] ) ) );
                    $premium = '' !== $m[2];
                    $line = $m[3];
                }
                $items[] = [
                    'kind'    => $kind,
                    'premium' => $premium,
                    'text'    => $line,
                ];
            }
            $releases[] = [
                'version' => trim( $headings[1][$i][0] ),
                'items'   => $items,
            ];
        }
        return $releases;
    }

    /**
     * Render the paywall preview for the Settings screen.
     *
     * admin-ajax, on an explicit click, for one post the administrator names.
     * The real paywall renderer runs with the message as it stands in the
     * editor, so tokens, shortcodes and the post's own override behave exactly
     * as they will for a visitor.
     */
    public function ajax_preview_paywall() {
        check_ajax_referer( 'wc_ppp_preview_paywall', 'nonce' );
        if ( !current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [
                'message' => __( 'You are not allowed to preview the paywall.', 'wc_pay_per_post' ),
            ], 403 );
        }
        $target = ( isset( $_POST['post'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['post'] ) ) ) : '' );
        if ( '' === $target ) {
            wp_send_json_error( [
                'message' => __( 'Enter a post ID or URL to preview the paywall on.', 'wc_pay_per_post' ),
            ], 400 );
        }
        $post_id = Woocommerce_Pay_Per_Post_Access_Diagnostic::resolve_post( ( ctype_digit( $target ) ? $target : esc_url_raw( $target ) ) );
        if ( !$post_id ) {
            wp_send_json_error( [
                'message' => __( 'No post was found at that ID or URL on this site.', 'wc_pay_per_post' ),
            ], 404 );
        }
        $message = ( isset( $_POST['message'] ) ? wp_kses_post( wp_unslash( $_POST['message'] ) ) : '' );
        wp_send_json_success( self::render_paywall_preview( $post_id, $message ) );
    }

    /**
     * The paywall one post would show, with $message as the default message.
     *
     * @param int    $post_id The post to render it for.
     * @param string $message The default paywall message to use instead of the saved one.
     *
     * @return array{html: string, title: string, post_id: int, protected: bool, override: bool, stylesheets: string[]}
     */
    public static function render_paywall_preview( int $post_id, string $message ) : array {
        global $post;
        $preview_post = get_post( $post_id );
        $saved_post = $post;
        $use_draft = static function () use($message) {
            return $message;
        };
        add_filter( 'pre_option_' . WC_PPP_SLUG . '_restricted_content_default', $use_draft );
        try {
            $post = $preview_post;
            // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
            setup_postdata( $preview_post );
            $restrict = new Woocommerce_Pay_Per_Post_Restrict_Content($post_id, false);
            $protection = Woocommerce_Pay_Per_Post_Helper::get_product_ids_by_post_id( $post_id );
            $html = $restrict->show_paywall( (string) $preview_post->post_content );
        } finally {
            remove_filter( 'pre_option_' . WC_PPP_SLUG . '_restricted_content_default', $use_draft );
            $post = $saved_post;
            // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
            if ( $saved_post instanceof WP_Post ) {
                setup_postdata( $saved_post );
            }
        }
        $stylesheets = [];
        if ( function_exists( 'WC' ) ) {
            $stylesheets[] = WC()->plugin_url() . '/assets/css/woocommerce-layout.css';
            $stylesheets[] = WC()->plugin_url() . '/assets/css/woocommerce.css';
        }
        return [
            'html'        => $html,
            'title'       => get_the_title( $preview_post ),
            'post_id'     => $post_id,
            'protected'   => !empty( $protection['is_protected'] ),
            'override'    => '' !== trim( (string) get_post_meta( $post_id, WC_PPP_SLUG . '_restricted_content_override', true ) ),
            'stylesheets' => $stylesheets,
        ];
    }

    /** @noinspection PhpUnused */
    public function meta_box() {
        if ( Woocommerce_Pay_Per_Post_Helper::allowed_roles_for_meta_box() ) {
            $post_types = $this->get_post_types();
            foreach ( $post_types as $post_type ) {
                add_meta_box(
                    WC_PPP_SLUG . '_meta_box',
                    __( 'Pay For Post', 'wc_pay_per_post' ),
                    [$this, 'output_meta_box'],
                    $post_type,
                    'normal',
                    'high',
                    [
                        '__block_editor_compatible_meta_box' => true,
                    ]
                );
            }
        }
    }

    public function get_post_types() : array {
        return Woocommerce_Pay_Per_Post_Helper::get_post_types();
    }

    public function output_meta_box() {
        ob_start();
        global $post;
        $id = $post->ID;
        $enable_block_editor_integration = (bool) get_option( WC_PPP_SLUG . '_block_editor_enabled' );
        if ( $enable_block_editor_integration && WP_Screen::get()->is_block_editor() ) {
            $meta_box_file_name = 'meta-box.php';
            $restricted_content_override = get_post_meta( $id, WC_PPP_SLUG . '_restricted_content_override', true );
        } else {
            $selected = get_post_meta( $id, WC_PPP_SLUG . '_product_ids', true );
            $delay_restriction_enable = get_post_meta( $id, WC_PPP_SLUG . '_delay_restriction_enable', true );
            $delay_restriction = get_post_meta( $id, WC_PPP_SLUG . '_delay_restriction', true );
            $delay_restriction_frequency = get_post_meta( $id, WC_PPP_SLUG . '_delay_restriction_frequency', true );
            $page_view_restriction_enable = get_post_meta( $id, WC_PPP_SLUG . '_page_view_restriction_enable', true );
            $page_view_restriction = get_post_meta( $id, WC_PPP_SLUG . '_page_view_restriction', true );
            $page_view_restriction_frequency = get_post_meta( $id, WC_PPP_SLUG . '_page_view_restriction_frequency', true );
            $page_view_restriction_enable_time_frame = get_post_meta( $id, WC_PPP_SLUG . '_page_view_restriction_enable_time_frame', true );
            $page_view_restriction_time_frame = get_post_meta( $id, WC_PPP_SLUG . '_page_view_restriction_time_frame', true );
            $expire_restriction_enable = get_post_meta( $id, WC_PPP_SLUG . '_expire_restriction_enable', true );
            $expire_restriction = get_post_meta( $id, WC_PPP_SLUG . '_expire_restriction', true );
            $expire_restriction_frequency = get_post_meta( $id, WC_PPP_SLUG . '_expire_restriction_frequency', true );
            $show_warnings = get_post_meta( $id, WC_PPP_SLUG . '_show_warnings', true );
            $restricted_content_override = get_post_meta( $id, WC_PPP_SLUG . '_restricted_content_override', true );
            $drop_down = $this->generate_products_dropdown( $selected );
            $box = $this->meta_box_view( $id, [
                'delay'     => $delay_restriction_enable,
                'page-view' => $page_view_restriction_enable,
                'expire'    => $expire_restriction_enable,
            ] );
            wp_enqueue_style( WC_PPP_SLUG . '_admin' );
            wp_enqueue_style( WC_PPP_SLUG . '_select2' );
            wp_enqueue_script( WC_PPP_SLUG . '_select2' );
            wp_enqueue_script( WC_PPP_SLUG . '_admin' );
            wp_localize_script( WC_PPP_SLUG . '_admin', 'wcPppBox', self::meta_box_summaries() );
            $meta_box_file_name = 'meta-box-base.php';
        }
        require plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/' . $meta_box_file_name;
        echo ob_get_clean();
    }

    /**
     * What the post editor box needs beyond the post's stored settings: the
     * protection type, what this site's plan offers, and where its links go.
     * The classic box and the block editor sidebar both use it.
     *
     * @param int                 $post_id The post being edited.
     * @param array<string, bool> $enabled Each Premium type's stored "enable" flag, keyed delay, page-view and expire.
     *
     * @return array{type: string, stored_type: string, premium: bool, upgrade_url: string, preview_url: string, check_url: string, frequencies: array<string, string>}
     *         type is the one to show selected: the stored type, or standard while
     *         Premium is not active, which is how the paywall treats the post then.
     */
    public function meta_box_view( int $post_id, array $enabled ) : array {
        $stored_type = 'standard';
        // The order the paywall reads them in, first match wins, so a post with
        // more than one flag set shows the type that is in force.
        foreach ( ['delay', 'page-view', 'expire'] as $type ) {
            if ( !empty( $enabled[$type] ) ) {
                $stored_type = $type;
                break;
            }
        }
        $premium = Woocommerce_Pay_Per_Post_Helper::can_use_premium_protection();
        $upgrade_url = ( wcppp_freemius()->is_not_paying() && !wcppp_freemius()->is_trial() ? wcppp_freemius()->get_upgrade_url() : '' );
        $links = current_user_can( 'manage_options' ) && 'auto-draft' !== get_post_status( $post_id );
        return [
            'type'        => ( $premium ? $stored_type : 'standard' ),
            'stored_type' => $stored_type,
            'premium'     => $premium,
            'upgrade_url' => (string) $upgrade_url,
            'preview_url' => ( $links ? add_query_arg( 'preview', $post_id, admin_url( 'admin.php?page=' . WC_PPP_SLUG . '-settings&tab=paywall' ) ) . '#wcppp-preview-post' : '' ),
            'check_url'   => ( $links ? add_query_arg( 'post', $post_id, self::support_url( 'check' ) ) : '' ),
            'frequencies' => [
                'minute' => __( 'minutes', 'wc_pay_per_post' ),
                'hour'   => __( 'hours', 'wc_pay_per_post' ),
                'day'    => __( 'days', 'wc_pay_per_post' ),
                'week'   => __( 'weeks', 'wc_pay_per_post' ),
                'month'  => __( 'months', 'wc_pay_per_post' ),
                'year'   => __( 'years', 'wc_pay_per_post' ),
            ],
        ];
    }

    // phpcs:disable WordPress.Security.NonceVerification.Missing -- Called only from save_meta_box(), after its nonce check.
    /**
     * Save the post's own paywall message, from either editor.
     *
     * The box offers "Your default message" or "A message for this post". The
     * default clears the post's message, which is what makes the paywall use the
     * default. A form without that choice (the block editor's meta box before
     * the redesign, or a tab left open across the update) saves the message
     * field as it is, as before.
     *
     * @param int $post_id The post being saved.
     */
    private function save_paywall_message( int $post_id ) : void {
        $choice = ( isset( $_POST[WC_PPP_SLUG . '_paywall_message'] ) ? sanitize_key( wp_unslash( $_POST[WC_PPP_SLUG . '_paywall_message'] ) ) : '' );
        if ( 'default' === $choice ) {
            update_post_meta( $post_id, WC_PPP_SLUG . '_restricted_content_override', '' );
            return;
        }
        if ( isset( $_POST[WC_PPP_SLUG . '_restricted_content_override'] ) ) {
            update_post_meta( $post_id, WC_PPP_SLUG . '_restricted_content_override', wp_filter_post_kses( $_POST[WC_PPP_SLUG . '_restricted_content_override'] ) );
        }
    }

    // phpcs:enable WordPress.Security.NonceVerification.Missing
    /**
     * The line at the top of the post editor box that says what a visitor gets,
     * one per protection type. Both editors fill in the numbers as they change.
     *
     * @return array<string, string>
     */
    public static function meta_box_summaries() : array {
        return [
            'unprotected' => __( 'Anyone can read this post. Add a product to put it behind the paywall.', 'wc_pay_per_post' ),
            'standard'    => __( 'Visitors who have not bought one of these see the paywall. Buyers keep access for good.', 'wc_pay_per_post' ),
            'delay'       => __( 'Free to everyone for %1$s %2$s after publishing. Then only buyers can read it.', 'wc_pay_per_post' ),
            'views'       => __( 'Each purchase allows %1$s views. Everyone else sees the paywall.', 'wc_pay_per_post' ),
            'viewsWithin' => __( 'Each purchase allows %1$s views within %2$s %3$s of the purchase. Everyone else sees the paywall.', 'wc_pay_per_post' ),
            'expire'      => __( 'Buyers can read it for %1$s %2$s after purchase. Everyone else sees the paywall.', 'wc_pay_per_post' ),
        ];
    }

    // phpcs:disable WordPress.Security.NonceVerification.Missing -- Called only from save_meta_box(), after its nonce check.
    /**
     * The protection type a classic save asks for, as the three stored "enable"
     * flags. The box posts one choice, protection_type; a form from before the
     * redesign (a tab left open across the update) posts the flags themselves.
     *
     * @return array<string, bool> Keyed delay, page_view and expire.
     */
    private function posted_protection_flags() : array {
        $posted = ( isset( $_POST[WC_PPP_SLUG . '_protection_type'] ) ? sanitize_key( wp_unslash( $_POST[WC_PPP_SLUG . '_protection_type'] ) ) : null );
        if ( null === $posted ) {
            return [
                'delay'     => isset( $_POST[WC_PPP_SLUG . '_delay_restriction_enable'] ),
                'page_view' => isset( $_POST[WC_PPP_SLUG . '_page_view_restriction_enable'] ),
                'expire'    => isset( $_POST[WC_PPP_SLUG . '_expire_restriction_enable'] ),
            ];
        }
        return [
            'delay'     => 'delay' === $posted,
            'page_view' => 'page-view' === $posted,
            'expire'    => 'expire' === $posted,
        ];
    }

    // phpcs:enable WordPress.Security.NonceVerification.Missing
    /** @noinspection PhpUnused */
    public function save_meta_box( $post_id ) {
        // Stop the script when doing autosave.
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        // Verify the nonce. If isn't there, stop the script.
        if ( !isset( $_POST[WC_PPP_SLUG . '_nonce'] ) || !wp_verify_nonce( $_POST[WC_PPP_SLUG . '_nonce'], WC_PPP_SLUG . '_nonce' ) ) {
            return;
        }
        // Stop the script if the user cannot edit THIS post. 'edit_posts' is the
        // general capability and says nothing about the post being saved.
        if ( !current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
        // The form belongs to the post being edited. Another plugin's save_post
        // handler can update a second post while this request runs; that
        // post must not get this one's protection.
        if ( isset( $_POST['post_ID'] ) && (int) $_POST['post_ID'] !== (int) $post_id ) {
            return;
        }
        // "Who can set protection" hides the panel and the box; a form that
        // was open before the setting changed must not save either.
        if ( !Woocommerce_Pay_Per_Post_Helper::allowed_roles_for_meta_box() ) {
            return;
        }
        if ( !isset( $_POST[WC_PPP_SLUG . '_classic_editor'] ) ) {
            $this->edit_post_block_editor( $post_id );
            $clear_transients = apply_filters( 'wc_pay_per_post_clear_transients_on_post_edit', true );
            if ( $clear_transients ) {
                Woocommerce_Pay_Per_Post_Debug::delete_transients();
            }
            return;
        }
        // Boolean Values.
        $_delay_restriction_enable = 0;
        $_page_view_restriction_enable = 0;
        $_page_view_restriction_enable_time_frame = 0;
        $_expire_restriction_enable = 0;
        $_show_warnings = 0;
        // Save the product_id's associated with page/post.
        $product_ids = $_POST[WC_PPP_SLUG . '_product_ids'] ?? '';
        $product_ids = $this->sanitize_product_ids( $product_ids );
        update_post_meta( $post_id, WC_PPP_SLUG . '_product_ids', $product_ids );
        $clear_transients = apply_filters( 'wc_pay_per_post_clear_transients_on_post_edit', true );
        if ( $clear_transients ) {
            Woocommerce_Pay_Per_Post_Debug::delete_transients();
        }
    }

    /** @noinspection PhpUnused */
    public function manage_custom_column( $column, $post_id ) {
        if ( $column === WC_PPP_SLUG . '_protected' ) {
            $protected = $this->is_protected( $post_id );
            if ( $protected['is_protected'] ) {
                echo Woocommerce_Pay_Per_Post_Helper::protection_display_icon( $protected['protection_type'] );
            }
        }
    }

    /**
     * @param $post_id
     *
     * @return array
     */
    public function is_protected( $post_id ) : array {
        $is_protected = false;
        $protection_type = 'standard';
        $origin_type = 'post';
        $standard_product_ids = get_post_meta( $post_id, WC_PPP_SLUG . '_product_ids', true );
        if ( is_array( $standard_product_ids ) && count( $standard_product_ids ) > 0 && !empty( $standard_product_ids[0] ) ) {
            $is_protected = true;
            $delay_restriction_enable = (bool) get_post_meta( $post_id, WC_PPP_SLUG . '_delay_restriction_enable', true );
            $page_view_restriction_enable = (bool) get_post_meta( $post_id, WC_PPP_SLUG . '_page_view_restriction_enable', true );
            $expire_restriction_enable = (bool) get_post_meta( $post_id, WC_PPP_SLUG . '_expire_restriction_enable', true );
            if ( $delay_restriction_enable ) {
                $protection_type = 'delay';
            } else {
                if ( $page_view_restriction_enable ) {
                    $protection_type = 'page-view';
                } else {
                    if ( $expire_restriction_enable ) {
                        $protection_type = 'expire';
                    }
                }
            }
        }
        if ( isset( $combined_product_ids ) ) {
            return [
                'product_ids'     => $combined_product_ids,
                'is_protected'    => $is_protected,
                'origin_type'     => $origin_type,
                'protection_type' => $protection_type,
            ];
        } else {
            return [
                'product_ids'     => $standard_product_ids,
                'is_protected'    => $is_protected,
                'origin_type'     => $origin_type,
                'protection_type' => $protection_type,
            ];
        }
    }

    /** @noinspection PhpUnused */
    public function manage_columns( $columns ) {
        $columns[WC_PPP_SLUG . '_protected'] = 'Pay For Post';
        return $columns;
    }

    /** @noinspection PhpUnused */
    public function sortable_columns( $columns ) {
        $columns[WC_PPP_SLUG . '_protected'] = WC_PPP_SLUG . '_protected';
        return $columns;
    }

    /** @noinspection PhpUnused */
    public function plugin_settings_link( $links ) {
        $url = admin_url( 'admin.php?page=' . WC_PPP_SLUG . '-settings' );
        $_link = '<a href="' . $url . '">' . __( 'Settings', 'wc_pay_per_post' ) . '</a>';
        $links[] = $_link;
        return $links;
    }

    /** @noinspection PhpUnused */
    public function prefix_plugin_update_message( $data ) {
        if ( isset( $data['upgrade_notice'] ) ) {
            printf( '<div class="update-message">%s</div>', esc_html( wpautop( $data['upgrade_notice'] ) ) );
        }
    }

    protected function upgrade_database( $products ) {
        foreach ( $products->posts as $post ) {
            // Get old meta key for product id's associated with posts.
            $post_meta = get_post_meta( $post->ID, 'woocommerce_ppp_product_id', true );
            if ( '' !== $post_meta ) {
                // Added in to account for fields that were there but with no products associated with them.
                $old_ppp_ids = explode( ',', $post_meta );
                update_post_meta( $post->ID, WC_PPP_SLUG . '_product_ids', $old_ppp_ids );
            }
        }
        update_option( 'wc_pay_per_post_needs_upgrade', 'false', false );
        update_option( 'wc_pay_per_post_db_version', WC_PPP_VERSION, false );
        $url = admin_url( 'admin.php?page=' . WC_PPP_SLUG . '-whats-new&upgrade_complete=true' );
        wp_safe_redirect( $url );
    }

    protected function generate_products_dropdown( $selected = [] ) : string {
        $products = Woocommerce_Pay_Per_Post_Helper::get_products();
        // The option builders compare strictly with string IDs. Stored IDs can be
        // integers (an import, or code writing the meta), and the save keeps only
        // what this select posts, so an ID shown unselected was removed by the
        // next save of the post.
        $selected = $this->normalize_protection_ids( $selected );
        $listed = array_merge( array_map( 'strval', array_keys( $this->membership_protection_options() ) ), array_map( 'strval', array_column( $products, 'ID' ) ) );
        $drop_down = '<select id="' . WC_PPP_SLUG . '_product_ids" name="' . WC_PPP_SLUG . '_product_ids[]" style="width: 100%" multiple="multiple">';
        $drop_down .= '<optgroup label="Products">';
        foreach ( $products as $product ) {
            $drop_down .= '<option value="' . $product['ID'] . '"';
            if ( in_array( (string) $product['ID'], (array) $selected, true ) ) {
                $drop_down .= ' selected="selected"';
            }
            $drop_down .= '>' . $product['post_title'] . ' - [#' . $product['ID'] . ']</option>';
        }
        $drop_down .= '</optgroup>';
        // IDs the post has that nothing above lists stay selected, so saving the
        // post keeps them until someone removes them here.
        $unlisted = array_diff( $selected, $listed );
        if ( $unlisted ) {
            $drop_down .= '<optgroup label="' . esc_attr__( 'Not available', 'wc_pay_per_post' ) . '">';
            foreach ( $unlisted as $id ) {
                $drop_down .= '<option value="' . esc_attr( $id ) . '" selected="selected">' . esc_html( $this->unlisted_protection_label( $id ) ) . '</option>';
            }
            $drop_down .= '</optgroup>';
        }
        $drop_down .= '</select>';
        return $drop_down;
    }

    /**
     * @param $product_ids
     *
     * @return array|string
     */
    private function sanitize_product_ids( $product_ids ) {
        if ( is_array( $product_ids ) ) {
            $return = [];
        } else {
            return '';
        }
        foreach ( $product_ids as $product_id ) {
            if ( is_numeric( $product_id ) ) {
                $return[] = $product_id;
            }
        }
        return $return;
    }

    /** @noinspection PhpUnused */
    public function admin_notices() {
        $enable_debugging = (bool) get_option( WC_PPP_SLUG . '_enable_debugging', false );
        // Settings shows the same reminder on the Advanced tab, and Need Help
        // shows it with a button that turns logging off and deletes the log.
        $screen = ( function_exists( 'get_current_screen' ) ? get_current_screen() : null );
        $on_own_page = $screen && in_array( $screen->id, array_filter( [$this->page_hooks[WC_PPP_SLUG] ?? '', $this->page_hooks[WC_PPP_SLUG . '-settings'] ?? '', $this->page_hooks[WC_PPP_SLUG . '-support'] ?? ''] ), true );
        if ( $enable_debugging && !$on_own_page && current_user_can( 'manage_options' ) ) {
            printf(
                '<div class="notice notice-warning"><p>%1$s <a href="%2$s">%3$s</a></p></div>',
                esc_html__( 'Pay For Post\'s debug log is on. It slows pages that are logged and records customers\' email addresses, so turn it off when you are done.', 'wc_pay_per_post' ),
                esc_url( Woocommerce_Pay_Per_Post_Admin::support_url( 'tools' ) ),
                esc_html__( 'Turn it off', 'wc_pay_per_post' )
            );
        }
        $this->dormant_premium_protection_notice();
    }

    /**
     * Warn when posts are configured with a protection type this install cannot
     * apply.
     *
     * Delay, page-view and expire settings live in post meta and survive a
     * licence lapsing or a move to the free build. Those posts keep working —
     * they fall back to standard protection, so the purchase is still required
     * — but the allowance the owner configured silently stops applying. Say so
     * rather than letting them find out from a customer.
     *
     * @return void
     */
    protected function dormant_premium_protection_notice() {
        if ( Woocommerce_Pay_Per_Post_Helper::can_use_premium_protection() ) {
            return;
        }
        if ( !current_user_can( 'manage_options' ) ) {
            return;
        }
        $affected = $this->count_posts_with_dormant_premium_protection();
        if ( $affected < 1 ) {
            return;
        }
        printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( 'notice notice-warning is-dismissible' ), esc_html( sprintf( 
            /* translators: %d: number of affected posts. */
            _n(
                'Pay For Post: %d post is set to Free at first, Limited views or Time limited, which needs an active licence. Until then it is sold as Buy once: visitors still have to buy the product, but the free period, the view limit and the time limit are not applied. Its settings are kept and apply again once the licence is active.',
                'Pay For Post: %d posts are set to Free at first, Limited views or Time limited, which needs an active licence. Until then they are sold as Buy once: visitors still have to buy the product, but the free period, the view limit and the time limit are not applied. Their settings are kept and apply again once the licence is active.',
                $affected,
                'wc_pay_per_post'
            ),
            $affected
         ) ) );
    }

    /**
     * How many published posts carry premium protection meta.
     *
     * Cached in a transient — this runs on every admin page load, and the
     * answer only changes when protection settings do. The transient uses the
     * plugin's prefix so the existing Tools-page clear picks it up.
     *
     * @return int
     */
    protected function count_posts_with_dormant_premium_protection() : int {
        $cache_key = WC_PPP_SLUG . '_dormant_premium_protection_count';
        $cached = get_transient( $cache_key );
        if ( false !== $cached ) {
            return (int) $cached;
        }
        $post_types = get_option( WC_PPP_SLUG . '_post_types', ['post', 'page'] );
        $query = new WP_Query([
            'post_type'              => ( !empty( $post_types ) ? $post_types : ['post', 'page'] ),
            'post_status'            => 'publish',
            'posts_per_page'         => 1,
            'fields'                 => 'ids',
            'no_found_rows'          => false,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
            'ignore_sticky_posts'    => true,
            'meta_query'             => [
                'relation' => 'AND',
                [
                    'key'     => WC_PPP_SLUG . '_product_ids',
                    'compare' => 'EXISTS',
                ],
                [
                    'relation' => 'OR',
                    [
                        'key'     => WC_PPP_SLUG . '_delay_restriction_enable',
                        'value'   => '1',
                        'compare' => '=',
                    ],
                    [
                        'key'     => WC_PPP_SLUG . '_page_view_restriction_enable',
                        'value'   => '1',
                        'compare' => '=',
                    ],
                    [
                        'key'     => WC_PPP_SLUG . '_expire_restriction_enable',
                        'value'   => '1',
                        'compare' => '=',
                    ],
                ],
            ],
        ]);
        $count = (int) $query->found_posts;
        set_transient( $cache_key, $count, apply_filters( 'wc_pay_per_post_posts_transient_time', DAY_IN_SECONDS ) );
        return $count;
    }

}
