<?php

namespace Php2Core\IO;

class Directory implements IDirectory
{
    /**
     * @var string
     */
    private $sPath;

    /**
     * @var bool
     */
    private $bExists;

    /**
     * @var resource
     */
    private $rHandler;

    /**
     * @var IDiskManager[]
     */
    private static $aRam;

    /**
     * @param  string $sDir
     * @return Directory
     */
    public static function fromString(string $sDir): Directory
    {
        //Initialize
        return new Directory($sDir);
    }

    /**
     * @param IDirectory $oDirectory
     * @return void
     * @throws \Core\Exceptions\NotImplementedException
     */
    #[\Override]
    public function copyTo(IDirectory $oDirectory): void
    {
        if(!$oDirectory -> exists())
        {
            $oDirectory -> create();
        }
        foreach($this -> list() as $oDiskManager)
        {
            if($oDiskManager instanceof IDirectory)
            {
                throw new \Core\Exceptions\NotImplementedException('Sub directory copying');
            }
            else if($oDiskManager instanceof IFile)
            {
                $oDiskManager -> copyTo($oDirectory);
            }
        }
    }
    
    /**
     * @return bool
     */
    #[\Override]
    public function remove(): bool
    {
        //Check if exists
        if (!$this -> exists()) {
            return false;
        }
        
        //remove directory
        return rmdir($this -> sPath);
    }

    /**
     * @param IDirectory $oDir
     * @param string $sName
     * @return Directory|null
     */
    public static function fromDirectory(IDirectory $oDir, string $sName): ?Directory
    {
        //Check if exists
        if (!$oDir -> exists()) {
            return null;
        }
        return self::fromString($oDir -> path() . '/' . $sName);
    }

    /**
     * @return Directory
     */
    #[\Override]
    public function parent(): Directory
    {
        //Split directory path;
        $aParts = preg_split('/[\/|\\\]+/', $this -> sPath);
        
        //slice last part of
        $aBuffer = array_slice($aParts, 0, count($aParts) - 1);
        
        //Initialize
        return self::fromString(implode('/', $aBuffer));
    }

    /**
     * @param  string $sRegex
     * @return bool
     */
    #[\Override]
    public function contains(string $sRegex): bool
    {
        //loop through all content
        foreach ($this -> list() as $iDiskManager) {
            
            //Check if name matches
            if (preg_match($sRegex, $iDiskManager -> name())) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return string
     */
    #[\Override]
    public function name(): string
    {
        //segment path with \ or /
		$aSegments = preg_split('/[\/|\\\]+/', $this -> sPath);
        
        return $aSegments[count($aSegments) - 1];
    }

    /**
     * @return string
     */
    #[\Override]
    public function path(): string
    {
        return $this -> sPath;
    }

    /**
     * @return bool
     */
    #[\Override]
    public function exists(): bool
    {
        return $this -> bExists;
    }

    /**
     * @return bool
     */
    #[\Override]
    public function create(): bool
    {
        //Check if not exists
        if (!$this -> exists()) {
            
            //Create directory
            mkdir($this -> sPath, 0777, true);
            
            //set path & exists flag
            $this -> sPath = realpath($this -> sPath);
            $this -> bExists = true;
            return true;
        }
        return false;
    }

    /**
     * @param string $sRegex
     * @param bool $bRefresh
     * @return array
     */
    #[\Override]
    public function list(string $sRegex = null, bool $bRefresh = false): array
    {
        //set key
        $sKey = $this -> sPath;
        
        //Check if directory is cached
        if (isset(self::$aRam[$sKey]) && !$bRefresh) {
            return self::$aRam[$sKey];
        }

        $aOutput = [];
        //Open direcory
        if ($this -> open()) {

            //set output
            $sOut = null;

            //Read through
            while ($this -> read($sOut) !== false) {

                //skip directory fallback
                if ($sOut === '.' || $sOut === '..') {
                    continue;
                }

                //if regex is set, match only on regex
                if ($sRegex !== null && !preg_match($sRegex, $sOut)) {
                    continue;
                }

                //compose output path
                $sPath = $this -> sPath . '/' . $sOut;

                //determine what kind of output
                if (is_dir($sPath)) {
                    $aOutput[] = self::fromString($sPath);
                } else {
                    $aOutput[] = File::fromString($sPath);
                }
            }

            //Close directory
            $this -> close();
        }
        
        //Set cache
        self::$aRam[$sKey] = $aOutput;

        //Output
        return $aOutput;
    }

    /**
     * @param  string|null $out
     * @return bool
     */
    #[\Override]
    public function read(?string &$out): bool
    {
        $out = readdir($this -> rHandler);
        if ($out === false) {
            $out = null;
            return false;
        }
        return true;
    }

    /**
     * @return bool
     */
    #[\Override]
    public function open(): bool
    {
        //Check if handler is set or not exists
        if ($this -> rHandler !== null || !$this -> bExists) {
            return false;
        }
        
        //open directory
        $this -> rHandler = opendir($this -> sPath);
        
        //Check handler
        if ($this -> rHandler === false) {
            $this -> rHandler = null;
            return false;
        }
        return true;
    }

    /**
     * @return void
     */
    #[\Override]
    public function close(): void
    {
        if ($this -> rHandler !== null) {
            closedir($this -> rHandler);
        }
    }

    /**
     * @param  string $sDir
     * @throws \Exception
     */
    private function __construct(string $sDir)
    {
        if (self::$aRam === null) 
		{
            self::$aRam = [];
        }
        
        $this -> sPath = $sDir;
        $this -> bExists = file_exists($sDir) || is_dir($sDir);
		
        if ($this -> bExists) 
		{
            $this -> sPath = realpath($sDir);
            if (!is_dir($this -> sPath))
			{
                throw new \Exception($this -> sPath . ' is not a ' . get_class($this));
            }
        }
    }
}
