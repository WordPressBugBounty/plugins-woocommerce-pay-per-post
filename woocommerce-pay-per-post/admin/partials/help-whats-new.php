<?php
/**
 * Need Help > What's new: recent releases, from README.txt's changelog.
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
<section class="wcppp-card wcppp-whats-new" aria-labelledby="wcppp-h-whats-new">
	<div class="wcppp-card-head">
		<h2 id="wcppp-h-whats-new"><?php esc_html_e( 'What\'s new', 'wc_pay_per_post' ); ?></h2>
		<?php /* translators: %s: plugin version */ ?>
		<p><?php echo esc_html( sprintf( __( 'You are running version %s.', 'wc_pay_per_post' ), WC_PPP_VERSION ) ); ?></p>
	</div>
	<div class="wcppp-card-body">
		<?php if ( empty( $changelog ) ) : ?>
			<p><?php esc_html_e( 'The changelog could not be read.', 'wc_pay_per_post' ); ?></p>
		<?php else : ?>
			<?php foreach ( $changelog as $wcppp_release ) : ?>
				<div class="wcppp-release">
					<h3><?php echo esc_html( $wcppp_release['version'] ); ?></h3>
					<ul>
						<?php foreach ( $wcppp_release['items'] as $wcppp_item ) : ?>
							<li>
								<?php if ( '' !== $wcppp_item['kind'] ) : ?>
									<span class="wcppp-badge is-mute"><?php echo esc_html( $wcppp_item['kind'] ); ?></span>
								<?php endif; ?>
								<?php if ( $wcppp_item['premium'] ) : ?>
									<span class="wcppp-badge is-premium"><?php esc_html_e( 'Premium', 'wc_pay_per_post' ); ?></span>
								<?php endif; ?>
								<span><?php echo esc_html( $wcppp_item['text'] ); ?></span>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endforeach; ?>
		<?php endif; ?>
		<p><a href="https://wordpress.org/plugins/woocommerce-pay-per-post/#developers" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Full changelog', 'wc_pay_per_post' ); ?><?php echo $wcppp_new_tab; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a></p>
	</div>
</section>
