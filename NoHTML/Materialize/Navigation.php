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
        $dropdownCounter = 0;
        
        $container -> add('div@.navbar-fixed/nav/div@.nav-wrapper/ul@.left hide-on-med-and-down&#nav-mobile', function(\Php2Core\NoHTML\Xhtml $ul) use($links, &$dropdownCounter, $container)
        {
            foreach($links as $link)
            {
                if($link === null)
                {
                    $ul -> add('li@.vertical-divider');
                    continue;
                }

                $ul -> add('li/a', function(\Php2Core\NoHTML\XHtml $a) use($link, &$dropdownCounter, $container)
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

                       $container -> add('ul@.dropdown-content&#dropdown'.$dropdownCounter, function(\Php2Core\NoHTML\XHtml $ul) use($dropLinks)
                       {
                            foreach($dropLinks as $link)
                            {
                                if($link === null)
                                {
                                    $ul -> add('li@.divider');
                                    continue;
                                }

                                $ul -> add('li/a', function(\Php2Core\NoHTML\XHtml $a) use($link)
                                {
                                    list($text, $object, $target) = $link;

                                    $a -> text($text);
                                    $a -> attributes() -> set('href', $object);
                                    if($target !== null)
                                    {
                                        $a -> attributes() -> set('target', $target);
                                    }
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
            }
        });
        
        if($dropdownCounter !== 0)
        {
            $container -> add('script', function(\Php2Core\NoHTML\XHtml $script)
            {
                $script -> attributes() -> set('type', 'text/javascript');
                $script -> text('$(document).ready(function() 
{
$(".dropdown-trigger").dropdown({ constrainWidth: false });
});');
            });
        }
    }
}
