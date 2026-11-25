<?php
/**
 * Settings screen.
 *
 * Every field keeps the option name, default and save path it had: the form
 * posts to options.php under the wc_pay_per_post_settings group.
 *
 * Variables come from Woocommerce_Pay_Per_Post_Admin::create_options_page().
 *
 * @package Woocommerce_pay_per_post
 * @noinspection PhpUndefinedVariableInspection
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$wcppp_current_screen = 'settings';
$wcppp_upgrade_url    = $upgrade_url;
$wcppp_settings_url   = admin_url( 'admin.php?page=' . WC_PPP_SLUG . '-settings' );
$wcppp_check_url      = Woocommerce_Pay_Per_Post_Admin::support_url( 'check' );
$wcppp_log_url        = Woocommerce_Pay_Per_Post_Admin::support_url( 'log' );

// Comments are two settings that make one three-way choice. "Nobody" is the
// free setting on its own; "Customers with access" is the premium setting,
// which opens comments to customers with access even when the free one is on.
// See Woocommerce_Pay_Per_Post_Admin::comments_mode().
$wcppp_comments_mode = Woocommerce_Pay_Per_Post_Admin::comments_mode( $premium, $turn_off_comments_when_protected, $turn_off_comments_public );

$wcppp_post_type_options = [];
foreach ( $available_post_types as $wcppp_post_type ) {
	$wcppp_object                                = get_post_type_object( $wcppp_post_type );
	$wcppp_post_type_options[ $wcppp_post_type ] = $wcppp_object ? $wcppp_object->labels->name : $wcppp_post_type;
}
// Post types saved earlier that are no longer public stay listed, ticked.
foreach ( $custom_post_types as $wcppp_post_type ) {
	if ( '' !== (string) $wcppp_post_type && ! isset( $wcppp_post_type_options[ $wcppp_post_type ] ) ) {
		$wcppp_object                                = get_post_type_object( $wcppp_post_type );
		$wcppp_post_type_options[ $wcppp_post_type ] = $wcppp_object ? $wcppp_object->labels->name : $wcppp_post_type;
	}
}

$wcppp_tokens = [
	[
		'token'       => '{{product_id}}',
		'description' => $premium
			? __( 'The IDs of the products that unlock the post. On a WooCommerce Memberships post, the products that grant the plan.', 'wc_pay_per_post' )
			: __( 'The IDs of the products that unlock the post.', 'wc_pay_per_post' ),
		'premium'     => false,
	],
	[
		'token'       => '{{parent_id}}',
		'description' => __( 'The parent of the first product, for variations.', 'wc_pay_per_post' ),
		'premium'     => false,
	],
	[
		'token'       => '{{excerpt}}',
		'description' => __( 'The post\'s excerpt, or its first 55 words.', 'wc_pay_per_post' ),
		'premium'     => true,
	],
	[
		'token'       => '{{memberships}}',
		'description' => __( 'Membership sign-up links, or a members-only notice. Left out, the links follow the message.', 'wc_pay_per_post' ),
		'premium'     => true,
	],
];

// The tab a request opens on; JavaScript switches tabs without a reload.
$wcppp_tab_url = static function ( $tab ) use ( $wcppp_settings_url ) {
	return 'paywall' === $tab ? $wcppp_settings_url : add_query_arg( 'tab', $tab, $wcppp_settings_url );
};
$wcppp_pane    = static function ( $tab ) use ( $settings_tab ) {
	echo ' id="' . esc_attr( $tab ) . '" data-pane="' . esc_attr( $tab ) . '"' . ( $tab === $settings_tab ? '' : ' hidden' );
};

// A checkbox with "On" beside it. The row's title is its label.
$wcppp_on = static function ( $name, $checked, $describedby = '', $extra = '' ) {
	printf(
		'<label class="wcppp-on"><input type="checkbox" value="1" id="%1$s" class="%1$s" name="%1$s"%2$s%3$s%4$s><span aria-hidden="true">%5$s</span></label>',
		esc_attr( WC_PPP_SLUG . '_' . $name ),
		'' !== $describedby ? ' aria-describedby="' . esc_attr( $describedby ) . '"' : '',
		$extra, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed attributes from this file.
		checked( $checked, true, false ),
		esc_html__( 'On', 'wc_pay_per_post' )
	);
};
$wcppp_on_locked = static function ( $label ) {
	printf(
		'<label class="wcppp-on is-locked"><input type="checkbox" disabled aria-label="%1$s"><span aria-hidden="true">%2$s</span></label>',
		esc_attr( $label ),
		esc_html__( 'On', 'wc_pay_per_post' )
	);
};

// On the free plan a Premium badge links to the plans. Inside a tab, which is
// a link already, it stays a label.
$wcppp_premium_tag   = '<span class="wcppp-badge is-premium">' . esc_html__( 'Premium', 'wc_pay_per_post' ) . '</span>';
$wcppp_premium_badge = '' === $upgrade_url ? $wcppp_premium_tag : sprintf(
	'<a class="wcppp-badge is-premium" href="%1$s">%2$s<span class="screen-reader-text"> %3$s</span></a>',
	esc_url( $upgrade_url ),
	esc_html__( 'Premium', 'wc_pay_per_post' ),
	esc_html__( '(compare plans)', 'wc_pay_per_post' )
);

// A note under a setting that a filter on this site may change. The filter
// receives the setting and has the last word.
$wcppp_filtered = static function ( $filter ) {
	if ( ! has_filter( $filter ) ) {
		return;
	}
	echo '<p class="wcppp-row-desc wcppp-filtered">';
	echo wp_kses(
		/* translators: %s: a filter name */
		sprintf( __( 'Code on this site changes this with the <code>%s</code> filter, which has the last word.', 'wc_pay_per_post' ), esc_html( $filter ) ),
		[ 'code' => [] ]
	);
	echo '</p>';
};

// A grid of role checkboxes. Roles saved earlier that no longer exist stay listed, ticked.
$wcppp_roles = static function ( $name, $selected, $describedby, $exclude = [] ) use ( $role_names ) {
	$roles = array_diff_key( $role_names, array_flip( $exclude ) );
	foreach ( $selected as $role ) {
		if ( ! isset( $roles[ $role ] ) && ! in_array( $role, $exclude, true ) ) {
			$roles[ $role ] = $role;
		}
	}
	echo '<div class="wcppp-checklist is-grid">';
	foreach ( $roles as $role => $label ) {
		printf(
			'<label><input type="checkbox" name="%1$s[]" value="%2$s" aria-describedby="%3$s"%4$s><span>%5$s</span></label>',
			esc_attr( WC_PPP_SLUG . '_' . $name ),
			esc_attr( $role ),
			esc_attr( $describedby ),
			checked( in_array( $role, $selected, true ), true, false ),
			esc_html( $label )
		);
	}
	echo '</div>';
};

// A text field whose placeholder is the plugin's own wording; empty keeps it.
$wcppp_text = static function ( $name, $label, $placeholder, $describedby ) use ( $custom_texts ) {
	printf(
		'<input type="text" class="wcppp-input wcppp-row-input" id="%1$s" name="%1$s" value="%2$s" placeholder="%3$s" aria-describedby="%4$s" aria-label="%5$s">',
		esc_attr( WC_PPP_SLUG . '_' . $name ),
		esc_attr( $custom_texts[ $name ] ?? '' ),
		esc_attr( $placeholder ),
		esc_attr( $describedby ),
		esc_attr( $label )
	);
};
?>
<div class="wrap wcppp-app">
	<?php require plugin_dir_path( __FILE__ ) . 'app-header.php'; ?>

	<div class="wcppp-body">
		<div class="wcppp-titlebar">
			<div>
				<h1 class="wcppp-h1"><?php esc_html_e( 'Settings', 'wc_pay_per_post' ); ?></h1>
				<p class="wcppp-sub"><?php esc_html_e( 'Site-wide defaults. Each post\'s protection is set in its editor.', 'wc_pay_per_post' ); ?></p>
			</div>
		</div>

		<?php settings_errors(); ?>

		<!--suppress HtmlUnknownTarget -->
		<form action="options.php" method="post" class="wcppp-settings-form" id="wcppp-settings-form">
			<?php settings_fields( WC_PPP_SLUG . '_settings' ); ?>

			<div class="wcppp-panel">
				<nav class="wcppp-sectabs" aria-label="<?php esc_attr_e( 'Settings sections', 'wc_pay_per_post' ); ?>" data-settings-tabs>
					<?php foreach ( $settings_tabs as $wcppp_tab => $wcppp_tab_label ) : ?>
						<a href="<?php echo esc_url( $wcppp_tab_url( $wcppp_tab ) ); ?>" data-tab="<?php echo esc_attr( $wcppp_tab ); ?>"<?php echo $wcppp_tab === $settings_tab ? ' class="is-current" aria-current="true"' : ''; ?>>
							<?php echo esc_html( $wcppp_tab_label ); ?>
							<?php if ( 'my-account' === $wcppp_tab && ! $premium ) : ?>
								<?php echo $wcppp_premium_tag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<?php elseif ( 'access' === $wcppp_tab && $guest_checkout_enabled ) : ?>
								<span class="wcppp-badge is-err"><?php esc_html_e( 'Guest checkout on', 'wc_pay_per_post' ); ?></span>
							<?php elseif ( 'advanced' === $wcppp_tab && $enable_debugging ) : ?>
								<span class="wcppp-badge is-warn"><?php esc_html_e( 'Debug log on', 'wc_pay_per_post' ); ?></span>
							<?php endif; ?>
						</a>
					<?php endforeach; ?>
				</nav>

				<?php // ---------------------------------------------------------------- Paywall ?>
				<section class="wcppp-pane" aria-labelledby="wcppp-h-paywall"<?php $wcppp_pane( 'paywall' ); ?>>
					<div class="wcppp-pane-head">
						<h2 id="wcppp-h-paywall"><?php esc_html_e( 'Paywall', 'wc_pay_per_post' ); ?></h2>
						<p><?php esc_html_e( 'Shown in place of a protected post\'s content to anyone without access.', 'wc_pay_per_post' ); ?></p>
					</div>

					<div class="wcppp-row wcppp-paywall-split">
						<div class="wcppp-paywall-editor">
							<label class="wcppp-field-label" for="<?php echo esc_attr( WC_PPP_SLUG ); ?>_restricted_content_default"><?php esc_html_e( 'Default paywall message', 'wc_pay_per_post' ); ?></label>
							<?php wp_editor( $restricted_content_default, WC_PPP_SLUG . '_restricted_content_default', [ 'textarea_rows' => 8 ] ); ?>

							<table class="wcppp-tokens">
								<caption><?php esc_html_e( 'Tokens: each is replaced on the post', 'wc_pay_per_post' ); ?></caption>
								<tbody>
								<?php foreach ( $wcppp_tokens as $wcppp_token ) : ?>
									<?php $wcppp_locked = $wcppp_token['premium'] && ! $premium; ?>
									<tr>
										<th scope="row"><code><?php echo esc_html( $wcppp_token['token'] ); ?></code></th>
										<td><?php echo esc_html( $wcppp_token['description'] ); ?></td>
										<td class="wcppp-tokens-action">
											<?php if ( $wcppp_locked ) : ?>
												<?php echo $wcppp_premium_badge; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
											<?php else : ?>
												<button type="button" class="wcppp-btn is-small wcppp-token" data-token="<?php echo esc_attr( $wcppp_token['token'] ); ?>"
													<?php /* translators: %s: a token such as {{product_id}} */ ?>
													aria-label="<?php echo esc_attr( sprintf( __( 'Insert %s', 'wc_pay_per_post' ), $wcppp_token['token'] ) ); ?>"><?php esc_html_e( 'Insert', 'wc_pay_per_post' ); ?></button>
											<?php endif; ?>
										</td>
									</tr>
								<?php endforeach; ?>
								</tbody>
							</table>
							<p class="wcppp-help">
								<?php
								echo wp_kses(
									__( 'WooCommerce shortcodes work here, such as <code>[products ids=\'{{product_id}}\']</code>. With no product to show, a shortcode that uses a product token is left out.', 'wc_pay_per_post' ),
									[ 'code' => [] ]
								);
								?>
								<?php if ( $premium ) : ?>
									<?php esc_html_e( 'A post can replace this message with its own in its Pay For Post settings.', 'wc_pay_per_post' ); ?>
								<?php endif; ?>
							</p>
						</div>

						<div class="wcppp-paywall-preview">
							<label class="wcppp-field-label" for="wcppp-preview-post"><?php esc_html_e( 'Preview on a post', 'wc_pay_per_post' ); ?></label>
							<div class="wcppp-inline">
								<input type="text" id="wcppp-preview-post" class="wcppp-input" autocomplete="off" spellcheck="false" placeholder="<?php esc_attr_e( 'Post ID or URL', 'wc_pay_per_post' ); ?>" aria-describedby="wcppp-preview-status" value="<?php echo isset( $_GET['preview'] ) ? esc_attr( (string) absint( $_GET['preview'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- fills a field; the preview itself is nonce-checked. ?>"<?php echo isset( $_GET['preview'] ) ? ' data-autopreview' : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>>
								<button type="button" class="wcppp-btn" id="wcppp-preview-button"><?php esc_html_e( 'Preview', 'wc_pay_per_post' ); ?></button>
							</div>
							<p class="wcppp-preview-empty" id="wcppp-preview-status" role="status"><?php esc_html_e( 'Shows this message on one of your posts, unsaved changes included, as a visitor without access sees it.', 'wc_pay_per_post' ); ?></p>
							<div class="wcppp-preview" id="wcppp-preview" hidden>
								<iframe class="wcppp-preview-frame" id="wcppp-preview-frame" title="<?php esc_attr_e( 'Paywall preview', 'wc_pay_per_post' ); ?>" sandbox=""></iframe>
							</div>
						</div>
					</div>

					<fieldset class="wcppp-row wcppp-row-stack" data-comments-mode>
						<legend class="wcppp-row-title"><?php esc_html_e( 'Who can comment on protected posts', 'wc_pay_per_post' ); ?></legend>
						<div class="wcppp-radios">
							<label><input type="radio" name="wc_ppp_comments_mode" value="everyone" <?php checked( $wcppp_comments_mode, 'everyone' ); ?>><span><b><?php esc_html_e( 'Everyone', 'wc_pay_per_post' ); ?></b> <?php esc_html_e( 'Comments work as they do on any other post.', 'wc_pay_per_post' ); ?></span></label>
							<label<?php echo $premium ? '' : ' class="is-locked"'; ?>><input type="radio" name="wc_ppp_comments_mode" value="customers" <?php checked( $wcppp_comments_mode, 'customers' ); ?> <?php disabled( ! $premium ); ?>><span><b><?php esc_html_e( 'Customers with access', 'wc_pay_per_post' ); ?></b><?php echo $premium ? '' : ' ' . $wcppp_premium_badge; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php esc_html_e( 'Only customers who can open the post can comment on it.', 'wc_pay_per_post' ); ?></span></label>
							<label><input type="radio" name="wc_ppp_comments_mode" value="nobody" <?php checked( $wcppp_comments_mode, 'nobody' ); ?>><span><b><?php esc_html_e( 'Nobody', 'wc_pay_per_post' ); ?></b> <?php esc_html_e( 'Comments close on protected posts for everyone, customers who have paid included.', 'wc_pay_per_post' ); ?></span></label>
						</div>
						<?php // The settings themselves. The choice above sets them; until it changes they post what is stored. Without JavaScript, the save compares the choice with the one shown here. ?>
						<input type="hidden" name="wc_ppp_comments_mode_shown" value="<?php echo esc_attr( $wcppp_comments_mode ); ?>">
						<input type="hidden" name="<?php echo esc_attr( WC_PPP_SLUG ); ?>_turn_off_comments_when_protected" id="<?php echo esc_attr( WC_PPP_SLUG ); ?>_turn_off_comments_when_protected" value="<?php echo $turn_off_comments_when_protected ? '1' : ''; ?>">
						<?php if ( $premium ) : ?>
							<input type="hidden" name="<?php echo esc_attr( WC_PPP_SLUG ); ?>_turn_off_comments_public" id="<?php echo esc_attr( WC_PPP_SLUG ); ?>_turn_off_comments_public" value="<?php echo $turn_off_comments_public ? '1' : ''; ?>">
						<?php endif; ?>
					</fieldset>

					<div class="wcppp-row<?php echo $premium ? '' : ' is-locked'; ?>">
						<div class="wcppp-row-text">
							<?php if ( $premium ) : ?>
								<label class="wcppp-row-title" for="<?php echo esc_attr( WC_PPP_SLUG ); ?>_show_locks"><?php esc_html_e( 'Lock icon on locked titles', 'wc_pay_per_post' ); ?></label>
							<?php else : ?>
								<span class="wcppp-row-title"><?php esc_html_e( 'Lock icon on locked titles', 'wc_pay_per_post' ); ?> <?php echo $wcppp_premium_badge; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							<?php endif; ?>
							<p class="wcppp-row-desc" id="wcppp-show-locks-desc">
								<?php
								echo wp_kses(
									__( 'Visitors see a lock beside the title of each protected post they can\'t open. Change the icon with the <code>wc_pay_per_post_paywall_icon</code> filter.', 'wc_pay_per_post' ),
									[ 'code' => [] ]
								);
								?>
							</p>
						</div>
						<?php $premium ? $wcppp_on( 'show_locks', $show_locks, 'wcppp-show-locks-desc' ) : $wcppp_on_locked( __( 'Lock icon on locked titles', 'wc_pay_per_post' ) ); ?>
					</div>

					<div class="wcppp-row<?php echo $premium ? '' : ' is-locked'; ?>">
						<div class="wcppp-row-text">
							<?php if ( $premium ) : ?>
								<label class="wcppp-row-title" for="<?php echo esc_attr( WC_PPP_SLUG ); ?>_status_box_position"><?php esc_html_e( 'Views or time remaining box', 'wc_pay_per_post' ); ?></label>
							<?php else : ?>
								<span class="wcppp-row-title"><?php esc_html_e( 'Views or time remaining box', 'wc_pay_per_post' ); ?> <?php echo $wcppp_premium_badge; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							<?php endif; ?>
							<p class="wcppp-row-desc" id="wcppp-status-box-desc"><?php esc_html_e( 'Where it appears on page-view and expiry posts that show it.', 'wc_pay_per_post' ); ?></p>
							<?php $premium && $wcppp_filtered( 'wc_pay_for_post_show_warnings_position' ); ?>
						</div>
						<?php if ( $premium ) : ?>
							<select class="wcppp-select" id="<?php echo esc_attr( WC_PPP_SLUG ); ?>_status_box_position" name="<?php echo esc_attr( WC_PPP_SLUG ); ?>_status_box_position" aria-describedby="wcppp-status-box-desc">
								<option value="top" <?php selected( $status_box_position, 'top' ); ?>><?php esc_html_e( 'Above the content', 'wc_pay_per_post' ); ?></option>
								<option value="bottom" <?php selected( $status_box_position, 'bottom' ); ?>><?php esc_html_e( 'Below the content', 'wc_pay_per_post' ); ?></option>
							</select>
						<?php else : ?>
							<select class="wcppp-select" disabled aria-label="<?php esc_attr_e( 'Views or time remaining box', 'wc_pay_per_post' ); ?>"><option><?php esc_html_e( 'Above the content', 'wc_pay_per_post' ); ?></option></select>
						<?php endif; ?>
					</div>

					<div class="wcppp-row<?php echo $premium ? '' : ' is-locked'; ?>">
						<div class="wcppp-row-text">
							<?php if ( $premium ) : ?>
								<label class="wcppp-row-title" for="<?php echo esc_attr( WC_PPP_SLUG ); ?>_expiry_reload"><?php esc_html_e( 'Reload when access runs out', 'wc_pay_per_post' ); ?></label>
							<?php else : ?>
								<span class="wcppp-row-title"><?php esc_html_e( 'Reload when access runs out', 'wc_pay_per_post' ); ?> <?php echo $wcppp_premium_badge; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							<?php endif; ?>
							<p class="wcppp-row-desc" id="wcppp-expiry-reload-desc"><?php esc_html_e( 'An expiry post a customer has open reloads itself the moment their access ends, so the paywall appears. Off: they keep reading until they leave the page.', 'wc_pay_per_post' ); ?></p>
							<?php $premium && $wcppp_filtered( 'wc_pay_per_post_enable_javascript_expiration_refresh' ); ?>
						</div>
						<?php $premium ? $wcppp_on( 'expiry_reload', $expiry_reload, 'wcppp-expiry-reload-desc' ) : $wcppp_on_locked( __( 'Reload when access runs out', 'wc_pay_per_post' ) ); ?>
					</div>
				</section>

				<?php // ---------------------------------------------------------------- Access ?>
				<section class="wcppp-pane" aria-labelledby="wcppp-h-access"<?php $wcppp_pane( 'access' ); ?>>
					<div class="wcppp-pane-head">
						<h2 id="wcppp-h-access"><?php esc_html_e( 'Access', 'wc_pay_per_post' ); ?></h2>
						<p><?php esc_html_e( 'Who reads protected posts without buying them. Access belongs to a customer account, so guest checkout must be off: a guest has no account to unlock.', 'wc_pay_per_post' ); ?></p>
						<?php if ( $guest_checkout_enabled ) : ?>
							<p class="wcppp-alert is-err">
								<?php echo Woocommerce_Pay_Per_Post_Admin::icon( 'warn' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<span><b><?php esc_html_e( 'Guest checkout is on, so customers who check out as guests get nothing for their money.', 'wc_pay_per_post' ); ?></b> <a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=account' ) ); ?>"><?php esc_html_e( 'Turn it off in WooCommerce', 'wc_pay_per_post' ); ?></a></span>
							</p>
						<?php else : ?>
							<p><span class="wcppp-badge is-ok"><?php echo Woocommerce_Pay_Per_Post_Admin::icon( 'check' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php esc_html_e( 'Guest checkout off', 'wc_pay_per_post' ); ?></span></p>
						<?php endif; ?>
					</div>

					<div class="wcppp-row">
						<div class="wcppp-row-text">
							<label class="wcppp-row-title" for="<?php echo esc_attr( WC_PPP_SLUG ); ?>_allow_admins_access_to_protected_posts"><?php esc_html_e( 'Administrators see everything', 'wc_pay_per_post' ); ?></label>
							<p class="wcppp-row-desc" id="wcppp-admins-desc">
								<?php esc_html_e( 'Administrators can read every protected post without buying it (on multisite, network super admins only). Turn off to see the paywall as a customer does.', 'wc_pay_per_post' ); ?>
								<a href="<?php echo esc_url( $wcppp_check_url ); ?>"><?php esc_html_e( 'Check what one customer sees', 'wc_pay_per_post' ); ?></a>
							</p>
						</div>
						<?php $wcppp_on( 'allow_admins_access_to_protected_posts', $allow_admins_access_to_protected_posts, 'wcppp-admins-desc' ); ?>
					</div>

					<?php if ( $premium ) : ?>
						<fieldset class="wcppp-row wcppp-row-stack">
							<legend class="wcppp-row-title"><?php esc_html_e( 'Other roles that skip the paywall', 'wc_pay_per_post' ); ?></legend>
							<p class="wcppp-row-desc" id="wcppp-bypass-roles-desc"><?php esc_html_e( 'These roles read every protected post without buying it, such as editors or staff. Administrators are set above. Leave the role your customers get unticked (usually Customer or Subscriber): ticking it opens every protected post to all of them.', 'wc_pay_per_post' ); ?></p>
							<?php $wcppp_roles( 'paywall_bypass_roles', $paywall_bypass_roles, 'wcppp-bypass-roles-desc', [ 'administrator' ] ); ?>
							<?php if ( has_filter( 'wc_pay_for_post_allowed_roles' ) ) : ?>
								<?php $wcppp_filtered( 'wc_pay_for_post_allowed_roles' ); ?>
								<p class="wcppp-row-desc">
									<?php
									$wcppp_effective_roles = [];
									foreach ( $allowed_roles as $wcppp_role ) {
										$wcppp_effective_roles[] = $role_names[ $wcppp_role ] ?? $wcppp_role;
									}
									/* translators: %s: comma-separated role names, or "none" */
									echo esc_html( sprintf( __( 'Roles skipping the paywall now: %s.', 'wc_pay_per_post' ), $wcppp_effective_roles ? implode( ', ', $wcppp_effective_roles ) : __( 'none', 'wc_pay_per_post' ) ) );
									?>
								</p>
							<?php endif; ?>
						</fieldset>
					<?php else : ?>
						<div class="wcppp-row is-locked">
							<div class="wcppp-row-text">
								<span class="wcppp-row-title"><?php esc_html_e( 'Other roles that skip the paywall', 'wc_pay_per_post' ); ?> <?php echo $wcppp_premium_badge; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
								<p class="wcppp-row-desc"><?php esc_html_e( 'Let roles such as editors or staff read protected posts without buying them.', 'wc_pay_per_post' ); ?></p>
							</div>
						</div>
					<?php endif; ?>
				</section>

				<?php // ---------------------------------------------------------------- My Account ?>
				<section class="wcppp-pane" aria-labelledby="wcppp-h-account"<?php $wcppp_pane( 'my-account' ); ?>>
					<div class="wcppp-pane-head">
						<h2 id="wcppp-h-account"><?php esc_html_e( 'Customers', 'wc_pay_per_post' ); ?> <?php echo $premium ? '' : $wcppp_premium_badge; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h2>
						<p><?php esc_html_e( 'What customers see after they buy: links to their content on their orders, and a Purchased Content tab in My Account.', 'wc_pay_per_post' ); ?></p>
						<?php if ( ! $premium ) : ?>
							<p class="wcppp-premium-note">
								<?php esc_html_e( 'Customers find everything they have bought in one place.', 'wc_pay_per_post' ); ?>
								<?php if ( '' !== $upgrade_url ) : ?>
									<a href="<?php echo esc_url( $upgrade_url ); ?>"><?php esc_html_e( 'Available with Premium', 'wc_pay_per_post' ); ?></a>
								<?php else : ?>
									<?php esc_html_e( 'Available with Premium.', 'wc_pay_per_post' ); ?>
								<?php endif; ?>
							</p>
						<?php endif; ?>
					</div>

					<?php if ( $premium ) : ?>
						<h3 class="wcppp-pane-sub"><?php esc_html_e( 'Orders and emails', 'wc_pay_per_post' ); ?></h3>
						<fieldset class="wcppp-row wcppp-row-stack">
							<legend class="wcppp-row-title"><?php esc_html_e( 'Purchased content links on orders', 'wc_pay_per_post' ); ?></legend>
							<p class="wcppp-row-desc"><?php esc_html_e( 'Under each product that unlocks content, a list of links to that content: on the order confirmation page, in My Account > Orders and in order emails.', 'wc_pay_per_post' ); ?></p>
							<div class="wcppp-radios">
								<label><input type="radio" name="<?php echo esc_attr( WC_PPP_SLUG ); ?>_order_links" value="everywhere" <?php checked( $order_links, 'everywhere' ); ?>><span><b><?php esc_html_e( 'Everywhere', 'wc_pay_per_post' ); ?></b> <?php esc_html_e( 'On the order pages and in emails.', 'wc_pay_per_post' ); ?></span></label>
								<label><input type="radio" name="<?php echo esc_attr( WC_PPP_SLUG ); ?>_order_links" value="not_in_emails" <?php checked( $order_links, 'not_in_emails' ); ?>><span><b><?php esc_html_e( 'Not in emails', 'wc_pay_per_post' ); ?></b> <?php esc_html_e( 'On the order pages only. Useful when one product unlocks a long list, such as a subscription to everything.', 'wc_pay_per_post' ); ?></span></label>
								<label><input type="radio" name="<?php echo esc_attr( WC_PPP_SLUG ); ?>_order_links" value="off" <?php checked( $order_links, 'off' ); ?>><span><b><?php esc_html_e( 'Off', 'wc_pay_per_post' ); ?></b> <?php esc_html_e( 'Orders show only WooCommerce\'s own item details.', 'wc_pay_per_post' ); ?></span></label>
							</div>
							<?php $wcppp_filtered( 'wc_pay_per_post_show_item_meta' ); ?>
							<?php $wcppp_filtered( 'wc_pay_per_post_hide_item_meta_in_email' ); ?>
						</fieldset>
						<div class="wcppp-row">
							<div class="wcppp-row-text">
								<label class="wcppp-row-title" for="<?php echo esc_attr( WC_PPP_SLUG ); ?>_order_links_heading"><?php esc_html_e( 'Heading above the links', 'wc_pay_per_post' ); ?></label>
								<p class="wcppp-row-desc" id="wcppp-order-links-heading-desc"><?php esc_html_e( 'Leave empty for the default.', 'wc_pay_per_post' ); ?></p>
								<?php $wcppp_filtered( 'wc_pay_per_post_purchased_content_title' ); ?>
							</div>
							<?php $wcppp_text( 'order_links_heading', __( 'Heading above the links', 'wc_pay_per_post' ), __( 'Access Your Purchased Content', 'wc_pay_per_post' ), 'wcppp-order-links-heading-desc' ); ?>
						</div>

						<h3 class="wcppp-pane-sub"><?php esc_html_e( 'My Account', 'wc_pay_per_post' ); ?></h3>
						<div class="wcppp-row">
							<div class="wcppp-row-text">
								<label class="wcppp-row-title" for="<?php echo esc_attr( WC_PPP_SLUG ); ?>_display_on_my_account"><?php esc_html_e( 'Purchased Content tab', 'wc_pay_per_post' ); ?></label>
								<p class="wcppp-row-desc" id="wcppp-my-account-desc"><?php esc_html_e( 'Appears below Dashboard. If its link opens a "page not found" error after you save, open Settings > Permalinks and save once.', 'wc_pay_per_post' ); ?></p>
							</div>
							<?php $wcppp_on( 'display_on_my_account', $display_on_my_account, 'wcppp-my-account-desc', ' aria-controls="wcppp-my-account-lists"' ); ?>
						</div>
						<div id="wcppp-my-account-lists" class="my-account-shortcode-container"<?php echo $display_on_my_account ? '' : ' hidden'; ?>>
							<fieldset class="wcppp-row">
								<div class="wcppp-row-text">
									<legend class="wcppp-row-title"><?php esc_html_e( 'Lists to show', 'wc_pay_per_post' ); ?></legend>
									<p class="wcppp-row-desc" id="wcppp-lists-desc"><?php esc_html_e( 'With neither ticked, both show.', 'wc_pay_per_post' ); ?></p>
								</div>
								<div class="wcppp-checklist">
									<label><input type="checkbox" name="<?php echo esc_attr( WC_PPP_SLUG ); ?>_shortcodes_to_display_on_my_account[]" value="wc_pay_per_post_my_account_show_has_access" aria-describedby="wcppp-lists-desc" <?php checked( in_array( 'wc_pay_per_post_my_account_show_has_access', $shortcodes_to_display_on_my_account, true ) ); ?>><span><?php esc_html_e( 'Content you have access to', 'wc_pay_per_post' ); ?></span></label>
									<label><input type="checkbox" name="<?php echo esc_attr( WC_PPP_SLUG ); ?>_shortcodes_to_display_on_my_account[]" value="wc_pay_per_post_my_account_show_purchased" aria-describedby="wcppp-lists-desc" <?php checked( in_array( 'wc_pay_per_post_my_account_show_purchased', $shortcodes_to_display_on_my_account, true ) ); ?>><span><?php esc_html_e( 'Content purchased', 'wc_pay_per_post' ); ?></span></label>
								</div>
							</fieldset>
							<div class="wcppp-row">
								<div class="wcppp-row-text">
									<label class="wcppp-row-title" for="<?php echo esc_attr( WC_PPP_SLUG ); ?>_my_account_sort_order"><?php esc_html_e( 'Sort the lists by', 'wc_pay_per_post' ); ?></label>
									<p class="wcppp-row-desc" id="wcppp-sort-desc"><?php esc_html_e( 'Applies to both lists. Titles sort naturally, so "Lesson 2" comes before "Lesson 10".', 'wc_pay_per_post' ); ?></p>
								</div>
								<select class="wcppp-select" id="<?php echo esc_attr( WC_PPP_SLUG ); ?>_my_account_sort_order" name="<?php echo esc_attr( WC_PPP_SLUG ); ?>_my_account_sort_order" aria-describedby="wcppp-sort-desc">
									<?php foreach ( $my_account_sort_orders as $wcppp_key => $wcppp_sort_order ) : ?>
										<option value="<?php echo esc_attr( $wcppp_key ); ?>" <?php selected( $my_account_sort_order, $wcppp_key ); ?>><?php echo esc_html( $wcppp_sort_order['label'] ); ?></option>
									<?php endforeach; ?>
								</select>
							</div>
						</div>
							<div class="wcppp-row">
								<div class="wcppp-row-text">
									<label class="wcppp-row-title" for="<?php echo esc_attr( WC_PPP_SLUG ); ?>_my_account_tab_title"><?php esc_html_e( 'Tab name', 'wc_pay_per_post' ); ?></label>
									<p class="wcppp-row-desc" id="wcppp-my-account-tab-title-desc"><?php esc_html_e( 'Leave empty for the default.', 'wc_pay_per_post' ); ?></p>
									<?php $wcppp_filtered( 'wc_pay_per_post_my_account_tab_title' ); ?>
								</div>
								<?php $wcppp_text( 'my_account_tab_title', __( 'Tab name', 'wc_pay_per_post' ), __( 'Purchased Content', 'wc_pay_per_post' ), 'wcppp-my-account-tab-title-desc' ); ?>
							</div>
							<div class="wcppp-row">
								<div class="wcppp-row-text">
									<label class="wcppp-row-title" for="<?php echo esc_attr( WC_PPP_SLUG ); ?>_my_account_has_access_title"><?php esc_html_e( 'Heading above the content they have access to', 'wc_pay_per_post' ); ?></label>
									<p class="wcppp-row-desc" id="wcppp-my-account-has-access-title-desc"><?php esc_html_e( 'Leave empty for the default.', 'wc_pay_per_post' ); ?></p>
									<?php $wcppp_filtered( 'wc_pay_per_post_my_account_has_access_title' ); ?>
								</div>
								<?php $wcppp_text( 'my_account_has_access_title', __( 'Heading above the content they have access to', 'wc_pay_per_post' ), __( 'Content you have access to', 'wc_pay_per_post' ), 'wcppp-my-account-has-access-title-desc' ); ?>
							</div>
							<div class="wcppp-row">
								<div class="wcppp-row-text">
									<label class="wcppp-row-title" for="<?php echo esc_attr( WC_PPP_SLUG ); ?>_my_account_purchased_title"><?php esc_html_e( 'Heading above the content they purchased', 'wc_pay_per_post' ); ?></label>
									<p class="wcppp-row-desc" id="wcppp-my-account-purchased-title-desc"><?php esc_html_e( 'Leave empty for the default.', 'wc_pay_per_post' ); ?></p>
									<?php $wcppp_filtered( 'wc_pay_per_post_my_account_purchased_title' ); ?>
								</div>
								<?php $wcppp_text( 'my_account_purchased_title', __( 'Heading above the content they purchased', 'wc_pay_per_post' ), __( 'Content purchased', 'wc_pay_per_post' ), 'wcppp-my-account-purchased-title-desc' ); ?>
							</div>
					<?php else : ?>
						<div class="wcppp-row is-locked">
							<div class="wcppp-row-text">
								<span class="wcppp-row-title"><?php esc_html_e( 'Purchased content links on orders', 'wc_pay_per_post' ); ?></span>
								<p class="wcppp-row-desc"><?php esc_html_e( 'Under each product that unlocks content, links to that content on the order pages and in emails.', 'wc_pay_per_post' ); ?></p>
							</div>
						</div>
						<div class="wcppp-row is-locked">
							<div class="wcppp-row-text">
								<span class="wcppp-row-title"><?php esc_html_e( 'Purchased Content tab', 'wc_pay_per_post' ); ?></span>
								<p class="wcppp-row-desc"><?php esc_html_e( 'Appears below Dashboard, with the content the customer can open and everything they bought.', 'wc_pay_per_post' ); ?></p>
							</div>
							<?php $wcppp_on_locked( __( 'Purchased Content tab', 'wc_pay_per_post' ) ); ?>
						</div>
						<div class="wcppp-row is-locked">
							<div class="wcppp-row-text">
								<span class="wcppp-row-title"><?php esc_html_e( 'Sort the lists by', 'wc_pay_per_post' ); ?></span>
								<p class="wcppp-row-desc"><?php esc_html_e( 'Title, publish date or menu order.', 'wc_pay_per_post' ); ?></p>
							</div>
							<select class="wcppp-select" disabled aria-label="<?php esc_attr_e( 'Sort the lists by', 'wc_pay_per_post' ); ?>"><option><?php esc_html_e( 'Newest published first', 'wc_pay_per_post' ); ?></option></select>
						</div>
					<?php endif; ?>
				</section>

				<?php // ---------------------------------------------------------------- Editor ?>
				<section class="wcppp-pane" aria-labelledby="wcppp-h-editor"<?php $wcppp_pane( 'editor' ); ?>>
					<div class="wcppp-pane-head">
						<h2 id="wcppp-h-editor"><?php esc_html_e( 'Editor', 'wc_pay_per_post' ); ?></h2>
						<p><?php esc_html_e( 'Where you set protection on a post.', 'wc_pay_per_post' ); ?></p>
					</div>

					<fieldset class="wcppp-row wcppp-row-stack">
						<legend class="wcppp-row-title"><?php esc_html_e( 'Post types you can protect', 'wc_pay_per_post' ); ?></legend>
						<p class="wcppp-row-desc" id="wcppp-post-types-desc">
							<?php esc_html_e( 'Pay For Post\'s settings appear in the editor of these types, and only their posts are protected. A custom post type must support custom fields, or the block editor can\'t save its protection.', 'wc_pay_per_post' ); ?>
							<a href="https://developer.wordpress.org/reference/functions/add_post_type_support/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Adding custom field support', 'wc_pay_per_post' ); ?><span class="screen-reader-text"> <?php esc_html_e( '(opens in a new tab)', 'wc_pay_per_post' ); ?></span></a>
						</p>
						<div class="wcppp-checklist is-grid">
							<?php foreach ( $wcppp_post_type_options as $wcppp_post_type => $wcppp_label ) : ?>
								<label>
									<input type="checkbox" name="<?php echo esc_attr( WC_PPP_SLUG ); ?>_custom_post_types[]" value="<?php echo esc_attr( $wcppp_post_type ); ?>" aria-describedby="wcppp-post-types-desc" <?php checked( in_array( $wcppp_post_type, $custom_post_types, false ) ); ?>>
									<span><?php echo esc_html( $wcppp_label ); ?> <code><?php echo esc_html( $wcppp_post_type ); ?></code></span>
								</label>
							<?php endforeach; ?>
						</div>
					</fieldset>

					<div class="wcppp-row">
						<div class="wcppp-row-text">
							<label class="wcppp-row-title" for="<?php echo esc_attr( WC_PPP_SLUG ); ?>_block_editor_enabled"><?php esc_html_e( 'Panel in the block editor sidebar', 'wc_pay_per_post' ); ?></label>
							<p class="wcppp-row-desc" id="wcppp-block-editor-desc"><?php esc_html_e( 'Protection settings sit in the block editor\'s sidebar. Turn off to show them in a box below the content instead, as the classic editor does.', 'wc_pay_per_post' ); ?></p>
						</div>
						<?php $wcppp_on( 'block_editor_enabled', $block_editor_enabled, 'wcppp-block-editor-desc' ); ?>
					</div>

					<div class="wcppp-row">
						<div class="wcppp-row-text">
							<label class="wcppp-row-title" for="<?php echo esc_attr( WC_PPP_SLUG ); ?>_only_show_virtual_products"><?php esc_html_e( 'Only offer virtual and downloadable products', 'wc_pay_per_post' ); ?></label>
							<p class="wcppp-row-desc" id="wcppp-virtual-desc"><?php esc_html_e( 'Shortens the product list in the post editor, for stores that also sell physical goods.', 'wc_pay_per_post' ); ?></p>
						</div>
						<?php $wcppp_on( 'only_show_virtual_products', $only_show_virtual_products, 'wcppp-virtual-desc' ); ?>
					</div>

					<fieldset class="wcppp-row wcppp-row-stack">
						<legend class="wcppp-row-title"><?php esc_html_e( 'Who can set protection', 'wc_pay_per_post' ); ?></legend>
						<p class="wcppp-row-desc" id="wcppp-editor-roles-desc"><?php esc_html_e( 'Only these roles see Pay For Post\'s settings in the post editor. With none ticked, everyone who can edit a post sees them. Tick Administrator too, or administrators will not see them either.', 'wc_pay_per_post' ); ?></p>
						<?php
						// Only roles that can edit posts have an editor to show it in. A role
						// saved earlier stays listed, so a save never drops it unseen.
						$wcppp_non_editors = [];
						foreach ( wp_roles()->roles as $wcppp_role => $wcppp_role_info ) {
							if ( empty( $wcppp_role_info['capabilities']['edit_posts'] ) && ! in_array( $wcppp_role, $editor_panel_roles, true ) ) {
								$wcppp_non_editors[] = $wcppp_role;
							}
						}
						$wcppp_roles( 'editor_panel_roles', $editor_panel_roles, 'wcppp-editor-roles-desc', $wcppp_non_editors );
						?>
						<?php $wcppp_filtered( 'wc_pay_per_post_allowed_roles_for_meta_box' ); ?>
					</fieldset>
				</section>

				<?php // ---------------------------------------------------------------- Integrations ?>
				<section class="wcppp-pane" aria-labelledby="wcppp-h-integrations"<?php $wcppp_pane( 'integrations' ); ?>>
					<div class="wcppp-pane-head">
						<h2 id="wcppp-h-integrations"><?php esc_html_e( 'Integrations', 'wc_pay_per_post' ); ?></h2>
						<p>
							<?php esc_html_e( 'Detected automatically when the plugin is active.', 'wc_pay_per_post' ); ?>
							<?php if ( ! $premium ) : ?>
								<?php esc_html_e( 'They work with Premium.', 'wc_pay_per_post' ); ?>
							<?php endif; ?>
						</p>
					</div>

					<?php foreach ( $integration_tiles as $wcppp_key => $wcppp_tile ) : ?>
						<div class="wcppp-row wcppp-int<?php echo 'missing' === $wcppp_tile['state'] ? ' is-off' : ''; ?>">
							<div class="wcppp-row-text">
								<span class="wcppp-row-title">
									<?php echo esc_html( $wcppp_tile['name'] ); ?>
									<?php
									switch ( $wcppp_tile['state'] ) {
										case 'active':
											echo '<span class="wcppp-badge is-ok">' . Woocommerce_Pay_Per_Post_Admin::icon( 'check' ) . esc_html__( 'Active', 'wc_pay_per_post' ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
											break;
										case 'off':
											echo '<span class="wcppp-badge is-mute">' . esc_html__( 'Installed, not in use', 'wc_pay_per_post' ) . '</span>';
											break;
										case 'needs-premium':
											echo $wcppp_premium_badge; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
											break;
										default:
											echo '<span class="wcppp-badge is-mute">' . esc_html__( 'Not installed', 'wc_pay_per_post' ) . '</span>';
									}
									?>
								</span>
								<p class="wcppp-row-desc"><?php echo esc_html( $wcppp_tile['description'] ); ?></p>
								<?php if ( 'woocommerce-subscriptions' === $wcppp_key && 'off' === $wcppp_tile['state'] ) : ?>
									<p class="wcppp-row-desc">
										<?php
										if ( $use_woocommerce_subscriptions ) {
											echo wp_kses(
												__( 'Switched off by the <code>wc_pay_per_post_can_use_woocommerce_subscriptions</code> filter, so subscription products count as ordinary purchases.', 'wc_pay_per_post' ),
												[ 'code' => [] ]
											);
										} else {
											esc_html_e( 'Switched off, so subscription products count as ordinary purchases. Use this when another plugin imitates WooCommerce Subscriptions and its subscription records can\'t be relied on.', 'wc_pay_per_post' );
										}
										?>
									</p>
								<?php elseif ( 'woocommerce-subscriptions' === $wcppp_key && 'active' === $wcppp_tile['state'] ) : ?>
									<?php $wcppp_filtered( 'wc_pay_per_post_can_use_woocommerce_subscriptions' ); ?>
								<?php endif; ?>
							</div>
							<?php if ( 'woocommerce-subscriptions' === $wcppp_key && $premium && $subscriptions_installed ) : ?>
								<span class="wcppp-ctl-group">
									<label for="<?php echo esc_attr( WC_PPP_SLUG ); ?>_use_woocommerce_subscriptions"><?php esc_html_e( 'Use WooCommerce Subscriptions', 'wc_pay_per_post' ); ?></label>
									<?php $wcppp_on( 'use_woocommerce_subscriptions', $use_woocommerce_subscriptions ); ?>
								</span>
							<?php endif; ?>
							<?php if ( 'polylang' === $wcppp_key && $premium && $polylang_installed ) : ?>
								<span class="wcppp-ctl-group">
									<label for="<?php echo esc_attr( WC_PPP_SLUG ); ?>_enable_polylang"><?php esc_html_e( 'Use Polylang', 'wc_pay_per_post' ); ?></label>
									<?php $wcppp_on( 'enable_polylang', $enable_polylang ); ?>
								</span>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
					<?php if ( $premium && ! $subscriptions_installed ) : ?>
						<?php // Without WooCommerce Subscriptions there is no checkbox; keep the setting for when it is back. ?>
						<input type="hidden" name="<?php echo esc_attr( WC_PPP_SLUG ); ?>_use_woocommerce_subscriptions" value="<?php echo $use_woocommerce_subscriptions ? '1' : ''; ?>">
					<?php endif; ?>
					<?php if ( $premium && ! $polylang_installed ) : ?>
						<?php // Without Polylang there is no checkbox; keep the setting as it is for when Polylang is back. ?>
						<input type="hidden" name="<?php echo esc_attr( WC_PPP_SLUG ); ?>_enable_polylang" value="<?php echo $enable_polylang ? '1' : ''; ?>">
					<?php endif; ?>
				</section>

				<?php // ---------------------------------------------------------------- Advanced ?>
				<section class="wcppp-pane" aria-labelledby="wcppp-h-advanced"<?php $wcppp_pane( 'advanced' ); ?>>
					<div class="wcppp-pane-head">
						<h2 id="wcppp-h-advanced"><?php esc_html_e( 'Advanced', 'wc_pay_per_post' ); ?></h2>
						<p><?php esc_html_e( 'Troubleshooting and removal.', 'wc_pay_per_post' ); ?></p>
					</div>

					<div class="wcppp-row">
						<div class="wcppp-row-text">
							<label class="wcppp-row-title" for="<?php echo esc_attr( WC_PPP_SLUG ); ?>_enable_debugging">
								<?php esc_html_e( 'Debug log', 'wc_pay_per_post' ); ?>
								<?php if ( $enable_debugging ) : ?>
									<span class="wcppp-badge is-warn"><?php echo esc_html( __( 'On', 'wc_pay_per_post' ) . ( $debug_log['exists'] ? ' · ' . size_format( $debug_log['size'], 1 ) : '' ) ); ?></span>
								<?php endif; ?>
							</label>
							<p class="wcppp-row-desc" id="wcppp-debug-desc">
								<?php
								echo wp_kses(
									__( 'Records how the paywall decides, on pages opened with <code>?wc-ppp-debug=true</code> added to the address. The log can include customers\' email addresses and orders, so turn this off and delete the log when you are done.', 'wc_pay_per_post' ),
									[ 'code' => [] ]
								);
								?>
							</p>
							<?php if ( $debug_log['exists'] ) : ?>
								<p class="wcppp-row-actions">
									<a class="wcppp-btn" href="<?php echo esc_url( $wcppp_log_url ); ?>"><?php esc_html_e( 'View or delete the log', 'wc_pay_per_post' ); ?></a>
								</p>
							<?php endif; ?>
						</div>
						<?php $wcppp_on( 'enable_debugging', $enable_debugging, 'wcppp-debug-desc' ); ?>
					</div>

					<div class="wcppp-row is-danger">
						<div class="wcppp-row-text">
							<label class="wcppp-row-title" for="<?php echo esc_attr( WC_PPP_SLUG ); ?>_delete_settings"><?php esc_html_e( 'Delete all Pay For Post data when the plugin is deleted', 'wc_pay_per_post' ); ?></label>
							<p class="wcppp-row-desc" id="wcppp-delete-desc">
								<?php esc_html_e( 'Applies when you delete the plugin from the Plugins screen, not when you deactivate it. Removes these settings, every post\'s protection settings, page-view history, cached lists and the debug log, so every protected post becomes free to read. Orders and products are kept.', 'wc_pay_per_post' ); ?>
								<strong><?php esc_html_e( 'This can\'t be undone.', 'wc_pay_per_post' ); ?></strong>
							</p>
						</div>
						<?php $wcppp_on( 'delete_settings', $delete_settings, 'wcppp-delete-desc' ); ?>
					</div>
				</section>
			</div>

			<div class="wcppp-savebar" data-savebar>
				<span class="wcppp-savebar-msg" data-savebar-msg role="status"><?php esc_html_e( 'No unsaved changes', 'wc_pay_per_post' ); ?></span>
				<span class="wcppp-grow"></span>
				<a class="wcppp-btn is-ghost" href="<?php echo esc_url( $wcppp_tab_url( $settings_tab ) ); ?>" data-discard hidden><?php esc_html_e( 'Discard changes', 'wc_pay_per_post' ); ?></a>
				<button type="submit" name="submit" id="submit" class="wcppp-btn is-primary"><?php esc_html_e( 'Save changes', 'wc_pay_per_post' ); ?></button>
			</div>
		</form>
	</div>
</div>
