<?php
namespace Php2Core\NoHTML\Materialize\Form;

enum InputTypes: string
{
    use \Php2Core\Collections\Enum\TInfo;
    
    case Text = 'text';
    case Password = 'password';
    case YesNo = 'yes-no';
}