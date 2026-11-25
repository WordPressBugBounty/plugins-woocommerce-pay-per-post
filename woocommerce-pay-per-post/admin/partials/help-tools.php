<?php
/**
 * Need Help > Status and tools: the site's status and the maintenance tools.
 * The debug log has its own view, help-log.php. The tools that change what customers can see sit apart,
 * under "Can't be undone".
 *
 * Every button keeps the nonce it had on the Debug and Tools screens.
 *
 * Included by support.php, which sets its variables.
 *
 * @package Woocommerce_pay_per_post
 * @noinspection PhpUndefinedVariableInspection
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wcppp-tools-grid" id="maintenance">
	<section class="wcppp-card" aria-labelledby="wcppp-h-status">
		<div class="wcppp-card-head is-row">
			<div>
				<h2 id="wcppp-h-status"><?php esc_html_e( 'Status', 'wc_pay_per_post' ); ?></h2>
				<p><?php esc_html_e( 'Paste this into a support request.', 'wc_pay_per_post' ); ?></p>
			</div>
			<button type="button" class="wcppp-btn wcppp-end" data-copy="#wcppp-status-text"><?php esc_html_e( 'Copy report', 'wc_pay_per_post' ); ?></button>
		</div>
		<table class="wcppp-table wcppp-status-table">
			<tbody>
			<?php foreach ( $status_rows as $wcppp_row ) : ?>
				<tr>
					<th scope="row"><?php echo esc_html( $wcppp_row['label'] ); ?></th>
					<td>
						<?php if ( isset( $wcppp_state_icons[ $wcppp_row['state'] ] ) ) : ?>
							<span class="wcppp-badge is-<?php echo esc_attr( $wcppp_row['state'] ); ?>"><?php echo call_user_func( $wcppp_icon, $wcppp_state_icons[ $wcppp_row['state'] ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo esc_html( $wcppp_row['value'] ); ?></span>
						<?php else : ?>
							<?php echo esc_html( $wcppp_row['value'] ); ?>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php $wcppp_status_source(); ?>
	</section>

	<section class="wcppp-card" aria-labelledby="wcppp-h-tools">
		<div class="wcppp-card-head">
			<h2 id="wcppp-h-tools"><?php esc_html_e( 'Tools', 'wc_pay_per_post' ); ?></h2>
			<p><?php esc_html_e( 'Safe to run at any time.', 'wc_pay_per_post' ); ?></p>
		</div>

		<div class="wcppp-tool">
			<div>
				<b><?php esc_html_e( 'Refresh cached lists', 'wc_pay_per_post' ); ?></b>
				<p><?php esc_html_e( 'Shortcode lists are cached for an hour. Refresh if a change hasn\'t appeared yet.', 'wc_pay_per_post' ); ?></p>
			</div>
			<form method="post" action="<?php echo esc_url( $wcppp_tools_url ); ?>">
				<?php wp_nonce_field( 'wc_ppp_delete_transients', 'wc_ppp_delete_transients_nonce' ); ?>
				<button type="submit" class="wcppp-btn"><?php esc_html_e( 'Refresh', 'wc_pay_per_post' ); ?></button>
			</form>
		</div>

		<div class="wcppp-tool">
			<div>
				<b><?php esc_html_e( 'Debug log', 'wc_pay_per_post' ); ?></b>
				<span class="wcppp-muted">
					&middot;
					<?php if ( $debug_log['exists'] ) : ?>
						<?php echo esc_html( size_format( $debug_log['size'], 1 ) ); ?> ·
					<?php endif; ?>
					<?php echo $enable_debugging ? esc_html__( 'logging is on', 'wc_pay_per_post' ) : esc_html__( 'logging is off', 'wc_pay_per_post' ); ?>
				</span>
				<p>
					<?php echo $debug_log['exists'] ? esc_html__( 'Records how the paywall decides. It can include customers\' email addresses, so delete it when you are done.', 'wc_pay_per_post' ) : esc_html__( 'No log yet.', 'wc_pay_per_post' ); ?>
					<a href="<?php echo esc_url( add_query_arg( 'tab', 'advanced', admin_url( 'admin.php?page=' . WC_PPP_SLUG . '-settings' ) ) ); ?>"><?php esc_html_e( 'Debug setting', 'wc_pay_per_post' ); ?></a>
				</p>
			</div>
			<?php if ( $debug_log['exists'] ) : ?>
				<span class="wcppp-btn-group">
					<a class="wcppp-btn" href="<?php echo esc_url( $wcppp_log_url ); ?>"><?php esc_html_e( 'View', 'wc_pay_per_post' ); ?></a>
					<form method="post" action="<?php echo esc_url( $wcppp_tools_url ); ?>" data-confirm="<?php esc_attr_e( 'Delete the debug log?', 'wc_pay_per_post' ); ?>">
						<?php wp_nonce_field( 'wc_ppp_delete_log', 'wc_ppp_delete_log_nonce' ); ?>
						<button type="submit" class="wcppp-btn is-danger"><?php esc_html_e( 'Delete', 'wc_pay_per_post' ); ?></button>
					</form>
				</span>
			<?php endif; ?>
		</div>

		<?php if ( $premium && false === $page_view_table ) : ?>
			<div class="wcppp-tool">
				<div>
					<b><?php esc_html_e( 'Create the page-view table', 'wc_pay_per_post' ); ?></b>
					<p><?php esc_html_e( 'Page-view protection records visits in its own table, which is missing.', 'wc_pay_per_post' ); ?></p>
				</div>
				<form method="post" action="<?php echo esc_url( $wcppp_tools_url ); ?>">
					<?php wp_nonce_field( 'wc_ppp_create_table', 'wc_ppp_create_table_nonce' ); ?>
					<button type="submit" class="wcppp-btn is-primary"><?php esc_html_e( 'Create', 'wc_pay_per_post' ); ?></button>
				</form>
			</div>
		<?php endif; ?>
	</section>
</div>

<section class="wcppp-card is-danger" aria-labelledby="wcppp-h-danger">
	<div class="wcppp-card-head">
		<h2 id="wcppp-h-danger"><?php esc_html_e( 'Can\'t be undone', 'wc_pay_per_post' ); ?></h2>
		<p><?php esc_html_e( 'These change what every customer can see. Take a backup first. Each asks you to confirm.', 'wc_pay_per_post' ); ?></p>
	</div>

	<?php if ( $premium ) : ?>
		<div class="wcppp-tool">
			<div>
				<b><?php esc_html_e( 'Reset page-view counts', 'wc_pay_per_post' ); ?></b>
				<p><?php esc_html_e( 'Every customer gets their full page-view allowance back.', 'wc_pay_per_post' ); ?></p>
			</div>
			<form method="post" action="<?php echo esc_url( $wcppp_tools_url ); ?>" data-confirm="<?php esc_attr_e( 'Reset every customer\'s page-view count? This can\'t be undone.', 'wc_pay_per_post' ); ?>">
				<?php wp_nonce_field( 'wc_ppp_reset_page_views', 'wc_ppp_reset_page_views_nonce' ); ?>
				<button type="submit" class="wcppp-btn is-danger"><?php esc_html_e( 'Reset page views…', 'wc_pay_per_post' ); ?></button>
			</form>
		</div>
	<?php endif; ?>

	<div class="wcppp-tool">
		<div>
			<b><?php esc_html_e( 'Remove all protection', 'wc_pay_per_post' ); ?></b>
			<p><?php esc_html_e( 'Unlinks every product from every post and clears page views, so every protected post becomes free to read. Settings, orders and products are kept.', 'wc_pay_per_post' ); ?></p>
		</div>
		<form method="post" action="<?php echo esc_url( $wcppp_tools_url ); ?>" data-confirm="<?php esc_attr_e( 'Remove protection from every post? Every protected post becomes free to read. This can\'t be undone.', 'wc_pay_per_post' ); ?>">
			<?php wp_nonce_field( 'wc_ppp_reset_data', 'wc_ppp_reset_data_nonce' ); ?>
			<button type="submit" class="wcppp-btn is-danger"><?php esc_html_e( 'Remove all protection…', 'wc_pay_per_post' ); ?></button>
		</form>
	</div>
</section>
