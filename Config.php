<?php

namespace Lib;

class Config
{
    const ENV_DEV   = 'dev';
    const ENV_STG   = 'stg';
    const ENV_PRD   = 'prd';

    public static $cfg = null;
    public static $env = '';

    public $data;
    
    private function __construct()
    {
        /* singleton */
        defined('LIB_CONFIG_PATH') || define('LIB_CONFIG_PATH', './application/config');        
        
        $this->data = [];
    }
    
    public static function instance()
    {
        if (self::$cfg == null)
            self::$cfg = new Config();
    
        return self::$cfg;
    }
    
    public static function setEnv($env)
    {
        self::$env = $env;
    }
    
    public static function get($cfg_file = 'cfg', $ext = 'ini')
    {
        $cfg = self::instance();
        
        if (isset($cfg->data[$cfg_file]))
            return $cfg->data[$cfg_file];
        
        $env = (self::$env) ? self::$env . '/' : '';
        
        switch ($ext)
        {
            case 'ini':
                $cfg->data[$cfg_file] = parse_ini_file(LIB_CONFIG_PATH . '/' . $env . $cfg_file . '.ini', true);
                break;
            case 'php':
                $cfg->data[$cfg_file] = require(LIB_CONFIG_PATH . '/' . $env . $cfg_file . '.php');
        }
        
        
        return $cfg->data[$cfg_file];
    }
}
