<?php
/**
 * De basis class voor de admin functies van de kleistad plugin.
 *
 * @link       https://www.kleistad.nl
 * @since      7.2.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/admin
 */

namespace Kleistad;

/**
 * De admin-specifieke functies van de plugin voor de cursisten page.
 */
abstract class Admin_Handler {

	/**
	 * Het display object
	 *
	 * @var Admin_Display $display De display class.
	 */
	protected Admin_Display $display;

	/**
	 * Eventuele foutmelding.
	 *
	 * @var string $notice Foutmelding.
	 */
	protected string $notice = '';

	/**
	 * Of de actie uitgevoerd is.
	 *
	 * @var string $message Actie melding.
	 */
	protected string $message = '';

	/**
	 * Definieer de panels
	 *
	 * @since    7.2.0
	 */
	abstract public function add_pages();

	/**
	 * Toon en verwerk ingevoerde cursist gegevens
	 *
	 * @since    7.2.0
	 */
	abstract public function form_handler();

	/**
	 * Toon en verwerk ingevoerde cursist gegevens
	 *
	 * @since    7.2.0
	 */
	public function page_handler() {
		$this->display->page();
	}
}
