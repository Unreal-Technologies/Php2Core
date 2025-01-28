<?php
namespace Php2Core;

class Router 
{
    /**
     * @var string
     */
    private string $_input = '';
    
    /**
     * @var string[]
     */
    private array $_querystring = [];
    
    /**
     * @var string[]
     */
    private array $_routes = [];
    
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
        
        $this -> _querystring = $_GET;
        
        $this -> _input = $slug;
    }
    
    /**
     * @return Route|null
     */
    public function Match(): ?Route
    {
        $method = $_SERVER['REQUEST_METHOD'];

        foreach(array_keys($this -> _routes) as $route)
        {
            $regex = str_replace('/', '\\/', preg_replace('/\{.+\}/U', '.+', $route));
            
            if(preg_match('/'.$regex.'/i', $method.'::'.$this -> _input))
            {
                $iComponents = explode('/', $this -> _input);
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
                    
                    return new Route($this -> _routes[$route], $parameters, $this -> _querystring);
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
    public function Register(string $route, string $target): void
    {
        $this -> _routes[$route] = $target;
    }
}
