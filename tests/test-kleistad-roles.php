<?php
/**
 * Class RolesTest
 *
 * @package Kleistad
 */

/**
 * Roles test case.
 */
class KleistadRolesTest extends WP_UnitTestCase {

	/**
	 * Subscriber id
	 *
	 * @var int $subscriber_id The subscriber.
	 */
	protected static $subscriber_id;

	/**
	 * Editor id
	 *
	 * @var int $editor_id The editor.
	 */
	protected static $editor_id;

	/**
	 * Nonmember id
	 *
	 * @var int $nonmember_id The nonmember.
	 */
	protected static $nonmember_id;

	/**
	 * Activate the plugin which includes the kleistad specific tables if not present.
	 *
	 * @param object $factory test factory object.
	 */
	public static function wpSetUpBeforeClass( $factory ) {
		activate_kleistad();
		self::$subscriber_id = $factory->user->create(
			[
				'role' => 'subscriber',
			]
		);
		self::$editor_id = $factory->user->create(
			[
				'role' => 'editor',
			]
		);
		self::$nonmember_id = $factory->user->create(
			[
				'role' => '',
			]
		);
	}

	/**
	 * Define the test users.
	 */
	public function setUp() {
		parent::setUp();
		// we want to make sure we're testing against the db, not just in-memory data.
		// this will flush everything and reload it from the db.
		unset( $GLOBALS['wp_user_roles'] );
		global $wp_roles;
		$wp_roles = new WP_Roles();
	}


	/**
	 * Test creation and modification of roles.
	 */
	public function test_roles() {
		$this->assertTrue( Kleistad_Roles::reserveer( self::$subscriber_id ), 'subscriber cannot reserveer' );
		$this->assertFalse( Kleistad_Roles::override( self::$subscriber_id ), 'subscriber can override' );

		$this->assertTrue( Kleistad_Roles::reserveer( self::$editor_id ), 'editor cannot reserveer' );
		$this->assertTrue( Kleistad_Roles::override( self::$editor_id ), 'editor cannot override' );

		$this->assertFalse( Kleistad_Roles::reserveer( self::$nonmember_id ), 'no role can reserveer' );
		$this->assertFalse( Kleistad_Roles::override( self::$nonmember_id ), 'no role can override' );
	}

}
