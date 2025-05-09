<?php
namespace Php2Core\Source;

class Analyzer
{
    public const DistributionHighlights = ['.php', '.css', '.js', '.sql', '.xml', '.html', '.ini'];
    
    /**
     * @var string[]
     */
    private $excluded = [];
    
    /**
     * @var array
     */
    private $basicSourceDistributionByFile = [];
    
    /**
     * @var array
     */
    private $basicSourceDistributionBySize = [];
    
    /**
     * @var \Php2Core\IO\Directory|null
     */
    private $target = null;
    
    /**
     * @param \Php2Core\IO\Directory $target
     * @param \Php2Core\IO\IDiskManager[] $exclude
     * @param string[] $distributionHighlights
     */
    public function __construct(\Php2Core\IO\Directory $target, array $exclude = [], array $distributionHighlights = self::DistributionHighlights)
    {
        //Create Exclude List
        $actualExcludes = new \Php2Core\Data\Collections\Linq($exclude);
        $actualExcludes -> select(function(\Php2Core\IO\IDiskManager $object)
        {
            return $object -> path();
        });
        
        //List all Files excluding exclude list
        $excludes = $actualExcludes -> toArray();
        $targets = $this -> getFileTreeExcluded($target, $excludes);
        
        //set internal data
        $this -> target = $target;
        $this -> excluded = $excludes;
        $this -> getBasicSourceDistribution($targets, $distributionHighlights);
        $this -> initializeSourceAnalyzers($targets);
        
        //Output report
        $this -> createReport();
    }
    
    private function createReport()
    {
        $temp = PHP2CORE -> get(\PHP2CORE::Temp);
        $content = [];
        
        $maxStringLength = (new \Php2Core\Data\Collections\Linq($this -> basicSourceDistributionByFile)) -> select(function(int $value, string $key)
        {
            return strlen($key);
        }) -> orderBy(function(int $val)
        {
            return $val;
        }, \Php2Core\Data\Collections\SortDirections::Desc) -> firstOrDefault();
        
        //Write current directory
        $content[] = 'Analyzed Directory:';
        $content[] = "\t".$this -> target -> path();
        $content[] = null;
        
        //Write Excluded Directories
        $content[] = 'Excluded Directories:';
        foreach($this -> excluded as $excluded)
        {
            $content[] = "\t".$excluded;
        }
        $content[] = null;
        
        //Create basic File Distribution
        $content[] = 'File Distribution:';
        $sumByFile = array_sum($this -> basicSourceDistributionByFile);
        foreach($this -> basicSourceDistributionByFile as $k => $v)
        {
            $percentage = ($v / $sumByFile) * 100;
            $content[] = "\t".str_pad($k, $maxStringLength, ' ').': '.str_pad(number_format($percentage, 2), 6, ' ', STR_PAD_LEFT).'% ('.$v.')';
        }
        $content[] = null;
        
        //Create basic Size Distribution
        $content[] = 'Size Distribution:';
        $sumBySize = array_sum($this -> basicSourceDistributionBySize);
        foreach($this -> basicSourceDistributionBySize as $k => $v)
        {
            $memory = \Php2Core\IO\Memory::fromInt($v);
            $percentage = ($v / $sumBySize) * 100;
            $content[] = "\t".str_pad($k, $maxStringLength, ' ').': '.str_pad(number_format($percentage, 2), 6, ' ', STR_PAD_LEFT).'% ('.$memory -> format().')';
        }
        
        $file = \Php2Core\IO\File::fromDirectory($temp, 'Analyzer.report.txt');
        $file -> write(implode("\r\n", $content));
    }
    
    private function initializeSourceAnalyzers(array $targets): void
    {
//        echo '<xmp>';
//        print_r($targets);
//        echo '</xmp>';
    }
    
    /**
     * @param \Php2Core\IO\IDiskManager[] $targets
     * @param string[] $distributionHighlights
     * @return void
     */
    private function getBasicSourceDistribution(array $targets, array $distributionHighlights): void
    {
        //Setup buffer
        $buffer1 = ['other' => 0];
        foreach($distributionHighlights as $extension)
        {
            $buffer1[strtolower($extension)] = 0;
        }
        ksort($buffer1);
        
        //Clone buffer
        $buffer2 = $buffer1;
        
        //Loop through file targets
        foreach($targets as $target)
        {
            $ext = strtolower('.'.$target -> extension());
            $extTarget = isset($buffer1[$ext]) ? $ext : 'other';
            
            if($target instanceof \Php2Core\IO\File)
            {
                $buffer2[$extTarget] += $target -> size();
            }
            
            $buffer1[$extTarget]++;
        }
        
        //Set data
        $this -> basicSourceDistributionByFile = $buffer1;
        $this -> basicSourceDistributionBySize = $buffer2;
    }
    
    /**
     * @param \Php2Core\IO\Directory $directory
     * @param \Php2Core\IO\IDiskManager[] $exclude
     * @return string[]
     */
    private function getFileTreeExcluded(\Php2Core\IO\Directory $directory, array &$exclude): array
    {
        //Loop through all directories recursive and exclude files from exclude list
        //Auto exclude .git & nbproject directories (Repository / IDE) when found
        $baseExclude = ['.git', 'nbproject'];
        
        $buffer = [];
        foreach($directory -> list() as $content)
        {
            if(!in_array($content -> path(), $exclude))
            {
                $name = strtolower($content -> name());
                if(in_array($name, $baseExclude))
                {
                    $exclude[] = $content -> path();
                    continue;
                }

                if($content instanceof \Php2Core\IO\Directory)
                {
                    $buffer = array_merge($buffer, $this -> getFileTreeExcluded($content, $exclude));
                }
                else
                {
                    $buffer[] = $content;
                }
            }
        }

        return $buffer;
    }
}