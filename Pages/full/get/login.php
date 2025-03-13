<?php
XHTML -> get('body', function(Php2Core\GUI\NoHTML\Xhtml $body)
{
    $body -> get('div@.section/h6', function(\Php2Core\GUI\NoHTML\Xhtml $h6)
    {
        $h6 -> clear();
        $h6 -> text('Login');
    });

    $form = new \Php2Core\GUI\NoHTML\Materialize\Form($body, Php2Core\GUI\NoHTML\Materialize\Form\Methods::Post);
    $form -> field('username', 'Username', \Php2Core\GUI\NoHTML\Materialize\Form\InputTypes::Text, '', true);
    $form -> field('password', 'Password', \Php2Core\GUI\NoHTML\Materialize\Form\InputTypes::Password, '', true);
    $form -> submit('Login', function(Php2Core\GUI\NoHTML\Materialize\Form\Options $options)
    {
        $options -> size(Php2Core\GUI\NoHTML\Materialize\Columns::S1);
        $options -> offset(Php2Core\GUI\NoHTML\Materialize\Columns::S11);
    }) -> parent() -> attributes() -> set('style', 'text-align: right;');
});