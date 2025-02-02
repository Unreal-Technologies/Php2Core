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
        define('SERVER_ADMIN', preg_match('/'.$ip.'/i', CONFIGURATION -> get('RemoteAdmin/IPs')));
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
        
        self::initializeDatabase();

        header('Location: '.self::baseUrl());
        exit;
    }
    
    /**
     * @return void
     */
    private static function classMap(): void //Callable Server Command
    {
        self::createClassMap();
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
}