<?php
namespace Php2Core\Php2Core;

trait TAutoloading
{
    /**
     * @return void
     */
    private static function initializeAutoloading(): void
    {
        if((int)PHP2CORE -> get(CoreProperties::Configuration) -> get('Logic/Autoloading') === 1)
        {
            //define map file;
            $mapFile = __DIR__.'/../class.map';

            if(!file_exists($mapFile)) //create map file if not exists
            {
                $map = self::map(__DIR__.'/..');
                file_put_contents($mapFile, json_encode($map));
            }
            else //Load map file
            {
                $map = json_decode(file_get_contents($mapFile), true);
            }

            define('MAP', $map); //Register map

            //Autoload missing components from map data;
            spl_autoload_register(function(string $className)
            {
                if(isset(MAP['Classes'][$className]) && file_exists(MAP['Classes'][$className]))
                {
                    require_once(MAP['Classes'][$className]);
                    return;
                }
            });

            foreach($map['Init'] as $module)
            {
                require_once($module);
            }
        }
    }

    /**
     * @param string $directory
     * @return array
     */
    private static function map(string $directory, array $map = ['Classes' => [], 'Init' => [], 'Skipped' => []], bool $topMost = true): array
    {
        $skipped = $map['Skipped'];
        
        foreach(self::ScanDir($directory) as $entry) //Loop Through all Entries
        {
            if(
                $entry['Path'] === __FILE__ || 
                (
                    $entry['Type'] === 'File' && 
                    (
                        !preg_match('/\.php$/i', $entry['Path']) || 
                        preg_match('/init\.php$/i', $entry['Path'])
                    )
                ) ||
                preg_match('/.git$/i', $entry['Path'])
            ) // Check if Path is not a git folder and not a self reference
            {
                continue;
            }

            if($entry['Type'] === 'Dir' && file_exists($entry['Path'].'/Init.php')) //Check if a init file exists, if so, execute it
            {
                //Create local map file
                $mapFile = $entry['Path'].'/class.map';
                if(!file_exists($mapFile) || DEBUG)
                {
                    file_put_contents($mapFile, json_encode(self::Map($entry['Path'], $map, false)));
                }
                
                //Import local map
                $loaded = json_decode(file_get_contents($mapFile), true);
                $map['Classes'] = array_merge($map['Classes'], $loaded['Classes']);
                $map['Init'] = array_merge($map['Init'], $loaded['Init']);
                $skipped = array_merge($skipped, $loaded['Skipped']);
                
                //Initialize
                require_once($entry['Path'].'/Init.php');
                
                $map['Init'][] = realpath($entry['Path'].'/Init.php');
                continue;
            }
            
            if($entry['Type'] === 'Dir')
            {
                $loaded = self::Map($entry['Path'], $map, false);
                $map['Classes'] = array_merge($map['Classes'], $loaded['Classes']);
                $map['Init'] = array_merge($map['Init'], $loaded['Init']);
                $skipped = array_merge($skipped, $loaded['Skipped']);
                continue;
            }
            
            if($entry['Type'] === 'File' && preg_match('/\.php$/i', $entry['Path']))
            {
                try
                {
                    $baseClasses = get_declared_classes();
                    $baseInterfaces = get_declared_interfaces();
                    $baseTraits = get_declared_traits();

                    require_once($entry['Path']);

                    $postClasses = get_declared_classes();
                    $postInterfaces = get_declared_interfaces();
                    $postTraits = get_declared_traits();

                    $difClasses = array_diff($postClasses, $baseClasses);
                    $difInterfaces = array_diff($postInterfaces, $baseInterfaces);
                    $difTraits = array_diff($postTraits, $baseTraits);

                    $difMerged = array_merge($difClasses, $difInterfaces, $difTraits);

                    foreach($difMerged as $class)
                    {
                        $map['Classes'][$class] = $entry['Path'];
                    }
                }
                catch(\Throwable $ex)
                {
					$entry['message'] = $ex -> getMessage();
                    $skipped[] = $entry;
                }
                continue;
            }
            
            throw new \Exception('Undefined object: '.$entry['Path'].' ('.$entry['Type'].')');
        }
        
        if($topMost)
        {
            $sk = -1;
            $i = 0;
            while($sk !== count($skipped))
            {
                $sk = count($skipped);
                
                $i++;
                $remove = [];
                foreach($skipped as $idx => $entry)
                {
                    try
                    {
                        $baseClasses = get_declared_classes();
                        $baseInterfaces = get_declared_interfaces();
                        $baseTraits = get_declared_traits();

                        include($entry['Path']);

                        $postClasses = get_declared_classes();
                        $postInterfaces = get_declared_interfaces();
                        $postTraits = get_declared_traits();

                        $difClasses = array_diff($postClasses, $baseClasses);
                        $difInterfaces = array_diff($postInterfaces, $baseInterfaces);
                        $difTraits = array_diff($postTraits, $baseTraits);

                        $difMerged = array_merge($difClasses, $difInterfaces, $difTraits);

                        foreach($difMerged as $class)
                        {
                            $map['Classes'][$class] = $entry['Path'];
                        }
                        $remove[] = $idx;
                    } 
                    catch (\Throwable $ex) 
                    { 
                        $entry['message'] = $ex -> getMessage();
						
                        $map['Skipped'][] = $entry;
                    }
                }
                
                foreach($remove as $idx)
                {
                    unset($skipped[$idx]);
                }
            }

            if(count($skipped) !== 0)
            {
                throw new \Exception('Could not get all class data');
            }
        }
        
        $map['Skipped'] = $skipped;

        if($topMost)
        {
            $map['Skipped'] = array_unique($map['Skipped']);
            $map['Init'] = array_unique($map['Init']);
        }
        
        return $map;
    }
}
