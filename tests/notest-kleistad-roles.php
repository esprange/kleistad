<?php
/**
 * Class SampleTest
 *
 * @package Kleistad
 */

/**
 * Sample test case.
 */
class KleistadRolesTest extends WP_UnitTestCase {

	/**
	 * Activate the plugin which includes the kleistad specific tables if not present.
	 */
	public function setUp() {
		activate_kleistad();
	}
	/**
	 * Test creation and modification of roles.
	 */
	function test_roles() {
		$user_id1 = $this->factory->user->create(
			[
				'role' => 'subscriber',
			]
		);

		$this->assertTrue( Kleistad_Roles::reserveer( $user_id1 ), 'subscriber cannot reserveer' );
		$this->assertFalse( Kleistad_Roles::override( $user_id1 ), 'subscriber can override' );

		$user_id2 = $this->factory->user->create(
			[
				'role' => 'editor',
			]
		);
		$this->assertTrue( Kleistad_Roles::reserveer( $user_id2 ), 'editor cannot reserveer' );
		$this->assertTrue( Kleistad_Roles::override( $user_id2 ), 'editor cannot override' );

		$user_id2 = $this->factory->user->create(
			[
				'role' => '',
			]
		);
		$this->assertFalse( Kleistad_Roles::reserveer( $user_id2 ), 'no role can reserveer' );
		$this->assertFalse( Kleistad_Roles::override( $user_id2 ), 'no role an override' );
	}

}
