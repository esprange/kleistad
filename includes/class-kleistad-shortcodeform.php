<?php
/**
 * De  abstracte class voor shortcodes.
 *
 * @link       https://www.kleistad.nl
 * @since      4.3.11
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

/**
 * De abstract class voor shortcodes
 */
abstract class Kleistad_ShortcodeForm extends Kleistad_ShortCode {
	/**
	 * Validatie functie, wordt voor form validatie gebruikt
	 *
	 * @since   4.0.87
	 * @param array $data de gevalideerde data.
	 * @return \WP_ERROR|bool
	 */
	abstract public function validate( &$data );

	/**
	 * Save functie, wordt gebruikt bij formulieren
	 *
	 * @since   4.0.87
	 * @param array $data de gevalideerde data die kan worden opgeslagen.
	 * @return \WP_ERROR|string
	 */
	abstract function save( $data );
}
