<?php
namespace Php2Core\NoHTML\Materialize;

class Icon
{
    /**
     * @param \Php2Core\NoHTML\IXhtml $container
     * @param string $icon
     */
    function __construct(\Php2Core\NoHTML\IXhtml $container, string $icon) 
    {
        $container -> add('i', function(\Php2Core\NoHTML\IXhtml $i) use($icon)
        {
            $i -> attributes() -> set('class', 'material-icons right');
            $i -> text($icon);
        });
    }
}
