<?php
namespace Php2Core\GUI\NoHTML\Materialize;

class Icon
{
    /**
     * @param \Php2Core\GUI\NoHTML\IXhtml $container
     * @param string $icon
     */
    function __construct(\Php2Core\GUI\NoHTML\IXhtml $container, string $icon) 
    {
        $container -> add('i', function(\Php2Core\GUI\NoHTML\IXhtml $i) use($icon)
        {
            $i -> attributes() -> set('class', 'material-icons right');
            $i -> text($icon);
        });
    }
}
