<?php
namespace Php2Core\NoHTML\Materialize;

class Navigation
{
    /**
     * @param array $links
     */
    public function __construct(\Php2Core\NoHTML\XHtml $container, array $links) 
    {
        $dropdownCounter = 0;
        $container -> Add('div', function(\Php2Core\NoHTML\XHtml $div) use($container, $links)
        {
            $div -> Attributes() -> Set('class', 'navbar-fixed');
            $div -> Add('nav', function(\Php2Core\NoHTML\XHtml $nav) use($links, &$dropdownCounter, $container)
            {
                $nav -> Add('div', function(\Php2Core\NoHTML\XHtml $div) use($links, &$dropdownCounter, $container)
                {
                    $div -> Attributes() -> Set('class', 'nav-wrapper');
                    $div -> Add('ul', function(\Php2Core\NoHTML\XHtml $ul) use($links, &$dropdownCounter, $container)
                    {
                         $ul -> Attributes() -> Set('id', 'nav-mobile');
                         $ul -> Attributes() -> Set('class', 'left hide-on-med-and-down');

                         foreach($links as $link)
                         {
                             $ul -> Add('li', function(\Php2Core\NoHTML\XHtml $li) use($link, &$dropdownCounter, $container)
                             {
                                 $li -> Add('a', function(\Php2Core\NoHTML\XHtml $a) use($link, &$dropdownCounter, $container)
                                 {
                                     $a -> Text($link[0]);

                                     if(is_array($link[1]))
                                     {
                                        $a -> Attributes() -> Set('href', '#!');
                                        $a -> Attributes() -> Set('class', 'dropdown-trigger');
                                        $a -> Attributes() -> Set('data-target', 'dropdown'.$dropdownCounter);
                                        new Icon($a, 'arrow_drop_down');
                                        
                                        $dropLinks = $link[1];

                                        $container -> Add('ul', function(\Php2Core\NoHTML\XHtml $ul) use($dropdownCounter, $dropLinks)
                                        {
                                            $ul -> Attributes() -> Set('class', 'dropdown-content');
                                            $ul -> Attributes() -> Set('id', 'dropdown'.$dropdownCounter);

                                            foreach($dropLinks as $link)
                                            {
                                                if($link === null)
                                                {
                                                    $ul -> Add('li', function(\Php2Core\NoHTML\XHtml $li)
                                                    {
                                                        $li -> Attributes() -> Set('class', 'divider');
                                                    });
                                                    continue;
                                                }

                                                $ul -> Add('li', function(\Php2Core\NoHTML\XHtml $li) use($link)
                                                {
                                                    $li -> Add('a', function(\Php2Core\NoHTML\XHtml $a) use($link)
                                                    {
                                                        $a -> Text($link[0]);
                                                        $a -> Attributes() -> Set('href', $link[1]);
                                                    });
                                                });
                                            }
                                        });

                                        $dropdownCounter++;
                                     }
                                     else
                                     {
                                         $a -> Attributes() -> Set('href', $link[1]);
                                     }
                                 });
                             });
                         }
                    });
                }); 
            });

            if($dropdownCounter !== 0)
            {
                $container -> Add('script', function(\Php2Core\NoHTML\XHtml $script)
                {
                    $script -> Attributes() -> Set('type', 'text/javascript');
                    $script -> Text('$(document).ready(function() 
{
    $(".dropdown-trigger").dropdown();
});');
                });
                
            }
        });
    }
}
