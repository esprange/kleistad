<?php

/**
 * The public-facing functionality of the plugin for shortcode cursus_beheer.
 *
 * @link       www.sprako.nl/wordpress/eric
 * @since      4.0.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/public
 */

/**
 * The public-facing functionality of the plugin for shortcode cursus_beheer.
 *
 * @package    Kleistad
 * @subpackage Kleistad/public
 * @author     Eric Sprangers <e.sprangers@sprako.nl>
 */
class Kleistad_Public_CursusBeheer extends Kleistad_Public_Shortcode {

  /**
   * 
   * prepareer 'cursus_beheer' form
   * 
   * @return array
   * 
   * @since   4.0.0
   */
  public function prepare($data = null) {
    $cursusStore = new Kleistad_Cursussen();
    $cursussen = $cursusStore->get();

    $inschrijvingStore = new Kleistad_Inschrijvingen();
    $inschrijvingen = $inschrijvingStore->get();

    $gebruikerStore = new Kleistad_Gebruikers();
    $gebruikers = $gebruikerStore->get();
    $rows = [];

    foreach ($cursussen as $cursus_id => $cursus) {
      $wachtlijst = [];
      $ingedeeld = [];

      foreach ($inschrijvingen as $cursist_id => $inschrijving) {

        if (array_key_exists($cursus_id, $inschrijving)) {
          $element = [
              'naam' => $gebruikers[$cursist_id]->voornaam . ' ' . $gebruikers[$cursist_id]->achternaam,
              'opmerking' => $inschrijving[$cursus_id]->opmerking,
              'technieken' => $inschrijving[$cursus_id]->technieken,
              'ingedeeld' => $inschrijving[$cursus_id]->ingedeeld,
              'id' => $cursist_id,
          ];
          if ($inschrijving[$cursus->id]->ingedeeld) {
            $ingedeeld[$cursist_id] = $element;
          } elseif ($inschrijving[$cursus_id]->i_betaald) {
            $wachtlijst[$cursist_id] = $element;
          }
        }
      }
      $rows[] = ['cursus' => $cursus, 'wachtlijst' => $wachtlijst, 'ingedeeld' => $ingedeeld,];
    }

    return compact('rows', 'gebruikers');
  }

  /**
   * 
   * valideer/sanitize 'cursus_beheer' form 
   * 
   * @return array
   * 
   * @since   4.0.0
   */
  public function validate() {

    $tab = filter_input(INPUT_POST, 'tab', FILTER_SANITIZE_STRING);
    if ($tab == 'info') {
      $input = filter_input_array(INPUT_POST, [
          'tab' => FILTER_SANITIZE_STRING,
          'cursus_id' => FILTER_SANITIZE_NUMBER_INT,
          'naam' => FILTER_SANITIZE_STRING,
          'docent' => FILTER_SANITIZE_STRING,
          'start_datum' => FILTER_SANITIZE_STRING,
          'eind_datum' => FILTER_SANITIZE_STRING,
          'start_tijd' => FILTER_SANITIZE_STRING,
          'eind_tijd' => FILTER_SANITIZE_STRING,
          'techniekkeuze' => FILTER_SANITIZE_STRING,
          'vol' => FILTER_SANITIZE_STRING,
          'vervallen' => FILTER_SANITIZE_STRING,
          'inschrijfkosten' => ['filter' => FILTER_SANITIZE_NUMBER_FLOAT, 'flags' => FILTER_FLAG_ALLOW_FRACTION],
          'cursuskosten' => ['filter' => FILTER_SANITIZE_NUMBER_FLOAT, 'flags' => FILTER_FLAG_ALLOW_FRACTION],
          'inschrijfslug' => FILTER_SANITIZE_STRING,
          'indelingslug' => FILTER_SANITIZE_STRING,
          'technieken' => ['filter' => FILTER_SANITIZE_STRING, 'flags' => FILTER_REQUIRE_ARRAY],
      ]);
    } elseif ($tab == 'indeling') {
      $input = filter_input_array(INPUT_POST, [
          'tab' => FILTER_SANITIZE_STRING,
          'cursus_id' => FILTER_SANITIZE_NUMBER_INT,
          'indeling_lijst' => FILTER_SANITIZE_STRING,
      ]);
      $input['cursisten'] = ($input['indeling_lijst'] == '') ? [] : json_decode($input['indeling_lijst'], true);
    }
    return compact('input');
  }

  /**
   * 
   * bewaar 'cursus_beheer' form gegevens 
   * 
   * @return string
   * 
   * @since   4.0.0
   */
  public function save($data) {
    extract($data);

    $cursus_id = $input['cursus_id'];

    if ($cursus_id > 0) {
      $cursus = new Kleistad_Cursus($cursus_id);
    } else {
      $cursus = new Kleistad_Cursus();
    }
    
    if ($input['tab'] == 'info') {
      $cursus->naam = $input['naam'];
      $cursus->docent = $input['docent'];
      $cursus->start_datum = strtotime($input['start_datum']);
      $cursus->eind_datum = strtotime($input['eind_datum']);
      $cursus->start_tijd = strtotime($input['start_tijd']);
      $cursus->eind_tijd = strtotime($input['eind_tijd']);
      $cursus->techniekkeuze = $input['techniekkeuze'] <> '';
      $cursus->vol = $input['vol'] <> '';
      $cursus->vervallen = $input['vervallen'] <> '';
      $cursus->inschrijfkosten = $input['inschrijfkosten'];
      $cursus->cursuskosten = $input['cursuskosten'];
      $cursus->inschrijfslug = $input['inschrijfslug'];
      $cursus->indelingslug = $input['indelingslug'];
      $cursus->technieken = $input['technieken'];
      $cursus->save();
    } elseif ($input['tab'] == 'indeling') {
      foreach ($input['cursisten'] as $cursist) {
        $inschrijving = new Kleistad_Inschrijving($cursist, $cursus_id);
        if (!$inschrijving->ingedeeld) {
          $inschrijving->ingedeeld = true;
          $inschrijving->save();

          $gebruiker = get_userdata($cursist);
          $technieken = $inschrijving->technieken;
          $to = "$gebruiker->first_name $gebruiker->last_name <$gebruiker->user_email>";
          self::compose_email($to, 'inschrijving cursus', $cursus->indelingslug, [
              'voornaam' => $gebruiker->first_name,
              'achternaam' => $gebruiker->last_name,
              'cursus_naam' => $cursus->naam,
              'cursus_docent' => $cursus->docent,
              'cursus_start_datum' => strftime('%A %d-%m-%y', $cursus->start_datum),
              'cursus_eind_datum' => strftime('%A %d-%m-%y', $cursus->eind_datum),
              'cursus_start_tijd' => strftime('%H:%M', $cursus->start_tijd),
              'cursus_eind_tijd' => strftime('%H:%M', $cursus->eind_tijd),
              'cursus_technieken' => is_array($technieken) ? implode(', ', $technieken) : '',
              'cursus_code' => $inschrijving->code,
              'cursus_kosten' => number_format($cursus->cursuskosten, 2, ',', ''),
              'cursus_inschrijfkosten' => number_format($cursus->inschrijfkosten, 2, ',', ''),
          ]);
        }
      }
    }
  }

}
