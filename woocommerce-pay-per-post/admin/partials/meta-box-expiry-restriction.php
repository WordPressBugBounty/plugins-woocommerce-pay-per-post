<?php

/** @noinspection PhpUndefinedVariableInspection */
?>

<?php 
if ( wcppp_freemius()->is_not_paying() && !wcppp_freemius()->is_trial() ) {
    ?>
    <a href="<?php 
    echo wcppp_freemius()->get_upgrade_url();
    ?>" title="upgrade today"><img alt="Expiry" src="<?php 
    echo plugin_dir_url( __DIR__ ) . 'img/expiry-protection.png';
    ?>" width="100%"></a>
<?php 
}