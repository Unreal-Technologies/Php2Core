<?php
require_once('Php2Core/TServerAdminCommands.php');
require_once('Php2Core/TDatabase.php');

class Php2Core
{
    use \Php2Core\Php2Core\TServerAdminCommands;
    use \Php2Core\Php2Core\TDatabase;
    
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
        self::initializeRouting();
        self::executeServerAdminCommands();
    }

    /**
     * @return string
     */
    public static function baseUrl(): string
    {
        $pi = pathinfo($_SERVER['SCRIPT_NAME']);
        return preg_replace('/'.substr($pi['dirname'], 1).'.+$/i', substr($pi['dirname'], 1), $_SERVER['SCRIPT_URI']);
    }
    
    /**
     * @param string $slug
     * @return array
     */
    private static function getPossibleMatchesFromSlug(string $slug): array
    {
        $parts = explode('/', $slug);
        $buffer = [ '^'.$parts[0].'$' ];
        $offParts = [ $parts[0] ];
        
        for($i=1; $i<count($parts); $i++)
        {
            $offParts[$i] = '{.+}';
            
            $temp1 = implode('\\/', array_slice($parts, 0, $i));
            $temp2 = implode('\\/', array_slice($offParts, 0, $i));
            
            $buffer[] = '^'.$temp1.'\\/'.$parts[$i].'$';
            $buffer[] = '^'.$temp2.'\\/'.$parts[$i].'$';
            
            $buffer[] = '^'.$temp1.'\\/{.+}$';
            $buffer[] = '^'.$temp2.'\\/{.+}$';
        }
        
        return array_values(array_unique($buffer));
    }
    
    /**
     * @return void
     * @throws \Exception
     */
    private static function initializeRouting(): void
    {
        //Get DB Instance
        $coreDbc = Php2Core\Db\Database::getInstance('Php2Core');
        $instanceId = 1;

        //Get Default handler
        $coreDbc -> query(
                'select '
                . 'case when `match` is null then \'index\' else `match` end as `match` '
                . 'from `route` '
                . 'where `default` = "true" '
                . 'and '.(SERVER_ADMIN ? '( `instance-id` = '.$instanceId.' or `instance-id` is null )' : '`instance-id` = '.$instanceId).' '
                . 'order by `id` asc '
                . 'limit 0,1'
        );
        
        $defaultResults = $coreDbc -> execute();
        define('DEFAULT_ROUTE', $defaultResults['iRowCount'] === 0 ? 'index' : $defaultResults['aResults'][0]['match']);
        
        //Get Router Information
        $router = new Php2Core\Router(DEFAULT_ROUTE);
        $slug = $router -> slug();
        $possibilities = self::getPossibleMatchesFromSlug($slug);
        
        //Get Possible routes
        $coreDbc -> query(
                'select '
                . '`method`, `match`, `target`, `type` '
                . 'from `route` '
                . 'where '.(SERVER_ADMIN ? '( `instance-id` = '.$instanceId.' or `instance-id` is null )' : '`instance-id` = '.$instanceId).' '
                . 'and (`match` regexp \''.implode('\' or `match` regexp \'', $possibilities).'\')'
                . (SERVER_ADMIN ? '' : 'and `type` != \'function\' ')
        );
        
        //Register possible routes
        $routeResult = $coreDbc -> execute();
        foreach($routeResult['aResults'] as $row)
        {
            $router -> register($row['method'].'::'.$row['match'], $row['type'].'#'.$row['target']);
        }
        
        //get current route (if matched)
        define('ROUTE', $router -> Match());
        
        //throw exception when not actually matched
        if(ROUTE === null)
        {
            throw new \Exception('Route not found');
        }
    }

    /**
     * @return void
     */
    private static function initializeHandlerOverride(): void
    {
        if((int)CONFIGURATION -> Get('Logic/ErrorHandling') === 1)
        {
            //register handlers
            set_error_handler('Php2Core::ErrorHandler');
            set_exception_handler('Php2Core::ExceptionHandler');
            register_shutdown_function('Php2Core::Shutdown');
        }
    }
    
    /**
     * @return void
     */
    private static function initializeAutoloading(): void
    {
        if((int)CONFIGURATION -> Get('Logic/Autoloading') === 1)
        {
            //define map file;
            $mapFile = __DIR__.'/class.map';

            if(!file_exists($mapFile)) //create map file if not exists
            {
                $map = Php2Core::Map(__DIR__);
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
     * @return void
     */
    private static function initializeConfiguration(): void
    {
        $appConfigFile = ROOT.'/Assets/Config.ini';
        $coreConfigFile = __DIR__.'/Assets/Config.ini';
        if(!file_exists($appConfigFile))
        {
            file_put_contents($appConfigFile, file_get_contents(__DIR__.'/Assets/Config.default.ini'));
        }
        if(!file_exists($coreConfigFile))
        {
            file_put_contents($coreConfigFile, file_get_contents(__DIR__.'/Assets/Config.default.ini'));
        }
        
        require_once('Configuration.php');

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
        require_once('Version.php');
        define('VERSION', new Php2Core\Version('Php2Core', 1,0,0,0, 'https://github.com/Unreal-Technologies/Php2Core'));
    }
    
    /**
     * @param string $path
     * @return string
     */
    public static function physicalToRelativePath(string $path): string
    {
        $basePath = $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'].pathinfo($_SERVER['SCRIPT_NAME'])['dirname'];

        $new = str_replace([ROOT.'\\', '\\', '//', ':/'], ['', '/', '/', '://'], $path);
        if($new !== $path)
        {
            return $basePath.'/'.$new;
        }
        
        var_dump($path);
        throw new Php2Core\Exceptions\NotImplementedException();
    }
    
    /**
     * @return string
     */
    private static function root(): string
    {
        //get Directory
        $pi = pathinfo(__DIR__);
        return $pi['dirname'];
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
     * @param string $directory
     * @return array
     */
    public static function map(string $directory, array $map = ['Classes' => [], 'Init' => [], 'Skipped' => []], bool $topMost = true): array
    {
        $skipped = $map['Skipped'];
        
        foreach(Php2Core::ScanDir($directory) as $entry) //Loop Through all Entries
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
                    file_put_contents($mapFile, json_encode(Php2Core::Map($entry['Path'], $map, true)));
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
                $loaded = Php2Core::Map($entry['Path'], $map, false);
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
                catch(\Throwable)
                {
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
                    catch (\Throwable) 
                    { 
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
        
        return $map;
    }
    
    /**
     * @param int $errno
     * @param string $errstr
     * @param string $errfile
     * @param int $errline
     * @return void
     */
    public static function errorHandler(int $errno, string $errstr, string $errfile, int $errline): void
    {
        $hasBody = false;
        
        XHTML -> Get('body', function(Php2Core\NoHTML\XHtml $body) use(&$hasBody, $errno, $errstr, $errfile, $errline)
        {
            $body -> Clear();
            $body -> Add('h2', function(Php2Core\NoHTML\XHtml $h2)
            {
                $h2 -> Text('Php2Core::ErrorHandler');
            });
            $body -> Add('xmp', function(Php2Core\NoHTML\XHtml $xmp) use($errfile, $errline)
            {
                $xmp -> Text(print_r($errfile.':'.$errline, true));
            });
            $body -> Add('xmp', function(Php2Core\NoHTML\XHtml $xmp) use($errstr, $errno)
            {
                $xmp -> Text($errno.' ');
                $xmp -> Text(print_r($errstr, true));
            });
            
            $hasBody = true; 
        });

        if(!$hasBody)
        {
            echo '<h2>Php2Core::ErrorHandler</h2>';
            echo '<xmp>';
            var_dump($errfile);
            var_dumP($errline);
            var_dumP($errno);
            var_dumP($errstr);
            echo '</xmp>';
        }
        exit;
    }
    
    /**
     * @param \Throwable $ex
     * @return void
     */
    public static function exceptionHandler(\Throwable $ex): void
    {
        $hasBody = false;
        
        XHTML -> Get('body', function(Php2Core\NoHTML\XHtml $body) use(&$hasBody, $ex)
        {
            $body -> Clear();
            $body -> Add('h2', function(Php2Core\NoHTML\XHtml $h2)
            {
                $h2 -> Text('Php2Core::ExceptionHandler');
            });
            $body -> Add('xmp', function(Php2Core\NoHTML\XHtml $xmp) use($ex)
            {
                $xmp -> Text(print_r($ex, true));
            });
            
            $hasBody = true; 
        });
        
        if(!$hasBody)
        {
            echo '<h2>Php2Core::ExceptionHandler</h2>';
            echo '<xmp>';
            print_r($ex);
            echo '</xmp>';
        }
        exit;
    }
    
    /**
     * @return void
     */
    public static function shutdown(): void
    {
        XHTML -> Get('body', function(Php2Core\NoHTML\XHtml $body)
        {
            $body -> Add('div', function(Php2Core\NoHTML\XHtml $div)
            {
                $dif = microtime(true) - TSTART;
                
                $div -> Attributes() -> Set('id', 'execution-time');
                $div -> Text('Process time: '.number_format(round($dif * 1000, 4), 4, ',', '.').' ms');
            });
            $body -> Add('div', function(\Php2Core\NoHTML\XHtml $div)
            {
                $div -> Attributes() -> Set('id', 'version');
                VERSION -> Render($div);
            });
        });
        XHTML -> Get('head', function(Php2Core\NoHTML\XHtml $head)
        {
            $children = $head -> Children();
            $head -> Clear();
            
            $head -> Add('link', function(Php2Core\NoHTML\XHtml $link)
            {
                $link -> Attributes() -> Set('rel', 'icon');
                $link -> Attributes() -> Set('type', 'image/x-icon');
                $link -> Attributes() -> Set('href', Php2Core::PhysicalToRelativePath(__DIR__.'/Assets/Images/favicon.ico'));
            });
            $head -> Add('link', function(Php2Core\NoHTML\XHtml $link)
            {
                $link -> Attributes() -> Set('rel', 'stylesheet');
                $link -> Attributes() -> Set('href', Php2Core::PhysicalToRelativePath(__DIR__.'/Assets/FA-all.min.5.15.4.css'));
            });
            $head -> Add('link', function(Php2Core\NoHTML\XHtml $link)
            {
                $link -> Attributes() -> Set('rel', 'stylesheet');
                $link -> Attributes() -> Set('href', Php2Core::PhysicalToRelativePath(__DIR__.'/Assets/Materialize.css'));
            });
            $head -> Add('link', function(Php2Core\NoHTML\XHtml $link)
            {
                $link -> Attributes() -> Set('rel', 'stylesheet');
                $link -> Attributes() -> Set('href', Php2Core::PhysicalToRelativePath(__DIR__.'/Assets/Php2Core.css'));
            });
            $head -> Add('link', function(Php2Core\NoHTML\XHtml $link)
            {
                $link -> Attributes() -> Set('rel', 'stylesheet');
                $link -> Attributes() -> Set('href', 'https://fonts.googleapis.com/icon?family=Material+Icons');
            });
            $head -> Add('script', function(Php2Core\NoHTML\XHtml $script)
            {
                $script -> Attributes() -> Set('type', 'text/javascript');
                $script -> Attributes() -> Set('src', Php2Core::PhysicalToRelativePath(__DIR__.'/Assets/jquery-3.7.1.min.js'));
            });
            $head -> Add('script', function(Php2Core\NoHTML\XHtml $script)
            {
                $script -> Attributes() -> Set('type', 'text/javascript');
                $script -> Attributes() -> Set('src', Php2Core::PhysicalToRelativePath(__DIR__.'/Assets/Materialize.js'));
            });

            foreach($children as $child)
            {
                $head -> Append($child);
            }
        });

        //output
        echo XHTML;
        
        if(DEBUG && (int)CONFIGURATION -> Get('Configuration/XhtmlOut') === 1)
        {
            echo '<hr />';
            echo '<xmp>';
            print_r(str_replace(['<xmp>', '</xmp>'], ['<.xmp>', '</.xmp>'], (string)XHTML));
            echo '</xmp>';
        }
    }
}
