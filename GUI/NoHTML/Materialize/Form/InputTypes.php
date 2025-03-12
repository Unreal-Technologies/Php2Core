<?php
namespace Php2Core\GUI\NoHTML\Materialize\Form;

enum InputTypes: string
{
    use \Php2Core\Data\Collections\Enum\TInfo;
    
    case Text = 'text';
    case Password = 'password';
    case YesNo = 'yes-no';
    case Number = 'number';
    case Select = 'select';
}