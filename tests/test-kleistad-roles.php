<?php
/**
 * Class SampleTest
 *
 * @package Kleistad
 */

/**
 * Sample test case.
 */
class OvensTest extends WP_UnitTestCase {

	/**
	 * Activate the plugin which includes the kleistad specific tables if not present.
	 */
	public function setUp() {
		activate_kleistad();
	}
	/**
	 * Test creation and modification of an oven.
	 */
	function test_roles() {
		$user_id1 = $this->factory->user->create(
			[
				'role' => 'subscriber',
			]
		);

		$this->assertTrue( Kleistad_Roles::reserveer( $user_id1 ) );
		$this->assertFalse( Kleistad_Roles::override( $user_id1 ) );

		$user_id2 = $this->factory->user->create(
			[
				'role' => 'subscriber',
			]
		);
		$this->assertTrue( Kleistad_Roles::reserveer( $user_id2 ) );
		$this->assertTrue( Kleistad_Roles::override( $user_id2 ) );

		$user_id2 = $this->factory->user->create(
			[
				'role' => '',
			]
		);
		$this->assertFalse( Kleistad_Roles::reserveer( $user_id2 ) );
		$this->assertFalse( Kleistad_Roles::override( $user_id2 ) );
	}

}
