<?php
namespace Php2Core\GUI\NoHTML\Materialize\Form;

class Options
{
    /**
     * @var \Php2Core\GUI\NoHTML\Materialize\Columns
     */
    private \Php2Core\GUI\NoHTML\Materialize\Columns $size;
    
    /**
     * @var \Php2Core\GUI\NoHTML\Materialize\Columns|null
     */
    private ?\Php2Core\GUI\NoHTML\Materialize\Columns $offset;
    
    /**
     * @var int|null
     */
    private ?int $min = null;
    
    /**
     * @var int|null
     */
    private ?int $max = null;
    
    /**
     * @var float|null
     */
    private ?float $step = null;
    
    /**
     * @var SelectOptions|null
     */
    private ?SelectOptions $options = null;
    
    /**
     */
    protected function __construct()
    {
        $this -> size = \Php2Core\GUI\NoHTML\Materialize\Columns::S12;
        $this -> offset = null;
    }
    
    /**
     * @param SelectOptions|null $value
     * @param bool $clear
     * @return SelectOptions|null
     */
    public function options(?SelectOptions $value = null, bool $clear = false): ?SelectOptions
    {
        if($value !== null || $clear)
        {
            $this -> options = $value;
        }
        return $this -> options;
    }
    
    /**
     * @param int|null $value
     * @param bool $clear
     * @return int|null
     */
    public function min(?int $value = null, bool $clear = false): ?int
    {
        if($value !== null || $clear)
        {
            $this -> min = $value;
        }
        return $this -> min;
    }
    
    /**
     * @param int|null $value
     * @param bool $clear
     * @return int|null
     */
    public function max(?int $value=null, bool $clear = false): ?int
    {
        if($value !== null || $clear)
        {
            $this -> max = $value;
        }
        return $this -> max;
    }
    
    /**
     * @param int|null $value
     * @param bool $clear
     * @return int|null
     */
    public function step(?float $value=null, bool $clear = false): ?float
    {
        if($value !== null || $clear)
        {
            $this -> step = $value;
        }
        return $this -> step;
    }
    
    /**
     * @param \Php2Core\GUI\NoHTML\Materialize\Columns $value
     * @param bool $clear
     * @return \Php2Core\GUI\NoHTML\Materialize\Columns|null
     */
    public function offset(\Php2Core\GUI\NoHTML\Materialize\Columns $value = null, bool $clear = false): ?\Php2Core\GUI\NoHTML\Materialize\Columns
    {
        if($value !== null || $clear)
        {
            $this -> offset = $value;
        }
        return $this -> offset;
    }
    
    /**
     * @param \Php2Core\GUI\NoHTML\Materialize\Columns $value
     * @return \Php2Core\GUI\NoHTML\Materialize\Columns
     */
    public function size(\Php2Core\GUI\NoHTML\Materialize\Columns $value = null): \Php2Core\GUI\NoHTML\Materialize\Columns
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