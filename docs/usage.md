Using Minty
========

This chapter describes how to use Minty in your projects.

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

Displaying templates
--------

After setting up a suitable Environment for your project, you can use `render` to display templates.

    $env->render('index.tpl', ['some' => 'variables', 'passed' => 'to the template']);

This will automatically load and display `index.tpl`.

Registering global variables
--------

Sometimes it is necessary to register certain variables to be accessible in every template. This
can be done by using `addGlobalVariable`.

    $env->addGlobalVariable('variableName', 'variableValue');
