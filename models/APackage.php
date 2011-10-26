<?php
Yii::import("packages.git.*");
/**
 * Represents a Yii package.
 * @author Charles Pick
 * @package packages.ypm.models
 */
class APackage extends CFormModel {
	/**
	 * The name of the package
	 * @var string
	 */
	public $name;
	/**
	 * A short description of the package
	 * @var string
	 */
	public $description;

	/**
	 * The name of the package author(s)
	 * @var string
	 */
	public $author;
	/**
	 * The hash for the installation
	 * @var string
	 */
	public $installationHash;

	/**
	 * The package type, only git is supported for now
	 * @var string
	 */
	public $type = "git";

	/**
	 * The git URL for this package.
	 * e.g. git://github.com/phpnode/Yii-Package-Manager.git
	 * @var string
	 */
	public $url;

	/**
	 * The repository this package belongs to
	 * @var APackageRepository
	 */
	protected $_repository;

	/**
	 * The repository this package belongs to
	 * @var AGitRepository
	 */
	protected $_gitRepository;

	/**
	 * An array of packages this package depends on
	 * @var APackage[]
	 */
	protected $_dependencies;
	/**
	 * An array of packages that depend on this package
	 * @var APackage[]
	 */
	protected $_dependents;
	/**
	 * Constructor.
	 * @param string $scenario name of the scenario that this model is used in.
	 * See {@link CModel::scenario} on how scenario is used by models.
	 * @see CModel::getScenario
	 */
	public function __construct($scenario='create')
	{
		parent::__construct($scenario);
	}
	/**
	 * Returns the list of attribute names.
	 * By default, this method returns all public properties of the class.
	 * You may override this method to change the default.
	 * @return array list of attribute names. Defaults to all public properties of the class.
	 */
	public function attributeNames() {
		return CMap::mergeArray(
			parent::attributeNames(),
			array(
				"repositoryName"
			)
		);
	}
	/**
	 * Sets the repository this package belongs to
	 * @param APackageRepository $repository the package repository
	 */
	public function setRepository($repository)
	{
		$this->_repository = $repository;
	}

	/**
	 * Gets the repository this package belongs to
	 * @return APackageRepository the repository this package belongs to
	 */
	public function getRepository()
	{
		if ($this->_repository === null) {
			$this->_repository = APackageRepository::load("local");
		}
		return $this->_repository;
	}

	/**
	 * Sets the name of the repository this package belongs to
	 * @param string $name the package repository name
	 */
	public function setRepositoryName($name)
	{
		if (!isset($this->getRepository()->getManager()->getRepositories()->{$name})) {
			throw new CException("No such repository: ".$name);
		}
		$this->setRepository($this->getRepository()->getManager()->getRepositories()->{$name});
	}

	/**
	 * Gets the name of the repository this package belongs to
	 * @return string the name of the repository this package belongs to
	 */
	public function getRepositoryName()
	{
		return $this->getRepository()->name;
	}
	/**
	 * The validation rules for packages
	 * @see CModel::rules()
	 * @return array The validation rules for this model
	 */
	public function rules() {
		return array(
			array("name","required",),
			array("name", "length", "max" => 50,),
			array("description", "length", "max" => 1000),
			array('name', 'match', 'pattern'=>'/^([a-zA-Z0-9-])+$/'),
			array('name','checkUniqueName',"on" => "create"),
		);
	}
	/**
	 * Checks that the package name is unique.
	 * Searches all trusted repositories looking for a repository with the same name, if one exists validation fails
	 * @return boolean true if the name is unique
	 */
	public function checkUniqueName() {
		if ($this->hasErrors("name")) {
			return false;
		}
		$package = $this->getRepository()->getManager()->find($this->name);
		if ($package === false) {
			return true;
		}
		$this->addError("name",Yii::t("packages.ypm","A package with this name already exists!"));
		return false;
	}
	/**
	 * Installs the package
	 * @return boolean whether the package installed successfully or not
	 */
	public function install() {
		if ($this->getIsInstalled()) {
			$this->addError(null, "This package is already installed.");
			return false;
		}
		$git = $this->getGitRepository();
		$git->cloneRemote($this->url);
		$this->installationHash = $this->getHash();
		if (!$this->save()) {
			return false;
		}
		$git->add("package.json");
		$git->commit("Installed ".$this->name);
		return true;
	}
	/**
	 * Gets the git repository to use with this package
	 * @return AGitRepository the git repository to use with this package
	 */
	public function getGitRepository()
	{
		if ($this->_gitRepository !== null) {
			return $this->_gitRepository;
		}
		$this->_gitRepository = new AGitRepository();
		$this->_gitRepository->setPath($this->getInstallationDirectory(), true);
		return $this->_gitRepository;
	}

	/**
	 * Uninstalls the package
	 * @return boolean whether the package uninstalled successfully or not
	 */
	public function uninstall($forceDelete = false, $removeDependents = false) {
		//TODO: add dependency check

		if (!$this->getIsInstalled()) {
			$this->addError(null,"This package is not installed");
			return false;
		}
		if (!$forceDelete && $this->getIsModified()) {
			$this->addError(null,"This package has been modified");
			return false;
		}
		$realPath = realpath($this->getInstallationDirectory());
		$iterator = new RecursiveDirectoryIterator($realPath);
		foreach(new RecursiveIteratorIterator($iterator,RecursiveIteratorIterator::CHILD_FIRST) as $path => $file) {
			if ($file->getFileName() == "." || $file->getFileName() == "..") {
				continue;
			}
			if ($file->isDir()) {
				rmdir($path);
			}
			else {
				unlink($path);
			}
		}
		rmdir($realPath);
		return true;
	}
	/**
	 * Determines whether the package is installed or not
	 * @return boolean true if the package is installed
	 */
	public function getIsInstalled() {
		return file_exists($this->getInstallationDirectory()."/package.json");
	}
	/**
	 * Determines whether the package contents have been modified since installation / upgrade
	 * @return boolean true if the package has been modified
	 */
	public function getIsModified() {
		$git = $this->getGitRepository();
		if (count($git->getBranches()) > 1) {
			return true;
		}
		return $this->getHash() != $this->installationHash;
	}
	/**
	 * Gets a hash for the package files.
	 * This hash can be used to determine whether the package has been modified since installation
	 * @return string the hash for the package files
	 */
	public function getHash() {
		$hashes = array();
		$dir = $this->getInstallationDirectory();
		$options = array(
			"exclude" => array(".svn",".git","package.json"),
		);
		foreach(CFileHelper::findFiles($dir, $options) as $file) {
			$hashes[] = sha1_file($file);
		}
		return sha1(implode("|",$hashes));
	}
	/**
	 * Gets the installation directory for this package
	 * @return string the installation directory
	 */
	public function getInstallationDirectory() {
		return Yii::getPathOfAlias("packages.".$this->name);
	}
	/**
	 * Saves the package.json file
	 * @param boolean $runValidation whether to run validation before saving or not
	 * @return boolean whether the save succeeded or not
	 */
	public function save($runValidation = true) {
		if ($runValidation && !$this->validate()) {
			return false;
		}
		$dir = $this->getInstallationDirectory();
		if (!file_exists($dir) || !is_dir($dir)) {
			mkdir($dir);
		}
		$filename = $dir."/package.json";
		$json = function_exists("json_encode") ? json_encode($this->toJSON()) : CJSON::encode($this->toJSON());
		$json = AJSON::prettyPrint($json);
		return file_put_contents($filename,$json) ? true : false;
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
		$attributes["dependencies"] = array();
		foreach($this->getDependencies() as $dependency) {
			$attributes['dependencies'][] = $dependency->name;
		}
		return $attributes;
	}
	/**
	 * Gets an array of broken dependency names
	 * @return array an array of broken dependency names
	 */
	public function getBrokenDependencies() {
		$broken = array();
		foreach($this->getDependencies() as $name => $dependency) {
			if (is_array($dependency)) {
				$broken[] = $name;
			}
			elseif ($dependency->url == "") {
				print_r($dependency->attributes);
				$broken[] = $name;
			}
		}
		return $broken;
	}

	/**
	 * Finds the packages that this package depends on
	 * @param boolean $forceRefresh whether to force a refresh of the package's dependencies or not
	 * @return array an array of package names that this package depends on
	 */
	public function getDependencies($forceRefresh = false) {
		if (!$forceRefresh && $this->_dependencies !== null) {
			return $this->_dependencies;
		}
		$options = array(
			"fileTypes" => array("php"),
			"exclude" => array(".svn",".git"),
		);
		$manager = $this->getRepository()->getManager();
		$files = CFileHelper::findFiles($this->getInstallationDirectory(),$options);
		$this->_dependencies = array();
		foreach($files as $file) {
			$contents = file_get_contents($file);
			if (preg_match_all("/packages\.\w+/ui",$contents,$matches)) {
				foreach($matches[0] as $match) {
					$match = substr($match,9);
					if ($match == $this->name) {
						continue;
					}
					if (isset($manager->getPackages()->{$match})) {
						$this->_dependencies[$match] = $manager->getPackages()->itemAt($match);
					}
					else {
						$this->_dependencies[$match] = array("name" => $match);
					}
				}
			}
		}
		return $this->_dependencies;
	}

	public function setDependencies($value) {
		$this->_dependencies = array();
		foreach($value as $name => $config) {
			if (is_string($config)) {
				$config = array("name" => $config);
				$name = $config;
			}
			else {
				$config = (array) $config;
			}
			$config['class'] = "APackage";
			$this->_dependencies[$name] = Yii::createComponent($config);
		}

	}
	/**
	 * Finds the packages that depend on this package
	 * @return APackage[]
	 */
	public function getDependents() {
		if ($this->_dependents !== null) {
			return $this->_dependents;
		}
		$options = array(
			"fileTypes" => array("php"),
			"exclude" => array(".svn",".git"),
		);
		$manager = $this->getRepository()->getManager();
		$this->_dependents = array();
		foreach($manager->getPackages() as $package) {
			if ($package->name == $this->name) {
				continue;
			}
			$files = CFileHelper::findFiles($package->getInstallationDirectory(),$options);
			foreach($files as $file) {
				$contents = file_get_contents($file);
				if (preg_match_all("/packages\.".$this->name."/ui",$contents,$matches)) {
					$this->_dependents[$package->name] = $package;
					continue 2;
				}
			}
		}
		return $this->_dependents;
	}
	/**
	 * Gets a list of errors to display for this package
	 * @param string $separator the separator for the errors, defaults to newline
	 * @return string the errors for this package
	 */
	public function listErrors($separator = "\n") {
		$errorList = array();
		foreach($this->getErrors() as $attribute => $errors) {
			if ($attribute != "") {
				$errorList[] = "Attribute: ".$attribute;
			}
			foreach($errors as $err) {
				$errorList[] = "\t".$err;
			}
		}
		return implode($separator,$errorList);
	}
}