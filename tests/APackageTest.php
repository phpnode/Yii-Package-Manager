<?php
require_once("common.php");
/**
 * Tests for the {@link APackage} class
 * @author Charles Pick
 * @package packages.ypm.tests
 */
class APackageTest extends CTestCase {
	/**
	 * Tests installing and uninstalling a package
	 */
	public function testInstallUninstall() {
		$manager = new APackageManager();
		Yii::app()->setComponent("packageManager",$manager);
		$package = new APackage();
		$package->name = uniqid();
		$package->url = "git://github.com/phpnode/YiiGit.git";
		$package->install();
		$this->assertTrue(file_exists($package->getInstallationDirectory()."/README.md"));
		$this->assertTrue($package->getIsInstalled());
		$this->assertFalse($package->getIsModified());
		file_put_contents($package->getInstallationDirectory()."/blah.txt","a test string");
		$this->assertTrue($package->getIsModified());
		$this->assertFalse($package->uninstall());
		unlink($package->getInstallationDirectory()."/blah.txt");
		$this->assertFalse($package->getIsModified());
		$this->assertTrue($package->uninstall());
		$this->assertFalse(file_exists($package->getInstallationDirectory()."/README.md"));
		$this->assertFalse($package->getIsInstalled());
	}
	/**
	 * Tests finding dependencies and dependents
	 */
	public function testDependencies() {
		$manager = new APackageManager();
		Yii::app()->setComponent("packageManager",$manager);
		$gitPackage = $manager->getPackages()->git;
		$ypmPackage = $manager->getPackages()->ypm;
		$this->assertArrayHasKey("git",$ypmPackage->getDependencies());
		$this->assertArrayHasKey("ypm",$gitPackage->getDependents());

	}
}