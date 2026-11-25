<?php
/**
 * Need Help > Docs: the docs home, one page, or search results. The pages
 * are docs/*.md, rendered by Woocommerce_Pay_Per_Post_Docs.
 *
 * Included by support.php, which sets its variables.
 *
 * @package Woocommerce_pay_per_post
 * @noinspection PhpUndefinedVariableInspection
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$wcppp_docs_home = Woocommerce_Pay_Per_Post_Docs::url();
$wcppp_doc_pages = Woocommerce_Pay_Per_Post_Docs::pages();

$wcppp_docsearch = static function ( $id, $button = false ) use ( $doc_query ) {
	?>
	<form class="wcppp-docsearch" method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" role="search">
		<input type="hidden" name="page" value="<?php echo esc_attr( WC_PPP_SLUG . '-support' ); ?>">
		<input type="hidden" name="view" value="docs">
		<label class="screen-reader-text" for="<?php echo esc_attr( $id ); ?>"><?php esc_html_e( 'Search the docs', 'wc_pay_per_post' ); ?></label>
		<input type="search" class="wcppp-input" id="<?php echo esc_attr( $id ); ?>" name="q" value="<?php echo esc_attr( $doc_query ); ?>" placeholder="<?php echo $button ? esc_attr__( 'Search the docs, for example: guest checkout, expiry, shortcode', 'wc_pay_per_post' ) : esc_attr__( 'Search the docs', 'wc_pay_per_post' ); ?>">
		<?php if ( $button ) : ?>
			<button type="submit" class="wcppp-btn is-primary"><?php esc_html_e( 'Search', 'wc_pay_per_post' ); ?></button>
		<?php endif; ?>
	</form>
	<?php
};

$wcppp_premium_tag = '<span class="wcppp-badge is-premium">' . esc_html__( 'Premium', 'wc_pay_per_post' ) . '</span>';
?>

<?php if ( $doc || '' !== $doc_query ) : ?>
	<?php
	$wcppp_location = $doc ? Woocommerce_Pay_Per_Post_Docs::location( $doc['slug'] ) : null;
	?>
	<div class="wcppp-docs<?php echo $doc ? ' has-page' : ''; ?><?php echo $doc && count( $doc['toc'] ) > 1 ? ' has-toc' : ''; ?>">
		<nav class="wcppp-docnav" aria-label="<?php esc_attr_e( 'Docs', 'wc_pay_per_post' ); ?>">
			<?php $wcppp_docsearch( 'wcppp-docs-q' ); ?>
			<div class="wcppp-docnav-links">
				<a class="wcppp-docnav-home" href="<?php echo esc_url( $wcppp_docs_home ); ?>"><?php esc_html_e( 'All docs', 'wc_pay_per_post' ); ?></a>
				<?php foreach ( $doc_sections as $wcppp_section ) : ?>
					<?php $wcppp_open = $wcppp_location && $wcppp_location['section'] === $wcppp_section['title']; ?>
					<details<?php echo $wcppp_open || ! $doc ? ' open' : ''; ?>>
						<summary><?php echo esc_html( $wcppp_section['title'] ); ?></summary>
						<div>
							<?php foreach ( $wcppp_section['groups'] as $wcppp_group ) : ?>
								<?php if ( '' !== $wcppp_group['label'] ) : ?>
									<span class="wcppp-docnav-group"><?php echo esc_html( $wcppp_group['label'] ); ?></span>
								<?php endif; ?>
								<?php foreach ( $wcppp_group['links'] as $wcppp_link ) : ?>
									<?php $wcppp_is_current = $doc && $doc['slug'] === $wcppp_link['slug']; ?>
									<a href="<?php echo esc_url( Woocommerce_Pay_Per_Post_Docs::url( $wcppp_link['slug'] ) ); ?>" title="<?php echo esc_attr( $wcppp_link['title'] ); ?>"<?php echo $wcppp_is_current ? ' class="is-current" aria-current="page"' : ''; ?>><?php echo esc_html( Woocommerce_Pay_Per_Post_Docs::short_title( $wcppp_link['slug'] ) ); ?></a>
								<?php endforeach; ?>
							<?php endforeach; ?>
						</div>
					</details>
				<?php endforeach; ?>
			</div>
		</nav>

		<?php if ( $doc ) : ?>

			<div class="wcppp-docmain">
				<a class="wcppp-docback" href="<?php echo esc_url( $wcppp_docs_home ); ?>">&larr; <?php esc_html_e( 'All docs', 'wc_pay_per_post' ); ?></a>
				<article class="wcppp-card wcppp-docpage">
					<p class="wcppp-crumbs">
						<a href="<?php echo esc_url( $wcppp_docs_home ); ?>"><?php esc_html_e( 'Docs', 'wc_pay_per_post' ); ?></a>
						<?php if ( $wcppp_location ) : ?>
							<span aria-hidden="true">›</span> <?php echo esc_html( $wcppp_location['section'] ); ?>
							<?php if ( '' !== $wcppp_location['group'] ) : ?>
								<span aria-hidden="true">›</span> <?php echo esc_html( $wcppp_location['group'] ); ?>
							<?php endif; ?>
						<?php endif; ?>
					</p>
					<div class="wcppp-prose wcppp-doc">
						<?php echo $doc['html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Woocommerce_Pay_Per_Post_Docs escapes the page as it renders it. ?>
					</div>
					<?php if ( '' !== $doc['prev'] || '' !== $doc['next'] ) : ?>
						<nav class="wcppp-docpager" aria-label="<?php esc_attr_e( 'Previous and next page', 'wc_pay_per_post' ); ?>">
							<?php if ( '' !== $doc['prev'] ) : ?>
								<a class="is-prev" href="<?php echo esc_url( Woocommerce_Pay_Per_Post_Docs::url( $doc['prev'] ) ); ?>" rel="prev"><small><?php esc_html_e( 'Previous', 'wc_pay_per_post' ); ?></small><?php echo esc_html( $wcppp_doc_pages[ $doc['prev'] ] ?? '' ); ?></a>
							<?php endif; ?>
							<?php if ( '' !== $doc['next'] ) : ?>
								<a class="is-next" href="<?php echo esc_url( Woocommerce_Pay_Per_Post_Docs::url( $doc['next'] ) ); ?>" rel="next"><small><?php esc_html_e( 'Next', 'wc_pay_per_post' ); ?></small><?php echo esc_html( $wcppp_doc_pages[ $doc['next'] ] ?? '' ); ?></a>
							<?php endif; ?>
						</nav>
					<?php endif; ?>
				</article>
			</div>

			<?php if ( count( $doc['toc'] ) > 1 ) : ?>
				<aside class="wcppp-doctoc" aria-labelledby="wcppp-doctoc-label">
					<span class="wcppp-doctoc-label" id="wcppp-doctoc-label"><?php esc_html_e( 'On this page', 'wc_pay_per_post' ); ?></span>
					<?php foreach ( $doc['toc'] as $wcppp_heading ) : ?>
						<a href="#<?php echo esc_attr( $wcppp_heading['id'] ); ?>"><?php echo esc_html( $wcppp_heading['title'] ); ?></a>
					<?php endforeach; ?>
					<span class="wcppp-doctoc-label"><?php esc_html_e( 'Still stuck?', 'wc_pay_per_post' ); ?></span>
					<a href="<?php echo esc_url( $wcppp_check_url ); ?>"><?php esc_html_e( 'Check a customer', 'wc_pay_per_post' ); ?></a>
					<?php if ( isset( $wcppp_doc_pages['troubleshooting'] ) && 'troubleshooting' !== $doc['slug'] ) : ?>
						<a href="<?php echo esc_url( Woocommerce_Pay_Per_Post_Docs::url( 'troubleshooting' ) ); ?>"><?php echo esc_html( $wcppp_doc_pages['troubleshooting'] ); ?></a>
					<?php endif; ?>
				</aside>
			<?php endif; ?>

		<?php else : ?>

			<section class="wcppp-card wcppp-docmain" aria-labelledby="wcppp-h-doc-results">
				<div class="wcppp-card-head">
					<h2 id="wcppp-h-doc-results">
						<?php
						/* translators: 1: number of pages, 2: what was searched for */
						echo esc_html( sprintf( _n( '%1$d page matches “%2$s”', '%1$d pages match “%2$s”', count( $doc_results ), 'wc_pay_per_post' ), count( $doc_results ), $doc_query ) );
						?>
					</h2>
				</div>
				<div class="wcppp-card-body">
					<?php if ( ! $doc_results ) : ?>
						<p><?php esc_html_e( 'Try fewer or different words, or browse the list of pages.', 'wc_pay_per_post' ); ?></p>
					<?php else : ?>
						<ul class="wcppp-docresults">
							<?php foreach ( $doc_results as $wcppp_result ) : ?>
								<li>
									<a href="<?php echo esc_url( Woocommerce_Pay_Per_Post_Docs::url( $wcppp_result['slug'] ) ); ?>"><?php echo esc_html( $wcppp_result['title'] ); ?></a>
									<p><?php echo $wcppp_result['excerpt']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by Woocommerce_Pay_Per_Post_Docs::search(). ?></p>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</div>
			</section>

		<?php endif; ?>
	</div>

<?php else : ?>

	<?php if ( '' !== $doc_slug ) : ?>
		<div class="notice notice-warning inline wcppp-notice"><p><?php esc_html_e( 'That page isn\'t in the docs for this version. Pick one below.', 'wc_pay_per_post' ); ?></p></div>
	<?php endif; ?>

	<?php
	// The first section is the way in, shown as numbered steps. A section
	// split into labelled groups gets a column per group; the rest sit side
	// by side.
	$wcppp_first   = array_shift( $doc_sections );
	$wcppp_grouped = [];
	$wcppp_panels  = [];
	foreach ( $doc_sections as $wcppp_section ) {
		if ( count( $wcppp_section['groups'] ) > 2 ) {
			$wcppp_grouped[] = $wcppp_section;
		} else {
			$wcppp_panels[] = $wcppp_section;
		}
	}
	$wcppp_panel_list = static function ( array $links ) use ( $wcppp_premium_tag ) {
		?>
		<ul class="wcppp-doclist">
			<?php foreach ( $links as $wcppp_link ) : ?>
				<li>
					<a href="<?php echo esc_url( Woocommerce_Pay_Per_Post_Docs::url( $wcppp_link['slug'] ) ); ?>"><?php echo esc_html( $wcppp_link['title'] ); ?></a>
					<?php echo $wcppp_link['premium'] ? $wcppp_premium_tag : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php if ( '' !== $wcppp_link['note'] ) : ?>
						<small><?php echo esc_html( $wcppp_link['note'] ); ?></small>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
		<?php
	};
	?>

	<section class="wcppp-card wcppp-dochero" aria-labelledby="wcppp-h-docs">
		<h2 id="wcppp-h-docs"><?php esc_html_e( 'What do you need to do?', 'wc_pay_per_post' ); ?></h2>
		<?php $wcppp_docsearch( 'wcppp-docs-q', true ); ?>
		<div class="wcppp-dochero-intro"><?php echo Woocommerce_Pay_Per_Post_Docs::intro(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped as rendered. ?></div>
	</section>

	<div class="wcppp-docsections">
		<?php if ( $wcppp_first ) : ?>
			<section class="wcppp-card wcppp-docsteps" aria-labelledby="wcppp-h-docs-start">
				<div class="wcppp-card-head">
					<?php /* translators: %s: the docs' first section, such as Getting started */ ?>
					<h2 id="wcppp-h-docs-start"><?php echo esc_html( sprintf( __( 'New to Pay For Post? %s', 'wc_pay_per_post' ), $wcppp_first['title'] ) ); ?></h2>
					<?php if ( '' !== $wcppp_first['intro'] ) : ?>
						<p><?php echo esc_html( $wcppp_first['intro'] ); ?></p>
					<?php endif; ?>
				</div>
				<div class="wcppp-card-body">
					<ol class="wcppp-steps">
						<?php foreach ( $wcppp_first['groups'] as $wcppp_group ) : ?>
							<?php foreach ( $wcppp_group['links'] as $wcppp_link ) : ?>
								<li>
									<a href="<?php echo esc_url( Woocommerce_Pay_Per_Post_Docs::url( $wcppp_link['slug'] ) ); ?>">
										<b><?php echo esc_html( $wcppp_link['title'] ); ?></b>
										<?php if ( '' !== $wcppp_link['note'] ) : ?>
											<span><?php echo esc_html( $wcppp_link['note'] ); ?></span>
										<?php endif; ?>
									</a>
								</li>
							<?php endforeach; ?>
						<?php endforeach; ?>
					</ol>
					<p class="wcppp-help">
						<?php esc_html_e( 'Prefer video?', 'wc_pay_per_post' ); ?>
						<a href="https://www.youtube.com/watch?v=ZepEicA3yeA" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Walkthrough video', 'wc_pay_per_post' ); ?><?php echo $wcppp_new_tab; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
						·
						<a href="https://www.youtube.com/watch?v=UEjs8JCknFU" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Second walkthrough video', 'wc_pay_per_post' ); ?><?php echo $wcppp_new_tab; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
					</p>
				</div>
			</section>
		<?php endif; ?>

		<?php foreach ( $wcppp_grouped as $wcppp_index => $wcppp_section ) : ?>
			<section class="wcppp-card" aria-labelledby="<?php echo esc_attr( 'wcppp-h-docs-g' . $wcppp_index ); ?>">
				<div class="wcppp-card-head">
					<h2 id="<?php echo esc_attr( 'wcppp-h-docs-g' . $wcppp_index ); ?>"><?php echo esc_html( $wcppp_section['title'] ); ?></h2>
					<?php if ( '' !== $wcppp_section['intro'] ) : ?>
						<p><?php echo esc_html( $wcppp_section['intro'] ); ?></p>
					<?php endif; ?>
				</div>
				<div class="wcppp-card-body wcppp-doccols">
					<?php foreach ( $wcppp_section['groups'] as $wcppp_group ) : ?>
						<div>
							<?php if ( '' !== $wcppp_group['label'] ) : ?>
								<h3 class="wcppp-docgroup"><?php echo esc_html( $wcppp_group['label'] ); ?></h3>
							<?php endif; ?>
							<?php $wcppp_panel_list( $wcppp_group['links'] ); ?>
						</div>
					<?php endforeach; ?>
				</div>
			</section>
		<?php endforeach; ?>

		<?php if ( $wcppp_panels ) : ?>
			<div class="wcppp-docpanels">
				<?php foreach ( $wcppp_panels as $wcppp_index => $wcppp_section ) : ?>
					<section class="wcppp-card" aria-labelledby="<?php echo esc_attr( 'wcppp-h-docs-p' . $wcppp_index ); ?>">
						<div class="wcppp-card-head">
							<h2 id="<?php echo esc_attr( 'wcppp-h-docs-p' . $wcppp_index ); ?>"><?php echo esc_html( $wcppp_section['title'] ); ?></h2>
							<?php if ( '' !== $wcppp_section['intro'] ) : ?>
								<p><?php echo esc_html( $wcppp_section['intro'] ); ?></p>
							<?php endif; ?>
						</div>
						<div class="wcppp-card-body">
							<?php foreach ( $wcppp_section['groups'] as $wcppp_group ) : ?>
								<?php if ( '' !== $wcppp_group['label'] ) : ?>
									<h3 class="wcppp-docgroup"><?php echo esc_html( $wcppp_group['label'] ); ?></h3>
								<?php endif; ?>
								<?php $wcppp_panel_list( $wcppp_group['links'] ); ?>
							<?php endforeach; ?>
							<?php if ( $wcppp_index === count( $wcppp_panels ) - 1 ) : ?>
								<ul class="wcppp-doclist">
									<li><a href="<?php echo esc_url( $wcppp_check_url ); ?>"><?php esc_html_e( 'Check one customer\'s access', 'wc_pay_per_post' ); ?></a></li>
									<li><a href="<?php echo esc_url( $wcppp_contact_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Contact support', 'wc_pay_per_post' ); ?><?php echo $wcppp_new_tab; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a></li>
								</ul>
							<?php endif; ?>
						</div>
					</section>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>

<?php endif; ?>
