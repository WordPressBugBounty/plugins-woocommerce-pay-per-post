<?php

/** @noinspection PhpUndefinedVariableInspection */
?>

<?php 
if ( wcppp_freemius()->is_not_paying() && !wcppp_freemius()->is_trial() ) {
    ?>
    <a href="<?php 
    echo wcppp_freemius()->get_upgrade_url();
    ?>" title="upgrade today"><img alt="Delay Restriction" src="<?php 
    echo plugin_dir_url( __DIR__ ) . 'img/delay-restriction.png';
    ?>" width="100%"></a>
<?php 
}