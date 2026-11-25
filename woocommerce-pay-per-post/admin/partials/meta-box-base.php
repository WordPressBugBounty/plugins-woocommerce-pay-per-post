<?php

/**
 * The Pay For Post box in the classic editor, and in the block editor when
 * "Panel in the block editor sidebar" is off.
 *
 * One screen: what unlocks the post, the protection type with its settings,
 * and the paywall message. The field names are the ones the box has always
 * posted, apart from protection_type and paywall_message, which replace the
 * three "enable" checkboxes and the implied custom message; see
 * Woocommerce_Pay_Per_Post_Admin::save_meta_box().
 *
 * Premium settings sit inside is__premium_only() blocks, so the free build
 * ships without them and shows each Premium choice locked instead.
 *
 * @var array  $box       Woocommerce_Pay_Per_Post_Admin::meta_box_view().
 * @var string $drop_down The product select.
 */
/** @noinspection PhpUndefinedVariableInspection */
$wcppp_slug = WC_PPP_SLUG;
$wcppp_premium = $box['premium'];
$wcppp_type = $box['type'];
$wcppp_upgrade = $box['upgrade_url'];
$wcppp_premium_badge = ( '' === $wcppp_upgrade ? '<span class="wcppp-mb-premium">' . esc_html__( 'Premium', 'wc_pay_per_post' ) . '</span>' : '<a class="wcppp-mb-premium" href="' . esc_url( $wcppp_upgrade ) . '">' . esc_html__( 'Premium', 'wc_pay_per_post' ) . '</a>' );
$wcppp_premium_more = static function ( string $text ) use($wcppp_upgrade) {
    echo '<p class="wcppp-mb-note">' . esc_html( $text ) . ' ';
    if ( '' !== $wcppp_upgrade ) {
        echo '<a href="' . esc_url( $wcppp_upgrade ) . '">' . esc_html__( 'Available with Premium', 'wc_pay_per_post' ) . '</a>';
    } else {
        echo '<strong>' . esc_html__( 'Available with Premium', 'wc_pay_per_post' ) . '</strong>';
    }
    echo '</p>';
};
$wcppp_types = [
    'standard'  => [Woocommerce_Pay_Per_Post_Helper::protection_type_label( 'standard' ), __( 'Customers who buy keep access for good.', 'wc_pay_per_post' )],
    'delay'     => [Woocommerce_Pay_Per_Post_Helper::protection_type_label( 'delay' ), __( 'Free to everyone for a while after publishing, then paid.', 'wc_pay_per_post' )],
    'page-view' => [Woocommerce_Pay_Per_Post_Helper::protection_type_label( 'page-view' ), __( 'Each purchase allows a set number of views.', 'wc_pay_per_post' )],
    'expire'    => [Woocommerce_Pay_Per_Post_Helper::protection_type_label( 'expire' ), __( 'Access lasts a set time after purchase.', 'wc_pay_per_post' )],
];
$wcppp_has_message = '' !== trim( (string) $restricted_content_override );
$wcppp_protected = !empty( $selected ) && !empty( array_filter( (array) $selected ) );
wp_nonce_field( $wcppp_slug . '_nonce', $wcppp_slug . '_nonce' );
?>
<input type="hidden" name="<?php 
echo esc_attr( $wcppp_slug );
?>_classic_editor" value="1">

<div class="wcppp-mb" data-wcppp-box>

	<div class="wcppp-mb-status">
		<span class="wcppp-mb-badge<?php 
echo ( $wcppp_protected ? ' is-on' : '' );
?>" data-wcppp-badge
			data-on="<?php 
esc_attr_e( 'Protected', 'wc_pay_per_post' );
?>"
			data-off="<?php 
esc_attr_e( 'Not protected', 'wc_pay_per_post' );
?>"><?php 
( $wcppp_protected ? esc_html_e( 'Protected', 'wc_pay_per_post' ) : esc_html_e( 'Not protected', 'wc_pay_per_post' ) );
?></span>
		<span class="wcppp-mb-summary" data-wcppp-summary aria-live="polite"></span>
		<?php 
if ( '' !== $box['preview_url'] ) {
    ?>
			<a href="<?php 
    echo esc_url( $box['preview_url'] );
    ?>" target="_blank"><?php 
    esc_html_e( 'Preview the paywall', 'wc_pay_per_post' );
    ?></a>
			<a href="<?php 
    echo esc_url( $box['check_url'] );
    ?>" target="_blank"><?php 
    esc_html_e( 'Check a customer', 'wc_pay_per_post' );
    ?></a>
		<?php 
}
?>
	</div>

	<div class="wcppp-mb-section">
		<label class="wcppp-mb-label" for="<?php 
echo esc_attr( $wcppp_slug );
?>_product_ids"><?php 
esc_html_e( 'Products that unlock it', 'wc_pay_per_post' );
?></label>
		<p class="wcppp-mb-help" id="wcppp-mb-products-help"><?php 
esc_html_e( 'Any one of them unlocks the post.', 'wc_pay_per_post' );
?></p>
		<?php 
echo $drop_down;
// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
?>
		<p class="wcppp-mb-help"><a href="<?php 
echo esc_url( admin_url( 'post-new.php?post_type=product' ) );
?>"><?php 
esc_html_e( 'Create a product', 'wc_pay_per_post' );
?></a></p>
	</div>

	<fieldset class="wcppp-mb-section">
		<legend class="wcppp-mb-label"><?php 
esc_html_e( 'Protection', 'wc_pay_per_post' );
?></legend>

		<div class="wcppp-mb-types">
			<?php 
foreach ( $wcppp_types as $wcppp_type_key => $wcppp_type_text ) {
    ?>
				<?php 
    $wcppp_locked = 'standard' !== $wcppp_type_key && !$wcppp_premium;
    ?>
				<label class="wcppp-mb-type<?php 
    echo ( $wcppp_locked ? ' is-locked' : '' );
    ?>">
					<span class="wcppp-mb-type-head">
						<input type="radio" name="<?php 
    echo esc_attr( $wcppp_slug );
    ?>_protection_type" value="<?php 
    echo esc_attr( $wcppp_type_key );
    ?>" <?php 
    checked( $wcppp_type, $wcppp_type_key );
    ?> <?php 
    disabled( $wcppp_locked );
    ?>>
						<strong><?php 
    echo esc_html( $wcppp_type_text[0] );
    ?></strong>
						<?php 
    if ( $wcppp_locked ) {
        ?>
							<?php 
        echo $wcppp_premium_badge;
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        ?>
						<?php 
    }
    ?>
					</span>
					<span class="wcppp-mb-type-desc"><?php 
    echo esc_html( $wcppp_type_text[1] );
    ?></span>
				</label>
			<?php 
}
?>
		</div>

		<?php 
if ( !$wcppp_premium ) {
    ?>
			<?php 
    if ( 'standard' !== $box['stored_type'] ) {
        ?>
				<p class="wcppp-mb-paused">
					<?php 
        /* translators: %s: protection type, such as "Limited views" */
        printf( esc_html__( 'This post is set to %s, which is paused while Premium is not active. Until then it is sold as Buy once.', 'wc_pay_per_post' ), '<strong>' . esc_html( $wcppp_types[$box['stored_type']][0] ) . '</strong>' );
        ?>
				</p>
			<?php 
    }
    ?>
			<?php 
    $wcppp_premium_more( __( 'Let everyone read a post free for its first two weeks, sell three views at a time, or sell 48-hour passes.', 'wc_pay_per_post' ) );
    ?>
		<?php 
}
?>

		<?php 
?>
	</fieldset>

	<fieldset class="wcppp-mb-section">
		<legend class="wcppp-mb-label"><?php 
esc_html_e( 'Paywall message', 'wc_pay_per_post' );
?></legend>
		<label class="wcppp-mb-choice">
			<input type="radio" name="<?php 
echo esc_attr( $wcppp_slug );
?>_paywall_message" value="default" <?php 
checked( !$wcppp_has_message );
?>>
			<span><strong><?php 
esc_html_e( 'Your default message', 'wc_pay_per_post' );
?></strong>
				<?php 
printf( 
    /* translators: %s: link to Settings > Paywall */
    esc_html__( 'set in %s', 'wc_pay_per_post' ),
    '<a href="' . esc_url( admin_url( 'admin.php?page=' . WC_PPP_SLUG . '-settings&tab=paywall' ) ) . '">' . esc_html__( 'Settings › Paywall', 'wc_pay_per_post' ) . '</a>'
 );
?>
			</span>
		</label>
		<label class="wcppp-mb-choice<?php 
echo ( $wcppp_premium ? '' : ' is-locked' );
?>">
			<input type="radio" name="<?php 
echo esc_attr( $wcppp_slug );
?>_paywall_message" value="custom" <?php 
checked( $wcppp_has_message );
?> <?php 
disabled( !$wcppp_premium );
?>>
			<span><strong><?php 
esc_html_e( 'A message for this post', 'wc_pay_per_post' );
?></strong>
				<?php 
if ( !$wcppp_premium ) {
    ?>
					<?php 
    echo $wcppp_premium_badge;
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    ?>
				<?php 
}
?>
			</span>
		</label>

		<?php 
if ( !$wcppp_premium && $wcppp_has_message ) {
    ?>
			<?php 
    // The paywall still shows a message written while Premium was active.
    ?>
			<p class="wcppp-mb-paused"><?php 
    esc_html_e( 'This post has its own paywall message, written with Premium, and visitors see it instead of your default message. Premium needs to be active to change it.', 'wc_pay_per_post' );
    ?></p>
		<?php 
}
?>

		<?php 
if ( !$wcppp_premium ) {
    ?>
			<?php 
    $wcppp_premium_more( __( 'Write a different paywall message for each post, with its own offer.', 'wc_pay_per_post' ) );
    ?>
		<?php 
}
?>

		<?php 
?>
	</fieldset>
</div>
