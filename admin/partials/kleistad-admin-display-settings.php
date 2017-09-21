<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       www.sprako.nl/wordpress/eric
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/admin/partials
 */

	$active_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'instellingen';
?>
<div class="wrap">
	<h2 class="nav-tab-wrapper">
	    <a href="?page=kleistad&tab=instellingen" class="nav-tab <?php echo 'instellingen' == $active_tab ? 'nav-tab-active' : ''; ?>">Instellingen</a>
	    <a href="?page=kleistad&tab=shortcodes" class="nav-tab <?php echo 'shortcodes' == $active_tab ? 'nav-tab-active' : ''; ?>">Shortcodes</a>
	    <a href="?page=kleistad&tab=email_parameters" class="nav-tab <?php echo 'email_parameters' == $active_tab ? 'nav-tab-active' : ''; ?>">Email parameters</a>
	</h2>
	<?php
		do_meta_boxes( $active_tab, 'normal', '' );
	?>
</div>
