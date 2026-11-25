<?php
/**
 * Need Help > Check a customer: the access diagnostic.
 *
 * Included by support.php, which sets its variables.
 *
 * @package Woocommerce_pay_per_post
 * @noinspection PhpUndefinedVariableInspection
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$wcppp_found_by = static function ( string $attachment ): string {
	if ( 'account' === $attachment ) {
		return __( 'Account', 'wc_pay_per_post' );
	}
	if ( 'GUEST' === $attachment ) {
		return __( 'Billing email, guest order', 'wc_pay_per_post' );
	}

	/* translators: %s: "user 12", the account an order belongs to */
	return sprintf( __( 'Billing email, another account (%s)', 'wc_pay_per_post' ), $attachment );
};
?>
<div class="wc-ppp-access-diagnostic wcppp-stack">
	<form class="wcppp-card wcppp-checkform" method="post" action="<?php echo esc_url( $wcppp_check_url ); ?>" aria-label="<?php esc_attr_e( 'Check a customer', 'wc_pay_per_post' ); ?>">
		<?php wp_nonce_field( 'wc_ppp_access_diagnostic', 'wc_ppp_access_diagnostic_nonce' ); ?>
		<div class="wcppp-checkform-fields">
			<div>
				<label class="wcppp-field-label" for="wc_ppp_diagnostic_email"><?php esc_html_e( 'Customer email', 'wc_pay_per_post' ); ?></label>
				<input type="email" class="wcppp-input" id="wc_ppp_diagnostic_email" name="wc_ppp_diagnostic_email" required autocomplete="off" value="<?php echo esc_attr( $diagnostic_email ); ?>">
			</div>
			<div>
				<label class="wcppp-field-label" for="wc_ppp_diagnostic_url"><?php esc_html_e( 'Page URL or post ID', 'wc_pay_per_post' ); ?></label>
				<input type="text" class="wcppp-input wcppp-mono" id="wc_ppp_diagnostic_url" name="wc_ppp_diagnostic_url" required spellcheck="false" aria-describedby="wcppp-diagnostic-url-help" value="<?php echo esc_attr( $diagnostic_url ); ?>">
			</div>
			<input type="submit" class="wcppp-btn is-primary" value="<?php esc_attr_e( 'Check access', 'wc_pay_per_post' ); ?>">
		</div>
		<p class="wcppp-help" id="wcppp-diagnostic-url-help"><?php esc_html_e( 'Runs the real paywall as this customer, exactly as a visit to the page would. Their page views aren\'t used up. A post ID works when the address won\'t resolve.', 'wc_pay_per_post' ); ?></p>
	</form>

	<?php if ( ! is_array( $diagnostic_result ) ) : ?>

		<div class="wcppp-card wcppp-empty">
			<span class="wcppp-empty-icon"><?php echo call_user_func( $wcppp_icon, 'lock' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
			<p><strong><?php esc_html_e( 'Customer can\'t get in?', 'wc_pay_per_post' ); ?></strong></p>
			<p><?php esc_html_e( 'Enter their email and the page they are trying to open. You\'ll see the answer the paywall gives them, each check it runs, and every order they have placed.', 'wc_pay_per_post' ); ?></p>
		</div>

	<?php elseif ( isset( $diagnostic_result['error'] ) ) : ?>

		<div class="notice notice-error inline wcppp-notice"><p><?php echo esc_html( $diagnostic_result['error'] ); ?></p></div>

	<?php else : ?>
		<?php
		$wcppp_has_access = (bool) $diagnostic_result['verdict'];
		$wcppp_checklist  = Woocommerce_Pay_Per_Post_Admin::diagnostic_checklist( (array) $diagnostic_result['checks'] );
		$wcppp_history    = (array) ( $diagnostic_result['history'] ?? [] );
		$wcppp_focus      = $diagnostic_result['focus_order'] ?? null;
		$wcppp_unlocks    = (array) ( $diagnostic_result['products'] ?? [] );
		$wcppp_others     = (array) ( $wcppp_history['other_products'] ?? [] );
		?>
		<section class="wcppp-card wcppp-result" aria-labelledby="wcppp-h-result" aria-live="polite">
			<div class="wcppp-verdict <?php echo $wcppp_has_access ? 'is-ok' : 'is-err'; ?>">
				<span class="wcppp-verdict-icon"><?php echo call_user_func( $wcppp_icon, $wcppp_has_access ? 'check' : 'cross' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				<div class="wcppp-verdict-text">
					<h2 id="wcppp-h-result"><?php echo $wcppp_has_access ? esc_html__( 'Has access', 'wc_pay_per_post' ) : esc_html__( 'No access', 'wc_pay_per_post' ); ?></h2>
					<p class="wcppp-who">
						<?php
						printf(
							/* translators: 1: customer email, 2: post title, 3: post ID */
							esc_html__( '%1$s on %2$s (post %3$d)', 'wc_pay_per_post' ),
							'<b>' . esc_html( $diagnostic_result['user']->user_email ) . '</b>',
							'<b>' . esc_html( get_the_title( $diagnostic_result['post'] ) ) . '</b>',
							(int) $diagnostic_result['post']->ID
						);
						?>
					</p>
					<p class="wcppp-reason"><?php echo esc_html( $diagnostic_result['reason'] ); ?></p>
				</div>
				<div class="wcppp-verdict-actions">
					<button type="button" class="wcppp-btn" data-copy="#wc-ppp-diagnostic-text"><?php esc_html_e( 'Copy for a support reply', 'wc_pay_per_post' ); ?></button>
					<?php if ( $wcppp_focus ) : ?>
						<?php /* translators: %d: order number */ ?>
						<a class="wcppp-btn" href="<?php echo esc_url( $wcppp_focus['url'] ); ?>"><?php echo esc_html( sprintf( __( 'Open order #%d', 'wc_pay_per_post' ), $wcppp_focus['id'] ) ); ?></a>
					<?php endif; ?>
				</div>
			</div>

			<?php foreach ( (array) $diagnostic_result['warnings'] as $wcppp_warning_key => $wcppp_warning ) : ?>
				<?php if ( 'guest_checkout' === $wcppp_warning_key ) : ?>
					<div class="wcppp-alert is-err wcppp-guest-alert">
						<?php echo call_user_func( $wcppp_icon, 'warn' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<p><b><?php echo esc_html( $wcppp_warning ); ?></b></p>
						<a class="wcppp-btn is-danger-fill" href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=account' ) ); ?>">
							<?php esc_html_e( 'Turn it off in WooCommerce', 'wc_pay_per_post' ); ?>
						</a>
					</div>
				<?php else : ?>
					<div class="notice notice-warning inline wcppp-notice"><p><?php echo esc_html( $wcppp_warning ); ?></p></div>
				<?php endif; ?>
			<?php endforeach; ?>

			<div class="wcppp-result-grid">
				<div class="wcppp-result-checks">
					<h3 class="wcppp-h3" id="wcppp-checks-label"><?php esc_html_e( 'How the paywall decided', 'wc_pay_per_post' ); ?></h3>
					<p class="wcppp-help">
						<?php esc_html_e( 'Every check runs, in this order.', 'wc_pay_per_post' ); ?>
						<?php if ( ! $wcppp_has_access ) : ?>
							<?php esc_html_e( 'The ones marked "Kept them out" are why the paywall showed.', 'wc_pay_per_post' ); ?>
						<?php endif; ?>
					</p>
					<ol class="wcppp-checks" aria-labelledby="wcppp-checks-label">
						<?php foreach ( $wcppp_checklist as $wcppp_check ) : ?>
							<li class="is-<?php echo esc_attr( $wcppp_check['state'] ); ?>">
								<span class="wcppp-check-ic"><?php echo call_user_func( $wcppp_icon, 'pass' === $wcppp_check['state'] ? 'check' : ( 'fail' === $wcppp_check['state'] ? 'cross' : 'dash' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
								<span class="wcppp-check-text">
									<b><?php echo esc_html( $wcppp_check['label'] ); ?></b>
									<?php if ( '' !== $wcppp_check['detail'] ) : ?>
										<span><?php echo esc_html( $wcppp_check['detail'] ); ?></span>
									<?php endif; ?>
									<?php if ( 'fail' === $wcppp_check['state'] && ! $wcppp_has_access ) : ?>
										<span class="wcppp-badge is-err"><?php esc_html_e( 'Kept them out', 'wc_pay_per_post' ); ?></span>
									<?php endif; ?>
								</span>
								<span class="wcppp-check-answer"><?php echo $wcppp_check['answer'] ? esc_html__( 'Yes', 'wc_pay_per_post' ) : esc_html__( 'No', 'wc_pay_per_post' ); ?></span>
							</li>
						<?php endforeach; ?>
					</ol>
				</div>

				<div class="wcppp-result-orders">
					<h3 class="wcppp-h3">
						<?php esc_html_e( 'Their orders', 'wc_pay_per_post' ); ?>
						<span class="wcppp-badge is-mute">
							<?php
							printf(
								/* translators: 1: orders found, 2: how many count toward access */
								esc_html__( '%1$d found · %2$d count', 'wc_pay_per_post' ),
								(int) ( $wcppp_history['total_found'] ?? 0 ),
								(int) ( $wcppp_history['qualifying_count'] ?? 0 )
							);
							?>
						</span>
					</h3>
					<p class="wcppp-help"><?php esc_html_e( 'Found by account and by billing email, so guest orders and a second account show too. A star marks a product that unlocks this page.', 'wc_pay_per_post' ); ?></p>
					<?php if ( ! empty( $wcppp_history['truncated'] ) ) : ?>
						<div class="notice notice-warning inline wcppp-notice"><p><?php esc_html_e( 'The order list was capped, so older orders are not shown.', 'wc_pay_per_post' ); ?></p></div>
					<?php endif; ?>
					<?php if ( empty( $wcppp_history['orders'] ) ) : ?>
						<p><?php esc_html_e( 'No orders found for this customer.', 'wc_pay_per_post' ); ?></p>
					<?php else : ?>
						<div class="wcppp-table-wrap">
							<table class="wcppp-table">
								<thead>
								<tr>
									<th scope="col"><?php esc_html_e( 'Order', 'wc_pay_per_post' ); ?></th>
									<th scope="col"><?php esc_html_e( 'Status', 'wc_pay_per_post' ); ?></th>
									<th scope="col"><?php esc_html_e( 'Items', 'wc_pay_per_post' ); ?></th>
									<th scope="col"><?php esc_html_e( 'Found by', 'wc_pay_per_post' ); ?></th>
									<th scope="col"><?php esc_html_e( 'Counts?', 'wc_pay_per_post' ); ?></th>
								</tr>
								</thead>
								<tbody>
								<?php foreach ( $wcppp_history['orders'] as $wcppp_order ) : ?>
									<tr>
										<td data-label="<?php esc_attr_e( 'Order', 'wc_pay_per_post' ); ?>"><a href="<?php echo esc_url( $wcppp_order['edit_url'] ); ?>">#<?php echo esc_html( $wcppp_order['id'] ); ?></a><br><span class="wcppp-muted"><?php echo esc_html( $wcppp_order['date'] ); ?></span></td>
										<td data-label="<?php esc_attr_e( 'Status', 'wc_pay_per_post' ); ?>">
											<?php echo esc_html( $wcppp_order['status_label'] ); ?>
											<?php /* translators: %s: date and time paid */ ?>
											<br><span class="wcppp-muted"><?php echo '—' === $wcppp_order['paid'] ? esc_html__( 'Not paid', 'wc_pay_per_post' ) : esc_html( sprintf( __( 'Paid %s', 'wc_pay_per_post' ), $wcppp_order['paid'] ) ); ?></span>
										</td>
										<td data-label="<?php esc_attr_e( 'Items', 'wc_pay_per_post' ); ?>">
											<?php foreach ( $wcppp_order['items'] as $wcppp_item ) : ?>
												<div>
													<?php if ( $wcppp_item['required'] ) : ?>
														<span class="wcppp-star" title="<?php esc_attr_e( 'Unlocks this page', 'wc_pay_per_post' ); ?>">&#9733;<span class="screen-reader-text"><?php esc_html_e( 'Unlocks this page:', 'wc_pay_per_post' ); ?></span></span>
													<?php endif; ?>
													<?php echo esc_html( $wcppp_item['name'] ); ?>
													<a class="wcppp-muted" href="<?php echo esc_url( admin_url( 'post.php?post=' . (int) $wcppp_item['id'] . '&action=edit' ) ); ?>">#<?php echo esc_html( $wcppp_item['id'] ); ?></a>
												</div>
											<?php endforeach; ?>
										</td>
										<td data-label="<?php esc_attr_e( 'Found by', 'wc_pay_per_post' ); ?>"><?php echo esc_html( $wcppp_found_by( (string) $wcppp_order['attachment'] ) ); ?></td>
										<td data-label="<?php esc_attr_e( 'Counts?', 'wc_pay_per_post' ); ?>">
											<?php if ( $wcppp_order['qualifies'] ) : ?>
												<span class="wcppp-badge is-ok"><?php echo call_user_func( $wcppp_icon, 'check' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php esc_html_e( 'Yes', 'wc_pay_per_post' ); ?></span>
											<?php else : ?>
												<span class="wcppp-badge is-err"><?php echo call_user_func( $wcppp_icon, 'cross' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php esc_html_e( 'No', 'wc_pay_per_post' ); ?></span>
												<?php echo esc_html( $wcppp_order['why_not'] ); ?>
											<?php endif; ?>
											<?php if ( '' !== $wcppp_order['note'] ) : ?>
												<br><span class="wcppp-muted"><?php echo esc_html( $wcppp_order['note'] ); ?></span>
											<?php endif; ?>
										</td>
									</tr>
								<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					<?php endif; ?>

					<?php if ( $wcppp_unlocks || $wcppp_others ) : ?>
						<div class="wcppp-unlocks">
							<?php if ( $wcppp_unlocks ) : ?>
								<p>
									<b><?php esc_html_e( 'Products that unlock this page:', 'wc_pay_per_post' ); ?></b>
									<?php
									echo implode( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- each part escaped.
										', ',
										array_map(
											static function ( $product ) {
												return '<a href="' . esc_url( admin_url( 'post.php?post=' . (int) $product['id'] . '&action=edit' ) ) . '">' . esc_html( $product['name'] ) . '</a> <span class="wcppp-muted">#' . (int) $product['id'] . '</span>';
											},
											$wcppp_unlocks
										)
									);
									?>
								</p>
							<?php endif; ?>
							<?php if ( $wcppp_others ) : ?>
								<p>
									<b><?php esc_html_e( 'They also bought:', 'wc_pay_per_post' ); ?></b>
									<?php
									echo implode( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- each part escaped.
										', ',
										array_map(
											static function ( $product ) {
												/* translators: %d: times bought */
												$times = sprintf( _n( 'bought %d time', 'bought %d times', (int) $product['count'], 'wc_pay_per_post' ), (int) $product['count'] );

												return '<a href="' . esc_url( admin_url( 'post.php?post=' . (int) $product['id'] . '&action=edit' ) ) . '">' . esc_html( $product['name'] ) . '</a> <span class="wcppp-muted">#' . (int) $product['id'] . ', ' . esc_html( $times ) . '</span>';
											},
											$wcppp_others
										)
									);
									?>
									<br><span class="wcppp-muted"><?php esc_html_e( 'These aren\'t set on this page, so they never count. If the customer thinks they paid for it, that is usually why.', 'wc_pay_per_post' ); ?></span>
								</p>
							<?php endif; ?>
						</div>
					<?php endif; ?>
				</div>
			</div>

			<div class="wcppp-acc">
				<?php if ( ! empty( $diagnostic_result['page_config'] ) || ! empty( $diagnostic_result['products'] ) ) : ?>
					<details>
						<summary><?php esc_html_e( 'Page setup: protection type, products and their types', 'wc_pay_per_post' ); ?></summary>
						<div>
							<?php if ( ! empty( $diagnostic_result['page_config'] ) ) : ?>
								<dl class="wcppp-kv">
									<?php foreach ( $diagnostic_result['page_config'] as $wcppp_label => $wcppp_value ) : ?>
										<div><dt><?php echo esc_html( $wcppp_label ); ?></dt><dd><?php echo esc_html( (string) $wcppp_value ); ?></dd></div>
									<?php endforeach; ?>
								</dl>
							<?php endif; ?>
							<?php if ( ! empty( $diagnostic_result['products'] ) ) : ?>
								<div class="wcppp-table-wrap">
									<table class="wcppp-table">
										<thead>
										<tr>
											<th scope="col"><?php esc_html_e( 'Product', 'wc_pay_per_post' ); ?></th>
											<th scope="col"><?php esc_html_e( 'Type', 'wc_pay_per_post' ); ?></th>
											<th scope="col"><?php esc_html_e( 'Subscription', 'wc_pay_per_post' ); ?></th>
											<th scope="col"><?php esc_html_e( 'Membership', 'wc_pay_per_post' ); ?></th>
										</tr>
										</thead>
										<tbody>
										<?php foreach ( $diagnostic_result['products'] as $wcppp_product ) : ?>
											<tr>
												<td data-label="<?php esc_attr_e( 'Product', 'wc_pay_per_post' ); ?>"><a href="<?php echo esc_url( admin_url( 'post.php?post=' . (int) $wcppp_product['id'] . '&action=edit' ) ); ?>">#<?php echo esc_html( $wcppp_product['id'] ); ?></a> <?php echo esc_html( $wcppp_product['name'] ); ?></td>
												<td data-label="<?php esc_attr_e( 'Type', 'wc_pay_per_post' ); ?>"><?php echo esc_html( $wcppp_product['type'] ); ?></td>
												<td data-label="<?php esc_attr_e( 'Subscription', 'wc_pay_per_post' ); ?>"><?php echo esc_html( $wcppp_product['subscription'] ); ?></td>
												<td data-label="<?php esc_attr_e( 'Membership', 'wc_pay_per_post' ); ?>"><?php echo esc_html( $wcppp_product['membership'] ); ?></td>
											</tr>
										<?php endforeach; ?>
										</tbody>
									</table>
								</div>
							<?php endif; ?>
						</div>
					</details>
				<?php endif; ?>

				<details>
					<summary><?php esc_html_e( 'Evidence: what the engine recorded', 'wc_pay_per_post' ); ?></summary>
					<div>
						<dl class="wcppp-kv">
							<?php foreach ( (array) $diagnostic_result['evidence'] as $wcppp_label => $wcppp_value ) : ?>
								<div><dt><?php echo esc_html( $wcppp_label ); ?></dt><dd><?php echo nl2br( esc_html( (string) $wcppp_value ) ); ?></dd></div>
							<?php endforeach; ?>
						</dl>
					</div>
				</details>

				<details>
					<summary><?php esc_html_e( 'Environment', 'wc_pay_per_post' ); ?></summary>
					<div>
						<dl class="wcppp-kv">
							<?php foreach ( (array) $diagnostic_result['environment'] as $wcppp_label => $wcppp_value ) : ?>
								<div><dt><?php echo esc_html( $wcppp_label ); ?></dt><dd><?php echo esc_html( (string) $wcppp_value ); ?></dd></div>
							<?php endforeach; ?>
						</dl>
					</div>
				</details>

				<details class="wcppp-copy-report">
					<summary><?php esc_html_e( 'The report as text', 'wc_pay_per_post' ); ?></summary>
					<div>
						<label class="screen-reader-text" for="wc-ppp-diagnostic-text"><?php esc_html_e( 'Diagnosis as text', 'wc_pay_per_post' ); ?></label>
						<textarea id="wc-ppp-diagnostic-text" class="wcppp-input wcppp-mono wcppp-report" readonly><?php echo esc_textarea( Woocommerce_Pay_Per_Post_Access_Diagnostic::as_text( $diagnostic_result ) ); ?></textarea>
					</div>
				</details>
			</div>
		</section>
	<?php endif; ?>
</div>
