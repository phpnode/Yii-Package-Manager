<?php
/**
 * The package manager deals with finding, installing, upgrading and uninstalling Yii packages.
 * @author Charles Pick
 * @package packages.ypm.components
 */
class APackageManager extends CApplicationComponent {
	/**
	 * A collection of trusted package repositories
	 * @var CAttributeCollection
	 */
	protected $_repositories;
	/**
	 * A collection of installed packages
	 * @var CAttributeCollection
	 */
	protected $_packages;

	/**
	 * Gets the installed packages
	 * @return CAttributeCollection a collection of installed packages
	 */
	public function getPackages()
	{
		if ($this->_packages === null) {
			$this->_packages = new CAttributeCollection();
			$iterator = new DirectoryIterator(realpath(Yii::getPathOfAlias("packages")));
			foreach($iterator as $directory) {
				if ($directory->isFile() || $directory->getFileName() == "." || $directory->getFileName() == "..") {
					continue;
				}
				$packageFile = $directory->getPathInfo()."/".$directory->getFileName()."/package.json";
				if (!file_exists($packageFile)) {
					continue;
				}
				$package = new APackage("edit");
				if (function_exists("json_decode")) {
					$data = json_decode(file_get_contents($packageFile));
				}
				else {
					$data = CJSON::decode(file_get_contents($packageFile));
				}
				foreach($data as $attribute => $value) {
					$package->{$attribute} = $value;
				}
				$this->_packages[$package->name] = $package;
			}
		}
		return $this->_packages;
	}
	/**
	 * Gets the trusted package repositories
	 * @return CAttributeCollaction a collection of package repositories
	 */
	public function getRepositories()
	{
		if ($this->_repositories === null) {
			$this->_repositories = new CAttributeCollection();
			$files = CFileHelper::findFiles(
				Yii::getPathOfAlias("packages.ypm.repositories"),
				array(
					"fileTypes" => array("json"),
					"level" => 0,
				)
			);
			foreach($files as $file) {
				$repository = APackageRepository::load(basename($file,".json"));
				$repository->setManager($this);
				if ($repository) {
					$this->_repositories->add($repository->name, $repository);
				}
			}
			if (!isset($this->_repositories->local)) {
				// adda local repository
				$repository = APackageRepository::load("local");
				$this->_repositories->add($repository->name, $repository);
			}
		}
		return $this->_repositories;
	}

	/**
	 * Finds a package with the given name
	 * @param string $name the package name
	 * @return APackage|boolean either the package instance or false if no package with this name was found
	 */
	public function find($name) {
		if (strstr($name,"/")) {
			$parts = explode("/",$name,2);
			$repositoryName = array_shift($parts);
			$name = array_shift($parts);
			if (!isset($this->getRepositories()->{$repositoryName})) {
				return false;
			}
			if (!isset($this->getRepositories()->{$repositoryName}->getPackages()->{$repositoryName})) {
				return false;
			}
			return $this->getRepositories()->{$repositoryName}->getPackages()->{$name};
		}
		else {
			foreach($this->getPackages() as $package) {
				if ($package->name == $name) {
					return $package;
				}
			}
			foreach($this->getRepositories() as $repository) {
				if (isset($repository->getPackages()->{$name})) {
					return $repository->getPackages()->{$name};
				}
			}
			return false;
		}
	}

	/**
	 * Installs a package with the given name
	 * @param string|APackage $name the package name or package instance to install
	 * @return boolean whether the installation succeeded or not
	 */
	public function install($name) {
		if (!($name instanceof APackage)) {
			$package = $this->find($name);
			if ($package === false) {
				return false;
			}
		}
		else {
			$package = $name;
		}
		return $package->install();
	}

	/**
	 * Uninstalls a package with the given name
	 * @param string|APackage $name the package name or package instance to uninstall
	 * @return boolean whether the uninstallation succeeded or not
	 */
	public function uninstall($name) {
		if (!($name instanceof APackage)) {
			$package = $this->find($name);
			if ($package === false) {
				return false;
			}
		}
		else {
			$package = $name;
		}
		return $package->uninstall();
	}
}