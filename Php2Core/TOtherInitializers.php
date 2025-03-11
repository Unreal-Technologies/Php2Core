<?php
namespace Php2Core\Php2Core;

trait TOtherInitializers
{
    /**
     * @return void
     */
    private static function initializeConfiguration(): void
    {
        $appConfigFile = PHP2CORE -> get(CoreProperties::Root) -> path().'/Assets/Config.ini';
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
