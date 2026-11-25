<?php
/**
 * Need Help > Start: check a customer, find an answer, ask us, and the site's
 * status at a glance.
 *
 * Included by support.php, which sets its variables.
 *
 * @package Woocommerce_pay_per_post
 * @noinspection PhpUndefinedVariableInspection
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$wcppp_doc_pages = Woocommerce_Pay_Per_Post_Docs::pages();
$wcppp_popular   = array_filter(
	[
		'getting-started/first-protected-post' => '',
		'troubleshooting'                      => __( 'symptoms and fixes', 'wc_pay_per_post' ),
		'concepts/how-access-is-decided'       => '',
		'reference/shortcodes'                 => '',
	],
	static function ( $slug ) use ( $wcppp_doc_pages ) {
		return isset( $wcppp_doc_pages[ $slug ] );
	},
	ARRAY_FILTER_USE_KEY
);
$wcppp_latest    = $changelog[0] ?? null;
?>
<div class="wcppp-help-grid">
	<form class="wcppp-card wc-ppp-access-diagnostic" method="post" action="<?php echo esc_url( $wcppp_check_url ); ?>" aria-labelledby="wcppp-h-start-check">
		<?php wp_nonce_field( 'wc_ppp_access_diagnostic', 'wc_ppp_access_diagnostic_nonce' ); ?>
		<div class="wcppp-card-head">
			<h2 id="wcppp-h-start-check"><?php esc_html_e( 'A customer can\'t get in', 'wc_pay_per_post' ); ?></h2>
			<p><?php esc_html_e( 'Runs the real paywall as them and shows why it let them in or kept them out. Their page views aren\'t used up.', 'wc_pay_per_post' ); ?></p>
		</div>
		<div class="wcppp-card-body wcppp-stack-sm">
			<div>
				<label class="wcppp-field-label" for="wc_ppp_diagnostic_email"><?php esc_html_e( 'Customer email', 'wc_pay_per_post' ); ?></label>
				<input type="email" class="wcppp-input" id="wc_ppp_diagnostic_email" name="wc_ppp_diagnostic_email" required autocomplete="off">
			</div>
			<div>
				<label class="wcppp-field-label" for="wc_ppp_diagnostic_url"><?php esc_html_e( 'Page URL or post ID', 'wc_pay_per_post' ); ?></label>
				<input type="text" class="wcppp-input wcppp-mono" id="wc_ppp_diagnostic_url" name="wc_ppp_diagnostic_url" required spellcheck="false">
			</div>
			<p><input type="submit" class="wcppp-btn is-primary" value="<?php esc_attr_e( 'Check access', 'wc_pay_per_post' ); ?>"></p>
		</div>
	</form>

	<section class="wcppp-card" aria-labelledby="wcppp-h-start-docs">
		<div class="wcppp-card-head">
			<h2 id="wcppp-h-start-docs"><?php esc_html_e( 'Find an answer', 'wc_pay_per_post' ); ?></h2>
			<p><?php esc_html_e( 'The documentation for the version you have installed.', 'wc_pay_per_post' ); ?></p>
		</div>
		<div class="wcppp-card-body wcppp-stack-sm">
			<form class="wcppp-docsearch" method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" role="search">
				<input type="hidden" name="page" value="<?php echo esc_attr( WC_PPP_SLUG . '-support' ); ?>">
				<input type="hidden" name="view" value="docs">
				<label class="screen-reader-text" for="wcppp-start-q"><?php esc_html_e( 'Search the docs', 'wc_pay_per_post' ); ?></label>
				<input type="search" class="wcppp-input" id="wcppp-start-q" name="q" placeholder="<?php esc_attr_e( 'Search the docs', 'wc_pay_per_post' ); ?>">
			</form>
			<ul class="wcppp-linklist">
				<?php foreach ( $wcppp_popular as $wcppp_slug => $wcppp_note ) : ?>
					<li>
						<a href="<?php echo esc_url( Woocommerce_Pay_Per_Post_Docs::url( $wcppp_slug ) ); ?>"><?php echo esc_html( $wcppp_doc_pages[ $wcppp_slug ] ); ?></a>
						<?php if ( '' !== $wcppp_note ) : ?>
							<span class="wcppp-muted"><?php echo esc_html( $wcppp_note ); ?></span>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
				<li><a href="<?php echo esc_url( Woocommerce_Pay_Per_Post_Docs::url() ); ?>"><b><?php esc_html_e( 'All docs', 'wc_pay_per_post' ); ?></b></a></li>
			</ul>
		</div>
	</section>

	<section class="wcppp-card" aria-labelledby="wcppp-h-start-ask">
		<div class="wcppp-card-head">
			<h2 id="wcppp-h-start-ask"><?php esc_html_e( 'Ask us', 'wc_pay_per_post' ); ?></h2>
			<p>
				<?php if ( $premium ) : ?>
					<?php esc_html_e( 'Premium includes priority support. Paste your status report into the message so we can answer first time.', 'wc_pay_per_post' ); ?>
				<?php else : ?>
					<?php esc_html_e( 'Ask in the plugin\'s support forum. Paste your status report into the post so we can answer first time.', 'wc_pay_per_post' ); ?>
				<?php endif; ?>
			</p>
		</div>
		<div class="wcppp-card-body wcppp-stack-sm">
			<p><button type="button" class="wcppp-btn" data-copy="#wcppp-status-text"><?php esc_html_e( '1. Copy status report', 'wc_pay_per_post' ); ?></button></p>
			<p>
				<a class="wcppp-btn is-primary" href="<?php echo esc_url( $wcppp_contact_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( '2. Contact support', 'wc_pay_per_post' ); ?><?php echo $wcppp_new_tab; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
			</p>
			<p class="wcppp-help">
				<?php echo $premium ? esc_html__( 'Opens pramadillo.com in a new tab.', 'wc_pay_per_post' ) : esc_html__( 'Opens wordpress.org in a new tab.', 'wc_pay_per_post' ); ?>
			</p>
			<?php $wcppp_status_source(); ?>
		</div>
	</section>
</div>

<section class="wcppp-card" aria-labelledby="wcppp-h-start-site">
	<div class="wcppp-card-head is-row">
		<h2 id="wcppp-h-start-site"><?php esc_html_e( 'Your site', 'wc_pay_per_post' ); ?></h2>
		<a class="wcppp-end" href="<?php echo esc_url( $wcppp_tools_url ); ?>"><?php esc_html_e( 'Status and tools', 'wc_pay_per_post' ); ?></a>
	</div>
	<dl class="wcppp-site-grid">
		<?php foreach ( $status_rows as $wcppp_row ) : ?>
			<div>
				<dt><?php echo esc_html( $wcppp_row['label'] ); ?></dt>
				<dd>
					<?php if ( isset( $wcppp_state_icons[ $wcppp_row['state'] ] ) ) : ?>
						<span class="wcppp-badge is-<?php echo esc_attr( $wcppp_row['state'] ); ?>"><?php echo call_user_func( $wcppp_icon, $wcppp_state_icons[ $wcppp_row['state'] ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo esc_html( $wcppp_row['value'] ); ?></span>
					<?php else : ?>
						<?php echo esc_html( $wcppp_row['value'] ); ?>
					<?php endif; ?>
				</dd>
			</div>
		<?php endforeach; ?>
	</dl>
</section>

<?php if ( $wcppp_latest && ! empty( $wcppp_latest['items'] ) ) : ?>
	<section class="wcppp-newstrip" aria-label="<?php esc_attr_e( 'What\'s new', 'wc_pay_per_post' ); ?>">
		<?php /* translators: %s: a version number */ ?>
		<b><?php echo esc_html( sprintf( __( 'New in %s', 'wc_pay_per_post' ), $wcppp_latest['version'] ) ); ?></b>
		<span><?php echo esc_html( $wcppp_latest['items'][0]['text'] ); ?></span>
		<a href="<?php echo esc_url( Woocommerce_Pay_Per_Post_Admin::support_url( 'whats-new' ) ); ?>"><?php esc_html_e( 'What\'s new', 'wc_pay_per_post' ); ?></a>
	</section>
<?php endif; ?>
