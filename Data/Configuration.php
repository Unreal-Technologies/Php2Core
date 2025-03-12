<?php
namespace Php2Core\Data;

class Configuration 
{
    /**
     * @var array
     */
    private array $aData = [];
    
    /**
     * @param array $basic
     */
    public function __construct(array $basic)
    {
        $this -> aData = $basic;
    }
    
    /**
     * @param IO\Xml\Document $xml
     * @return void
     */
    public function extend(IO\Xml\Document $xml): void
    {
        $routes = $xml -> Search('/^routes$/i')[0];
        $default = $routes -> Search('/^default/i')[0] -> Text();
        foreach($routes -> Search('/^route$/i') as $route)
        {
            $match = $route -> Search('/^match$/i')[0] -> Text();
            $target = $route -> Search('/^target$/i')[0] -> Text();
            $method = $route -> Search('/^method$/i')[0] -> Text();
            
            $this -> aData['Routes'][$match] = [strtoupper($method), $target];
        }
        $this -> aData['DefaultRoute'] = $default;
    }
    
    /**
     * @param string $path
     * @return string|array
     */
    public function get(string $path): mixed
    {
        $parts = explode('/', $path);
        $current = $this -> aData;
        
        foreach($parts as $part)
        {
            $current = $current[$part];
        }
        
        return $current;
    }
}
