<?php

/** @noinspection PhpUndefinedVariableInspection */
?>

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

<div id="wc-ppp-product-info">
	<div id="ppp-product" style="display: none;">
		<?php 
?>
	</div>
</div>
