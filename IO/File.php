<?php

namespace Php2Core\IO;

class File implements IFile
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
     * @var mixed
     */
    private mixed $oHandler;

    /**
     * @param string $name
     * @return void
     */
    public function forceDownload(string $name): void
    {
        if(!$this -> bExists)
        {
            return;
        }
        $type = mime_content_type($this -> sPath);
        
        header('Content-Description: File Transfer');
        header('Content-Type: '.$type);
        header('Content-Disposition: attachment; filename='.$name);
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($this -> sPath));
        ob_clean();
        flush();
        readfile($this -> sPath);
        exit;
    }
    
    /**
     * @param string $sPath
     * @return IFile
     */
    #[\Override]
    public static function fromString(string $sPath): IFile
    {
        //get current class
        $sCls = get_called_class();
        
        //Initialize current class (could be other that file)
        return new $sCls($sPath);
    }

    /**
     * @param IFile $oFile
     * @return IFile
     */
    #[\Override]
    public static function fromFile(IFile $oFile): IFile
    {
        //Initialize from file
        return self::fromString($oFile -> sPath());
    }

    /**
     * @return Common\IXmlFile|null
     */
    #[\Override]
    public function asXml(): ?Common\IXmlFile
    {
        //Is current a XML file
        if ($this -> extension() !== 'xml') {
            return null;
        }
        return new Common\Xml($this -> sPath);
    }

    /**
     * @return Common\IDtdFile|null
     */
    #[\Override]
    public function asDtd(): ?Common\IDtdFile
    {
        //Is current a DTD file
        if ($this -> extension() !== 'dtd') {
            return null;
        }
        return new Common\Dtd($this -> sPath);
    }

    /**
     * @return bool
     */
    #[\Override]
    public function remove(): bool
    {
        //check if exists
        if (!$this -> bExists) {
            return false;
        }
        
        //remove file
        return unlink($this -> sPath);
    }

    /**
     * @param IDirectory $oDir
     * @return string|null
     * @throws \Exception
     */
    #[\Override]
    public function relativeTo(IDirectory $oDir): ?string
    {
        //Check relativity of path
        if (stristr($this -> sPath, $oDir -> path())) {
            return substr($this -> sPath, strlen($oDir -> path()) + 1);
        }

        throw new \Exception('Not implemented');
    }

    /**
     * @param Directory $oDir
     * @param string $sName
     * @return bool
     */
    #[\Override]
    public function copyTo(IDirectory $oDir, string $sName = null): bool
    {
        //Check if file exists
        if (!$oDir -> exists()) {
            return false;
        }
        
        //if no name defined, use current name
        if ($sName === null) {
            $sName = $this -> name();
        }
        
        //Get Full Path
        $sFull = $oDir -> path() . '/' . $sName;
        
        //Get Parent Directory of Full Path
        $oDirectory = self::fromString($sFull) -> parent();
        
        //Check if parent Directory exists, if not create
        if(!$oDirectory ->exists())
        {
            $oDirectory -> create();
        }

        //copy file
        return copy($this -> path(), $sFull);
    }

    /**
     * @param string $sStream
     * @return void
     */
    #[\Override]
    public function write(string $sStream, bool $bCreateDirectory = true): void
    {
        if($bCreateDirectory)
        {
            $oParent = $this -> parent();
            if(!$oParent -> exists())
            {
                $oParent -> create();
            }
        }
        
        //Write file content
        file_put_contents($this -> path(), $sStream);
        
        //set existence
        $this -> bExists = true;
        
        //set new path
        $this -> sPath = realpath($this -> path());
    }

    /**
     * @return IDirectory|null
     */
    #[\Override]
    public function parent(): ?IDirectory
    {
        //Determine parent path
        $aParts = explode('/', str_replace('\\', '/', $this -> sPath));
        unset($aParts[count($aParts) - 1]);
        
        //Check if not empty
        if (count($aParts) === 0) {
            return null;
        }
        
        //Compose new path
        $sNew = implode('/', $aParts);
        
        //Initialize directory
        return Directory::fromString($sNew);
    }

    /**
     * @return string
     */
    #[\Override]
    public function read(): string
    {
        //return file contents
        return file_get_contents($this -> sPath);
    }

    /**
     * @return bool
     */
    public function fClose(): bool
    {
        if($this -> oHandler === null)
        {
            return false;
        }
        return fclose($this -> oHandler);
    }
    
    /**
     * @param int $length
     * @return string|null
     */
    public function fRead(int $length): ?string
    {
        if($this -> oHandler === null)
        {
            return null;
        }
        
        $res = fread($this -> oHandler, $length);
        if($res === false)
        {
            return null;
        }
        return $res;
    }
    
    /**
     * @param string $mode
     * @param bool $useIncludePath
     * @param type $context
     * @return bool
     */
    public function fOpen(string $mode, bool $useIncludePath = false, $context = null): bool
    {
        $fh = fopen($this -> sPath, $mode, $useIncludePath, $context);
        if($fh !== false)
        {
            $this -> oHandler = $fh;
            return true;
        }
        return false;
    }
    
    /**
     * @param IDirectory $oDir
     * @param string $sName
     * @return IFile|null
     */
    #[\Override]
    public static function fromDirectory(IDirectory $oDir, string $sName): ?IFile
    {
        //Initialize file
        return self::fromString($oDir -> path() . '/' . $sName);
    }

    /**
     * @return string
     */
    #[\Override]
    public function extension(): string
    {
        //get name
        $sName = $this -> name();
        
        //segment name
        $aSegments = explode('.', $sName);

        //if only 1 segment, name is the extension
        if (count($aSegments) === 1) {
            return $sName;
        }

        //return last segment
        return $aSegments[count($aSegments) - 1];
    }

    /**
     * @return string
     */
    #[\Override]
    public function basename(): string
    {
        //get name
        $sName = $this -> name();
        
        //segment name
        $aSegments = explode('.', $sName);

        //if only 1 segment, name is the basename
        if (count($aSegments) === 1) {
            return $sName;
        }
        
        //remove last segment
        unset($aSegments[count($aSegments) - 1]);
        
        //compose basename
        return implode('.', $aSegments);
    }

    /**
     * @return string
     */
    #[\Override]
    public function name(): string
    {
        //seperate on \
        $aSegments = explode('\\', $this -> sPath);
        
        //if no segments, seperate on /
        if (count($aSegments) <= 1) {
            $aSegments = explode('/', $this -> sPath);
        }
        
        //return last segment as name
        return $aSegments[count($aSegments) - 1];
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
     * @return string
     */
    #[\Override]
    public function path(): string
    {
        return $this -> sPath;
    }

    /**
     * @param  string $sPath
     * @throws \Exception
     */
    protected function __construct(string $sPath)
    {
        $this -> sPath = $sPath;
        $this -> bExists = file_exists($sPath);
        if ($this -> bExists) {
            $this -> sPath = realpath($sPath);
            if (!is_file($this -> sPath)) {
                throw new \Exception($this -> sPath . ' is not a ' . get_class($this));
            }
        }
    }
}
