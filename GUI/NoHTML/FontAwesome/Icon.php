<?php
namespace Php2Core\GUI\NoHTML\FontAwesome;

class Icon
{
    /**
     * @param \Php2Core\GUI\NoHTML\IXhtml $container
     * @param string $icon
     */
    public function __construct(\Php2Core\GUI\NoHTML\IXhtml $container, string $icon) 
    {
        $container -> Add('i', function(\Php2Core\GUI\NoHTML\IXhtml $i) use($icon)
        {
            $i -> Attributes() -> Set('class', $icon);
        });
    }
}
