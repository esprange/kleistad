<?php
/**
 * Toon de plugins settings
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/admin/partials
 */

	$active_tab = ! is_null( filter_input( INPUT_GET, 'tab' ) ) ? filter_input( INPUT_GET, 'tab' ) : 'instellingen';
?>
<div class="wrap">
	<h2 class="nav-tab-wrapper">
	    <a href="?page=kleistad&tab=instellingen" class="nav-tab <?php echo 'instellingen' === $active_tab ? 'nav-tab-active' : ''; ?>">Instellingen</a>
	    <a href="?page=kleistad&tab=google_connect" class="nav-tab <?php echo 'google_connect' === $active_tab ? 'nav-tab-active' : ''; ?>">Google kalender connectie</a>
	    <a href="?page=kleistad&tab=shortcodes" class="nav-tab <?php echo 'shortcodes' === $active_tab ? 'nav-tab-active' : ''; ?>">Shortcodes</a>
	    <a href="?page=kleistad&tab=email_parameters" class="nav-tab <?php echo 'email_parameters' === $active_tab ? 'nav-tab-active' : ''; ?>">Email parameters</a>
	</h2>
	<?php
		do_meta_boxes( $active_tab, 'normal', '' );
	?>
</div>
