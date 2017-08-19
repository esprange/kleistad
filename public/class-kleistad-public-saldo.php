<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       www.sprako.nl/wordpress/eric
 * @since      4.0.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * @package    Kleistad
 * @subpackage Kleistad/public
 * @author     Eric Sprangers <e.sprangers@sprako.nl>
 */
class Kleistad_Public_Saldo extends Kleistad_Public_Shortcode {

  /**
   * 
   * prepareer 'saldo' form 
   * 
   * @return array
   * 
   * @since   4.0.0
   */
  public function prepare($data = null) {
    $gebruiker_id = get_current_user_id();
    $saldo = number_format((float) get_user_meta($gebruiker_id, 'stooksaldo', true), 2, ',', '');
    return compact('gebruiker_id', 'saldo');
  }

  /**
   * 
   * valideer/sanitize 'saldo' form 
   * 
   * @return array
   * 
   * @since   4.0.0
   */
  public function validate() {

    $gebruiker_id = filter_input(INPUT_POST, 'kleistad_gebruiker_id', FILTER_SANITIZE_NUMBER_INT);
    $via = filter_input(INPUT_POST, 'kleistad_via', FILTER_SANITIZE_STRING);
    $bedrag = filter_input(INPUT_POST, 'kleistad_bedrag', FILTER_SANITIZE_NUMBER_FLOAT);
    $datum = strftime('%d-%m-%Y', strtotime(filter_input(INPUT_POST, 'kleistad_datum', FILTER_SANITIZE_STRING)));

    return compact('gebruiker_id', 'via', 'bedrag', 'datum');
  }

  /**
   * 
   * bewaar 'saldo' form gegevens 
   * 
   * @return string
   * 
   * @since   4.0.0
   */
  public function save($data) {
    $error = new WP_Error();

    extract($data);
    $gebruiker = get_userdata($gebruiker_id);

    $to = "$gebruiker->first_name $gebruiker->last_name <$gebruiker->user_email>";
    if (self::compose_email($to, 'wijziging stooksaldo', 'kleistad_email_saldo_wijziging', ['datum' => $datum, 'via' => $via, 'bedrag' => $bedrag, 'voornaam' => $gebruiker->first_name, 'achternaam' => $gebruiker->last_name,])) {
      $huidig = (float) get_user_meta($gebruiker_id, 'stooksaldo', true);
      $saldo = $bedrag + $huidig;
      update_user_meta($gebruiker->ID, 'stooksaldo', $saldo);
      Kleistad_Oven::log_saldo("wijziging saldo $gebruiker->display_name van $huidig naar $saldo, betaling per $via.");
      return "Het saldo is bijgewerkt naar &euro; $saldo en een email is verzonden.";
    } else {
      $error->add('', 'Er is een fout opgetreden want de email kon niet verzonden worden');
      return $error;
    }
  }

}
