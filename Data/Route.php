<?php
namespace Php2Core\Data;

class Route
{
    public const Routingmode_Raw = 'raw';
    public const Routingmode_Full = 'full';
    
    
    /**
     * @var string
     */
    private string $sMatch;
    
    /**
     * @var string
     */
    private string $sMode;
    
    /**
     * @var string
     */
    private string $sTarget;
    
    /**
     * @var string[]
     */
    private array $aParameters;
    
    /**
     * @var string[]
     */
    private array $aQueryString;
    
    /**
     * @param string $target
     * @param array $parameters
     * @param array $queryString
     */
    public function __construct(string $match, string $target, array $parameters, string $mode, array $queryString)
    {
        $this -> sMode = $mode;
        $this -> sMatch = $match;
        $this -> sTarget = $target;
        $this -> aParameters = $parameters;
        $this -> aQueryString = $queryString;
    }
    
    /**
     * @return string
     */
    public function mode(): string
    {
        return $this -> sMode;
    }
    
    /**
     * @return array
     */
    public function route(): array
    {
        $parts = explode(':', $this -> sMatch);
        return [
            'method' => $parts[0],
            'slug' => $parts[1]
        ];
    }
    
    /**
     * @return array
     */
    public function target(): array
    {
        $parts = explode('#', $this -> sTarget);
        
        return [
            'type' => $parts[0],
            'target' => implode('#', array_slice($parts, 1, count($parts) - 1))
        ];
    }
    
    /**
     * @return array
     */
    public function queryString(): array
    {
        return $this -> aQueryString;
    }
    
    /** 
     * @return array
     */
    public function parameters(): array
    {
        return $this -> aParameters;
    }
    
    /**
     * @return array
     */
    public function match(): array
    {
        $parts = explode('::', $this -> sMatch);
        
        return [
            'Method' => $parts[0],
            'Slug' => $parts[1]
        ];
    }
}
