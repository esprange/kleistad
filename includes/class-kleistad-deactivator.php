<?php

/**
 * Fired during plugin deactivation
 *
 * @link       www.sprako.nl/wordpress/eric
 * @since      4.0.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      4.0.0
 * @package    Kleistad
 * @subpackage Kleistad/includes
 * @author     Eric Sprangers <e.sprangers@sprako.nl>
 */

require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-kleistad-roles.php';

class Kleistad_Deactivator {

  /**
   * Short Description. (use period)
   *
   * Long Description.
   *
   * @since    4.0.0
   */
  public static function deactivate() {
    wp_clear_scheduled_hook('kleistad_kosten');

    global $wp_roles;
    /*
     * de rollen verwijderen bij deactivering van de plugin. Bij aanpassing rollen (zie activate) het onderstaande ook aanpassen.
     */
    $wp_roles->remove_cap('administrator', Kleistad_Roles::OVERRIDE);
    $wp_roles->remove_cap('editor', Kleistad_Roles::OVERRIDE);
    $wp_roles->remove_cap('author', Kleistad_Roles::OVERRIDE);

    $wp_roles->remove_cap('administrator', Kleistad_Roles::RESERVEER);
    $wp_roles->remove_cap('editor', Kleistad_Roles::RESERVEER);
    $wp_roles->remove_cap('author', Kleistad_Roles::RESERVEER);
    $wp_roles->remove_cap('contributor', Kleistad_Roles::RESERVEER);
    $wp_roles->remove_cap('subscriber', Kleistad_Roles::RESERVEER);
  }

}
