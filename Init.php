<?php
define('TSTART', microtime(true));

require_once('Version.class.php');

define('VERSION', new Php2Core\Version('Php2Core', 1,0,0,0));

if(!defined('DEBUG'))
{
    define('DEBUG', false);
}

class Php2Core
{
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
    public static function Map(string $directory): array
    {
        $skipped = [];
        $map = [[], []];
        foreach(Php2Core::ScanDir($directory) as $entry) //Loop Through all Entries
        {
            if($entry['Path'] === __FILE__ || preg_match('/\.git$/i', $entry['Path'])) // Check if Path is not a git folder and not a self reference
            {
                continue;
            }

            if($entry['Type'] === 'Dir' && file_exists($entry['Path'].'/Init.php')) //Check if a init file exists, if so, execute it
            {
                //Create local map file
                $mapFile = $entry['Path'].'/class.map';
                if(!file_exists($mapFile) || DEBUG)
                {
                    file_put_contents($mapFile, json_encode(Php2Core::Map($entry['Path'])));
                }
                
                //Import local map
                $loaded = json_decode(file_get_contents($mapFile), true);
                $map[0] = array_merge($map[0], (array)$loaded[0]);
                $map[1] = array_merge($map[1], (array)$loaded[1]);
                
                //Initialize
                require_once($entry['Path'].'/Init.php');
                
                $map[1][] = realpath($entry['Path'].'/Init.php');
            }
            else if($entry['Type'] === 'Dir') //Loop through content recursive
            {
                echo '<xmp>';
                print_r($entry);
                echo '</xmp>';
            }
            else if($entry['Type'] === 'File' && preg_match('/\.php/', $entry['Path']) && !preg_match('/init\.php$/i', $entry['Path'])) //Each file check declared components (Classes, Interfaces & Traits)
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
                        $map[0][$class] = $entry['Path'];
                    }
                }
                catch(\Throwable $ex)
                {
                    $skipped[] = $entry;
                }
            }
        }
        
        $sc = -1;
        while($sc !== count($skipped))
        {
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
                        $map[0][$class] = $entry['Path'];
                    }
                    $remove[] = $idx;
                } 
                catch (\Throwable $ex) { }
            }
            
            foreach($remove as $idx)
            {
                unset($skipped[$idx]);
            }
            
            $sc = count($skipped);
        }
        
        if(count($skipped) !== 0)
        {
            throw new \Exception('Could not get all class data');
        }
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
            $dif = microtime(true) - TSTART;
            $body -> Raw('Process time: '.number_format(round($dif * 1000, 4), 4, ',', '.').' ms');
            $body -> Raw(VERSION);
        });
        
        //output
        echo HTML;
    }
}

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
    if(isset(MAP[0][$className]) && file_exists(MAP[0][$className]))
    {
        require_once(MAP[0][$className]);
        return;
    }
});

if(!DEBUG) //Load modules when not in debug mode
{
    foreach($map[1] as $module)
    {
        require_once($module);
    }
}

//register handlers
set_error_handler('Php2Core::ErrorHandler');
set_exception_handler('Php2Core::ExceptionHandler');
register_shutdown_function('Php2Core::Shutdown');

