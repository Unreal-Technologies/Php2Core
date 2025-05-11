<?php
namespace Php2Core\Source\Analyzers;

class PhpAnalyzer implements \Php2Core\Source\ISourceAnalyzer
{
    /**
     * @var string|null
     */
    private ?string $target = null;
    
    /**
     * @var string|null
     */
    private ?string $namespace = null;
    
    /**
     * @var Components\Class_[]
     */
    private array $classes = [];
    
    /**
     * @var Components\Method[]
     */
    private array $methods = [];
    
    /**
     * @param array $tokens
     * @param int $start
     * @param int $tokenType
     * @param int $max
     * @return string|null
     */
    private function tokenScan(array $tokens, int $start, int $tokenType, int $max=10): ?string
    {
        $maxSearch = min(count($tokens), $start + $max);
        
        for($i=$start; $i<$maxSearch; $i++)
        {
            if($tokens[$i][0] === $tokenType)
            {
                return $tokens[$i][1];
            }
        }
        return null;
    }
    
    /**
     * @param \Php2Core\IO\IFile $target
     */
    #[\Override]
    public function __construct(\Php2Core\IO\IFile $target)
    {
        $this -> target = $target -> path();
        $source = $target -> read();
        $tokens = token_get_all($source);
        
        if($tokens === false || count($tokens) === 0)
        {
            throw new \Php2Core\Data\Exceptions\NotImplementedException('No Tokens');
        }
        
        $inNamespace = false;
        $inClass = false;
        $classData = null;
        
        foreach($tokens as $idx => $token)
        {
            $tType = is_array($token) ? $token[0] : null;
            $tName = $tType !== null ? token_name($tType) : null;
            $tValue = is_array($token) ? $token[1] : $token;
            $tLine = is_array($token) ? $token[2] : null;
            
            if($inNamespace)
            {
                if($inNamespace && $tType === 265)
                {
                    $this -> namespace = $tValue;
                    $inNamespace = false;
                }
            }
            else if($inClass)
            {
                if($classData === null && $tType === 262)
                {
                    $classData = new Components\Class_($tValue);
                }
                else if($classData !== null && $tType === 338)
                {
                    $implements = $this -> tokenScan($tokens, $idx + 1, 263);
                    if($implements !== null)
                    {
                        $classData -> implements($implements);
                    }
                }
                else if($classData !== null && $tType === 337)
                {
                    $extends = $this -> tokenScan($tokens, $idx + 1, 263);
                    if($extends !== null)
                    {
                        $classData -> extends($extends);
                    }
                }
                
                if($tValue === '{')
                {
                    $inClass = false;
                    $this -> classes[] = $classData;
                    $classData = null;
                }
            }
            else
            {
                if($tType === 339)
                {
                    $inNamespace = true;
                }
                else if($tType === 333)
                {
                    $inClass = true;
                }
            }
            
            echo '<xmp>';
            var_dumP($tType);
            var_dumP($tName);
            var_dumP($tValue);
            var_dumP($tLine);
            echo '</xmp>';
        }
        
//        echo '<xmp>';
//        print_r($target);
//        print_r($tokens);
//        echo '</xmp>';
    }
}