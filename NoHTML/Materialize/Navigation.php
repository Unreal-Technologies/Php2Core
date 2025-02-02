<?php
namespace Php2Core\NoHTML\Materialize;

class Navigation extends Submenu
{
    #[\Override]
    public function __construct() 
    {
        parent::__construct();
    }
    
    /**
     * @param string $text
     * @param \Closure $callback
     * @return void
     */
    public function submenu(string $text, \Closure $callback): void
    {
        $sub = new Submenu();
        $this -> aChildren[] = [$text, $sub];
        $callback($sub);
    }
    
    /**
     * @param \Php2Core\NoHTML\IXhtml $container
     * @return void
     */
    public function navBar(\Php2Core\NoHTML\IXhtml $container): void
    {
        $links = $this -> toArray();
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
                            if($link === null)
                            {
                                $ul -> add('li', function(\Php2Core\NoHTML\XHtml $li)
                                {
                                    $li -> attributes() -> set('class', 'vertical-divider');
                                });
                                continue;
                            }
                            
                            $ul -> add('li', function(\Php2Core\NoHTML\XHtml $li) use($link, &$dropdownCounter, $container)
                            {
                                $li -> add('a', function(\Php2Core\NoHTML\XHtml $a) use($link, &$dropdownCounter, $container)
                                {
                                    list($text, $object, $target) = $link;
                                    
                                    $a -> text($text);

                                    if(is_array($object))
                                    {
                                       $a -> attributes() -> set('href', '#!');
                                       $a -> attributes() -> set('class', 'dropdown-trigger');
                                       $a -> attributes() -> set('data-target', 'dropdown'.$dropdownCounter);
                                       new \Php2Core\NoHTML\Materialize\Icon($a, 'arrow_drop_down');

                                       $dropLinks = $object;

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
                                                       list($text, $object, $target) = $link;
                                                       
                                                       $a -> text($text);
                                                       $a -> attributes() -> set('href', $object);
                                                       if($target !== null)
                                                       {
                                                           $a -> attributes() -> set('target', $target);
                                                       }
                                                   });
                                               });
                                           }
                                       });

                                       $dropdownCounter++;
                                    }
                                    else
                                    {
                                       $a -> attributes() -> set('href', $object);
                                       if($link[2] !== null)
                                       {
                                           $a -> attributes() -> set('target', $target);
                                       }
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
