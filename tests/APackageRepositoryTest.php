<?php
require_once("common.php");
/**
 * Tests for the {@link APackageRepository} class
 * @author Charles Pick
 * @package packages.ypm.tests
 */
class APackageRepositoryTest extends CTestCase {
	/**
	 * Tests basic functionality
	 */
	public function testBasics() {
		$repo = new APackageRepository();
		$repo->name = uniqid();
		$repo->description = "test test test";
		$repo->url = "http://localhost/";
		$this->assertTrue($repo->save());
		$this->assertTrue(file_exists(Yii::getPathOfAlias("packages.ypm.repositories")."/".$repo->name.".json"));

		$this->assertFalse(APackageRepository::load(uniqid()));
		$repo2 = APackageRepository::load($repo->name);
		$this->assertTrue(is_object($repo2));
		$this->assertEquals($repo->name,$repo2->name);
		unset($repo2);

		$repo->description .= " test test test";
		$this->assertTrue($repo->save());
		$package = new AFakeInstalledPackage();
		$package->name = uniqid();

		$repo->setPackages(array($package->name => $package));
		$this->assertFalse($repo->delete());
		$repo->setPackages(array());
		$this->assertTrue($repo->delete(true));
	}
}