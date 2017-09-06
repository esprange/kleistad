<?php
/**
 * Class FactoryKleistad
 *
 * @package Kleistad
 */

/**
 * The oven factory
 *
 * @author espra Eric Sprangers
 */
class WP_UnitTest_Factory_For_Oven extends WP_UnitTest_Factory_For_Thing {
	/**
	 * Define the defaults.
	 *
	 * @param type $factory the factory object.
	 */
	public function __construct( $factory = null ) {
		parent::__construct( $factory );
		$this->default_generation_definitions = [
			'naam'               => new WP_UnitTest_Generator_Sequence( 'testoven %s' ),
			'kosten'             => new WP_UnitTest_Generator_Sequence( '%s' ),
			'beschikbaarheid'    => [ 'maandag', 'woensdag', 'vrijdag' ],
		];
	}

	/**
	 * Create the oven.
	 *
	 * @param array $args the arguments.
	 * @return boolean
	 */
	public function create_object( $args ) {
		$oven = new Kleistad_Oven();
		$oven->naam = $args['naam'];
		$oven->kosten = $args['kosten'];
		$oven->beschikbaarheid = $args['beschikbaarheid'];
		$id = $oven->save();
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
	 * @return boolean
	 */
	public function update_object( $id, $args ) {
		$oven = new Kleistad_Oven( $id );
		$oven->naam = $args['naam'];
		$oven->kosten = $args['kosten'];
		$oven->beschikbaarheid = $args['beschikbaarheid'];
		$id = $oven->save();
		if ( ! $id ) {
			return false;
		}
	}

	/**
	 * Get the oven by id.
	 *
	 * @param int $id the oven id.
	 * @return \Kleistad_Oven the object.
	 */
	public function get_object_by_id( $id ) {
		$oven = new Kleistad_Oven( $id );
		return $oven;
	}

}
	/**
	 * The cursus factory
	 *
	 * @author espra Eric Sprangers
	 */
class WP_UnitTest_Factory_For_Cursus extends WP_UnitTest_Factory_For_Thing {
	/**
	 * Define the defaults.
	 *
	 * @param type $factory the factory object.
	 */
	public function __construct( $factory = null ) {
		parent::__construct( $factory );
		$this->default_generation_definitions = [
			'naam'          => new WP_UnitTest_Generator_Sequence( 'testcursus %s' ),
			'docent'        => new WP_UnitTest_Generator_Sequence( 'docent %s' ),
			'technieken'    => [ new WP_UnitTest_Generator_Sequence( 'techniek%s' ) ],
		];
	}

	/**
	 * Create the cursus.
	 *
	 * @param array $args the arguments.
	 * @return boolean
	 */
	public function create_object( $args ) {
		$cursus = new Kleistad_Cursus();
		$cursus->naam = $args['naam'];
		$cursus->docent = $args['docent'];
		$cursus->technieken = $args['technieken'];
		$id = $cursus->save();
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
	 * @return boolean
	 */
	public function update_object( $id, $args ) {
		$cursus = new Kleistad_Cursus( $id );
		$cursus->naam = $args['naam'];
		$cursus->docent = $args['docent'];
		$cursus->technieken = $args['technieken'];
		$id = $cursus->save();
		if ( ! $id ) {
			return false;
		}
	}

	/**
	 * Get the cursus by id.
	 *
	 * @param int $id the cursus id.
	 * @return \Kleistad_Oven the object.
	 */
	public function get_object_by_id( $id ) {
		$cursus = new Kleistad_Cursus( $id );
		return $cursus;
	}

}
