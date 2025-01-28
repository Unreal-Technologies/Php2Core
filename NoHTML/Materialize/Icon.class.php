<?php
namespace Php2Core\NoHTML\Materialize;

class Icon
{
    /**
     * @param \Php2Core\NoHTML\XHtml $obj
     * @param string $icon
     */
    function __construct(\Php2Core\NoHTML\XHtml $obj, string $icon) 
    {
        $obj -> Add('i', function(\Php2Core\NoHTML\XHtml $i) use($icon)
        {
            $i -> Attributes() -> Set('class', 'material-icons right');
            $i -> Text($icon);
        });
    }
}
