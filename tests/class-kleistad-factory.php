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
		$this->default_generation_definitions = [
			'naam'            => new WP_UnitTest_Generator_Sequence( 'testoven %s' ),
			'kosten_laag'     => new WP_UnitTest_Generator_Sequence( '%s' ),
			'beschikbaarheid' => [ 'maandag', 'woensdag', 'vrijdag' ],
		];
	}

	/**
	 * Create the oven.
	 *
	 * @param array $args the arguments.
	 *
	 * @return bool|int
	 */
	public function create_object( $args ) {
		$oven                  = new Oven();
		$oven->naam            = $args['naam'];
		$oven->kosten_laag     = $args['kosten_laag'];
		$oven->beschikbaarheid = $args['beschikbaarheid'];
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
			'naam'       => new WP_UnitTest_Generator_Sequence( 'testcursus %s' ),
			'docent'     => new WP_UnitTest_Generator_Sequence( 'docent %s' ),
			'technieken' => [ new WP_UnitTest_Generator_Sequence( 'techniek%s' ) ],
		];
	}

	/**
	 * Create the cursus.
	 *
	 * @param array $args the arguments.
	 *
	 * @return bool|int
	 */
	public function create_object( $args ) {
		$cursus             = new Cursus();
		$cursus->naam       = $args['naam'];
		$cursus->docent     = $args['docent'];
		$cursus->technieken = $args['technieken'];
		$id                 = $cursus->save();
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
		$cursus             = new Cursus( $id );
		$cursus->naam       = $args['naam'];
		$cursus->docent     = $args['docent'];
		$cursus->technieken = $args['technieken'];
		$id                 = $cursus->save();
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
