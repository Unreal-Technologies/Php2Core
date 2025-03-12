<?php
namespace Php2Core\GUI\NoHTML\Materialize\Form;

enum Methods: string
{
    use \Php2Core\Data\Collections\Enum\TInfo;
    
    case Get = 'get';
    case Post = 'post';
}