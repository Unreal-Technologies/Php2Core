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
     * @param array $tokens
     * @param int $skip
     * @return void
     */
    private function parse(array $tokens, int $skip = 0): void
    {
        $inNamespace = false;
        $pos = $skip;
        $count = count($tokens);
        
        while($pos < $count)
        {
            $token = $tokens[$pos];
            
            $tType = is_array($token) ? $token[0] : null;
            $tName = $tType !== null ? token_name($tType) : null;
            $tValue = is_array($token) ? $token[1] : $token;
            $tLine = is_array($token) ? $token[2] : null;
            
            if($inNamespace)
            {
                if($tType === 265)
                {
                    $this -> namespace = $tValue;
                }
                else if($this -> namespace !== null && $tValue === ';')
                {
                    $inNamespace = false;
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
                    $class = new Components\Class_($this -> namespace, $tokens, $pos);
                    $this -> classes[] = $class;
                    $pos = $class -> end();
                }
            }
            
//            echo '<xmp>';
//            var_dumP($tType);
//            var_dumP($tName);
//            var_dumP($tValue);
//            var_dumP($tLine);
//            echo '</xmp>';
            
            $pos++;
        }
        echo '<hr />';
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
        
        $this -> parse($tokens);
    }
}