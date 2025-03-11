<?php
namespace Php2Core\Php2Core;

trait TServerAdminCommands
{
    /**
     * @return void
     */
    private static function initializeServerAdminCommands(): void
    {
        $ip = $_SERVER['REMOTE_ADDR'];
        define('SERVER_ADMIN', preg_match('/'.$ip.'/i', PHP2CORE -> get(CoreProperties::Configuration) -> get('RemoteAdmin/IPs')));
    }
    
    /**
     * @return void
     */
    private static function resetDatabases(): void //Callable Server Command
    {
        $appDbc = \Php2Core\Db\Database::getInstance(TITLE);
        $coreDbc = \Php2Core\Db\Database::getInstance('Php2Core');
        
        $appDbc -> query('drop database `'.$appDbc -> database().'`;');
        $appDbc -> execute();
        
        $coreDbc -> query('drop database `'.$coreDbc -> database().'`;');
        $coreDbc -> execute();

        header('Location: '.self::baseUrl());
        exit;
    }
    
    /**
     * @return void
     */
    private static function classMap(): void //Callable Server Command
    {
        self::removeClassMap();
        header('Location: '.self::baseUrl());
        exit;
    }
    
    /**
     * @return void
     */
    private static function executeServerAdminCommands(): void
    {
        $info = ROUTE -> target();
        if(SERVER_ADMIN && $info['type'] === 'function')
        {
            eval($info['target'].'();');
            exit;
        }
    }
    
    /**
     * @return void
     */
    private static function removeClassMap($directory=__DIR__.'/..'): void
    {
        foreach(self::scanDir($directory) as $entry) //Loop Through all Entries
        {
            $pi = pathinfo($entry['Path']);
            if($entry['Type'] === 'File' && $pi['basename'] === 'class.map')
            {
                unlink($entry['Path']);
            }
            else if($entry['Type'] === 'Dir' && $pi['basename'] === '.git')
            {
                continue;
            }
            else if($entry['Type'] === 'Dir')
            {
                self::removeClassMap($entry['Path']);
            }
        }
    }
}