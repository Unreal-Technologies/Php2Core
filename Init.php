<?php
define('TSTART', microtime(true));

require_once('Version.class.php');

define('VERSION', new Php2Core\Version('Php2Core', 1,0,0,0, 'https://github.com/Unreal-Technologies/Php2Core'));

require_once('Php2Core.class.php');

define('ROOT', Php2Core::Root());

$configFile = ROOT.'/Assets/Config.ini';
if(!file_exists($configFile))
{
    file_put_contents($configFile, file_get_contents(__DIR__.'/Assets/Config.default.ini'));
}
$configExtendedFile = ROOT.'/Assets/Config.xml';
if(!file_exists($configExtendedFile))
{
    file_put_contents($configExtendedFile, file_get_contents(__DIR__.'/Assets/Config.default.xml'));
}

require_once('Configuration.class.php');

define('CONFIGURATION', new \Php2Core\Configuration(parse_ini_file($configFile, true)));
define('DEBUG', (int)CONFIGURATION -> Get('Configuration/Debug') === 1);

if((int)CONFIGURATION -> Get('Logic/Autoloading') === 1)
{
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
    
    CONFIGURATION -> Extend(Php2Core\IO\Common\Xml::fromString($configExtendedFile) -> asXml() -> document());
    
    if((int)CONFIGURATION -> Get('Logic/Routing') === 1)
    {
        $router = new Php2Core\Router(CONFIGURATION -> Get('DefaultRoute'));

        foreach(CONFIGURATION -> Get('Routes') as $match => $data)
        {
            list($method, $target) = $data;
            $router -> Register($method.'::'.$match, $target);
        }

        define('ROUTE', $router -> Match());
    }
}

if((int)CONFIGURATION -> Get('Logic/ErrorHandling') === 1)
{
    //register handlers
    set_error_handler('Php2Core::ErrorHandler');
    set_exception_handler('Php2Core::ExceptionHandler');
    register_shutdown_function('Php2Core::Shutdown');
}

if(ROUTE === null && (int)CONFIGURATION -> Get('Logic/Routing') === 1)
{
    throw new \Exception('Route not found');

    
}