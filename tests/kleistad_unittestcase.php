<?php
/**
 * Class Kleistad_UnitTestCase
 *
 * @package Kleistad
 * @phpcs:disable WordPress.Files, Generic.Files
 */

namespace Kleistad\Tests;

use Kleistad\Admin_Upgrade;
use Kleistad\Kleistad;
use WP_UnitTestCase;

/**
 * Oven test case.
 */
abstract class Kleistad_UnitTestCase extends WP_UnitTestCase {

	/**
	 * Activate the plugin which includes the kleistad specific tables if not present.
	 */
	public function setUp(): void {
		parent::setUp();
		update_option( 'kleistad-database-versie', 0 );
		$this->class_instance = new Kleistad();
		$upgrade              = new Admin_Upgrade();
		$upgrade->run();
	}


}
