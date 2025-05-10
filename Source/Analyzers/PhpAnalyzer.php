<?php
namespace Php2Core\Source\Analyzers;

class PhpAnalyzer implements \Php2Core\Source\ISourceAnalyzer
{
    /**
     * @param \Php2Core\IO\IFile $target
     */
    #[\Override]
    public function __construct(\Php2Core\IO\IFile $target)
    {
        $source = $target -> read();
        $tokens = token_get_all($source);
        
        if($tokens === false || count($tokens) === 0)
        {
            throw new \Php2Core\Data\Exceptions\NotImplementedException('No Tokens');
        }
        
        foreach($tokens as $token)
        {
            $tType = is_array($token) ? $token[0] : null;
            $tName = $tType !== null ? token_name($tType) : null;
            $tValue = is_array($token) ? $token[1] : $token;
            $tLine = is_array($token) ? $token[2] : null;
            
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