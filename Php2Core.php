<?php
require_once('Php2Core/TServerAdminCommands.php');
require_once('Php2Core/TDatabase.php');
require_once('Php2Core/TRouting.php');
require_once('Php2Core/THandlers.php');
require_once('Php2Core/TSession.php');

class Php2Core
{
    use \Php2Core\Php2Core\TServerAdminCommands;
    use \Php2Core\Php2Core\TDatabase;
    use \Php2Core\Php2Core\TRouting;
    use \Php2Core\Php2Core\THandlers;
    use \Php2Core\Php2Core\TSession;

    /**
     * @return void
     */
    public static function initialize(): void
    {
        require_once(__DIR__.'/IO/IDiskManager.php');
        require_once(__DIR__.'/IO/IDirectory.php');
        require_once(__DIR__.'/IO/Directory.php');
        require_once(__DIR__.'/IO/IFile.php');
        require_once(__DIR__.'/IO/File.php');
        require_once(__DIR__.'/CoreProperties.php');
        require_once(__DIR__.'/Php2Core/Core.php');
        require_once(__DIR__.'/Version.php');
        
        define('PHP2CORE', new Php2Core\Php2Core\Core(function(Php2Core\Php2Core\Core $core)
        {
            $root = \Php2Core\IO\Directory::fromString(__DIR__.'/../');
            
            $temp = Php2Core\IO\Directory::fromDirectory($root, '/__TEMP__');
            if($temp -> exists())
            {
                $temp -> remove();
            }
            $temp -> create();
            
            $cache = Php2Core\IO\Directory::fromDirectory($root, '__CACHE__');
            if(!$cache -> exists())
            {
                $cache -> create();
            }

            $core -> set(Php2Core\CoreProperties::Root, $root);
            $core -> set(Php2Core\CoreProperties::Temp, $temp);
            $core -> set(Php2Core\CoreProperties::Cache, $cache);
            $core -> set(Php2Core\CoreProperties::Start, microtime(true));
            $core -> set(Php2Core\CoreProperties::Version, new \Php2Core\Version('Php2Core', 1,0,0,2, 'https://github.com/Unreal-Technologies/Php2Core'));
            
            
            $appConfigFile = \Php2Core\IO\File::fromDirectory($cache, 'Config.app.ini');
            if(!$appConfigFile -> exists())
            {
                $appConfigFile -> write(file_get_contents(__DIR__.'/Assets/Config.App.Default.ini'));
            }
            
            $coreConfigFile = \Php2Core\IO\File::fromDirectory($cache, 'Config.Core.ini');
            if(!$coreConfigFile -> exists())
            {
                $coreConfigFile -> write(file_get_contents(__DIR__.'/Assets/Config.Core.Default.ini'));
            }
            
            require_once(__DIR__.'/Configuration.php');
            
            $configuration = new \Php2Core\Configuration(
                array_merge(parse_ini_file($appConfigFile -> path(), true), parse_ini_file($coreConfigFile -> path(), true)),
            );
            
            $core -> set(Php2Core\CoreProperties::Configuration, $configuration);
            $core -> set(Php2Core\CoreProperties::Debug, (int)$configuration -> get('Configuration/Debug') === 1);
            $core -> set(Php2Core\CoreProperties::Title, $configuration -> get('Configuration/Title'));

        }));
        
        session_start();

        self::initializeAutoloading();
        self::initializeHandlerOverride();
        self::initializeDatabase();
        self::initializeServerAdminCommands();
        self::initializeSession();
        self::initializeRouting();
        self::executeServerAdminCommands();
    }
    
    /**
     * @param string $url
     * @return void
     */
    public static function refresh(string $url): void
    {
        header('Location: '.$url);
        exit;
    }
    
    /**
     * @return void
     */
    public static function trace(): void
    {
        $path = [];
        $components = debug_backtrace();
        
        for($i=1; $i<count($components); $i++)
        {
            $entry = $components[$i];
            
            $args = [];
            foreach($entry['args'] as $arg)
            {
                if(is_object($arg))
                {
                    $args[] = get_class($arg);
                    continue;
                }
                else if(is_string($arg) && !is_numeric($arg))
                {
                    $args[] = '"'.$arg.'"';
                    continue;
                }
                else if(is_array($arg))
                {
                    $args[] = 'array';
                    continue;
                }
                $args[] = $arg;
            }
            
            if(isset($entry['class']))
            {
                if(!isset($entry['file']))
                {
                    $path = [];
                    continue;
                }
                $path[] = [$entry['file'].':'.$entry['line'], $entry['class'].' '.$entry['type'].' '.$entry['function'].'('.implode(', ', $args).')'];
                continue;
            }
            $path[] = [$entry['file'].':'.$entry['line'], $entry['function'].'('.implode(', ', $args).')'];
        }
        $pathReversed = array_reverse($path);
        
        XHTML -> get('body', function(Php2Core\NoHTML\Xhtml $body) use($pathReversed)
        {
            $table = $body -> add('table@#trace');
            $table -> add('tr/th@colspan=3') -> text('Trace');
            
            foreach($pathReversed as $idx => $data)
            {
                list($line, $call) = $data;
                
                $tr = $table -> add('tr');
                $tr -> add('td') -> text($idx + 1);
                $tr -> add('td') -> text($line === null ? '' : $line);
                $tr -> add('td') -> text($call);
            }
        });
    }
    
    /**
     * @return string
     */
    public static function baseUrl(): string
    {
        $pi = pathinfo($_SERVER['SCRIPT_NAME']);
        if(!isset($_SERVER['SCRIPT_URI']))
        {
            $result = $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'].$pi['dirname'];

            if(substr($result, -1, 1) === '/')
            {
                    return substr($result, 0, -1);
            }

            return $result;
        }

        return preg_replace('/'.substr($pi['dirname'], 1).'.+$/i', substr($pi['dirname'], 1), $_SERVER['SCRIPT_URI']);
    }

    /**
     * @param string $path
     * @return string
     */
    public static function physicalToRelativePath(string $path): string
    {
        $basePath = $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'].pathinfo($_SERVER['SCRIPT_NAME'])['dirname'];
        $root = PHP2CORE -> get(\Php2Core\CoreProperties::Root) -> path();
        
        $new = str_replace([$root.'\\', $root.'/', '\\', '//', ':/'], ['', '', '/', '/', '://'], $path);
        if($new !== $path)
        {
            return $basePath.'/'.$new;
        }

        throw new Php2Core\Exceptions\NotImplementedException($path);
    }

    /**
     * @param string $directory
     * @return array
     */
    public static function scanDir(string $directory): array
    {
        $entries = [];
        if (($handle = opendir($directory)) !== false) //Open Dir
        {
            while (false !== ($entry = readdir($handle))) //Loop through files
            {
                if ($entry != "." && $entry != "..") 
                {
                    $path = $directory.'/'.$entry; //Get new Path
                    $entries[] = ['Path' => realpath($path), 'Type' => is_dir($path) ? 'Dir' : 'File']; //Register Path & Type
                }
            }

            closedir($handle); //Close Dir
        }
        return $entries;
    }
    
    /**
     * @return void
     */
    private static function initializeAutoloading(): void
    {
        if((int)PHP2CORE -> get(\Php2Core\CoreProperties::Configuration) -> get('Logic/Autoloading') === 1)
        {
            //define map file;
            $mapFile = __DIR__.'/class.map';

            if(!file_exists($mapFile)) //create map file if not exists
            {
                $map = self::map(__DIR__);
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
