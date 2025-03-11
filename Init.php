<?php
spl_autoload_register(function(string $className)
{
    $file = realpath(__DIR__.'/../'.str_replace('\\', '/', $className.'.php'));
    if($file !== false)
    {
        require_once($file);
    }
});

require_once('Php2Core.php');
Php2Core::initialize();

$xhtml = new Php2Core\NoHTML\Xhtml('<!DOCTYPE html>');
$head = $xhtml -> add('head');
$xhtml -> add('body');

$head -> add('script', function(\Php2Core\NoHTML\Xhtml $script)
{
    $script -> Attributes() -> Set('type', 'text/javascript');
    $script -> Attributes() -> Set('src', PHP2CORE -> physicalToRelativePath(__DIR__.'/NoHTML/Materialize/Form.js'));
});
