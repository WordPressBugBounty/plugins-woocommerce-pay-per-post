<?php
/**
 * Need Help > Debug log: the log itself, where it is, and the buttons to
 * delete it or stop logging. Its tab shows while logging is on or a log is
 * left; the address works either way.
 *
 * Included by support.php, which sets its variables.
 *
 * @package Woocommerce_pay_per_post
 * @noinspection PhpUndefinedVariableInspection
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$wcppp_debug_setting_url = add_query_arg( 'tab', 'advanced', admin_url( 'admin.php?page=' . WC_PPP_SLUG . '-settings' ) );
?>
<section class="wcppp-card wcppp-log-card" aria-labelledby="wcppp-h-log">
	<div class="wcppp-card-head is-row">
		<div>
			<h2 id="wcppp-h-log">
				<?php esc_html_e( 'Debug log', 'wc_pay_per_post' ); ?>
				<?php if ( $enable_debugging ) : ?>
					<span class="wcppp-badge is-warn"><?php esc_html_e( 'Logging is on', 'wc_pay_per_post' ); ?></span>
				<?php else : ?>
					<span class="wcppp-badge is-mute"><?php esc_html_e( 'Logging is off', 'wc_pay_per_post' ); ?></span>
				<?php endif; ?>
			</h2>
			<?php if ( $debug_log['exists'] ) : ?>
				<p>
					<?php echo esc_html( size_format( $debug_log['size'], 1 ) ); ?>
					<?php if ( $debug_log['size'] > 65536 ) : ?>
						· <?php esc_html_e( 'showing the most recent 64 KB', 'wc_pay_per_post' ); ?>
					<?php endif; ?>
					· <code class="wcppp-log-path"><?php echo esc_html( $debug_log['path'] ); ?></code>
				</p>
			<?php endif; ?>
		</div>
		<span class="wcppp-btn-group wcppp-end">
			<?php if ( $debug_log['exists'] ) : ?>
				<button type="button" class="wcppp-btn" data-copy="#wcppp-log-text"><?php esc_html_e( 'Copy', 'wc_pay_per_post' ); ?></button>
				<form method="post" action="<?php echo esc_url( $wcppp_log_url ); ?>" data-confirm="<?php esc_attr_e( 'Delete the debug log?', 'wc_pay_per_post' ); ?>">
					<?php wp_nonce_field( 'wc_ppp_delete_log', 'wc_ppp_delete_log_nonce' ); ?>
					<button type="submit" class="wcppp-btn is-danger"><?php esc_html_e( 'Delete', 'wc_pay_per_post' ); ?></button>
				</form>
			<?php endif; ?>
			<?php if ( $enable_debugging ) : ?>
				<form method="post" action="<?php echo esc_url( $wcppp_log_url ); ?>">
					<?php wp_nonce_field( 'wc_ppp_debug_off', 'wc_ppp_debug_off_nonce' ); ?>
					<button type="submit" class="wcppp-btn is-primary"><?php esc_html_e( 'Turn off and delete', 'wc_pay_per_post' ); ?></button>
				</form>
			<?php else : ?>
				<a class="wcppp-btn" href="<?php echo esc_url( $wcppp_debug_setting_url ); ?>"><?php esc_html_e( 'Debug setting', 'wc_pay_per_post' ); ?></a>
			<?php endif; ?>
		</span>
	</div>

	<?php if ( $debug_log['exists'] ) : ?>
		<div class="wcppp-card-body">
			<p class="wcppp-help">
				<?php esc_html_e( 'How the paywall decided on each page it logged. It can include customers\' email addresses and orders, so delete it when you are done.', 'wc_pay_per_post' ); ?>
			</p>
			<label class="screen-reader-text" for="wcppp-log-text"><?php esc_html_e( 'Debug log', 'wc_pay_per_post' ); ?></label>
			<textarea id="wcppp-log-text" class="wcppp-input wcppp-mono wcppp-log" readonly wrap="off"><?php echo esc_textarea( $log_tail ); ?></textarea>
		</div>
	<?php else : ?>
		<div class="wcppp-empty">
			<p><strong><?php esc_html_e( 'No log yet.', 'wc_pay_per_post' ); ?></strong></p>
			<p>
				<?php
				if ( $enable_debugging ) {
					echo wp_kses(
						__( 'Open a protected page with <code>?wc-ppp-debug=true</code> added to its address, and how the paywall decided appears here.', 'wc_pay_per_post' ),
						[ 'code' => [] ]
					);
				} else {
					esc_html_e( 'Turn on the debug log under Settings > Advanced to record how the paywall decides.', 'wc_pay_per_post' );
				}
				?>
			</p>
		</div>
	<?php endif; ?>
</section>
