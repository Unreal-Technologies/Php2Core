<?php
namespace Php2Core\Php2Core;

trait TDatabase
{
    /**
     * @return void
     */
    private static function initializeDatabase(): void
    {
        $dbInfo1 = PHP2CORE -> get(\Php2Core::Configuration) -> get('Database');
        $dbInfo2 = PHP2CORE -> get(\Php2Core::Configuration) -> get('CDatabase');
        
        $dbc1 = \Php2Core\Db\Database::createInstance(PHP2CORE -> get(\Php2Core::Title), $dbInfo1['Host'], $dbInfo1['Username'], $dbInfo1['Password'], $dbInfo1['Database']);
        $dbc2 = \Php2Core\Db\Database::createInstance('Php2Core', $dbInfo2['Host'], $dbInfo2['Username'], $dbInfo2['Password'], $dbInfo2['Database']);
        
        if(!defined('Database'))
        {
            define('Database', [$dbc1, $dbc2]);
        }
        
        self::initializeDatabaseOverride($dbc2, $dbInfo2);
        self::initializeDatabaseOverride($dbc1, $dbInfo1);
    }
    
    /**
     * @param \Php2Core\Db\Database $instance
     * @param array $configuration
     * @return void
     * @throws \PDOException
     */
    private static function initializeDatabaseOverride(\Php2Core\Db\Database $instance, array $configuration): void
    {
        $instance -> query('SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = \''.$configuration['Database'].'\'');
        
        try
        {
            $instance -> execute();
        } 
        catch (\PDOException $pex) 
        {
            if($pex -> getCode() === 1049)
            {
                $structureFile = realpath(str_replace(['{ROOT}', '{__DIR__}'], [ROOT, __DIR__.'/..'], $configuration['Structure']));
                $contentFile = realpath(str_replace(['{ROOT}', '{__DIR__}'], [ROOT, __DIR__.'/..'], $configuration['Content']));
                
                if($structureFile !== false)
                {
                    $instance -> structure(file_get_contents($structureFile), \Php2Core\Db\Cache::CACHE_MEMORY, true);
                }
                
                if($contentFile !== false)
                {
                    include($contentFile);
                }
                
                header('Location: '.self::baseUrl());
                return;
            }
            throw $pex;
        }
    }
}
