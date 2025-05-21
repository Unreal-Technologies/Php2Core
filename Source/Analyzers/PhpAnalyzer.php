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
     * @var Components\Interface_[]
     */
    private array $interfaces = [];
    
    /**
     * @var Components\Method[]
     */
    private array $methods = [];
    
    /**
     * @var PhpAnalyzer\Constant[]
     */
    private array $constants = [];
    
    
    /**
     * @var PhpAnalyzer\Enum[]
     */
    private array $enums = [];
   
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
            $tName = $tType !== null ? PhpAnalyzer\Tokens::getToken($tType) : null;
            $tValue = is_array($token) ? $token[1] : $token;
            $tLine = is_array($token) ? $token[2] : null;
            
            if($inNamespace)
            {
                if($tType === PhpAnalyzer\Tokens::T_NAME_QUALIFIED)
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
                if($tType === PhpAnalyzer\Tokens::T_NAMESPACE)
                {
                    $inNamespace = true;
                }
                else if($tType === PhpAnalyzer\Tokens::T_CLASS)
                {
                    $class = new PhpAnalyzer\Class_($this -> namespace, $tokens, $pos);
                    $this -> classes[] = $class;
                    $pos = $class -> end();
                    
                    if($pos < 0)
                    {
                        break;
                    }
                }
            }
            
//            \Php2Core::dUmp($tType, $tName, $tValue, $tLine);
            
            $pos++;
        }
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