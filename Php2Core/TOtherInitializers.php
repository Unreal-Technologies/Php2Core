<?php
namespace Php2Core\Php2Core;

trait TOtherInitializers
{
    /**
     * @return void
     */
    public static function initialize(): void
    {
        require_once(__DIR__.'/../IO/IDiskManager.php');
        require_once(__DIR__.'/../IO/IDirectory.php');
        require_once(__DIR__.'/../IO/Directory.php');
        require_once(__DIR__.'/../IO/IFile.php');
        require_once(__DIR__.'/../IO/File.php');
        require_once(__DIR__.'/CoreProperties.php');
        require_once(__DIR__.'/Core.php');
        require_once(__DIR__.'/../Version.php');
        
        define('CORE', new Core(function(Core $core)
        {
            $core -> set(CoreProperties::Temp, \Php2Core::temp());
            $core -> set(CoreProperties::Cache, \Php2Core::cache());
            $core -> set(CoreProperties::Root, \Php2Core::root());
            $core -> set(CoreProperties::Start, microtime(true));
            $core -> set(CoreProperties::Version, new \Php2Core\Version('Php2Core', 1,0,0,2, 'https://github.com/Unreal-Technologies/Php2Core'));
        }));
        
        session_start();

//        echo '<xmp>';
//        print_r(CORE);
//        echo '</xmp>';
//        
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
        $appConfigFile = CORE -> get(CoreProperties::Root).'/Assets/Config.ini';
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
}
