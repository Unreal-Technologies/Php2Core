<?php
spl_autoload_register(function(string $className)
{
    $file = realpath(__DIR__.'/../'.str_replace('\\', '/', $className.'.php'));
    if($file !== false)
    {
        require_once($file);
        return;
    }
    PHP2CORE -> trace();
    throw new \Exception('Could not find class "'.$className.'"');
});

require_once('Php2Core.php');
Php2Core::initialize();

$xhtml = new Php2Core\GUI\NoHTML\Xhtml('<!DOCTYPE html>');
$head = $xhtml -> add('head');
$xhtml -> add('body');

$head -> add('script', function(\Php2Core\GUI\NoHTML\Xhtml $script)
{
    $script -> Attributes() -> Set('type', 'text/javascript');
    $script -> Attributes() -> Set('src', PHP2CORE -> physicalToRelativePath(__DIR__.'/GUI/NoHTML/Materialize/Form.js'));
});
