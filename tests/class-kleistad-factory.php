<?php
/**
 * Class FactoryKleistad
 *
 * @package Kleistad
 * @phpcs:disable WordPress.Files, Generic.Files
 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
 */

namespace Kleistad;

use WP_UnitTest_Generator_Sequence;
use WP_UnitTest_Factory_For_Thing;
use WP_UnitTest_Factory;

class Kleistad_Factory extends WP_UnitTest_Factory {
	/**
	 * De oven factory
	 *
	 * @var Kleistad_Factory_For_Oven $oven
	 */
	public Kleistad_Factory_For_Oven $oven;

	/**
	 * De cursus factory
	 *
	 * @var Kleistad_Factory_For_Cursus $cursus
	 */
	public Kleistad_Factory_For_Cursus $cursus;

	/**
	 * De order factory
	 *
	 * @var Kleistad_Factory_For_Order $order
	 */
	public Kleistad_Factory_For_Order $order;

	/**
	 * Uitbreiding op de constructor.
	 */
	public function __construct() {
		parent::__construct();
		$this->oven   = new Kleistad_Factory_For_Oven( $this );
		$this->order  = new Kleistad_Factory_For_Order( $this );
		$this->cursus = new Kleistad_Factory_For_Cursus( $this );
	}
}

/**
 * The oven factory
 *
 * @author espra Eric Sprangers
 */
class Kleistad_Factory_For_Oven extends WP_UnitTest_Factory_For_Thing {
	/**
	 * Define the defaults.
	 *
	 * @param object $factory the factory object.
	 */
	public function __construct( $factory = null ) {
		parent::__construct( $factory );
		$kosten_laag                          = wp_rand( 10.0, 20.0 );
		$this->default_generation_definitions = [
			'naam'          => new WP_UnitTest_Generator_Sequence( 'testoven %s' ),
			'kosten_laag'   => $kosten_laag,
			'kosten_midden' => $kosten_laag + wp_rand( 2.0, 5.0 ),
			'kosten_hoog'   => $kosten_laag + wp_rand( 5.0, 10.0 ),
		];
	}

	/**
	 * Create the oven.
	 *
	 * @param array $args the arguments.
	 *
	 * @return bool|int
	 */
	public function create_object( $args ): bool|int {
		$oven                  = new Oven();
		$oven->naam            = $args['naam'];
		$oven->kosten_laag     = $args['kosten_laag'];
		$oven->kosten_midden   = $args['kosten_midden'];
		$oven->kosten_hoog     = $args['kosten_hoog'];
		$oven->beschikbaarheid = [ 'maandag', 'woensdag', 'vrijdag' ];
		$id                    = $oven->save();
		if ( ! $id ) {
			return false;
		}
		return $id;
	}

	/**
	 * Update the oven.
	 *
	 * @param int   $id the oven id.
	 * @param array $args the arguments.
	 *
	 * @return bool
	 */
	public function update_object( $id, $args ) : bool {
		$oven                  = new Oven( $id );
		$oven->naam            = $args['naam'];
		$oven->kosten_laag     = $args['kosten_laag'];
		$oven->kosten_midden   = $args['kosten_midden'];
		$oven->kosten_hoog     = $args['kosten_hoog'];
		$oven->beschikbaarheid = $args['beschikbaarheid'];
		$id                    = $oven->save();
		return $id > 0;
	}

	/**
	 * Get the oven by id.
	 *
	 * @param int $id the oven id.
	 *
	 * @return Oven the object.
	 */
	public function get_object_by_id( $id ) : Oven {
		return new Oven( $id );
	}

}

/**
 * The cursus factory
 *
 * @author espra Eric Sprangers
 */
class Kleistad_Factory_For_Cursus extends WP_UnitTest_Factory_For_Thing {
	/**
	 * Define the defaults.
	 *
	 * @param object $factory the factory object.
	 */
	public function __construct( $factory = null ) {
		parent::__construct( $factory );
		$this->default_generation_definitions = [
			'naam'            => new WP_UnitTest_Generator_Sequence( 'testcursus %s' ),
			'docent'          => new WP_UnitTest_Generator_Sequence( 'docent %s' ),
			'technieken'      => new WP_UnitTest_Generator_Sequence( 'techniek%s' ),
			'start_datum'     => strtotime( '+ ' . wp_rand( 1, 30 ) . 'days' ),
			'eind_datum'      => strtotime( '+ ' . wp_rand( 31, 50 ) . 'days' ),
			'inschrijfkosten' => wp_rand( 10.0, 25.0 ),
			'cursuskosten'    => wp_rand( 25.0, 200.0 ),
			'maximum'         => wp_rand( 6, 12 ),
		];
	}

	/**
	 * Create the cursus.
	 *
	 * @param array $args the arguments.
	 *
	 * @return bool|int
	 */
	public function create_object( $args ): bool|int {
		$cursus                  = new Cursus();
		$cursus->naam            = $args['naam'];
		$cursus->docent          = $args['docent'];
		$cursus->technieken      = [ $args['technieken'] ];
		$cursus->inschrijfkosten = $args['inschrijfkosten'];
		$cursus->cursuskosten    = $args['cursuskosten'];
		$cursus->start_datum     = $args['start_datum'];
		$cursus->eind_datum      = $args['eind_datum'];
		$cursus->maximum         = $args['maximum'];
		$id                      = $cursus->save();
		if ( ! $id ) {
			return false;
		}
		return $id;
	}

	/**
	 * Update the cursus.
	 *
	 * @param int   $id the cursus id.
	 * @param array $args the arguments.
	 *
	 * @return bool
	 */
	public function update_object( $id, $args ) : bool {
		$cursus                  = new Cursus( $id );
		$cursus->naam            = $args['naam'];
		$cursus->docent          = $args['docent'];
		$cursus->technieken      = [ $args['technieken'] ];
		$cursus->inschrijfkosten = $args['inschrijfkosten'];
		$cursus->cursuskosten    = $args['cursuskosten'];
		$cursus->start_datum     = $args['start_datum'];
		$cursus->eind_datum      = $args['eind_datum'];
		$cursus->maximum         = $args['maximum'];
		$id                      = $cursus->save();
		return $id > 0;
	}

	/**
	 * Get the cursus by id.
	 *
	 * @param int $id the cursus id.
	 *
	 * @return Cursus the object.
	 */
	public function get_object_by_id( $id ) : Cursus {
		return new Cursus( $id );
	}

}
/**
 * The order factory
 *
 * @author espra Eric Sprangers
 */
class Kleistad_Factory_For_Order extends WP_UnitTest_Factory_For_Thing {
	/**
	 * Define the defaults.
	 *
	 * @param object $factory the factory object.
	 */
	public function __construct( $factory = null ) {
		parent::__construct( $factory );
		$this->default_generation_definitions = [
			'artikel' => new WP_UnitTest_Generator_Sequence( 'artikel %s' ),
			'prijs'   => wp_rand( 1.0, 50.0 ),
			'klant'   => new WP_UnitTest_Generator_Sequence( 'klant%s' ),
			'email'   => new WP_UnitTest_Generator_Sequence( 'klant%s&test.com' ),
		];
	}

	/**
	 * Create the order.
	 *
	 * @param array $args the arguments.
	 *
	 * @return int
	 */
	public function create_object( $args ): int {
		$verkoop        = new LosArtikel();
		$verkoop->klant = [
			'naam'  => $args['klant'],
			'adres' => '',
			'email' => $args['email'],
		];
		$verkoop->bestelregel( $args['artikel'], 1, $args['prijs'] );
		$verkoop->save();
		$order = new Order( $verkoop->get_referentie() );
		$order->bestel();
		return $order->id;
	}

	/**
	 * Update the order.
	 *
	 * @param int   $id the order id.
	 * @param array $args the arguments.
	 */
	public function update_object( $id, $args ) {}

	/**
	 * Get the order by id.
	 *
	 * @param int $id the order id.
	 *
	 * @return Order the object.
	 */
	public function get_object_by_id( $id ) : Order {
		return new Order( $id );
	}

}
