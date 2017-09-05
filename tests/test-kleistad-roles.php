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
	 */
	public function setUp() {
		parent::Setup();
		
		activate_kleistad();
		$this->subscriber_id = $this->factory->user->create(
			[
				'role' => 'subscriber',
			]
		);
		$this->editor_id = $this->factory->user->create(
			[
				'role' => 'editor',
			]
		);
		$this->nonmember_id = $this->factory->user->create(
			[
				'role' => '',
			]
		);
	}
	/**
	 * Test creation and modification of roles.
	 */
	function test_roles() {
		$this->assertTrue( Kleistad_Roles::reserveer( $this->subscriber_id ), 'subscriber cannot reserveer' );
		$this->assertFalse( Kleistad_Roles::override( $this->subscriber_id ), 'subscriber can override' );

		$this->assertTrue( Kleistad_Roles::reserveer( $this->editor_id ), 'editor cannot reserveer' );
		$this->assertTrue( Kleistad_Roles::override( $this->editor_id ), 'editor cannot override' );

		$this->assertFalse( Kleistad_Roles::reserveer( $this->nonmember_id ), 'no role can reserveer' );
		$this->assertFalse( Kleistad_Roles::override( $this->nonmember_id ), 'no role can override' );
	}

}
