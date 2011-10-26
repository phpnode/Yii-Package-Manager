# Warning - Pre Alpha, don't use it yet!

# Yii Package Manager

A package manager for Yii based on git, you can use it to install and publish reusable Yii extensions.
Things like upgrading and installing dependencies are managed for you.


# Installing YPM

Installation is somewhat awkward at the moment and a script will be provided in future.
For now, follow these instructions:

1. Ensure you have git installed.
2. Create a packages directory in your application folder. This is where your installed packages will live.
3. In your packages folder, run the following git commands:
<pre>
git clone git://github.com/phpnode/YiiGit.git git
git clone git://github.com/phpnode/YiiCurl.git curl
git clone git://github.com/phpnode/Yii-Package-Manager.git ypm
</pre>
4. In your main application config (config/main.php), add the packages alias and configure the package manger:
<pre>
"aliases" => array(
	"packages" => dirname(__FILE__)."/packages",
	...
),
"components" => array(
	"packageManager" => array(
		"class" => "packages.ypm.components.APackageManager",
	),
	...
),
</pre>

5. In your console application config (config/console.php), add the packages alias, configure the package manager and add the ypm command.
<pre>
"aliases" => array(
	"packages" => dirname(__FILE__)."/packages",
	...
),
"commandMap" => array(
	"ypm" => array(
		"class" => "packages.ypm.commands.APackageCommand",
	),
),
"components" => array(
	"packageManager" => array(
		"class" => "packages.ypm.components.APackageManager",
	),
	...
),
</pre>

6. Finish installation by running the following command:
<pre>
./yiic package configure ypm
</pre>

