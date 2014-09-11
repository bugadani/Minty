Using Minty
========

This chapter describes how to use Minty in your projects.

Getting started
--------

### Installation

Minty can be installed using [Composer](http://getcomposer.org). To get composer, please refer to
the Composer documentation [here](https://getcomposer.org/doc/00-intro.md).

To install Minty, simply open a command line to the working directory, then execute the following
command (substitute <version> with an appropriate version number, or `dev-master` for the current developer snapshot):

    composer require bugadani/minty <version>

### Loading Minty

Using the Composer installation method, Minty can be loaded using the Composer class loader.
To do this, simply add the following to your `index.php` file (or any scripts you want to use Minty in):

    require_once 'vendor/autoload.php';

Setting up the Environment
--------

The Environment class is the central element of Minty. This class stores configuration and extensions
and it is used to display templates.

    use Minty\Environment;
    use Minty\TemplateLoaders\FileLoader;

    //Assuming installation using Composer
    require_once 'vendor/autoload.php';

    //This line assumes that templates have the .tpl file extension
    $loader = new FileLoader('path/to/template/directory', '.tpl');
    $env = new Environment($loader, ['cache' => 'path/to/template/cache']);

    //Add the standard and optimizing extensions
    $env->addExtension(new Core());
    $env->addExtension(new Optimizer());

For details on the available environment options, please see [this page](environment_options.md).

Registering global variables
--------

Sometimes it is necessary to register certain variables to be accessible in every template. This
can be done by using `addGlobalVariable`.

    $env->addGlobalVariable('variableName', 'variableValue');

Displaying templates
--------

After setting up a suitable Environment for your project, you can use `render` to display templates.

    $env->render('index.tpl', ['some' => 'variables', 'passed' => 'to the template']);

This will automatically load and display `index.tpl`.
