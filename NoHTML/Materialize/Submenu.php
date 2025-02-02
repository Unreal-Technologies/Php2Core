<?php
namespace Php2Core\NoHTML\Materialize;

class Submenu
{
    /**
     * @var mixed[]
     */
    protected array $aChildren = [];
    
    /**
     */
    public function __construct() 
    {
    }
    
    /**
     * @param string $text
     * @param string $link
     * @param string $target
     * @return void
     */
    public function link(string $text, string $link, string $target=null): void
    {
        $this -> aChildren[] = [$text, $link, $target];
    }
    
    /**
     * @return void
     */
    public function seperator(): void
    {
        $this -> aChildren[] = null;
    }
    
    /**
     * @return array
     */
    public function toArray(): array
    {
        $buffer = [];
        foreach($this -> aChildren as $child)
        {
            if($child !== null && $child[1] instanceof Submenu)
            {
                $buffer[] = [$child[0], $child[1] -> toArray(), null];
                continue;
            }
            
            $buffer[] = $child;
        }
        
        return $buffer;
    }
}
