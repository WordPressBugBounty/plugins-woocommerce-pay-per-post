<?php

/** @noinspection PhpUndefinedVariableInspection */
?>
<div class="wc-ppp-purchased-container">
	<?php 
if ( count( $purchased ) > 0 ) {
    /** @noinspection PhpUndefinedFunctionInspection */
    $current_language = pll_current_language();
    ?>
        <ul id="what-ever-you-want">
			<?php 
    foreach ( $purchased as $post ) {
        /** @noinspection PhpUndefinedFunctionInspection */
        $languages = pll_get_post_translations( $post->ID );
        ?>
                <li>
                    <strong><a href="<?php 
        echo esc_url( get_permalink( $languages[$current_language] ) );
        ?>">[<?php 
        echo $current_language;
        ?>] <?php 
        echo esc_html( get_the_title( $languages[$current_language] ) );
        ?></a></strong><br>
                    <?php 
        if ( $languages > 1 ) {
            ?>
                    <?php 
            apply_filters( 'wc_pay_per_post_languages_title', esc_html__( 'Additional Languages', 'wc_pay_per_post' ) );
            ?>
                    <ul>
	                    <?php 
            foreach ( $languages as $language => $post_id ) {
                if ( $language === $current_language ) {
                    continue;
                }
                ?>
                            <li><a href="<?php 
                echo esc_url( get_permalink( $post_id ) );
                ?>">[<?php 
                echo $language;
                ?>] <?php 
                echo esc_html( get_the_title( $post_id ) );
                ?></a></li>
	                    <?php 
            }
            ?>
                    </ul>
                    <?php 
        }
        ?>
                </li>
			<?php 
    }
    ?>
        </ul>
	<?php 
} else {
    ?>
        <p><?php 
    /** @noinspection PhpVoidFunctionResultUsedInspection */
    apply_filters( 'wc_pay_per_post_shortcode_purchased_no_posts', _e( 'You have not purchased any protected posts.', 'wc_pay_per_post' ) );
    ?></p>
	<?php 
}
?>
</div>