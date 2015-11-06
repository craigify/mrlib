<?php
// Mister Lib Foundation Library
// Copyright(C) 2015 McDaniel Consulting, LLC
//
// Pronounced "Mister Lib" - kind of like Mr. Coffee and Mr. Radar in the movie Space Balls.
//
// MrLib is a static class that initializes the library and provides some basic functionality
// for use throughout.  Include this file before including any other mrlib file to make sure
// that things have been initialized correctly.
//

class mrlib
{
    public static $filesystemLocation;
    
    
    // Initialize MrLib.
    public static function init()
    {
        mrlib::detectFilesystemLocation();
        
        if (MRLIB_AUTOLOAD_ENABLED == TRUE)
        {
            mrlib::autoload();
        }
    }
    
    

    // Helper function to load a file.  This provides consistent 
    //
    // This method can be called in the following two ways:
    // 1 -  Specify the path syntax:
    //      mrlib::load("sql/MrDatabaseManager");
    //
    // 2 -  Specify the module and filename explicitly: 
    //      mrlib::load("sql", "MrDatabaseManager.php");
    //
    public static function load($file)
    {
        $location = mrlib::getFileSystemLocation();

        // If file starts with "./", we look into the application root dir.
        if ($file[0] == "." && $file[1] == "/")
        {
            require_once($location['root_dir'] . "/" . $file . ".php");
        }
        
        // The file did not start with "./".  Assume we're loading a mrlib module.  Look in mrlib modules dir.
        else
        {
            require_once($location['modules_dir'] . "/" . $file . ".php");            
        }
    }



    // Factory method to initialize and return new object.  Pass it a path to an object in the module tree
    // A path of "event/MrEventHandler" would return a new MrEventHandler object
    //
    // @param string $path
    // @return object
    public static function getNew($path)
    {
        $elements = explode("/", $path);
        $last = count($elements) - 1;
        $file = $elements[$last] . ".php";

        mrlib::load($elements[0], $file);
        $obj = new $elements[$last];
        return $obj;
    }


    // Factory method to get a reference to a singleton object.  Initialize the singleton if not already done.
    // @return object
    public static function getSingleton($path)
    {
        $elements = explode("/", $path);
        $last = count($elements) - 1;
        $file = $elements[$last] . ".php";

        mrlib::load($elements[0], $file);
        $code = "\$ref = {$elements[$last]}::getInstance();";
        eval($code);

        return $ref;
    }


    // Return filesystem location information
    // @return array
    public static function getFileSystemLocation()
    {
        return mrlib::$filesystemLocation;
    }



    // Detect location of mrlib in filesystem.
    public static function detectFilesystemLocation()
    {
        mrlib::$filesystemLocation['lib_dir'] = "";
        mrlib::$filesystemLocation['modules_dir'] = "";
        mrlib::$filesystemLocation['root_dir'] = "";

        // lib_dir is the base of the mrlib framework.     
        if (defined("MRLIB_DIR"))
        {
            mrlib::$filesystemLocation['lib_dir'] = MRLIB_DIR;
            mrlib::$filesystemLocation['modules_dir'] = MRLIB_DIR . "/modules/";
        }
        else
        {
            $f = dirname(__FILE__);
            $arr = explode("/", $f);
            $arr_num = count($arr);

            for ($i=0; $i < $arr_num; $i++)
            {
                mrlib::$filesystemLocation['lib_dir'] .= $arr[$i] . "/";
            }

            for ($i=0; $i < $arr_num - 1; $i++)
            {
                mrlib::$filesystemLocation['modules_dir'] .= $arr[$i] . "/";
            }
        
            mrlib::$filesystemLocation['modules_dir'] .= $arr[$i] . "/modules/";            
        }
        
        // root_dir is where the application lives.  Models, Views, Controllers, Includes, Templates, etc...
        if (defined("MRLIB_ROOT"))
        {
            mrlib::$filesystemLocation['root_dir'] = MRLIB_ROOT;
        }        
        else
        {
            throw new Exception("MRLIB_ROOT was not defined.");
        }        
    }


    // Automatically include any files in the specified autoload directory.
    public static function autoload()
    {
        if (is_dir(MRLIB_AUTOLOAD_DIR))
        {
            if ($dh = opendir(MRLIB_AUTOLOAD_DIR))
            {
                while (($file = readdir($dh)) !== false)
                {
                    if ($file != "." && $file != ".." && strpos($file, '.') != 0)
                    {
                        include(MRLIB_AUTOLOAD_DIR . "/" . $file);
                    }
                }

                closedir($dh);
            }    
        }        
    }


// end class
}



// Initialize mrlib!
include_once(realpath(dirname(__FILE__)) . "/config.php");
mrlib::init();


?>
