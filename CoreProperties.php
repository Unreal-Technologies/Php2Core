<?php
namespace Php2Core;

require_once('Php2Core/Collections/Enum/TInfo.php');

enum CoreProperties: string
{
    use \Php2Core\Collections\Enum\TInfo;

    case Temp = 'temp';
    case Cache = 'cache';
    case Start = 'start';
    case Root = 'root';
    case Version = 'version';
    case Configuration = 'configuration';
    case Debug = 'debug';
    case Title = 'title';
}