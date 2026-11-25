<?php

/**
 * The Pay For Post box in the block editor while the sidebar panel is on.
 *
 * It shows nothing: the panel is where the settings are. It still has to
 * exist, because after saving the post the block editor posts every meta box
 * to post.php?meta-box-loader=1, and these fields are how the panel's changes
 * reach the server. See Woocommerce_Pay_Per_Post_Admin::edit_post_block_editor().
 *
 * @var string $restricted_content_override The post's own paywall message.
 */
/** @noinspection PhpUndefinedVariableInspection */
?>
<style>#<?php 
echo esc_attr( WC_PPP_SLUG );
?>_meta_box{display:none}</style>

<?php 
wp_nonce_field( WC_PPP_SLUG . '_nonce', WC_PPP_SLUG . '_nonce' );
?>

<?php 
// Filled by the Pay For Post sidebar when one of its settings changes.
// See Woocommerce_Pay_Per_Post_Admin::edit_post_block_editor().
?>
<input type="hidden"
	id="<?php 
echo esc_attr( WC_PPP_SLUG . '_document_settings' );
?>"
	name="<?php 
echo esc_attr( WC_PPP_SLUG . '_document_settings' );
?>"
	value="">

