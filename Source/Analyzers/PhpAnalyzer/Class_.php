<?php
namespace Php2Core\Source\Analyzers\PhpAnalyzer;

class Class_
{
    private ?string $namespace = null;
    
    /**
     * @var int
     */
    private int $start = -1;
    
    /**
     * @var int
     */
    private int $end = -1;
    
    /**
     * @var bool
     */
    private bool $isAbstract = false;
    
    /**
     * @var string
     */
    private ?string $name = null;
    
    /**
     * @var array
     */
    private array $implements = [];
    
    /**
     * @var string|null
     */
    private ?string $extends = null;
    
    /**
     * @var Method[]
     */
    private array $methods = [];
    
    /**
     * @param string $namespace
     * @param array $tokens
     * @param int $start
     */
    public function __construct(string $namespace, array $tokens, int $start)
    {
        $this -> namespace = $namespace;
        $this -> start = $start;
        $isClass = false;
        
        $depth = 0;
        for($i=$this -> start; $i<count($tokens); $i++)
        {
            $token = $tokens[$i];
            
            $tType = is_array($token) ? $token[0] : null;
            $tName = $tType !== null ? token_name($tType) : null;
            $tValue = is_array($token) ? $token[1] : $token;
            $tLine = is_array($token) ? $token[2] : null;
            
            if($depth === 0 && $tType === 333)
            {
                $isClass = true;
                $this -> getClassTags($tokens, $i);
            }
            
            if($tValue === '{')
            {
                $depth++;
            }
            
            if($depth > 0 && $isClass) 
            {
                //Parse Current Class Tokens
                
//                echo '<xmp>';
//                var_dump($i);
//                var_dumP($tType);
//                var_dumP($tName);
//                var_dumP($tValue);
//                var_dumP($tLine);
//                echo '</xmp>';
            }
            
            if($tValue === '}')
            {
                $depth--;
                
                if($isClass && $depth === 0)
                {
                    $this -> end = $i;
                    break;
                }
            }
        }
    }
    
    /**
     * @param array $tokens
     * @param int $pos
     * @return void
     */
    private function getClassTags(array $tokens, int $pos): void
    {
        //Get the Abstract tag if exists
        $start = max(0, $pos - 10);
        
        for($i=$start; $i<$pos; $i++)
        {
            $token = $tokens[$i];
            
            $tType = is_array($token) ? $token[0] : null;

            if($tType === 322)
            {
                $this -> isAbstract = true;
                break;
            }
        }

        //Get Class Name
        $end = min(count($tokens), $pos + 10);
        $extImpPos = 0;
        
        for($i=$pos; $i<$end; $i++)
        {
            $token = $tokens[$i];
            
            $tType = is_array($token) ? $token[0] : null;
            $tValue = is_array($token) ? $token[1] : $token;
            
            if($tType === 262)
            {
                $this -> name = $tValue;
                $extImpPos = $i;
                break;
            }
        }
        
        //Get Class Extend & Implementations
        $inExtend = false;
        $inImplement = false;
        for($i=$extImpPos; $i<count($tokens); $i++)
        {
            $token = $tokens[$i];
            
            $tType = is_array($token) ? $token[0] : null;
            $tValue = is_array($token) ? $token[1] : $token;
            
            if($tValue === '{')
            {
                break;
            }
            
            if($tType === 337)
            {
                $inExtend = true;
                $inImplement = false;
            }
            
            if($tType === 338)
            {
                $inImplement = true;
                $inExtend = false;
            }
            
            if($inExtend || $inImplement)
            {
                $target = null;
                if($tType === 263)
                {
                    $target = $tValue;
                }
                else if($tType === 262)
                {
                    $target = '\\'.$this -> namespace.'\\'.$tValue;
                }
                
                if($inExtend && $target !== null)
                {
                    $this -> extends = $target;
                    $inExtend = false;
                }
                
                if($inImplement && $target !== null)
                {
                    $this -> implements[] = $target;
                }
            }
        }
    }
    
    /**
     * @return string|null
     */
    public function extends(): ?string
    {
        return $this -> extends;
    }
    
    /**
     * @return string[]
     */
    public function implements(): array
    {
        return $this -> implements;
    }
    
    /**
     * @return string
     */
    public function name(): string
    {
        return $this -> name;
    }
    
    /**
     * @return int
     */
    public function start(): int
    {
        return $this -> start;
    }
    
    /**
     * @return int
     */
    public function end(): int
    {
        return $this -> end;
    }
}