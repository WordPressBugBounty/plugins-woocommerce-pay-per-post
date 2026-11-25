<?php
/**
 * The app shell's header: the plugin's name, its three screens and the plan.
 *
 * Expects:
 *   $wcppp_current_screen  settings | protected-content | support (Need Help)
 *   $wcppp_upgrade_url     Upgrade link to show beside "Free plan", or ''.
 *
 * @package Woocommerce_pay_per_post
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$wcppp_premium = Woocommerce_Pay_Per_Post_Helper::can_use_premium_protection();
$wcppp_screens = [
	'settings' => __( 'Settings', 'wc_pay_per_post' ),
];

if ( $wcppp_premium ) {
	$wcppp_screens['protected-content'] = __( 'Protected Content', 'wc_pay_per_post' );
}

$wcppp_screens['support'] = __( 'Need Help', 'wc_pay_per_post' );
$wcppp_upgrade_url        = $wcppp_upgrade_url ?? '';
?>
<header class="wcppp-head">
	<a class="wcppp-brand" href="<?php echo esc_url( admin_url( 'admin.php?page=' . WC_PPP_SLUG . '-settings' ) ); ?>">
		<span class="wcppp-mark"><?php echo Woocommerce_Pay_Per_Post_Admin::icon( 'lock' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
		<span class="wcppp-brand-text">
			<span class="wcppp-brand-name">Pay For Post</span>
			<?php /* translators: %s: plugin version */ ?>
			<span class="wcppp-brand-sub"><?php echo esc_html( sprintf( __( 'for WooCommerce · %s', 'wc_pay_per_post' ), WC_PPP_VERSION ) ); ?></span>
		</span>
	</a>
	<nav class="wcppp-tabs" aria-label="<?php esc_attr_e( 'Pay For Post', 'wc_pay_per_post' ); ?>">
		<?php foreach ( $wcppp_screens as $wcppp_slug => $wcppp_label ) : ?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . WC_PPP_SLUG . '-' . $wcppp_slug ) ); ?>"<?php echo $wcppp_slug === $wcppp_current_screen ? ' class="is-current" aria-current="page"' : ''; ?>><?php echo esc_html( $wcppp_label ); ?></a>
		<?php endforeach; ?>
	</nav>
	<span class="wcppp-grow"></span>
	<?php if ( $wcppp_premium ) : ?>
		<span class="wcppp-plan is-premium"><?php esc_html_e( 'Premium', 'wc_pay_per_post' ); ?></span>
	<?php else : ?>
		<span class="wcppp-plan is-free">
			<?php esc_html_e( 'Free plan', 'wc_pay_per_post' ); ?>
			<?php if ( '' !== $wcppp_upgrade_url ) : ?>
				&middot; <a href="<?php echo esc_url( $wcppp_upgrade_url ); ?>"><?php esc_html_e( 'Compare plans', 'wc_pay_per_post' ); ?></a>
			<?php endif; ?>
		</span>
	<?php endif; ?>
</header>
<hr class="wp-header-end">
