<?php
define('TSTART', microtime(true));

require_once('Version.class.php');

define('VERSION', new Php2Core\Version('Php2Core', 1,0,0,0, 'https://github.com/Unreal-Technologies/Php2Core'));

class Php2Core
{
    /**
     * @param string $path
     * @return string
     */
    public static function PhysicalToRelativePath(string $path): string
    {
        $basePath = $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'].pathinfo($_SERVER['SCRIPT_NAME'])['dirname'];

        $new = str_replace([ROOT.'\\', '\\', '//', ':/'], ['', '/', '/', '://'], $path);
        if($new !== $path)
        {
            return $basePath.'/'.$new;
        }
        
        var_dump($path);
        return '';
    }
    
    /**
     * @return string
     */
    public static function Root(): string
    {
        //get Directory
        $pi = pathinfo(__DIR__);
        return $pi['dirname'];
    }
    
    /**
     * @param string $directory
     * @return array
     */
    public static function ScanDir(string $directory): array
    {
        $entries = [];
        if ($handle = opendir($directory)) //Open Dir
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
    public static function Map(string $directory, array $map = ['Classes' => [], 'Init' => [], 'Skipped' => []], bool $topMost = true): array
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
                catch(\Throwable $ex)
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
                    catch (\Throwable $ex) 
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
    public static function ErrorHandler(int $errno, string $errstr, string $errfile, int $errline): void
    {
        echo '<h2>Php2Core::ErrorHandler</h2>';
        echo '<xmp>';
        var_dump($errfile);
        var_dumP($errline);
        var_dumP($errno);
        var_dumP($errstr);
        echo '</xmp>';
    }
    
    /**
     * @param \Throwable $ex
     * @return void
     */
    public static function ExceptionHandler(\Throwable $ex): void
    {
        $hasBody = false;
        
        HTML -> Child('body', function(\Php2Core\NoHTML\Body $body) use(&$hasBody, $ex)
        {
            $body -> Clear();
            $body -> H2('Php2Core::ExceptionHandler');
            $body -> Xmp(print_r($ex, true));

            $hasBody = true; 
        });
        
        if(!$hasBody)
        {
            echo '<h2>Php2Core::ExceptionHandler</h2>';
            echo '<xmp>';
            print_r($ex);
            echo '</xmp>';
        }
    }
    
    /**
     * @return void
     */
    public static function Shutdown(): void
    {
        //Inject execution time & Version
        HTML -> Child('body', function(\Php2Core\NoHTML\Body $body)
        {
            $body -> Div(function(Php2Core\NoHTML\Div $div)
            {
                $dif = microtime(true) - TSTART;
                
                $div -> Attributes() -> Set('id', 'execution-time');
                $div -> Raw('Process time: '.number_format(round($dif * 1000, 4), 4, ',', '.').' ms');
            });
            $body -> Div(function(\Php2Core\NoHTML\Div $div)
            {
                $div -> Attributes() -> Set('id', 'version');
                VERSION -> Render($div);
            });
        });
        
        //Inject Php2Core Styles
        HTML -> Child('head', function(\Php2Core\NoHTML\Head $head)
        {
            //Write core head values as top most
            
            $children = $head -> Children();
            $head -> Clear();
            
            $head -> Link(function(\Php2Core\NoHTML\Link $link)
            {
                $link -> Attributes() -> Set('rel', 'icon');
                $link -> Attributes() -> Set('type', 'image/x-icon');
                $link -> Attributes() -> Set('href', Php2Core::PhysicalToRelativePath(__DIR__.'/Assets/Images/favicon.ico'));
            });
            $head -> Link(function(\Php2Core\NoHTML\Link $link)
            {
                $link -> Attributes() -> Set('rel', 'stylesheet');
                $link -> Attributes() -> Set('href', Php2Core::PhysicalToRelativePath(__DIR__.'/Assets/FA-all.min.5.15.4.css'));
            });
            $head -> Link(function(\Php2Core\NoHTML\Link $link)
            {
                $link -> Attributes() -> Set('rel', 'stylesheet');
                $link -> Attributes() -> Set('href', Php2Core::PhysicalToRelativePath(__DIR__.'/Assets/Materialize.css'));
            });
            $head -> Link(function(\Php2Core\NoHTML\Link $link)
            {
                $link -> Attributes() -> Set('rel', 'stylesheet');
                $link -> Attributes() -> Set('href', Php2Core::PhysicalToRelativePath(__DIR__.'/Assets/Php2Core.css'));
            });
            $head -> ScriptExtern('text/javascript', Php2Core::PhysicalToRelativePath(__DIR__.'/Assets/Materialize.js'));
            
            foreach($children as $child)
            {
                $head -> Append($child);
            }
        });
        
        //output
        echo HTML;
    }
}

define('ROOT', Php2Core::Root());

$configFile = ROOT.'/Assets/Config.ini';
if(!file_exists($configFile))
{
    file_put_contents($configFile, file_get_contents(__DIR__.'/Assets/Config.default.ini'));
}

define('CONFIGURATION', parse_ini_file($configFile, true));
define('DEBUG', (int)CONFIGURATION['Configuration']['Debug'] === 1);

//define map file;
$mapFile = __DIR__.'/class.map';

if(!file_exists($mapFile) || DEBUG) //create map file if not exists
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

if(!DEBUG) //Load modules when not in debug mode
{
    foreach($map['Init'] as $module)
    {
        require_once($module);
    }
}

//register handlers
set_error_handler('Php2Core::ErrorHandler');
set_exception_handler('Php2Core::ExceptionHandler');
register_shutdown_function('Php2Core::Shutdown');