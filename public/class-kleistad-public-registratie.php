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
class Kleistad_Public_Registratie extends Kleistad_Public_Shortcode {

  /**
   * 
   * prepareer 'registratie' form
   * 
   * @return array
   * 
   * @since   4.0.0
   */
  public function prepare($data = null) {
    $gebruiker_id = get_current_user_id();
    $gebruiker = new Kleistad_Gebruiker($gebruiker_id);

    if (is_null($data)) {
      $input = [
          'voornaam' => $gebruiker->voornaam,
          'achternaam' => $gebruiker->achternaam,
          'straat' => $gebruiker->straat,
          'huisnr' => $gebruiker->huisnr,
          'pcode' => $gebruiker->pcode,
          'plaats' => $gebruiker->plaats,
          'telnr' => $gebruiker->telnr,
      ];
    } else {
      extract($data);
    }
    return compact('input');
  }

  /**
   * 
   * valideer/sanitize 'registratie' form 
   * 
   * @return array
   * 
   * @since   4.0.0
   */
  public function validate() {
    $error = new WP_Error();

    $input = filter_input_array(INPUT_POST, [
        'gebruiker_id' => FILTER_SANITIZE_NUMBER_INT,
        'voornaam' => FILTER_SANITIZE_STRING,
        'achternaam' => FILTER_SANITIZE_STRING,
        'straat' => FILTER_SANITIZE_STRING,
        'huisnr' => FILTER_SANITIZE_STRING,
        'pcode' => FILTER_SANITIZE_STRING,
        'plaats' => FILTER_SANITIZE_STRING,
        'telnr' => FILTER_SANITIZE_STRING,
    ]);

    $input['pcode'] = strtoupper($input['pcode']);
    if (!$input['voornaam']) {
      $error->add('verplicht', 'Een voornaam is verplicht');
    }
    if (!$input['achternaam']) {
      $error->add('verplicht', 'Een achternaam is verplicht');
    }
    $err = $error->get_error_codes();
    if (!empty($err)) {
      return $error;
    }

    return compact('input');
  }

  /**
   * 
   * bewaar 'registratie' form gegevens 
   * 
   * @return string
   * 
   * @since   4.0.0
   */
  public function save($data) {
    extract($data);
    $error = new WP_Error();

    if (!is_user_logged_in()) {
      $error->add('security', 'Dit formulier mag alleen ingevuld worden door ingelogde gebruikers');
      return $error;
    } else {
      $gebruiker = new Kleistad_Gebruiker($input['gebruiker_id']);
      $gebruiker->voornaam = $input['voornaam'];
      $gebruiker->achternaam = $input['achternaam'];
      $gebruiker->straat = $input['straat'];
      $gebruiker->huisnr = $input['huisnr'];
      $gebruiker->pcode = $input['pcode'];
      $gebruiker->plaats = $input['plaats'];
      $gebruiker->telnr = $input['telnr'];
      if ($gebruiker->save()) {
        return 'De wijzigingen zijn verwerkt';
      } else {
        $error->add('security', 'De wijzigingen konden niet worden verwerkt');
        return $error;
      }
    }
  }
}
