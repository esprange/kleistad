<?php
/**
 * De abstract product class.
 *
 * Een class definitie als basis voor de Kleistad producten.
 *
 * @link       https://www.kleistad.nl
 * @since      4.5.1
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

/**
 * Definitie van Kleistad producten.
 */
const KLEISTAD_PRODUCTEN = [
	'dagdelenkaart' => [
		[
			'naam'         => '',
			'betaalschema' => [
				1,
			],
			'prijs'        => 'dagdelenkaart',
		],
	],
	'abonnement'    => [
		[
			'naam'         => 'dag',
			'betaalschema' => [
				3,
				'maandelijks',
			],
			'prijs'        => 'beperkt_abonnement',
		],
		[
			'naam'         => 'volledig',
			'betaalschema' => [
				3,
				'maandelijks',
			],
			'prijs'        => 'onbeperkt_abonnement',
		],
	],
	'cursus'        => [
		[
			'naam'         => 'dagcursus',
			'betaalschema' => [
				'cursusgeld',
			],
			'prijs'        => '',
		],
		[
			'naam'         => 'workshop',
			'betaalschema' => [
				'inschrijving',
				'restant workshop',
			],
			'prijs'        => '',
		],
		[
			'naam'         => 'cursus',
			'betaalschema' => [
				'inschrijving',
				'restant cursus',
			],
			'prijs'        => '',
		],
	],
	'stooksaldo'    => [
		[
			'naam'         => '15 euro',
			'betaalschema' => [
				'eenmalig',
			],
			'prijs'        => 15,
		],
		[
			'naam'         => '30 euro',
			'betaalschema' => [
				'eenmalig',
			],
			'prijs'        => 30,
		],
	],
];

/**
 * Kleistad Product class.
 *
 * Een class definitie, basis voor overige classes.
 *
 * @since 4.5.1
 */
abstract class Kleistad_Product extends Kleistad_Entity {

	/**
	 * Verstuur een email naar de klant
	 *
	 * @param string       $type Het type email.
	 * @param string|array $param tekst of array van teksten.
	 */
	abstract public function email( $type, $param = null );

	/**
	 * Betaal het product
	 */
	abstract public function betalen();

	/**
	 * Maak een controle string aan.
	 *
	 * @param  string $unique Een string die de hash uniek maakt.
	 * @return string         Hash string.
	 */
	public function controle( $unique ) {
		return hash( 'sha256', "KlEiStAd{$unique}cOnTrOlE" );
	}
}
