<?php

class Seocrawler_Proxy
{
    private static $instance;

    private function __construct()
    {
    }

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new Seocrawler_Proxy();
        }

        return self::$instance;
    }

    public function getProxy($session_id)
    {
        $proxyes = $this->getProxyConfig();

        $proxy_index = $session_id % count($proxyes);
        return $proxyes[$proxy_index];
    }

    public function getProxyConfig()
    {
        return array(
            array(null, null),
            array('114.80.91.166:7780', CURLPROXY_HTTP),
            array('183.207.224.17:81', CURLPROXY_HTTP),
            array('183.207.224.19:82', CURLPROXY_HTTP),
            array('183.207.224.39:80', CURLPROXY_HTTP),
            array('183.207.224.43:80', CURLPROXY_HTTP),
            array('183.207.228.140:80', CURLPROXY_HTTP),
            array('183.207.228.155:80', CURLPROXY_HTTP),
            array('183.207.224.19:83', CURLPROXY_HTTP),
            array('183.207.224.21:81', CURLPROXY_HTTP),
            array('183.207.228.156:80', CURLPROXY_HTTP),
            array('183.207.224.21:86', CURLPROXY_HTTP),
            array('183.207.224.22:82', CURLPROXY_HTTP),
            array('183.207.228.137:80', CURLPROXY_HTTP),
            array('183.207.228.154:80', CURLPROXY_HTTP),
            array('183.207.224.17:83', CURLPROXY_HTTP),
            array('183.207.224.22:83', CURLPROXY_HTTP),
    
            
        );
    }
}
