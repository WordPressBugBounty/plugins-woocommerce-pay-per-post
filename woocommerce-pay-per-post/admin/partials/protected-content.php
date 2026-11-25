<?php
/**
 * Protected Content screen (Premium).
 *
 * Built by Woocommerce_Pay_Per_Post_Admin::create_protected_content_page():
 *   $wcppp_request  From Woocommerce_Pay_Per_Post_Protected_Content::request().
 *   $wcppp_buyers   One post's buyers, for ?product_id=<post ID>; otherwise null.
 *   $wcppp_query    The page of protected posts (when $wcppp_buyers is null).
 *   $wcppp_rows     Its rows, from Woocommerce_Pay_Per_Post_Protected_Content::rows().
 *
 * Buyers load on request: the "Show buyers" link fetches them over
 * admin-ajax.php (wc-ppp-app.js), and without JavaScript opens the one-post
 * view this partial also renders.
 *
 * @package Woocommerce_pay_per_post
 * @noinspection PhpUndefinedVariableInspection
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$wcppp_current_screen = 'protected-content';
$wcppp_upgrade_url    = '';
$wcppp_screen_url     = admin_url( 'admin.php?page=' . WC_PPP_SLUG . '-protected-content' );

$wcppp_protection_labels = [];
foreach ( Woocommerce_Pay_Per_Post_Helper::$protection_types as $wcppp_type ) {
	$wcppp_protection_labels[ $wcppp_type ] = Woocommerce_Pay_Per_Post_Helper::protection_type_label( $wcppp_type );
}
$wcppp_origin_labels     = [
	'post'      => __( 'Whole post', 'wc_pay_per_post' ),
	'elementor' => __( 'Elementor sections', 'wc_pay_per_post' ),
];
?>
<div class="wrap wcppp-app">
	<?php require plugin_dir_path( __FILE__ ) . 'app-header.php'; ?>

	<div class="wcppp-body">
		<?php if ( null !== $wcppp_buyers ) : ?>
			<?php
			$wcppp_post = $wcppp_buyers['post'];
			$wcppp_back = wp_get_referer();
			$wcppp_back = $wcppp_back && false !== strpos( $wcppp_back, 'page=' . WC_PPP_SLUG . '-protected-content' ) ? remove_query_arg( 'product_id', $wcppp_back ) : $wcppp_screen_url;
			?>
			<div class="wcppp-titlebar">
				<div>
					<a class="wcppp-back" href="<?php echo esc_url( $wcppp_back ); ?>">&larr; <?php esc_html_e( 'All protected content', 'wc_pay_per_post' ); ?></a>
					<?php /* translators: %s: post title */ ?>
					<h1 class="wcppp-h1"><?php echo esc_html( sprintf( __( 'Buyers of %s', 'wc_pay_per_post' ), get_the_title( $wcppp_post ) ) ); ?></h1>
					<p class="wcppp-sub"><?php esc_html_e( 'Customers with a paid order for a product that unlocks this post.', 'wc_pay_per_post' ); ?></p>
				</div>
			</div>

			<section class="wcppp-card" aria-labelledby="wcppp-h-buyers">
				<h2 id="wcppp-h-buyers" class="screen-reader-text"><?php esc_html_e( 'Buyers', 'wc_pay_per_post' ); ?></h2>
				<div class="wcppp-card-body">
					<?php require plugin_dir_path( __FILE__ ) . 'protected-content-buyers.php'; ?>
				</div>
			</section>
		<?php else : ?>
			<?php
			$wcppp_total     = (int) $wcppp_query->found_posts;
			$wcppp_pages     = (int) $wcppp_query->max_num_pages;
			$wcppp_per_page  = Woocommerce_Pay_Per_Post_Protected_Content::per_page();
			$wcppp_paged     = min( $wcppp_request['paged'], max( 1, $wcppp_pages ) );
			$wcppp_types     = Woocommerce_Pay_Per_Post_Protected_Content::post_types();
			$wcppp_filtered  = '' !== $wcppp_request['s'] || '' !== $wcppp_request['post_type'];
			$wcppp_page_args = array_filter(
				[
					's'         => $wcppp_request['s'],
					'post_type' => $wcppp_request['post_type'],
				]
			);
			$wcppp_page_url  = static function ( $paged ) use ( $wcppp_screen_url, $wcppp_page_args ) {
				$args = $wcppp_page_args;
				if ( $paged > 1 ) {
					$args['paged'] = $paged;
				}

				return add_query_arg( array_map( 'rawurlencode', $args ), $wcppp_screen_url );
			};
			?>
			<div class="wcppp-titlebar">
				<div>
					<h1 class="wcppp-h1"><?php esc_html_e( 'Protected Content', 'wc_pay_per_post' ); ?></h1>
					<p class="wcppp-sub"><?php esc_html_e( 'Your protected posts and what unlocks them. Open a post\'s buyers when you need them.', 'wc_pay_per_post' ); ?></p>
				</div>
			</div>

			<section class="wcppp-card wcppp-pc" aria-labelledby="wcppp-h-protected">
				<h2 id="wcppp-h-protected" class="screen-reader-text"><?php esc_html_e( 'Protected posts', 'wc_pay_per_post' ); ?></h2>

				<form class="wcppp-pc-filters" method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" role="search">
					<input type="hidden" name="page" value="<?php echo esc_attr( WC_PPP_SLUG . '-protected-content' ); ?>">
					<div class="wcppp-pc-search">
						<label class="screen-reader-text" for="wcppp-pc-s"><?php esc_html_e( 'Search titles', 'wc_pay_per_post' ); ?></label>
						<input type="search" class="wcppp-input" id="wcppp-pc-s" name="s" value="<?php echo esc_attr( $wcppp_request['s'] ); ?>" placeholder="<?php esc_attr_e( 'Search titles', 'wc_pay_per_post' ); ?>">
					</div>
					<?php if ( count( $wcppp_types ) > 1 ) : ?>
						<label class="screen-reader-text" for="wcppp-pc-type"><?php esc_html_e( 'Post type', 'wc_pay_per_post' ); ?></label>
						<select class="wcppp-select" id="wcppp-pc-type" name="post_type">
							<option value=""><?php esc_html_e( 'All post types', 'wc_pay_per_post' ); ?></option>
							<?php foreach ( $wcppp_types as $wcppp_type ) : ?>
								<?php $wcppp_type_object = get_post_type_object( $wcppp_type ); ?>
								<option value="<?php echo esc_attr( $wcppp_type ); ?>"<?php selected( $wcppp_request['post_type'], $wcppp_type ); ?>><?php echo esc_html( $wcppp_type_object ? $wcppp_type_object->labels->name : $wcppp_type ); ?></option>
							<?php endforeach; ?>
						</select>
					<?php endif; ?>
					<button type="submit" class="wcppp-btn"><?php esc_html_e( 'Filter', 'wc_pay_per_post' ); ?></button>
					<?php if ( $wcppp_filtered ) : ?>
						<a class="wcppp-btn is-ghost" href="<?php echo esc_url( $wcppp_screen_url ); ?>"><?php esc_html_e( 'Clear', 'wc_pay_per_post' ); ?></a>
					<?php endif; ?>
					<span class="wcppp-grow"></span>
					<p class="wcppp-pc-count" role="status">
						<?php if ( $wcppp_total && $wcppp_rows ) : ?>
							<?php
							echo esc_html(
								sprintf(
									/* translators: 1: first row number, 2: last row number, 3: number of protected posts */
									_n( '%1$s–%2$s of %3$s protected post', '%1$s–%2$s of %3$s protected posts', $wcppp_total, 'wc_pay_per_post' ),
									number_format_i18n( ( $wcppp_paged - 1 ) * $wcppp_per_page + 1 ),
									number_format_i18n( ( $wcppp_paged - 1 ) * $wcppp_per_page + count( $wcppp_rows ) ),
									number_format_i18n( $wcppp_total )
								)
							);
							?>
						<?php endif; ?>
					</p>
				</form>

				<?php if ( ! $wcppp_rows ) : ?>
					<div class="wcppp-empty">
						<span class="wcppp-empty-icon"><?php echo Woocommerce_Pay_Per_Post_Admin::icon( 'lock' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<?php if ( $wcppp_filtered ) : ?>
							<p><strong><?php esc_html_e( 'No protected posts match.', 'wc_pay_per_post' ); ?></strong></p>
							<p><a href="<?php echo esc_url( $wcppp_screen_url ); ?>"><?php esc_html_e( 'Show all protected posts', 'wc_pay_per_post' ); ?></a></p>
						<?php elseif ( $wcppp_total ) : ?>
							<p><strong><?php esc_html_e( 'There is nothing on this page.', 'wc_pay_per_post' ); ?></strong></p>
							<p><a href="<?php echo esc_url( $wcppp_screen_url ); ?>"><?php esc_html_e( 'Go to the first page', 'wc_pay_per_post' ); ?></a></p>
						<?php else : ?>
							<p><strong><?php esc_html_e( 'No protected posts yet.', 'wc_pay_per_post' ); ?></strong></p>
							<p><?php esc_html_e( 'Choose the products that unlock a post in its Pay For Post settings, in the post editor.', 'wc_pay_per_post' ); ?></p>
						<?php endif; ?>
					</div>
				<?php else : ?>
					<div class="wcppp-card-body wcppp-table-wrap">
						<table class="wcppp-table wcppp-pc-table" data-buyers-nonce="<?php echo esc_attr( wp_create_nonce( Woocommerce_Pay_Per_Post_Protected_Content::BUYERS_NONCE ) ); ?>">
							<thead>
							<tr>
								<th scope="col"><?php esc_html_e( 'Post', 'wc_pay_per_post' ); ?></th>
								<th scope="col"><?php esc_html_e( 'Protection', 'wc_pay_per_post' ); ?></th>
								<th scope="col"><?php esc_html_e( 'Protects', 'wc_pay_per_post' ); ?></th>
								<th scope="col"><?php esc_html_e( 'Unlocked by', 'wc_pay_per_post' ); ?></th>
								<th scope="col"><?php esc_html_e( 'Buyers', 'wc_pay_per_post' ); ?></th>
							</tr>
							</thead>
							<tbody>
							<?php foreach ( $wcppp_rows as $wcppp_row ) : ?>
								<?php
								$wcppp_post   = $wcppp_row['post'];
								$wcppp_ids    = $wcppp_row['ids'];
								$wcppp_title  = '' !== $wcppp_post->post_title ? $wcppp_post->post_title : __( '(no title)', 'wc_pay_per_post' );
								$wcppp_panel  = 'wcppp-buyers-' . $wcppp_post->ID;
								$wcppp_edit   = get_edit_post_link( $wcppp_post->ID );
								?>
								<tr class="wcppp-pc-row">
									<td data-label="<?php esc_attr_e( 'Post', 'wc_pay_per_post' ); ?>">
										<?php if ( $wcppp_edit ) : ?>
											<a class="wcppp-strong-link" href="<?php echo esc_url( $wcppp_edit ); ?>"><?php echo esc_html( $wcppp_title ); ?></a>
										<?php else : ?>
											<strong><?php echo esc_html( $wcppp_title ); ?></strong>
										<?php endif; ?>
										<span class="wcppp-muted wcppp-pc-meta">
											#<?php echo absint( $wcppp_post->ID ); ?>
											&middot; <a href="<?php echo esc_url( get_permalink( $wcppp_post ) ); ?>"><?php esc_html_e( 'View', 'wc_pay_per_post' ); ?></a>
										</span>
									</td>
									<td data-label="<?php esc_attr_e( 'Protection', 'wc_pay_per_post' ); ?>">
										<?php if ( '' !== $wcppp_row['protection_type'] ) : ?>
											<span class="wcppp-badge is-accent"><?php echo esc_html( $wcppp_protection_labels[ $wcppp_row['protection_type'] ] ?? $wcppp_row['protection_type'] ); ?></span>
										<?php else : ?>
											<span class="wcppp-badge is-mute"><?php esc_html_e( 'Not set', 'wc_pay_per_post' ); ?></span>
										<?php endif; ?>
									</td>
									<td data-label="<?php esc_attr_e( 'Protects', 'wc_pay_per_post' ); ?>"><?php echo esc_html( $wcppp_origin_labels[ $wcppp_row['origin_type'] ] ?? $wcppp_row['origin_type'] ); ?></td>
									<td data-label="<?php esc_attr_e( 'Unlocked by', 'wc_pay_per_post' ); ?>">
										<?php
										$wcppp_tags = [];
										foreach ( $wcppp_ids['products'] as $wcppp_id => $wcppp_name ) {
											$wcppp_product_edit = get_edit_post_link( wp_get_post_parent_id( $wcppp_id ) ?: $wcppp_id );
											$wcppp_tags[]       = $wcppp_product_edit
												? '<a href="' . esc_url( $wcppp_product_edit ) . '" class="wcppp-tag">' . esc_html( $wcppp_name ) . '</a>'
												: '<span class="wcppp-tag">' . esc_html( $wcppp_name ) . '</span>';
										}
										foreach ( $wcppp_ids['plans'] as $wcppp_name ) {
											/* translators: %s: membership plan name */
											$wcppp_tags[] = '<span class="wcppp-tag">' . esc_html( sprintf( __( 'Plan: %s', 'wc_pay_per_post' ), $wcppp_name ) ) . '</span>';
										}
										foreach ( $wcppp_ids['levels'] as $wcppp_name ) {
											/* translators: %s: membership level name */
											$wcppp_tags[] = '<span class="wcppp-tag">' . esc_html( sprintf( __( 'Level: %s', 'wc_pay_per_post' ), $wcppp_name ) ) . '</span>';
										}
										foreach ( $wcppp_ids['missing'] as $wcppp_id ) {
											/* translators: %s: product, plan or level ID */
											$wcppp_tags[] = '<span class="wcppp-tag is-missing">' . esc_html( sprintf( __( '#%s (not available)', 'wc_pay_per_post' ), $wcppp_id ) ) . '</span>';
										}
										// A post can be unlocked by dozens of products: show the first few.
										echo implode( '', array_slice( $wcppp_tags, 0, 6 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
										?>
										<?php if ( count( $wcppp_tags ) > 6 ) : ?>
											<details class="wcppp-pc-more">
												<?php /* translators: %s: number of further products, plans or levels */ ?>
												<summary><?php echo esc_html( sprintf( __( '+%s more', 'wc_pay_per_post' ), number_format_i18n( count( $wcppp_tags ) - 6 ) ) ); ?></summary>
												<?php echo implode( '', array_slice( $wcppp_tags, 6 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?>
											</details>
										<?php endif; ?>
									</td>
									<td data-label="<?php esc_attr_e( 'Buyers', 'wc_pay_per_post' ); ?>">
										<?php if ( $wcppp_ids['products'] ) : ?>
											<a class="wcppp-btn is-small" role="button" href="<?php echo esc_url( add_query_arg( 'product_id', $wcppp_post->ID, $wcppp_screen_url ) ); ?>" data-buyers="<?php echo absint( $wcppp_post->ID ); ?>" aria-expanded="false" aria-controls="<?php echo esc_attr( $wcppp_panel ); ?>"><?php esc_html_e( 'Show buyers', 'wc_pay_per_post' ); ?></a>
										<?php elseif ( $wcppp_ids['plans'] || $wcppp_ids['levels'] ) : ?>
											<span class="wcppp-muted"><?php esc_html_e( 'Memberships: buyers are not listed', 'wc_pay_per_post' ); ?></span>
										<?php else : ?>
											<span class="wcppp-muted"><?php esc_html_e( 'No products', 'wc_pay_per_post' ); ?></span>
										<?php endif; ?>
									</td>
								</tr>
								<?php if ( $wcppp_ids['products'] ) : ?>
									<tr class="wcppp-pc-buyers-row" id="<?php echo esc_attr( $wcppp_panel ); ?>" hidden>
										<td colspan="5">
											<div class="wcppp-pc-buyers" data-buyers-panel aria-live="polite"></div>
										</td>
									</tr>
								<?php endif; ?>
							<?php endforeach; ?>
							</tbody>
						</table>
					</div>

					<?php if ( $wcppp_pages > 1 ) : ?>
						<nav class="wcppp-pager" aria-label="<?php esc_attr_e( 'Pages of protected posts', 'wc_pay_per_post' ); ?>">
							<?php if ( $wcppp_paged > 1 ) : ?>
								<a class="wcppp-btn is-small" href="<?php echo esc_url( $wcppp_page_url( 1 ) ); ?>" aria-label="<?php esc_attr_e( 'First page', 'wc_pay_per_post' ); ?>">&laquo;</a>
								<a class="wcppp-btn is-small" href="<?php echo esc_url( $wcppp_page_url( $wcppp_paged - 1 ) ); ?>" rel="prev"><?php esc_html_e( 'Previous', 'wc_pay_per_post' ); ?></a>
							<?php endif; ?>
							<span class="wcppp-pager-label">
								<?php
								/* translators: 1: current page, 2: number of pages */
								echo esc_html( sprintf( __( 'Page %1$s of %2$s', 'wc_pay_per_post' ), number_format_i18n( $wcppp_paged ), number_format_i18n( $wcppp_pages ) ) );
								?>
							</span>
							<?php if ( $wcppp_paged < $wcppp_pages ) : ?>
								<a class="wcppp-btn is-small" href="<?php echo esc_url( $wcppp_page_url( $wcppp_paged + 1 ) ); ?>" rel="next"><?php esc_html_e( 'Next', 'wc_pay_per_post' ); ?></a>
								<a class="wcppp-btn is-small" href="<?php echo esc_url( $wcppp_page_url( $wcppp_pages ) ); ?>" aria-label="<?php esc_attr_e( 'Last page', 'wc_pay_per_post' ); ?>">&raquo;</a>
							<?php endif; ?>
						</nav>
					<?php endif; ?>
				<?php endif; ?>
			</section>
		<?php endif; ?>
	</div>
</div>
