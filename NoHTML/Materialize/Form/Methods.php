<?php
namespace Php2Core\NoHTML\Materialize\Form;

enum Methods: string
{
    use \Php2Core\Collections\Enum\TInfo;
    
    case Get = 'get';
    case Post = 'post';
}