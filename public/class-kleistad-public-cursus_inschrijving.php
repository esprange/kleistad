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
class Kleistad_Public_CursusInschrijving extends Kleistad_Public_Shortcode {

  /**
   * 
   * prepareer 'cursus_inschrijving' form
   * 
   * @return array
   * 
   * @since   4.0.0
   */
  public function prepare($data = null) {

    if (is_null($data)) {
      $input = [
          'emailadres' => '',
          'voornaam' => '',
          'achternaam' => '',
          'straat' => '',
          'huisnr' => '',
          'pcode' => '',
          'plaats' => '',
          'telnr' => '',
          'cursus_id' => '',
          'opmerking' => '',
      ];
    } else {
      extract($data);
    }
    $gebruikers = get_users(['fields' => ['id', 'display_name'], 'orderby' => ['nicename']]);
    $open_cursussen = [];
    
    $cursusStore = new Kleistad_Cursussen();
    $cursussen = $cursusStore->get();
    foreach ($cursussen as $cursus) {

      if ($cursus->eind_datum < time()) {
        continue; 
      }
      $open_cursussen[$cursus->id] = ['naam' => $cursus->naam .
          ', start ' . strftime('%A %d-%m-%y', $cursus->start_datum) .
          ' vanaf ' . strftime('%H:%M', $cursus->start_tijd) .
          ($cursus->vervallen ? ': vervallen' : ($cursus->vol ? ': vol' : '')),
          'vol' => $cursus->vol,
          'vervallen' => $cursus->vervallen,
          'technieken' => $cursus->technieken,
      ];
    }
    return compact('gebruikers', 'input', 'open_cursussen');
  }

  /**
   * 
   * valideer/sanitize 'cursus_inschrijving' form 
   * 
   * @return array
   * 
   * @since   4.0.0
   */
  public function validate() {
    $error = new WP_Error();

    $input = filter_input_array(INPUT_POST, [
        'emailadres' => FILTER_SANITIZE_EMAIL,
        'voornaam' => FILTER_SANITIZE_STRING,
        'achternaam' => FILTER_SANITIZE_STRING,
        'straat' => FILTER_SANITIZE_STRING,
        'huisnr' => FILTER_SANITIZE_STRING,
        'pcode' => FILTER_SANITIZE_STRING,
        'plaats' => FILTER_SANITIZE_STRING,
        'telnr' => FILTER_SANITIZE_STRING,
        'cursus_id' => FILTER_SANITIZE_NUMBER_INT,
        'gebruiker_id' => FILTER_SANITIZE_NUMBER_INT,
        'technieken' => ['filter' => FILTER_SANITIZE_STRING, 'flags' => FILTER_FORCE_ARRAY],
        'opmerking' => FILTER_SANITIZE_STRING,]);

    if (intval($input['cursus_id']) == 0) {
      $error->add('verplicht', 'Er is nog geen cursus gekozen');
    }
    $cursus = new Kleistad_Cursus($input['cursus_id']);
    if (is_null($cursus->id)) {
      $error->add('onbekend', 'De gekozen cursus is niet bekend');
    }
    if (intval($input['gebruiker_id']) == 0) {
      $input['emailadres'] = strtolower($input['emailadres']);
      if (!filter_var($input['emailadres'], FILTER_VALIDATE_EMAIL)) {
        $error->add('verplicht', 'Een geldig E-mail adres is verplicht');
      }
      $input['pcode'] = strtoupper($input['pcode']);
      if (!$input['voornaam']) {
        $error->add('verplicht', 'Een voornaam is verplicht');
      }
      if (!$input['achternaam']) {
        $error->add('verplicht', 'Een achternaam is verplicht');
      }
    }
    $err = $error->get_error_codes();
    if (!empty($err)) {
      return $error;
    }

    return compact('input', 'cursus');
  }

  /**
   * 
   * bewaar 'cursus_inschrijving' form gegevens 
   * 
   * @return string
   * 
   * @since   4.0.0
   */
  public function save($data) {
    extract($data);
    $error = new WP_Error();

    if (!is_user_logged_in()) {
      $gebruiker = new Kleistad_Gebruiker();
      $gebruiker->voornaam = $input['voornaam'];
      $gebruiker->achternaam = $input['achternaam'];
      $gebruiker->straat = $input['straat'];
      $gebruiker->huisnr = $input['huisnr'];
      $gebruiker->pcode = $input['pcode'];
      $gebruiker->plaats = $input['plaats'];
      $gebruiker->email = $input['emailadres'];
      $gebruiker->telnr = $input['telnr'];
      $gebruiker_id = $gebruiker->save();
    } else {
      if (is_super_admin()) {
        $gebruiker_id = $input['gebruiker_id'];
      } else {
        $gebruiker_id = get_current_user_id();
      }
      $gebruiker = new Kleistad_Gebruiker($gebruiker_id);
    }

    $inschrijving = new Kleistad_Inschrijving ($gebruiker_id, $cursus->id);
    $inschrijving->technieken = $input['technieken'];
    $inschrijving->opmerking = $input['opmerking'];
    $inschrijving->datum = time();
    $inschrijving->save();
    if (is_super_admin()) {
      return 'De inschrijving is verwerkt';
    }
    $to = "$gebruiker->voornaam $gebruiker->achternaam <$gebruiker->email>";
    if (self::compose_email($to, 'inschrijving cursus', $cursus->inschrijfslug, [
                'voornaam' => $gebruiker->voornaam,
                'achternaam' => $gebruiker->achternaam,
                'cursus_naam' => $cursus->naam,
                'cursus_docent' => $cursus->docent,
                'cursus_start_datum' => strftime('%A %d-%m-%y', $cursus->start_datum),
                'cursus_eind_datum' => strftime('%A %d-%m-%y', $cursus->eind_datum),
                'cursus_start_tijd' => strftime('%H:%M', $cursus->start_tijd),
                'cursus_eind_tijd' => strftime('%H:%M', $cursus->eind_tijd),
                'cursus_technieken' => implode(', ', $inschrijving->technieken),
                'cursus_opmerking' => $inschrijving->opmerking,
                'cursus_code' => $inschrijving->code,
                'cursus_kosten' => $cursus->cursuskosten,
                'cursus_inschrijfkosten' => $cursus->inschrijfkosten,
            ])) {
      return 'De inschrijving is verwerkt en er is een email verzonden met bevestiging';
    } else {
      $error->add('', 'De inschrijving is verwerkt maar een bevestigings email kon niet worden verzonden');
      return $error;
    }
  }

}
