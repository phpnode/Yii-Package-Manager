<?php
/**
 * Represents a fake installed package for testing
 * @package packages.ypm.tests.doubles
 * @author Charles Pick
 */
class AFakeInstalledPackage extends APackage {
	/**
	 * Determines whether this package is installed or not
	 * @return boolean always true
	 */
	public function getIsInstalled() {
		return true;
	}
}