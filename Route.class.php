<?php
namespace Php2Core;

class Route
{
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
    public function __construct(string $target, array $parameters, array $queryString)
    {
        $this -> sTarget = $target;
        $this -> aParameters = $parameters;
        $this -> aQueryString = $queryString;
    }
}