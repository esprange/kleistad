<?php

/**
 * The file that defines the oven class
 *
 * A class definition including the ovens, reserveringen and regelingen
 *
 * @link       www.sprako.nl/wordpress/eric
 * @since      4.0.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

/**
 * Kleistad Oven class.
 *
 * A class definition that define the attributes of a single oven class.
 *
 * @since 4.0.0
 *
 * @see n.a.
 * @link URL
 */
class Kleistad_Oven extends Kleistad_Entity {

  /**
   * Constructor
   *
   * Constructor, Long description.
   *
   * @since 4.0.0
   *
   * @param  int $oven_id (optional) oven to load.
   * @global  object $wpdb Wordpress database.
   * @return null.
   */
  public function __construct($oven_id = null) {
    global $wpdb;
    $default_data = [
        'id' => null,
        'naam' => '',
        'kosten' => 0,
        'beschikbaarheid' => [],
    ];
    if (is_null($oven_id)) {
      $this->_data = $default_data;
    } else {
      $this->_data = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}kleistad_ovens WHERE id=$oven_id", ARRAY_A);
    }
  }

  /**
   * Getter, using the magic function
   *
   * get attribuut from the object.
   *
   * @since 4.0.0
   *
   * @param  string $attribuut Attribuut name.
   * @return mixed Attribute value.
   */
  public function __get($attribuut) {
    switch ($attribuut) {
      case 'beschikbaarheid':
        return json_decode($this->_data[$attribuut], true);
      case 'zondag':
      case 'maandag':
      case 'dinsdag':
      case 'woensdag':
      case 'donderdag':
      case 'vrijdag':
      case 'zaterdag':
        return (array_search($attribuut, json_decode($this->_data['beschikbaarheid'],true)) !== false);
      default:
        return $this->_data[$attribuut];
    }
  }

  /**
   * Setter, using the magic function
   *
   * Set attribuut from the object.
   *
   * @since 4.0.0
   *
   * @param  string $attribuut Attribuut name.
   * @param  mixed $waarde Attribuut value.
   * @return null.
   */
  public function __set($attribuut, $waarde) {
    switch ($attribuut) {
      case 'beschikbaarheid':
        $this->_data[$attribuut] = json_encode($waarde);
        break;
      default:
        $this->_data[$attribuut] = $waarde;
    }
  }

  /**
   * Save the data
   *
   * Saves the data to the database.
   *
   * @since 4.0.0
   *
   * @global  object $wpdb Wordpress database.
   * @return int The id of the oven.
   */
  public function save() {
    global $wpdb;
    $wpdb->replace("{$wpdb->prefix}kleistad_ovens", $this->_data);
    return $wpdb->insert_id;
  }

  /**
   * help functie, log de tekstregel naar de saldo log
   * @param string $tekstregel
   */
  static public function log_saldo($tekstregel) {
    $upload_dir = wp_upload_dir();
    $transactie_log = $upload_dir['basedir'] . '/stooksaldo.log';
    $f = fopen($transactie_log, 'a');
    $timestamp = date('c');
    fwrite($f, $timestamp . ': ' . $tekstregel . "\n");
    fclose($f);
  }

}

/**
   * Collection of Oven
   *
   * Collection of Oven, loaded from the database.
   *
   * @since 4.0.0
   *
   * @see class Kleistad_Oven
   * @link URL
    */
class Kleistad_Ovens extends Kleistad_EntityStore {

  /**
   * Constructor
   *
   * Loads the data from the database.
   *
   * @since 4.0.0
   *
   * @global  object $wpdb Wordpress database.
   * @return null.
   */
  public function __construct() {
    global $wpdb;
    $ovens = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}kleistad_ovens", ARRAY_A);
    foreach ($ovens as $oven) {
      $this->_data[$oven['id']] = new Kleistad_Oven();
      $this->_data[$oven['id']]->load($oven);
    }
  }

}

/**
 * Kleistad Reservering class.
 *
 * A class definition that define the attributes of a single reservering class.
 *
 * @since 4.0.0
 *
 * @see n.a.
 * @link URL
 */
class Kleistad_Reservering extends Kleistad_Entity {

  /**
   * Constructor
   *
   * Constructor, Long description.
   *
   * @since 4.0.0
   *
   * @param  int $id (optional) reservering to load.
   * @return null.
   */
  public function __construct($oven_id) {
    $default_data = [
        'id' => null,
        'oven_id' => $oven_id,
        'jaar' => 0,
        'maand' => 0,
        'dag' => 0,
        'gebruiker_id' => 0,
        'temperatuur' => 0,
        'soortstook' => '',
        'programma' => 0,
        'gemeld' => 0,
        'verwerkt' => 0,
        'verdeling' => '',
        'opmerking' => '',
    ];
    $this->_data = $default_data;
  }

  /**
   * vind de reservering
   * 
   * @global object $wpdb wp database
   * @param int $jaar
   * @param int $maand
   * @param int $dag
   * @return boolean
   */
  public function find($jaar, $maand, $dag) {
    global $wpdb;

    $resultaat = $wpdb->get_row("SELECT * FROM  {$wpdb->prefix}kleistad_reserveringen WHERE oven_id='{$this->_data['oven_id']}' AND
      jaar='$jaar' AND maand='$maand' AND dag='$dag'", ARRAY_A);
    if ($resultaat) {
      $this->_data = $resultaat;
      return true;
    }
    return false;
  }

  /**
   * delete the current object
   * 
   * @global object $wpdb
   * @return type
   */
  public function delete() {
    global $wpdb;
    if ($wpdb->delete("{$wpdb->prefix}kleistad_reserveringen", ['id' => $this->_data['id']], ['%d'])) {
      $this->_data['id'] = null;
    }
  }

  /**
   * Getter, using the magic function
   *
   * get attribuut from the object.
   *
   * @since 4.0.0
   *
   * @param  string $attribuut Attribuut name.
   * @return mixed Attribute value.
   */
  public function __get($attribuut) {
    switch ($attribuut) {
      case 'verdeling':
        $verdeling = json_decode($this->_data['verdeling'], true);
        if (is_array($verdeling)) {
          return $verdeling;
        } else {
          return [['id' => $this->_data['gebruiker_id'], 'perc' => 100],
              ['id' => 0, 'perc' => 0], ['id' => 0, 'perc' => 0], ['id' => 0, 'perc' => 0], ['id' => 0, 'perc' => 0],];
        }
      case 'datum':
        return strtotime($this->_data['jaar'] . '-' . $this->_data['maand'] . '-' . $this->_data['dag']);
      case 'gemeld':
      case 'verwerkt':
        return $this->_data[$attribuut] == 1;
      default:
        return $this->_data[$attribuut];
    }
  }

  /**
   * Setter, using the magic function
   *
   * Set attribuut from the object.
   *
   * @since 4.0.0
   *
   * @param  string $attribuut Attribuut name.
   * @param  mixed $waarde Attribuut value.
   * @return null.
   */
  public function __set($attribuut, $waarde) {
    switch ($attribuut) {
      case 'verdeling':
        if (is_array($waarde)) {
          $this->_data[$attribuut] = json_encode($waarde);
        } else {
          $this->_data[$attribuut] = $waarde;
        }
        break;
      case 'datum':
        $this->_data['jaar'] = date('Y', $waarde);
        $this->_data['maand'] = date('m', $waarde);
        $this->_data['dag'] = date('d', $waarde);
      case 'gemeld':
      case 'verwerkt':
        $this->_data[$attribuut] = $waarde ? 1 : 0;
      default:
        $this->_data[$attribuut] = $waarde;
    }
  }

  /**
   * Save the data
   *
   * Saves the data to the database.
   *
   * @since 4.0.0
   *
   * @global  object $wpdb Wordpress database.
   * @return int The id of the oven.
   */
  public function save() {
    global $wpdb;
    $wpdb->replace("{$wpdb->prefix}kleistad_reserveringen", $this->_data);
    return $wpdb->insert_id;
  }

  public static function verwijder($gebruiker_id) {
    /**
     * Verwijder reservering van gebruiker
     *
     * get attribuut from the object.
     *
     * @since 4.0.0
     *
     * $global object $wpdb wordpress db
     * @param  int $gebruiker_id Gebruiker id.
     * @return mixed Attribute value.
     */
// to do, alleen reserveringen in de toekomst verwijderen ?
  }

}

/**
   * Collection of Reservering
   *
   * Collection of Oven, loaded from the database.
   *
   * @since 4.0.0
   *
   * @see class Kleistad_Reservering
   * @link URL
 */
class Kleistad_Reserveringen extends Kleistad_EntityStore {

  /**
   * Constructor
   *
   * Loads the data from the database.
   *
   * @since 4.0.0
   *
   * @global  object $wpdb Wordpress database.
   * @return null.
   */
  public function __construct($oven_id = null) {
    global $wpdb;
    if (is_null($oven_id)) {
      $reserveringen = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}kleistad_reserveringen ORDER BY jaar DESC, maand DESC, dag DESC", ARRAY_A);
    } else {
      $reserveringen = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}kleistad_reserveringen WHERE oven_id = $oven_id ORDER BY jaar DESC, maand DESC, dag DESC", ARRAY_A);
    }
    foreach ($reserveringen as $reservering_id => $reservering) {
      $this->_data[$reservering_id] = new Kleistad_Reservering($reservering['oven_id']);
      $this->_data[$reservering_id]->load($reservering);
    }
  }

}

class Kleistad_Regelingen {

  /**
   * Store the regeling data
   *
   * @since 4.0.0
   * @access private
   * @var array $_data contains regeling attributes.
   */
  private $_data;

  /**
   * Constructor
   *
   * Constructor, Long description.
   *
   * @since 4.0.0
   *
   * @param  int $gebruiker_id gebruiker for regeling to load.
   * @return null.
   */
  public function __construct() {
    $gebruikers = get_users(['meta_key' => 'ovenkosten']);
    foreach ($gebruikers as $gebruiker) {
      $ovenkosten = json_decode(get_user_meta($gebruiker->ID, 'ovenkosten', true), true);
      $this->_data[$gebruiker->ID] = $ovenkosten;
    }
  }

  /**
   * Getter,
   *
   * get single regeling from the object.
   *
   * @since 4.0.0
   *
   * @param  int $gebruiker_id wp user id.
   * @param  int $oven_id oven id
   * @return float kosten or null if unknown regeling.
   */
  public function get($gebruiker_id, $oven_id) {
    if (array_key_exists($gebruiker_id, $this->_data)) {
      if (array_key_exists($oven_id, $this->_data[$gebruiker_id])) {
        return $this->_data[$gebruiker_id][$oven_id];
      }
    }
    return null;
  }

  /**
   * Setter
   *
   * Set the regeling and store it to the database.
   *
   * @since 4.0.0
   *
   * @param  int $gebruiker_id wp user id.
   * @param  int $oven_id oven id
   * @param  float $kosten kostenregeling
   * @return null.
   */
  public function set_and_save($gebruiker_id, $oven_id, $kosten) {
    $this->_data[$gebruiker_id][$oven_id] = $kosten;
    update_user_meta($gebruiker_id, 'ovenkosten', json_encode($kosten));
  }

}
