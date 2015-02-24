<?php
namespace RESTful;

use RESTful\Exception\Environment\CannotGetHost;
use RESTful\Util\OptionableArray;

class Environment{

    const UNIT_TEST = 'unit_test';
    const CLI = 'cli';
    const SANDBOX = 'sandbox';
    const DEV = 'development';
    const STAGE = 'stage';
    const PRODUCTION = 'production';

    private static $list = [
        self::SANDBOX,
        self::DEV,
        self::UNIT_TEST,
        self::CLI,
        self::STAGE,
        self::PRODUCTION
    ];

    public static function all(){
        return static::$list;
    }

    public static function isValid($environment){
        return in_array($environment, static::$list);
    }

    /**
     * @return Environment
     */
    public static function factory(){
        $instance = new Environment(
            new OptionableArray($_SERVER),
            defined('PHPUNIT_RESTful'),
            php_sapi_name()
        );

        return $instance;
    }

    /**
     * @var OptionableArray
     */
    protected $server;
    protected $is_unit_test;
    protected $sapi_name;

    public function __construct(
        OptionableArray $server,
        $is_unit_test,
        $sapi_name
    ){
        $this->server = $server;
        $this->is_unit_test = $is_unit_test;
        $this->sapi_name = $sapi_name;
    }

    public function url(){
        $url = $this->protocol() . '://';
        $url .= $this->domain();
        return $url;
    }

    public function protocol(){

        if( in_array($this->domain(), [self::UNIT_TEST, self::CLI]) ){
            return 'cmd';
        }

        $is_ssl = $this->server->get('HTTPS') === 'on' || $this->server->get('SERVER_PORT') == 443;
        $protocol = $is_ssl ? "https" : "http";
        return $protocol;
    }

    public function domain(){
        if( $this->is_unit_test ){
            return self::UNIT_TEST;
        }
        if( $this->sapi_name == 'cli' ){
            return self::CLI;
        }

        if( is_null($this->server->get("HTTP_HOST")) ){
            throw new CannotGetHost($this->server->source());
        }

        return $this->server->get("HTTP_HOST");
    }

}