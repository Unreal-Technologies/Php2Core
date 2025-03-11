<?php
namespace Php2Core\Php2Core;

class Core
{
    /**
     * @var type
     */
    private $data = [];
    
    /**
     * @param \Closure $cb
     */
    public function __construct(\Closure $cb) 
    {
        $cb($this);
    }
    
    /**
     * @param CoreProperties $property
     * @param mixed $value
     */
    public function set(\Php2Core\CoreProperties $property, mixed $value)
    {
        $this -> data[$property -> value] = $value;
    }
    
    /**
     * @param CoreProperties $property
     * @return mixed
     */
    public function get(\Php2Core\CoreProperties $property): mixed
    {
        if(isset($this -> data[$property -> value]))
        {
            return $this -> data[$property -> value];
        }
        return null;
    }
}