<?php
$xhtml = new Php2Core\NoHTML\Xhtml('<!DOCTYPE html>');
$head = $xhtml -> add('head');
$xhtml -> add('body');

$head -> add('script', function(\Php2Core\NoHTML\Xhtml $script)
{
    $script -> Attributes() -> Set('type', 'text/javascript');
    $script -> Attributes() -> Set('src', self::PhysicalToRelativePath(__DIR__.'/Materialize/Form.js'));
});
