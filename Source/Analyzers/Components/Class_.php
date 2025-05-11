<?php
namespace Php2Core\Source\Analyzers\Components;

class Class_
{
    /**
     * @var string
     */
    private ?string $name = null;
    
    /**
     * @var string|null
     */
    private ?string $implements = null;
    
    /**
     * @var string|null
     */
    private ?string $extends = null;
    
    /**
     * @var Method[]
     */
    private array $methods = [];
    
    /**
     * @param string $name
     */
    public function __construct(string $name)
    {
        $this -> name = $name;
    }
    
    /**
     * @return string
     */
    public function name(): string
    {
        return $this -> name;
    }
    
    /**
     * @param string $value
     * @return string|null
     */
    public function implements(string $value = null): ?string
    {
        if($value !== null)
        {
            $this -> implements = $value;
        }
        return $this -> implements;
    }
    
    /**
     * @param string $value
     * @return string|null
     */
    public function extends(string $value = null): ?string
    {
        if($value !== null)
        {
            $this -> extends = $value;
        }
        return $this -> extends;
    }
}