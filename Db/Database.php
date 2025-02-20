<?php

namespace Php2Core\Db;

class Database extends \Php2Core\Data
{
    /**
     * @var array
     */
    private static array $oInstance = [];
    
    /**
     * @var string
     */
    private string $sQuery = '';
    
    /**
     * @var array
     */
    private array $aBindings = [];
    
    /**
     * @param string $baseSql
     * @param string $sCache
     * @param bool $noDb
     * @return array
     */
    public function structure(string $baseSql,string $sCache = Cache::CACHE_MEMORY, bool $noDb = false): array
    {
        $delimiter = ';';
        $delimiters = [];
        $queryBuffer = [];
        $pos = 0;
        
        preg_match_all('/^delimiter.+$/msiU', $baseSql, $delimiters);
        
        foreach($delimiters[0] as $delim)
        {
            $sql = substr($baseSql, $pos, strpos($baseSql, $delim, $pos) - $pos);
            $pos += strlen($sql) + strlen($delim);
            
            $trimmed = trim($sql);

            if($delimiter !== ';')
            {
                foreach(explode($delimiter, $trimmed) as $sql)
                {
                    $queryBuffer[] = trim($sql);
                }
            }
            else
            {
                $queryBuffer[] = trim($sql);
            }
            
            $delimiter = trim(substr($delim, 9));
        }
        $queryBuffer[] = trim(substr($baseSql, $pos));
        
        $results = [];
        foreach($queryBuffer as $sql)
        {
            if(strlen($sql) === 0)
            {
                continue;
            }
            
            $this -> query($sql);
            $results[] = $this -> execute($sCache, $noDb);
            $noDb = false;
        }

        return $results;
    }
    
    /** 
     * @param string $sInstanceID
     * @param string $sHost
     * @param string $sUsername
     * @param string|null $sPassword
     * @param string $sDatabase
     * @return Database
     */
    public static function createInstance(string $sInstanceID, string $sHost, string $sUsername, ?string $sPassword, string $sDatabase): Database
    {
        //Check if session is set
        if(!isset($_SESSION) || session_id() === false)
        {
            session_start();
            $_SESSION['aDatabases'] = [];
        }
        
        //Initialize DB
        $oDatabase = new Database($sHost, $sUsername, $sPassword, $sDatabase);
        
        //Set Instance
        $_SESSION['aDatabases'][$sInstanceID] = $oDatabase;
        
        //Return
        return $oDatabase;
    }
    
    /**
     * @param mixed $mInstanceID
     * @return Database|null
     */
    public static function getInstance(mixed $mInstanceID = null): ?Database
    {
        //Check if instance id is set
        if($mInstanceID === null)
        {
            // get first instance ID
            $mInstanceID = key($_SESSION['aDatabases']);
        }
        //Check if instance is numeric
        else if(is_numeric($mInstanceID) && is_int($mInstanceID))
        {
            //get instance keys
            $aKeys = array_keys($_SESSION['aDatabases']);
            
            //check if key exists
            if(!isset($aKeys[$mInstanceID]))
            {
                return null;
            }
            
            //change numeric instance id to string variant
            $mInstanceID = $aKeys[$mInstanceID];
        }
        
        //return db
        return $_SESSION['aDatabases'][$mInstanceID];
    }
    
    /**
     * @param string $sHost
     * @param string $sUsername
     * @param string|null $sPassword
     * @param string $sDatabase
     */
    #[\Override]
    private function __construct(string $sHost, string $sUsername, ?string $sPassword, string $sDatabase)
    {
        parent::__construct();
        
        //Set data
        $this -> set('sDsn', 'mysql:'.$sHost.';port=3306;dbname='.$sDatabase);
        $this -> set('sUsername', $sUsername);
        $this -> set('sPassword', $sPassword);
        $this -> set('sDatabase', $sDatabase);
    }
    
    /**
     * @return string
     */
    public function database(): string
    {
        return $this -> get('sDatabase');
    }
    
    /**
     * @param string $sQuery
     * @return void
     */
    public function query(string $sQuery): void
    {
        $this -> sQuery = $sQuery;
    }
    
    /**
     * @param string $sTarget
     * @param mixed $mValue
     * @return void
     */
    public function bind(string $sTarget, mixed $mValue): void
    {
        $this -> aBindings[$sTarget] = $mValue;
    }
    
    /**
     * @return void
     */
    public function beginTransaction(): void
    {
        //Get Pdo Instance
        $oPdo = $this -> getPdoInstance();
        
        $oPdo -> beginTransaction();
    }
    
    /**
     * @return void
     */
    public function commit(): void
    {
        //Get Pdo Instance
        $oPdo = $this -> getPdoInstance();
        
        $oPdo ->commit();
    }
    
    /**
     * @return void
     */
    public function rollback(): void
    {
        //Get Pdo Instance
        $oPdo = $this -> getPdoInstance();
        
        $oPdo -> rollBack();
    }
    
    /**
     * @return \PDO
     */
    private function getPdoInstance(): \PDO
    {
        //Get PDO instance
        if(!isset(self::$oInstance[$this -> database()]))
        {
            self::$oInstance[$this -> database()] = new \PDO($this -> Get('sDsn'), $this -> Get('sUsername'), $this -> Get('sPassword'));
        }
        return self::$oInstance[$this -> database()];
    }
    
    /**
     * @param string $sCache
     * @param bool $noDb
     * @return array
     */
    public function execute(string $sCache = Cache::CACHE_MEMORY, bool $noDb = false): array
    {
        //Get Pdo Instance
        try
        {
            $oPdo = $this -> getPdoInstance();
        }
        catch(\PDOException $pex)
        {
            if(!$noDb)
            {
                throw $pex;
            }
            
            $parts = explode(';', $this -> get('sDsn'));
            $buffer = [];
            foreach($parts as $part)
            {
                if(!preg_match('/^dbname/i', $part))
                {
                    $buffer[] = $part;
                }
            }
            $dsn = implode(';', $buffer);

            $oPdo = new \PDO($dsn, $this -> Get('sUsername'), $this -> Get('sPassword'));
        }
        
        //Prepare statement
        $oStatement = $this -> sQuery === '' ? null : $oPdo -> prepare($this -> sQuery);
        
        //Get Cache Key
        $sCacheKey = $oStatement === null ? null : $oStatement -> queryString;
        
        //Get Cache Data
        $mCached = $sCacheKey === null ? null : Cache::get($sCache, $sCacheKey);
        if($mCached !== null)
        {
            //Update Exec time to 0 (cache)
            $mCached['fExecutionTime'] = 0;
            
            //return cache
            return $mCached;
        }

        //Set bindings
        if(isset($this -> aBindings))
        {
            foreach($this -> aBindings as $sBindingKey => $mBingingValue)
            {
                //set default type
                $iType = \PDO::PARAM_NULL;

                //Get Correct Types
                switch(gettype($mBingingValue))
                {
                    case 'integer':
                        //Set Type to int
                        $iType = \PDO::PARAM_INT;
                        break;
                    default:
                        //Dump and exit when undefined type is passed
                        dump(gettype($mBingingValue));
                        exit;
                        break;
                }

                //Bind statement value
                $oStatement -> bindValue($sBindingKey, $mBingingValue, $iType); 
            }
        }

        try
        {
            //get start time
            $fStart = microtime(true);
            
            //execute statement
            if($oStatement !== null)
            {
                $oStatement -> execute();
            }
            
            //get end time
            $fEnd = microtime(true);

            //set data variables
            $iRowCount = $oStatement === null ? 0 : $oStatement -> rowCount();
            $aResults  = $oStatement === null ? [] : $oStatement -> fetchAll(\PDO::FETCH_ASSOC);
            $sMessage  = '';
        }
        catch(\PDOException $ex)
        {
            //set data variables
            $iRowCount  = 0;
            $aResults   = [];
            $sMessage   = $ex -> getMessage();
            //get end time
            $fEnd = microtime(true);
        }

        //compose resultset
        $aData = [
            'sMessage'          => $sMessage,
            'iRowCount'         => $iRowCount,
            'fExecutionTime'    => $fEnd - $fStart,
            'sQuery'            => $this -> sQuery,
            'aBindings'         => isset($this -> aBindings) ? $this -> aBindings : [],
            'aResults'          => $aResults,
            'iLastInsertId'     => $oPdo -> lastInsertId()
        ];
        
        //Cleanup
        unset($this -> aBindings);
        unset($this -> sQuery);
        
        //Set Cache
        if($sCacheKey !== null)
        {
            Cache::get(Cache::CACHE_MEMORY, $sCacheKey, $aData);
        }
        
        //return data
        return $aData;
    }
}
