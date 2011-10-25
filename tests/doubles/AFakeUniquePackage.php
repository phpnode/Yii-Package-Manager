<?php
/**
 * Represents a fake unique package for testing
 * @package packages.ypm.tests.doubles
 * @author Charles Pick
 */
class AFakeUniquePackage extends APackage {
	/**
	 * Checks that the package name is unique.
	 * Searches all trusted repositories looking for a repository with the same name, if one exists validation fails
	 * @return boolean always true
	 */
	public function checkUniqueName() {
		return true;
	}
}