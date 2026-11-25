<?php
/**
 * Need Help screen (slug -support): the shell around its views, each in its
 * own partial: help-start, help-check, help-tools, help-log, help-docs and
 * help-whats-new. The Debug log tab shows only while logging is on or a log is
 * left; its address works either way.
 *
 * Variables come from Woocommerce_Pay_Per_Post_Admin::create_support_page().
 *
 * @package Woocommerce_pay_per_post
 * @noinspection PhpUndefinedVariableInspection
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$wcppp_current_screen = 'support';
$wcppp_upgrade_url    = '';
$wcppp_icon           = [ 'Woocommerce_Pay_Per_Post_Admin', 'icon' ];
$wcppp_new_tab        = '<span class="screen-reader-text"> ' . esc_html__( '(opens in a new tab)', 'wc_pay_per_post' ) . '</span>';
$wcppp_contact_url    = $premium ? 'https://pramadillo.com/support/' : 'https://wordpress.org/support/plugin/woocommerce-pay-per-post/';
$wcppp_tools_url      = Woocommerce_Pay_Per_Post_Admin::support_url( 'tools' );
$wcppp_check_url      = Woocommerce_Pay_Per_Post_Admin::support_url( 'check' );
$wcppp_log_url        = Woocommerce_Pay_Per_Post_Admin::support_url( 'log' );

$wcppp_status_text = 'Pay For Post status' . "\n";
foreach ( $status_rows as $wcppp_row ) {
	$wcppp_status_text .= sprintf( '%-16s %s', $wcppp_row['label'] . ':', $wcppp_row['value'] ) . "\n";
}

$wcppp_state_icons = [
	'ok'   => 'check',
	'warn' => 'warn',
	'err'  => 'cross',
];

// The status report as text, for "Copy report" and "Copy status report".
$wcppp_status_source = static function () use ( $wcppp_status_text ) {
	?>
	<label class="screen-reader-text" for="wcppp-status-text"><?php esc_html_e( 'Status as text', 'wc_pay_per_post' ); ?></label>
	<textarea id="wcppp-status-text" class="wcppp-copy-source" readonly tabindex="-1" aria-hidden="true"><?php echo esc_textarea( $wcppp_status_text ); ?></textarea>
	<?php
};
?>
<div class="wrap wcppp-app">
	<?php require plugin_dir_path( __FILE__ ) . 'app-header.php'; ?>

	<div class="wcppp-body">
		<div class="wcppp-titlebar">
			<div>
				<h1 class="wcppp-h1"><?php esc_html_e( 'Need Help', 'wc_pay_per_post' ); ?></h1>
				<p class="wcppp-sub"><?php esc_html_e( 'Answers about one customer, about your site, or about the plugin.', 'wc_pay_per_post' ); ?></p>
			</div>
		</div>

		<nav class="wcppp-subnav" aria-label="<?php esc_attr_e( 'Need Help', 'wc_pay_per_post' ); ?>">
			<?php foreach ( $support_views as $wcppp_view => $wcppp_view_label ) : ?>
				<?php
				if ( 'log' === $wcppp_view && ! $show_log_tab && 'log' !== $support_view ) {
					continue;
				}
				?>
				<a href="<?php echo esc_url( Woocommerce_Pay_Per_Post_Admin::support_url( $wcppp_view ) ); ?>"<?php echo $wcppp_view === $support_view ? ' class="is-current" aria-current="page"' : ''; ?>><?php echo esc_html( $wcppp_view_label ); ?></a>
			<?php endforeach; ?>
		</nav>

		<?php foreach ( $support_notices as $wcppp_notice ) : ?>
			<div class="notice notice-<?php echo esc_attr( $wcppp_notice['type'] ); ?> inline wcppp-notice"><p><?php echo esc_html( $wcppp_notice['message'] ); ?></p></div>
		<?php endforeach; ?>

		<?php if ( 'yes' === get_option( 'woocommerce_enable_guest_checkout' ) && in_array( $support_view, [ 'start', 'tools' ], true ) ) : ?>
			<div class="wcppp-alert is-err wcppp-guest-alert">
				<?php echo call_user_func( $wcppp_icon, 'warn' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<p>
					<b><?php esc_html_e( 'Guest checkout is on, so customers who check out as guests get nothing for their money.', 'wc_pay_per_post' ); ?></b>
					<?php esc_html_e( 'Access belongs to a customer account, and a guest has none to unlock.', 'wc_pay_per_post' ); ?>
				</p>
				<a class="wcppp-btn is-danger-fill" href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=account' ) ); ?>"><?php esc_html_e( 'Turn it off in WooCommerce', 'wc_pay_per_post' ); ?></a>
			</div>
		<?php endif; ?>

		<?php if ( $enable_debugging && in_array( $support_view, [ 'start', 'tools' ], true ) ) : ?>
			<div class="wcppp-alert is-warn wcppp-debug-alert">
				<?php echo call_user_func( $wcppp_icon, 'warn' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<p>
					<b>
						<?php
						echo esc_html(
							$debug_log['exists']
								/* translators: %s: size of the log, such as 838 KB */
								? sprintf( __( 'The debug log is on and holds %s.', 'wc_pay_per_post' ), size_format( $debug_log['size'], 1 ) )
								: __( 'The debug log is on.', 'wc_pay_per_post' )
						);
						?>
					</b>
					<?php esc_html_e( 'It slows the pages it logs and records customers\' email addresses. Turn it off when you are done.', 'wc_pay_per_post' ); ?>
				</p>
				<?php if ( $debug_log['exists'] ) : ?>
					<a class="wcppp-btn" href="<?php echo esc_url( $wcppp_log_url ); ?>"><?php esc_html_e( 'View log', 'wc_pay_per_post' ); ?></a>
				<?php endif; ?>
				<form method="post" action="<?php echo esc_url( Woocommerce_Pay_Per_Post_Admin::support_url( $support_view ) ); ?>">
					<?php wp_nonce_field( 'wc_ppp_debug_off', 'wc_ppp_debug_off_nonce' ); ?>
					<button type="submit" class="wcppp-btn is-primary"><?php esc_html_e( 'Turn off and delete', 'wc_pay_per_post' ); ?></button>
				</form>
			</div>
		<?php endif; ?>

		<?php require plugin_dir_path( __FILE__ ) . 'help-' . $support_view . '.php'; ?>
	</div>
</div>
