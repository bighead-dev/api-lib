<?php

namespace Lib;

class Config
{
    const PATH      = './application/config/';
    const EXT       = 'ini';
    const ENV_DEV   = 'dev';
    const ENV_STG   = 'stg';
    const ENV_PRD   = 'prd';

    public static $cfg = null;
    public static $env = '';

    public $data;
    
    private function __construct()
    {
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
    
    public static function get($cfg_file = 'api')
    {
        $cfg = self::instance();
        
        if (isset($cfg->data[$cfg_file]))
            return $cfg->data[$cfg_file];
        
        $env = (self::$env) ? self::$env . '/' : '';
        
        $cfg->data[$cfg_file] = parse_ini_file(self::PATH . $env . $cfg_file . '.' . self::EXT, true);
        return $cfg->data[$cfg_file];
    }
}
