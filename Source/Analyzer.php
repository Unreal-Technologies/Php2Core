<?php
namespace Php2Core\Source;

class Analyzer
{
    // List of file extensions that will be highlighted in distribution analysis
    public const DistributionHighlights = ['.php', '.css', '.js', '.sql', '.xml', '.html', '.ini'];

    /**
     * @var string[] List of paths to exclude from analysis
     */
    private $excluded = [];

    /**
     * @var array File count distribution by extension
     */
    private $basicSourceDistributionByFile = [];

    /**
     * @var array File size distribution by extension
     */
    private $basicSourceDistributionBySize = [];

    /**
     * @var \Php2Core\IO\Directory|null Target directory for analysis
     */
    private $target = null;

    /**
     * @var ISourceAnalyzer[] List of source analyzers for specific file types
     */
    private $analyzers = [];

    /**
     * Constructor to initialize the Analyzer with a target directory, exclusion list, and distribution highlights
     *
     * @param \Php2Core\IO\Directory $target Target directory for analysis
     * @param \Php2Core\IO\IDiskManager[] $exclude List of directories or files to exclude
     * @param string[] $distributionHighlights List of file extensions to highlight in analysis
     */
    public function __construct(\Php2Core\IO\Directory $target, array $exclude = [], array $distributionHighlights = self::DistributionHighlights)
    {
        // Build the list of paths to exclude
        $actualExcludes = new \Php2Core\Data\Collections\Linq($exclude);
        $actualExcludes -> select(function(\Php2Core\IO\IDiskManager $object)
        {
            return $object -> path();
        });

        // Convert exclude list to an array of paths
        $excludes = $actualExcludes -> toArray();
        
        // Get the list of all files in the target directory excluding the specified paths
        $targets = $this -> getFileTreeExcluded($target, $excludes);

        // Initialize internal properties
        $this -> target = $target;
        $this -> excluded = $excludes;
        $this -> getBasicSourceDistribution($targets, $distributionHighlights);
        $this -> initializeSourceAnalyzers($targets);

        // Generate the report
        $this -> createReport();
    }

    /**
     * Generates and writes a report of the analysis to a file
     *
     * @return void
     */
    private function createReport(): void
    {
        $temp = PHP2CORE -> get(\PHP2CORE::Temp);
        $content = [];

        // Determine the maximum string length for formatting
        $maxStringLength = (new \Php2Core\Data\Collections\Linq($this -> basicSourceDistributionByFile))
            -> select(fn(int $value, string $key) => strlen($key))
            -> orderBy(fn(int $val) => $val, \Php2Core\Data\Collections\SortDirections::Desc)
            -> firstOrDefault();

        // Add analyzed directory to the report
        $content[] = 'Analyzed Directory:';
        $content[] = "\t".$this -> target -> path();
        $content[] = null;

        // Add excluded directories to the report
        $content[] = 'Excluded Directories:';
        foreach($this -> excluded as $excluded)
        {
            $content[] = "\t".$excluded;
        }
        $content[] = null;

        // File distribution by count
        $content[] = 'File Distribution:';
        $sumByFile = array_sum($this -> basicSourceDistributionByFile);
        foreach($this -> basicSourceDistributionByFile as $k => $v)
        {
            $percentage = $sumByFile === 0 ? 0 : ($v / $sumByFile) * 100;
            $content[] = "\t".str_pad($k, $maxStringLength, ' ').': '.str_pad(number_format($percentage, 2), 6, ' ', STR_PAD_LEFT).'% ('.$v.')';
        }
        $content[] = null;

        // File distribution by size
        $content[] = 'Size Distribution:';
        $sumBySize = array_sum($this -> basicSourceDistributionBySize);
        foreach($this -> basicSourceDistributionBySize as $k => $v)
        {
            $memory = \Php2Core\IO\Memory::fromInt($v);
            $percentage = $sumBySize === 0 ? 0 : ($v / $sumBySize) * 100;
            $content[] = "\t".str_pad($k, $maxStringLength, ' ').': '.str_pad(number_format($percentage, 2), 6, ' ', STR_PAD_LEFT).'% ('.$memory -> format().')';
        }

        // Write the final report to a text file
        $file = \Php2Core\IO\File::fromDirectory($temp, 'Analyzer.report.txt');
        $file -> write(implode("\r\n", $content));
    }
    
    /**
     * Initializes specific source analyzers based on file type.
     *
     * @param \Php2Core\IO\File[] $targets List of target files for analysis
     */
    private function initializeSourceAnalyzers(array $targets): void
    {
        $buffer = [];
        foreach($targets as $target)
        {
            // Detect file extension and prepare class name
            $extLC = strtolower($target -> extension());
            $extUCF = ucfirst($extLC);
            $cls = __NAMESPACE__.'\\Analyzers\\'.$extUCF.'Analyzer';
            $file = __DIR__.'/Analyzers/'.$extUCF.'Analyzer.php';
            
            // If the analyzer class exists, instantiate and add to buffer
            if(file_exists($file) && class_exists($cls))
            {
                if(!isset($buffer['.'.$extLC]))
                {
                    $buffer['.'.$extLC] = [];
                }
                $buffer['.'.$extLC][] = new $cls($target);
            }
        }
        
        $this -> analyzers = $buffer;
    }

    /**
     * Retrieves the complete file tree, excluding specified paths.
     *
     * @param \Php2Core\IO\Directory $directory Root directory to scan
     * @param \Php2Core\IO\IDiskManager[] $exclude Paths to exclude from the scan
     * @return string[] List of discovered file paths
     */
    private function getFileTreeExcluded(\Php2Core\IO\Directory $directory, array &$exclude): array
    {
        // Directories to always exclude (e.g., VCS or IDE directories)
        $baseExclude = ['.git', 'nbproject'];
        
        $buffer = [];
        
        // Loop through all contents of the directory
        foreach($directory -> list() as $content)
        {
            // Skip if it's in the exclude list
            if(!in_array($content -> path(), $exclude))
            {
                $name = strtolower($content -> name());
                
                // Automatically exclude specific system folders
                if(in_array($name, $baseExclude))
                {
                    $exclude[] = $content -> path();
                    continue;
                }

                // If it's a directory, recurse into it
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
}