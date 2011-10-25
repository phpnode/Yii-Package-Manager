<?php
require_once("common.php");
/**
 * Tests for the {@link APackageManager} class
 * @author Charles Pick
 * @package packages.ypm.tests
 */
class APackageManagerTest extends CTestCase {
	/**
	 * Tests getting a list of installed packages
	 */
	public function testGetPackages() {
		$manager = new APackageManager();
		$packages = $manager->getPackages();
		$this->assertTrue($packages instanceof CAttributeCollection);
		$this->assertGreaterThan(0,count($packages));
	}
	/**
	 * Tests getting the trusted repositories
	 */
	public function testGetRepositories() {
		$manager = new APackageManager();
		$repositories = $manager->getRepositories();
		$this->assertTrue(isset($repositories->local));
	}

}