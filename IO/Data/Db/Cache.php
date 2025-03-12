<?php
namespace Php2Core\IO\Data\Db;

class Cache 
{
    public const CACHE_MEMORY = 'MEMORY';
    public const CACHE_SESSION = 'SESSION';
    
    /**
     * @var array
     */
    private static array $memory = [];

    /**
     * @param string $sCache
     * @param string $sKey
     * @return mixed
     */
    public static function get(string $sCache, string $sKey): mixed
    {
        switch($sCache)
        {
            case self::CACHE_MEMORY:
                return isset(self::$memory[$sKey]) ? self::$memory[$sKey] : null;
            case self::CACHE_SESSION:
                return isset($_SESSION['aCache'][$sKey]) ? $_SESSION['aCache'][$sKey] : null;
        }
    }
    
    /**
     * @param string $sCache
     * @param string $sKey
     * @param mixed $mValue
     * @return void
     */
    public static function set(string $sCache, string $sKey, mixed $mValue): void
    {
        switch($sCache)
        {
            case self::CACHE_MEMORY:
                self::$memory[$sKey] = $mValue;
                break;
            case self::CACHE_SESSION:
                $_SESSION['aCache'][$sKey] = $mValue;
                break;
        }
    }
    
    /** 
     * @param string $sCache
     * @param string $sKey
     * @return bool
     */
    public static function clear(string $sCache, string $sKey): bool
    {
        switch($sCache)
        {
            case self::CACHE_MEMORY:
                if(isset(self::$memory[$sKey]))
                {
                    unset(self::$memory[$sKey]);
                    return true;
                }
                break;
            case self::CACHE_SESSION:
                if(isset($_SESSION['aCache'][$sKey]))
                {
                    unset($_SESSION['aCache'][$sKey]);
                    return true;
                }
                break;
        }
        return false;
    }
}
