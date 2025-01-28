<?php
namespace Php2Core;

class Route
{
    /**
     * @var string
     */
    private string $_target;
    
    /**
     * @var string[]
     */
    private array $_parameters;
    
    /**
     * @var string[]
     */
    private array $_queryString;
    
    /**
     * @param string $target
     * @param array $parameters
     * @param array $queryString
     */
    public function __construct(string $target, array $parameters, array $queryString)
    {
        $this -> _target = $target;
        $this -> _parameters = $parameters;
        $this -> _queryString = $queryString;
    }
}