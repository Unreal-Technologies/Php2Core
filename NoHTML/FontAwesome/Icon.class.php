<?php
namespace Php2Core\NoHTML\FontAwesome;

class Icon
{
    /**
     * @param \Php2Core\NoHTML\XHtml $obj
     * @param string $icon
     */
    public function __construct(\Php2Core\NoHTML\XHtml $obj, string $icon) 
    {
        $obj -> Add('i', function(\Php2Core\NoHTML\XHtml $i) use($icon)
        {
            $i -> Attributes() -> Set('class', $icon);
        });
    }
}
