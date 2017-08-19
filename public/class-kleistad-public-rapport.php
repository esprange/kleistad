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
class Kleistad_Public_Rapport extends Kleistad_Public_Shortcode {

  /**
   * 
   * prepareer 'rapport' form inhoud
   * 
   * @return array
   * 
   * @since   4.0.0
   */
  public function prepare($data = null) {
    $huidige_gebruiker = wp_get_current_user();
    $naam = $huidige_gebruiker->display_name;
    $saldo = number_format((float) get_user_meta($huidige_gebruiker->ID, 'stooksaldo', true), 2, ',', '');
    $items = [];

    $ovenStore = new Kleistad_Ovens();
    $ovens = $ovenStore->get();
    $reserveringStore = new Kleistad_Reserveringen();
    $reserveringen = $reserveringStore->get();
    $regelingStore = new Kleistad_Regelingen();

    foreach ($reserveringen as $reservering) {
      foreach ($reservering->verdeling as $stookdeel) {
        if ($stookdeel['id'] == $huidige_gebruiker->ID) {
          // als er een speciale regeling / tarief is afgesproken, dan geldt dat tarief
          $regeling = $regelingStore->get($huidige_gebruiker->ID, $reservering->oven_id);
          $kosten = number_format(round($stookdeel['perc'] / 100 * ( ( is_null($regeling)) ? $ovens[$reservering->oven_id]->kosten : $regeling ), 2), 2, ',', '');
          $stoker = get_userdata($reservering->gebruiker_id);
          $items[] = ['datum' => $reservering->dag . '-' . $reservering->maand . '-' . $reservering->jaar,
              'sdatum' => $reservering->datum,
              'oven' => $ovens[$reservering->oven_id]->naam,
              'stoker' => !$stoker ? 'onbekend' : $stoker->display_name,
              'stook' => $reservering->soortstook,
              'temp' => $reservering->temperatuur,
              'prog' => $reservering->programma,
              'perc' => $stookdeel['perc'],
              'kosten' => $kosten,
              'voorlopig' => $reservering->verwerkt ? '' : '<span class="genericon genericon-checkmark"></span>',
          ];
        }
      }
    }
    return compact('naam', 'saldo', 'items');
  }

}
