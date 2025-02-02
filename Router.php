<?php
namespace Php2Core;

class Router 
{
    /**
     * @var string
     */
    private string $sInput = '';
    
    /**
     * @var string[]
     */
    private array $aQuerystring = [];
    
    /**
     * @var string[]
     */
    private array $aRoutes = [];
    
    /**
     * @param string $default
     */
    public function __construct(string $default) 
    {
        $composedUrl = $default;
        if(isset($_SERVER['REDIRECT_URL']))
        {
            $composedUrl = $_SERVER['CONTEXT_DOCUMENT_ROOT'].$_SERVER['REDIRECT_URL'];
        }

        $slug = str_ireplace(str_replace('\\', '/', ROOT), '', $composedUrl);
        if($slug[0] === '/')
        {
            $slug = substr($slug, 1);
        }
        
        $this -> aQuerystring = $_GET;
        
        $this -> sInput = $slug;
    }
    
    /**
     * @return string
     */
    public function slug(): string
    {
        return $this -> sInput;
    }
    
    /**
     * @return Route|null
     */
    public function match(): ?Route
    {
        $method = $_SERVER['REQUEST_METHOD'];

        foreach(array_keys($this -> aRoutes) as $route)
        {
            $regex = str_replace('/', '\\/', preg_replace('/\{.+\}/U', '.+', $route));
            
            if(preg_match('/'.$regex.'/i', $method.'::'.$this -> sInput))
            {
                $iComponents = explode('/', $this -> sInput);
                $rComponents = explode('/', $route);
                
                if(count($iComponents) === count($rComponents))
                {
                    $parameters = [];
                    foreach($rComponents as $idx => $component)
                    {
                        if(preg_match('/^\{.+\}$/i', $component))
                        {
                            $parameters[substr($component, 1, -1)] = $iComponents[$idx];
                        }
                    }
                    
                    return new Route($route, $this -> aRoutes[$route], $parameters, $this -> aQuerystring);
                }
            }
        }
        return null;
    }
    
    /**
     * @param string $route
     * @param string $target
     * @return void
     */
    public function register(string $route, string $target): void
    {
        $this -> aRoutes[$route] = $target;
    }
}
