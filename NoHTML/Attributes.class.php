<?php
namespace Php2Core\NoHTML;

class Attributes 
{
    /**
     * @var array
     */
    private array $aChildren = [];
    
    /**
     */
    public function __construct()
    {
        
    }
    
    /**
     * @param string $name
     * @param string $value
     * @return void
     */
    public function set(string $name, string $value): void
    {
        $this -> aChildren[$name] = $value;
    }
    
    /**
     * @return string
     */
    public function __toString(): string 
    {
        if($this -> count() === 0)
        {
            return '';
        }
        
        $buffer = [];
        foreach($this -> aChildren as $k => $v)
        {
            $buffer[] = $k.'="'.str_replace(['"', '\\'], ['\"', '\\\\'], $v).'"';
        }
        return ' '.implode(' ', $buffer);
    }
    
    /**
     * @return void
     */
    public function clear(): void
    {
        $this -> aChildren = [];
    }
    
    /**
     * @return int
     */
    public function count(): int
    {
        return count($this -> aChildren);
    }
}
