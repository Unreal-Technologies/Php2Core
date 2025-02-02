<?php
namespace Php2Core\NoHTML\FontAwesome;

class Icon
{
    /**
     * @param \Php2Core\NoHTML\IXhtml $container
     * @param string $icon
     */
    public function __construct(\Php2Core\NoHTML\IXhtml $container, string $icon) 
    {
        $container -> Add('i', function(\Php2Core\NoHTML\IXhtml $i) use($icon)
        {
            $i -> Attributes() -> Set('class', $icon);
        });
    }
}
