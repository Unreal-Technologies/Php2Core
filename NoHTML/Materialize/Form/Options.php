<?php
namespace Php2Core\NoHTML\Materialize\Form;

class Options
{
    /**
     * @var \Php2Core\NoHTML\Materialize\Columns
     */
    private \Php2Core\NoHTML\Materialize\Columns $size;
    
    /**
     * @var \Php2Core\NoHTML\Materialize\Columns|null
     */
    private ?\Php2Core\NoHTML\Materialize\Columns $offset;
    
    /**
     */
    protected function __construct()
    {
        $this -> size = \Php2Core\NoHTML\Materialize\Columns::S12;
        $this -> offset = null;
    }
    
    /**
     * @param \Php2Core\NoHTML\Materialize\Columns $value
     * @param bool $clear
     * @return \Php2Core\NoHTML\Materialize\Columns|null
     */
    public function offset(\Php2Core\NoHTML\Materialize\Columns $value = null, bool $clear = false): ?\Php2Core\NoHTML\Materialize\Columns
    {
        if($value !== null || $clear)
        {
            $this -> offset = $value;
        }
        return $this -> offset;
    }
    
    /**
     * @param \Php2Core\NoHTML\Materialize\Columns $value
     * @return \Php2Core\NoHTML\Materialize\Columns
     */
    public function size(\Php2Core\NoHTML\Materialize\Columns $value = null): \Php2Core\NoHTML\Materialize\Columns
    {
        if($value !== null)
        {
            $this -> size = $value;
        }
        return $this -> size;
    }
    
    /**
     * @return Options
     */
    public static function Default(): Options
    {
        return new Options();
    }
}