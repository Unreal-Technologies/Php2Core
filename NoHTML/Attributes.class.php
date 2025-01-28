<?php
namespace Php2Core\NoHTML;

class Attributes 
{
    /**
     * @var array
     */
    private array $_children = [];
    
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
    public function Set(string $name, string $value): void
    {
        $this -> _children[$name] = $value;
    }
    
    /**
     * @return string
     */
    public function __toString(): string 
    {
        if($this -> Count() === 0)
        {
            return '';
        }
        
        $buffer = [];
        foreach($this -> _children as $k => $v)
        {
            $buffer[] = $k.'="'.str_replace(['"', '\\'], ['\"', '\\\\'], $v).'"';
        }
        return ' '.implode(' ', $buffer);
    }
    
    /**
     * @return void
     */
    public function Clear(): void
    {
        $this -> _children = [];
    }
    
    /**
     * @return int
     */
    public function Count(): int
    {
        return count($this -> _children);
    }
}
