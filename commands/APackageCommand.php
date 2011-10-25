<?php
Yii::import("packages.ypm.components.*");
Yii::import("packages.ypm.models.*");
/**
 * Allows command line access to Yii Package Manager commands
 *
 * @author Charles Pick
 * @package packages.ypm.commands
 */
class APackageCommand extends CConsoleCommand {
	/**
	 * The package manager to use
	 * @var APackageManager
	 */
	protected $_manager;

	public function actionIndex() {
		echo "HELLO!\n";
	}

	/**
	 * Creates a new package with the given name and optional description.
	 * Simple usage:
	 * <pre>
	 * ./yiic package create yourPackageName
	 * </pre>
	 * Specifying a description:
	 * <pre>
	 * ./yiic package create yourPackageName a description of the package goes here
	 * </pre>
	 * @param array $args the arguments passed to the command
	 * @param boolean $force whether to force creation of the package if it already exists
	 */
	public function actionCreate($args, $force = false) {
		$packageName = array_shift($args);
		if (!$packageName) {
			$this->usageError("No package name specified!");
		}
		$manager = $this->getManager();
		if (!$force && (isset($manager->getPackages()->{$packageName}) || $manager->find($packageName) !== false)) {
			echo "A package with this name already exists!\n";
			return;
		}
		$package = new APackage();
		$package->name = $packageName;
		$package->description = implode(" ",$args);
		$package->setRepositoryName("local");
		if (!$package->save(!$force)) {
			echo "There was a problem saving the package!\n";
		}

		echo "Package ".$packageName." was created.\n";
	}
	/**
	 * Sets a package attribute to the given value.
	 * <pre>
	 * ./yiic package set ypm author "Charles Pick"
	 * </pre>
	 * @param array $args an array of arguments passed to the function
	 */
	public function actionSet($args) {
		$packageName = array_shift($args);
		$attribute = array_shift($args);
		$value = implode(" ",$args);
		if (!$packageName) {
			$this->usageError("No package name specified!");
		}
		if (!$attribute) {
			$this->usageError("No attribute name specified!");
		}
		if ($value == "" && $value !== 0) {
			$this->usageError("No value specified!");
		}
		$manager = $this->getManager();
		$package = $manager->getPackages()->itemAt($packageName); /* @var APackage $package */
		if ($package === null) {
			echo "No package found with the name: '".$packageName."'\n";
			return;
		}
		$package->{$attribute} = $value;
		if ($package->save()) {
			echo "OK\n";
		}
		else {
			echo "Error setting value:\n";
			echo $package->listErrors();
		}
	}
	/**
	 * Gets a package attribute with the given name
	 * <pre>
	 * ./yiic package get ypm author
	 * </pre>
	 * @param array $args an array of arguments passed to the function
	 */
	public function actionGet($args) {
		$packageName = array_shift($args);
		$attribute = array_shift($args);
		if (!$packageName) {
			$this->usageError("No package name specified!");
		}
		if (!$attribute) {
			$this->usageError("No attribute name specified!");
		}
		$manager = $this->getManager();
		$package = $manager->getPackages()->itemAt($packageName); /* @var APackage $package */
		if ($package === null) {
			echo "No package found with the name: '".$packageName."'\n";
			return;
		}
		echo $package->{$attribute}."\n";
	}

	/**
	 * Gets a package attribute with the given name
	 * <pre>
	 * ./yiic package get ypm author
	 * </pre>
	 * @param array $args an array of arguments passed to the function
	 */
	public function actionPublish($args) {
		$packageName = array_shift($args);
		if (!$packageName) {
			$this->usageError("No package name specified!");
		}
		$manager = $this->getManager();
		$package = $manager->getPackages()->itemAt($packageName); /* @var APackage $package */
		if ($package === null) {
			echo "No package found with the name: '".$packageName."'\n";
			return;
		}
		$remotes = $package->getGitRepository()->getRemotes();
		if (!isset($remotes['origin'])) {
			echo "No remote git repository is set for this package, make sure you've added a remote called 'origin'\n";
			return;
		}
		if ($package->url == "") {
			$package->url = $remotes['origin']->pushUrl;
		}
		$package->save();
		$git = $package->getGitRepository(); /** @var AGitRepository $git */
		$git->commit("Published package with Yii Package Manager",true);
		echo $git->push("origin","master");
	}

	/**
	 * Finds a package with the given name
	 * @param $args an array of arguments passed to the function
	 */
	public function actionFind($args) {
		$packageName = array_shift($args);
		if (!$packageName) {
			$this->usageError("No package name specified!");
		}
		$manager = $this->getManager();
		$package = $manager->find($packageName);
		if ($package === false) {
			echo "No package found with the name: '".$packageName."'\n";
			return;
		}
		echo str_repeat("-",70)."\n";
		echo "Package Name: ".$package->name."\n";
		echo "Description:  ".$package->description."\n";
		echo "URL:          ".$package->url."\n";
		echo "Repository:   ".$package->getRepositoryName()."\n";
		echo str_repeat("-",70)."\n";
	}
	/**
	 * Installs a package with the given name
	 * @param $args an array of arguments passed to the function
	 */
	public function actionInstall($args) {
		$packageName = array_shift($args);
		if (!$packageName) {
			$this->usageError("No package name specified!");
		}
		$manager = $this->getManager();
		$package = $manager->find($packageName);

		if (!$manager->install($package)) {
			echo "There was a problem installing this package.\n";
			echo $package->listErrors()."\n";
			return;
		}
		echo $package->name." was installed successfully\n";
	}
	/**
	 * Uninstalls a package with the given name
	 * @param $args an array of arguments passed to the function
	 */
	public function actionUninstall($args) {
		$packageName = array_shift($args);
		if (!$packageName) {
			$this->usageError("No package name specified!");
		}
		$manager = $this->getManager();
		$package = $manager->getPackages()->itemAt($packageName); /* @var APackage $package */
		if ($package === null) {
			echo "No package found with the name: '".$packageName."'\n";
			return;
		}
		if (!$manager->uninstall($packageName)) {
			echo "There was a problem uninstalling this package:\n";
			echo $package->listErrors()."\n";
			return;
		}
		echo $package->name." was uninstalled successfully\n";
	}

	/**
	 * Provides the command description.
	 * This method may be overridden to return the actual command description.
	 * @return string the command description. Defaults to 'Usage: php entry-script.php command-name'.
	 */
	public function getHelp() {
		$help='Usage: '.$this->getCommandRunner()->getScriptName().' '.$this->getName();
		$options=$this->getOptionHelp();
		if(empty($options))
			return $help;
		if(count($options)===1)
			return $help.' '.$options[0];
		$help.=" <action>\nActions:\n";
		foreach($options as $option)
			$help.='    '.$option."\n";
		return $help;
	}

	/**
	 * Sets the package manager
	 * @param APackageManager $manager The package manager
	 */
	public function setManager($manager)
	{
		$this->_manager = $manager;
	}

	/**
	 * Gets the package manager
	 * @return APackageManager The package manager
	 */
	public function getManager()
	{
		if ($this->_manager === null) {
			$this->_manager = Yii::app()->packageManager;
		}
		return $this->_manager;
	}
}