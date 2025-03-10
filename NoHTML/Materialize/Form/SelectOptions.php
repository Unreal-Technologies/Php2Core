<?php
namespace Php2Core\NoHTML\Materialize\Form;

class SelectOptions
{
    /**
     * @var array
     */
    private array $data;
    
    /**
     */
    public function __construct()
    {
        $this -> data = [];
    }
    
    /**
     * @param string $text
     * @param string $value
     * @param bool $isSelected
     * @return void
     */
    public function set(string $text, string $value, bool $isSelected): void
    {
        $this -> data[] = [
            'text' => $text,
            'value' => $value,
            'selected' => $isSelected
        ];
    }
    
    /**
     * @return array
     */
    public function data(): array
    {
        return $this -> data;
    }
}