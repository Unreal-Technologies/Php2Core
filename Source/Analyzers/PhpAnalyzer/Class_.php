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
     * @var Member[]
     */
    private array $members = [];
    
    /**
     * @var Constant[]
     */
    private array $constants = [];
    
    /**
     * @param string $namespace
     * @param array $tokens
     * @param int $start
     */
    public function __construct(?string $namespace, array $tokens, int $start)
    {
        $this -> namespace = $namespace;
        $this -> start = $start;
        $isClass = false;
        
        $position = $this -> start;
        $count = count($tokens);
        $depth = 0;
        
        while($position < $count)
        {
            $token = $tokens[$position];
            
            $tType = is_array($token) ? $token[0] : null;
            $tValue = is_array($token) ? $token[1] : $token;
            
            if($depth === 0 && $tType === Tokens::T_CLASS)
            {
                $isClass = true;
                $this -> getClassTags($tokens, $position);
            }
            
            if($tValue === '{')
            {
                $depth++;
            }
            
            if($depth > 0 && $isClass) 
            {
                $skip = $this -> analyzeClassContent($tokens, $position);
                $position += $skip;
            }
            
            if($tValue === '}')
            {
                $depth--;
                
                if($isClass && $depth === 0)
                {
                    $this -> end = $position;
                    break;
                }
            }
            
            $position++;
        }
    }
    
    /**
     * @param array $tokens
     * @param int $position
     * @return int
     */
    private function analyzeClassContent(array $tokens, int $position): int
    {
        $depth = 0;
        $pos = $position;
        $count = count($tokens);
        
        while($pos < $count)
        {
            $token = $tokens[$pos];
            
            $tValue = is_array($token) ? $token[1] : $token;
            
            if($tValue === '{')
            {
                $depth++;
            }
            
            if($tValue === '}')
            {
                $depth--;
                
                if($depth === 0)
                {
                    break;
                }
            }
            
            if($depth < 0)
            {
                return 0;
            }
            $pos++;
        }

        return $pos - $position;
    }

    /**
     * @param array $tokensD
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

            if($tType === Tokens::T_ABSTRACT)
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
            
            if($tType === Tokens::T_STRING)
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
            
            if($tType === Tokens::T_EXTENDS)
            {
                $inExtend = true;
                $inImplement = false;
            }
            
            if($tType === Tokens::T_IMPLEMENTS)
            {
                $inImplement = true;
                $inExtend = false;
            }
            
            if($inExtend || $inImplement)
            {
                $target = null;
                if($tType === Tokens::T_NAME_FULLY_QUALIFIED)
                {
                    $target = $tValue;
                }
                else if($tType === Tokens::T_STRING)
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