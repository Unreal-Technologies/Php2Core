<?php
namespace Php2Core;

class Configuration 
{
    /**
     * @var array
     */
    private array $_data = [];
    
    /**
     * @param array $basic
     */
    public function __construct(array $basic)
    {
        $this -> _data = $basic;
    }
    
    /**
     * @param IO\Xml\Document $xml
     * @return void
     */
    public function Extend(IO\Xml\Document $xml): void
    {
        $routes = $xml -> Search('/^routes$/i')[0];
        $default = $routes -> Search('/^default/i')[0] -> Text();
        foreach($routes -> Search('/^route$/i') as $route)
        {
            $match = $route -> Search('/^match$/i')[0] -> Text();
            $target = $route -> Search('/^target$/i')[0] -> Text();
            $method = $route -> Search('/^method$/i')[0] -> Text();
            
            $this -> _data['Routes'][$match] = [strtoupper($method), $target];
        }
        $this -> _data['DefaultRoute'] = $default;
    }
    
    /**
     * @param string $path
     * @return string|array
     */
    public function Get(string $path): mixed
    {
        $parts = explode('/', $path);
        $current = $this -> _data;
        
        foreach($parts as $part)
        {
            $current = $current[$part];
        }
        
        return $current;
    }
}
