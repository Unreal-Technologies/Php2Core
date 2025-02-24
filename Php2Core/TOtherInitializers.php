<?php
namespace Php2Core\Php2Core;

trait TOtherInitializers
{
    /**
     * @return void
     */
    public static function initialize(): void
    {
        self::initializeStart();
        self::initializeVersion();
        self::initializeRoot();
        self::initializeConfiguration();
        self::initializeAutoloading();
        self::initializeHandlerOverride();
        self::initializeDatabase();
        self::initializeServerAdminCommands();
        self::initializeSession();
        self::initializeRouting();
        self::executeServerAdminCommands();
    }
    
    /**
     * @return void
     */
    private static function initializeConfiguration(): void
    {
        $appConfigFile = ROOT.'/Assets/Config.ini';
        $coreConfigFile = __DIR__.'/../Assets/Config.ini';
        if(!file_exists($appConfigFile))
        {
            file_put_contents($appConfigFile, file_get_contents(__DIR__.'/../Assets/Config.default.ini'));
        }
        if(!file_exists($coreConfigFile))
        {
            file_put_contents($coreConfigFile, file_get_contents(__DIR__.'/../Assets/Config.default.ini'));
        }
        
        require_once(__DIR__.'/../Configuration.php');

        define('CONFIGURATION', new \Php2Core\Configuration(
            array_merge(parse_ini_file($appConfigFile, true), parse_ini_file($coreConfigFile, true)),
        ));
        define('DEBUG', (int)CONFIGURATION -> Get('Configuration/Debug') === 1);
        define('TITLE', CONFIGURATION -> Get('Configuration/Title'));
    }
    
    /**
     * @return void
     */
    private static function initializeStart(): void
    {
        define('TSTART', microtime(true));
        session_start();
    }
    
    /**
     * @return void
     */
    private static function initializeRoot(): void
    {
        define('ROOT', self::root());
    }
    
    /**
     * @return void
     */
    private static function initializeVersion(): void
    {
        require_once(__DIR__.'/../Version.php');
        define('VERSION', new \Php2Core\Version('Php2Core', 1,0,0,1, 'https://github.com/Unreal-Technologies/Php2Core'));
    }
}
