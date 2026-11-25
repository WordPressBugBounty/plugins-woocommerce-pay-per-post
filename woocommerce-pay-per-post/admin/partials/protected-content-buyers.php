<?php
/**
 * One protected post's buyers, on the Protected Content screen (Premium).
 *
 * Rendered into the screen's "Show buyers" panel by
 * Woocommerce_Pay_Per_Post_Admin::ajax_protected_content_buyers(), and into
 * the one-post view (?product_id=<post ID>) by the screen itself.
 *
 * Expects $wcppp_buyers from Woocommerce_Pay_Per_Post_Protected_Content::buyers().
 *
 * @package Woocommerce_pay_per_post
 * @noinspection PhpUndefinedVariableInspection
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$wcppp_b_post     = $wcppp_buyers['post'];
$wcppp_b_ids      = $wcppp_buyers['ids'];
$wcppp_b_total    = (int) $wcppp_buyers['total'];
$wcppp_b_list     = $wcppp_buyers['buyers'];
$wcppp_b_members  = array_merge( array_values( $wcppp_b_ids['plans'] ), array_values( $wcppp_b_ids['levels'] ) );
$wcppp_b_support  = Woocommerce_Pay_Per_Post_Admin::support_url( 'check' );
$wcppp_b_date_fmt = get_option( 'date_format' );
?>
<div class="wcppp-buyers">
	<p class="wcppp-buyers-summary">
		<?php if ( ! $wcppp_b_ids['products'] ) : ?>
			<?php esc_html_e( 'No product unlocks this post, so it has no buyers to list.', 'wc_pay_per_post' ); ?>
		<?php elseif ( ! $wcppp_b_total ) : ?>
			<strong><?php esc_html_e( 'No buyers yet.', 'wc_pay_per_post' ); ?></strong>
			<?php esc_html_e( 'No paid order contains a product that unlocks this post.', 'wc_pay_per_post' ); ?>
		<?php elseif ( count( $wcppp_b_list ) < $wcppp_b_total ) : ?>
			<?php
			echo wp_kses(
				sprintf(
					/* translators: 1: number of buyers shown, 2: number of buyers */
					__( 'The <strong>%1$s</strong> most recent of <strong>%2$s</strong> buyers.', 'wc_pay_per_post' ),
					number_format_i18n( count( $wcppp_b_list ) ),
					number_format_i18n( $wcppp_b_total )
				),
				[ 'strong' => [] ]
			);
			?>
		<?php else : ?>
			<?php
			echo wp_kses(
				sprintf(
					/* translators: %s: number of buyers */
					_n( '<strong>%s</strong> buyer.', '<strong>%s</strong> buyers.', $wcppp_b_total, 'wc_pay_per_post' ),
					number_format_i18n( $wcppp_b_total )
				),
				[ 'strong' => [] ]
			);
			?>
		<?php endif; ?>
	</p>
	<p class="wcppp-help">
		<?php esc_html_e( 'Customers with a paid order for a product that unlocks this post. Whether each still has access also depends on expiry, page views, subscriptions and memberships: use Check access to find out.', 'wc_pay_per_post' ); ?>
		<?php if ( $wcppp_b_members ) : ?>
			<?php
			/* translators: %s: membership plan or level names, comma separated */
			echo esc_html( sprintf( __( 'Members of %s are not listed.', 'wc_pay_per_post' ), implode( ', ', $wcppp_b_members ) ) );
			?>
		<?php endif; ?>
	</p>

	<?php if ( $wcppp_b_list ) : ?>
		<div class="wcppp-table-wrap">
			<table class="wcppp-table wcppp-buyers-table">
				<thead>
				<tr>
					<th scope="col"><?php esc_html_e( 'Customer', 'wc_pay_per_post' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Email', 'wc_pay_per_post' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Paid orders', 'wc_pay_per_post' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Latest order', 'wc_pay_per_post' ); ?></th>
					<th scope="col"><span class="screen-reader-text"><?php esc_html_e( 'Actions', 'wc_pay_per_post' ); ?></span></th>
				</tr>
				</thead>
				<tbody>
				<?php foreach ( $wcppp_b_list as $wcppp_b ) : ?>
					<?php
					$wcppp_b_user  = $wcppp_b['user'];
					$wcppp_b_email = $wcppp_b_user ? $wcppp_b_user->user_email : $wcppp_b['email'];
					$wcppp_b_time  = $wcppp_b['last_order_date'] ? strtotime( $wcppp_b['last_order_date'] . ' UTC' ) : 0;
					?>
					<tr>
						<td data-label="<?php esc_attr_e( 'Customer', 'wc_pay_per_post' ); ?>">
							<?php if ( $wcppp_b_user ) : ?>
								<a href="<?php echo esc_url( get_edit_user_link( $wcppp_b_user->ID ) ); ?>"><?php echo esc_html( $wcppp_b_user->display_name ); ?></a>
							<?php elseif ( $wcppp_b['user_id'] > 0 ) : ?>
								<span class="wcppp-badge is-warn"><?php echo Woocommerce_Pay_Per_Post_Admin::icon( 'warn' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php esc_html_e( 'Account deleted, so no access', 'wc_pay_per_post' ); ?></span>
							<?php else : ?>
								<span class="wcppp-badge is-warn"><?php echo Woocommerce_Pay_Per_Post_Admin::icon( 'warn' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php esc_html_e( 'No account: a guest checkout, so no access', 'wc_pay_per_post' ); ?></span>
							<?php endif; ?>
						</td>
						<td data-label="<?php esc_attr_e( 'Email', 'wc_pay_per_post' ); ?>"><?php echo esc_html( $wcppp_b_email ); ?></td>
						<td data-label="<?php esc_attr_e( 'Paid orders', 'wc_pay_per_post' ); ?>">
							<?php if ( $wcppp_b_user ) : ?>
								<a href="<?php echo esc_url( Woocommerce_Pay_Per_Post_Protected_Content::orders_url( [ '_customer_user' => (string) $wcppp_b_user->ID ] ) ); ?>"><?php echo esc_html( number_format_i18n( $wcppp_b['orders'] ) ); ?></a>
							<?php else : ?>
								<?php echo esc_html( number_format_i18n( $wcppp_b['orders'] ) ); ?>
							<?php endif; ?>
						</td>
						<td data-label="<?php esc_attr_e( 'Latest order', 'wc_pay_per_post' ); ?>">
							<a href="<?php echo esc_url( Woocommerce_Pay_Per_Post_Protected_Content::order_url( (int) $wcppp_b['last_order_id'] ) ); ?>">#<?php echo absint( $wcppp_b['last_order_id'] ); ?></a>
							<?php if ( $wcppp_b_time ) : ?>
								<span class="wcppp-muted"><?php echo esc_html( wp_date( $wcppp_b_date_fmt, $wcppp_b_time ) ); ?></span>
							<?php endif; ?>
						</td>
						<td class="wcppp-buyers-actions">
							<?php if ( $wcppp_b_user ) : ?>
								<form method="post" action="<?php echo esc_url( $wcppp_b_support ); ?>">
									<?php wp_nonce_field( 'wc_ppp_access_diagnostic', 'wc_ppp_access_diagnostic_nonce', false ); ?>
									<input type="hidden" name="wc_ppp_diagnostic_email" value="<?php echo esc_attr( $wcppp_b_user->user_email ); ?>">
									<input type="hidden" name="wc_ppp_diagnostic_url" value="<?php echo absint( $wcppp_b_post->ID ); ?>">
									<?php /* translators: %s: customer name */ ?>
									<button type="submit" class="wcppp-btn is-small" aria-label="<?php echo esc_attr( sprintf( __( 'Check %s\'s access', 'wc_pay_per_post' ), $wcppp_b_user->display_name ) ); ?>"><?php esc_html_e( 'Check access', 'wc_pay_per_post' ); ?></button>
								</form>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>

	<?php if ( $wcppp_b_total ) : ?>
		<p class="wcppp-buyers-orders">
			<?php foreach ( array_slice( $wcppp_b_ids['products'], 0, 5, true ) as $wcppp_b_name ) : ?>
				<?php /* translators: %s: product name */ ?>
				<a href="<?php echo esc_url( Woocommerce_Pay_Per_Post_Protected_Content::product_orders_url( $wcppp_b_name ) ); ?>"><?php echo esc_html( sprintf( __( 'Search orders for “%s”', 'wc_pay_per_post' ), $wcppp_b_name ) ); ?></a>
			<?php endforeach; ?>
			<?php if ( count( $wcppp_b_ids['products'] ) > 5 ) : ?>
				<a href="<?php echo esc_url( Woocommerce_Pay_Per_Post_Protected_Content::orders_url() ); ?>">
					<?php
					/* translators: %s: number of further products */
					echo esc_html( sprintf( _n( 'All orders (%s more product unlocks this post)', 'All orders (%s more products unlock this post)', count( $wcppp_b_ids['products'] ) - 5, 'wc_pay_per_post' ), number_format_i18n( count( $wcppp_b_ids['products'] ) - 5 ) ) );
					?>
				</a>
			<?php endif; ?>
		</p>
	<?php endif; ?>
</div>
