<?php
namespace Php2Core\NoHTML\Materialize;

class Navigation
{
    /**
     * @param array $links
     */
    public function __construct(\Php2Core\NoHTML\XHtml $container, array $links) 
    {
        $container -> add('div', function(\Php2Core\NoHTML\XHtml $div) use($container, $links)
        {
            $dropdownCounter = 0;
            
            $div -> attributes() -> set('class', 'navbar-fixed');
            $div -> add('nav', function(\Php2Core\NoHTML\XHtml $nav) use($links, &$dropdownCounter, $container)
            {
                $nav -> add('div', function(\Php2Core\NoHTML\XHtml $div) use($links, &$dropdownCounter, $container)
                {
                    $div -> attributes() -> set('class', 'nav-wrapper');
                    $div -> add('ul', function(\Php2Core\NoHTML\XHtml $ul) use($links, &$dropdownCounter, $container)
                    {
                         $ul -> attributes() -> set('id', 'nav-mobile');
                         $ul -> attributes() -> set('class', 'left hide-on-med-and-down');

                         foreach($links as $link)
                         {
                             $ul -> add('li', function(\Php2Core\NoHTML\XHtml $li) use($link, &$dropdownCounter, $container)
                             {
                                 $li -> add('a', function(\Php2Core\NoHTML\XHtml $a) use($link, &$dropdownCounter, $container)
                                 {
                                     $a -> text($link[0]);

                                     if(is_array($link[1]))
                                     {
                                        $a -> attributes() -> set('href', '#!');
                                        $a -> attributes() -> set('class', 'dropdown-trigger');
                                        $a -> attributes() -> set('data-target', 'dropdown'.$dropdownCounter);
                                        new Icon($a, 'arrow_drop_down');
                                        
                                        $dropLinks = $link[1];

                                        $container -> add('ul', function(\Php2Core\NoHTML\XHtml $ul) use($dropdownCounter, $dropLinks)
                                        {
                                            $ul -> attributes() -> set('class', 'dropdown-content');
                                            $ul -> attributes() -> set('id', 'dropdown'.$dropdownCounter);

                                            foreach($dropLinks as $link)
                                            {
                                                if($link === null)
                                                {
                                                    $ul -> add('li', function(\Php2Core\NoHTML\XHtml $li)
                                                    {
                                                        $li -> attributes() -> set('class', 'divider');
                                                    });
                                                    continue;
                                                }

                                                $ul -> add('li', function(\Php2Core\NoHTML\XHtml $li) use($link)
                                                {
                                                    $li -> add('a', function(\Php2Core\NoHTML\XHtml $a) use($link)
                                                    {
                                                        $a -> text($link[0]);
                                                        $a -> attributes() -> set('href', $link[1]);
                                                    });
                                                });
                                            }
                                        });

                                        $dropdownCounter++;
                                     }
                                     else
                                     {
                                         $a -> attributes() -> set('href', $link[1]);
                                     }
                                 });
                             });
                         }
                    });
                }); 
            });

            if($dropdownCounter !== 0)
            {
                $container -> add('script', function(\Php2Core\NoHTML\XHtml $script)
                {
                    $script -> attributes() -> set('type', 'text/javascript');
                    $script -> text('$(document).ready(function() 
{
    $(".dropdown-trigger").dropdown();
});');
                });
                
            }
        });
    }
}
