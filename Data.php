<?php
namespace Php2Core;

abstract class Data 
{
    /**
     * @var array
     */
    private array $aData;
      
    /**
     */
    protected function __construct(array $aList = null)
    {
        //Initialize
        $this -> aData = [];
        
        //Check if list is empty
        if($aList !== null)
        {
            //set list
            $this -> aData = $aList;
        }
    }
    
    /**
     * @return array
     */
    public function toArray(): array
    {
        return $this -> aData;
    }
    
    /**
     * @param string $sName
     * @param mixed $mValue
     * @return void
     */
    protected function set(string $sName, mixed $mValue): void
    {
        //Set Variable in array
        $this -> aData[$sName] = $mValue;
    }
    
    /**
     * @param string $sName
     * @return mixed
     */
    public function get(string $sName): mixed
    {
        //Check if value is set
        if(isset($this -> aData[$sName]))
        {
            //return value
            return $this -> aData[$sName];
        }
        
        return null;
    }
}
