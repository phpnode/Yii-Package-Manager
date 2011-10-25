<?php
/**
 * Represents a package repository.
 * A package repository is a trusted URL that provides a list of package details in JSON format.
 *
 * @author Charles Pick
 * @package packages.ypm.models
 */
class APackageRepository extends CFormModel {
	/**
	 * The name of this package repository
	 * @var string
	 */
	public $name;
	/**
	 * A description of this package repository
	 * @var string
	 */
	public $description;
	/**
	 * The package repository URL
	 * @var string
	 */
	public $url;

	/**
	 * A collection of packages that belong to this repository
	 * @var CAttributeCollection
	 */
	protected $_packages;

	/**
	 * The curl instance we use to interact with the repository
	 * @var ACurl
	 */
	protected $_curl;

	/**
	 * The package manager
	 * @var APackageManager
	 */
	protected $_manager;

	/**
	 * Sets the packages that belong to this repository
	 * @param APackage[] $packages the packages that belong to this repository
	 */
	public function setPackages($packages)
	{
		$collection = new CAttributeCollection();
		foreach($packages as $name => $package) {
			if (!($package instanceof APackage)) {
				$p = $package;
				$package = new APackage();
				foreach($p as $attribute => $value) {
					$package->{$attribute} = $value;
				}
			}
			$package->setRepository($this);
			$collection->add($name,$package);
		}
		$this->_packages = $collection;
	}

	/**
	 * Gets a list of packages that belong to this repository
	 * @return APackage[] the packages that belong to the repository
	 */
	public function getPackages()
	{
		if ($this->_packages === null) {
			$this->loadDetails();
		}
		return $this->_packages;
	}

	/**
	 * Loads the repository details from the repository server
	 */
	public function loadDetails() {
		if ($this->url != "") {
			$this->setPackages(array());
			return;
		}
		$curl = $this->getCurl();
		$response = $curl->get($this->url)->exec()->fromJSON();
		$this->name = $response['name'];
		$this->description = $response['description'];
		$this->setPackages($response['packages']);
	}
	/**
	 * Gets a collection of installed packages
	 * @return CAttributeCollection a collection of installed packages
	 */
	public function getInstalledPackages() {
		$packages = new CAttributeCollection();
		foreach($this->getPackages() as $package) {
			if ($package->getIsInstalled()) {
				$packages[$package->name] = $package;
			}
		}
		return $packages;
	}
	/**
	 * Sets the curl instance we use to interact with the repository
	 * @param ACurl $curl the curl instance
	 */
	public function setCurl($curl)
	{
		$this->_curl = $curl;
	}

	/**
	 * Gets the curl instance used to interact with the repository
	 * @return ACurl the curl instance
	 */
	public function getCurl()
	{
		if ($this->_curl === null) {
			$this->_curl = Yii::createComponent("packages.curl.ACurl");
		}
		return $this->_curl;
	}

	/**
	 * Saves the repository json file
	 * @param boolean $runValidation whether to run validation before saving or not
	 * @return boolean whether the save succeeded or not
	 */
	public function save($runValidation = true) {
		if ($runValidation && !$this->validate()) {
			return false;
		}
		$dir = Yii::getPathOfAlias("packages.ypm.repositories");
		if (!file_exists($dir) || !is_dir($dir)) {
			mkdir($dir);
		}
		$filename = $dir."/".$this->name.".json";
		$json = function_exists("json_encode") ? json_encode($this->toJSON()) : CJSON::encode($this->toJSON());
		return file_put_contents($filename,$json) ? true : false;
	}

	/**
	 * Deletes a package repository.
	 * Unless $force is true, deletion will fail if there are installed packages from this repository.
	 * @param boolean $force whether to force a delete, even if there are installed packages for this repo.
	 * @return boolean true if the delete succeeded
	 */
	public function delete($force = false) {
		if (!$force && count($this->getInstalledPackages()) > 0) {
			return false;
		}
		$filename = Yii::getPathOfAlias("packages.ypm.repositories")."/".$this->name.".json";
		unlink($filename);
		return true;

	}
	/**
	 * Gets an array of keys => values to use when encoding this object as JSON
	 * @return array the attributes and values to encode as JSON
	 */
	public function toJSON() {
		$attributes = array();
		foreach($this->attributeNames() as $attribute) {
			$attributes[$attribute] = $this->{$attribute};
		}
		return $attributes;
	}
	/**
	 * Loads a package repository with the given name
	 * @param string $name the repository name
	 * @return APackageRepository|boolean the loaded package repository or false if the repository cannot be loaded
	 */
	public static function load($name) {
		$repo = new APackageRepository();
		$dir = Yii::getPathOfAlias("packages.ypm.repositories");
		$filename = realpath($dir."/".$name.".json");
		if (!$filename || !file_exists($filename)) {
			if ($name == "local") {
				// we always need a local repository, so create it
				return self::createLocalRepository();
			}
			return false;
		}
		$json = file_get_contents($filename);
		$data = function_exists("json_decode") ? json_decode($json) : CJSON::decode($json);
		if (!$data) {
			return false;
		}
		foreach($data as $attribute => $value) {
			$repo->{$attribute} = $value;
		}
		return $repo;
	}
	/**
	 * Creates a local repository
	 * @return APackageRepository the local repository
	 */
	protected static function createLocalRepository() {
		$repo = new APackageRepository();
		$repo->name = "local";
		$repo->description = "A local package repository, somewhere you can stash packages you're working on before you publish them";
		$repo->url = Yii::app()->createAbsoluteUrl("/ypm/package/list");
		$repo->save();
		return $repo;
	}
	/**
	 * Sets the package manager for this repository
	 * @param APackageManager $manager the package manager for this repository
	 */
	public function setManager($manager)
	{
		$this->_manager = $manager;
	}

	/**
	 * Gets the package manager for this repository
	 * @return APackageManager the package manager
	 */
	public function getManager()
	{
		if ($this->_manager === null) {
			if (!isset(Yii::app()->packageManager)) {
				throw new CException('APackageRepository expects a "packageManager" application component.');
			}
			$this->_manager = Yii::app()->packageManager;
		}
		return $this->_manager;
	}

}