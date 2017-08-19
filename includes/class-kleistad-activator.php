<?php

/**
 * Fired during plugin activation
 *
 * @link       www.sprako.nl/wordpress/eric
 * @since      4.0.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */
/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      4.0.0
 * @package    Kleistad
 * @subpackage Kleistad/includes
 * @author     Eric Sprangers <e.sprangers@sprako.nl>
 */
require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-kleistad-roles.php';

class Kleistad_Activator {

  /**
   * Plugin-database-versie
   */
  const DBVERSIE = 4;

  /**
   * Short Description. (use period)
   *
   * Long Description.
   *
   * @since    4.0.0
   */
  public static function activate() {
    $default_options = [
        'onbeperkt_abonnement' => 50,
        'beperkt_abonnement' => 30,
        'dagdelenkaart' => 60,
        'cursusprijs' => 130,
        'cursusinschrijfprijs' => 25,
        'workshopprijs' => 110,
        'kinderworkshopprijs' => 110,
        'termijn' => 4,
    ];
    $options = shortcode_atts( $default_options, get_option('kleistad-opties') );
    update_option('kleistad-opties', $options);

    $database_version = intval(get_option('kleistad-database-versie', 0));
    if ($database_version < self::DBVERSIE) {
      global $wpdb;
      $charset_collate = $wpdb->get_charset_collate();

      flush_rewrite_rules();
      require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
      dbDelta("CREATE TABLE {$wpdb->prefix}kleistad_reserveringen (
                id int(10) NOT NULL AUTO_INCREMENT,
                oven_id smallint(4) NOT NULL,
                jaar smallint(4) NOT NULL,
                maand tinyint(2) NOT NULL,
                dag tinyint(1) NOT NULL,
                gebruiker_id int(10) NOT NULL,
                temperatuur int(10),
                soortstook tinytext,
                programma smallint(4),
                gemeld tinyint(1) DEFAULT 0,
                verwerkt tinyint(1) DEFAULT 0,
                verdeling tinytext,
                opmerking tinytext,
                PRIMARY KEY  (id)
                ) $charset_collate;"
      );

      dbDelta("CREATE TABLE {$wpdb->prefix}kleistad_ovens (
                id int(10) NOT NULL AUTO_INCREMENT,
                naam tinytext,
                kosten numeric(10,2),
                beschikbaarheid tinytext,
                PRIMARY KEY  (id)
                ) $charset_collate;"
      );

      dbDelta("CREATE TABLE {$wpdb->prefix}kleistad_cursussen (
                id int(10) NOT NULL AUTO_INCREMENT, 
                naam tinytext,
                start_datum date,
                eind_datum date,
                start_tijd time,
                eind_tijd time,
                docent tinytext,
                technieken tinytext,
                vervallen tinyint(1) DEFAULT 0,
                vol tinyint(1) DEFAULT 0,
                techniekkeuze tinyint(1) DEFAULT 0,
                inschrijfkosten numeric(10,2),
                cursuskosten numeric(10,2),
                inschrijfslug tinytext,
                indelingslug tinytext,
                PRIMARY KEY  (id)
              ) $charset_collate;"
      );
      update_option('kleistad-database-versie', self::DBVERSIE);
    }

    if (!wp_next_scheduled('kleistad_kosten')) {
      wp_schedule_event(strtotime('midnight'), 'daily', 'kleistad_kosten');
    }

    /*
     * n.b. in principe heeft de (toekomstige) rol bestuurde de override capability en de (toekomstige) rol lid de reserve capability
     * zolang die rollen nog niet gedefinieerd zijn hanteren we de onderstaande toekenning
     */
    global $wp_roles;

    $wp_roles->add_cap('administrator', Kleistad_Roles::OVERRIDE);
    $wp_roles->add_cap('editor', Kleistad_Roles::OVERRIDE);
    $wp_roles->add_cap('author', Kleistad_Roles::OVERRIDE);

    $wp_roles->add_cap('administrator', Kleistad_Roles::RESERVEER);
    $wp_roles->add_cap('editor', Kleistad_Roles::RESERVEER);
    $wp_roles->add_cap('author', Kleistad_Roles::RESERVEER);
    $wp_roles->add_cap('contributor', Kleistad_Roles::RESERVEER);
    $wp_roles->add_cap('subscriber', Kleistad_Roles::RESERVEER);
  }

}
