<?php

apf_require_class('Seocrawler_SiteParserInterface');
apf_require_class('Seocrawler_SiteParser');
apf_require_class('Seocrawler_Const');

class Seocrawler_Site_BaseSiteParser extends Seocrawler_SiteParser implements Seocrawler_SiteParserInterface
{

    protected $crawler_db_master;

    protected $crawler_db_slave;


    function __construct(){
        $this->redis = APF_Cache_Factory::get_instance()->get_redis('crawler_redis');
        $this->initDatabase();
    }

    public function getLinkType($url, $anchor)
    {
        return Seocrawler_Const:: LINK_TYPE_ROOT;
    }

    public function processDetail($link){
        return true;
    }

    public function getBaseUrl(){
        $parseUrl = parse_url($this->siteConfig['root_url']);
        if($parseUrl['host']){
            $baseUrl = $parseUrl['host'];
        }
        return "http://".$baseUrl;
    }

    public function initDatabase()
    {
        $this->crawler_db_master = APF_DB_Factory::get_instance()->get_pdo("crawler_db_master");
        $this->crawler_db_slave = APF_DB_Factory::get_instance()->get_pdo("crawler_db_slave");
    }

    public function getXpathSource( $pregArr, $content )
    {
        $pageDom = Utils_DOM::createDomFromHtml($content);
        $xpath = new DOMXPath($pageDom);
        if( !is_array($pregArr) || !$content ){
            return false;
        }
        $return_data = array();
        foreach($pregArr as $key => $val){
            $_likelyTitleNodes = $xpath->query($val);
            $item_len = $_likelyTitleNodes->length;
            for($i = 0;$i < $item_len;$i++) {
                $return_data[$key][$i] = $_likelyTitleNodes->item($i)->nodeValue;
            }
        }
        return $return_data;
    }

    public function xml_to_array($xml) {
        $array = array();
        if (!empty ( $xml )) {
            $xml_obj = @simplexml_load_string ($xml);
            if($xml_obj){
                $array = (array) $xml_obj;
                foreach ( $array as $key => $item ) {
                    $array [$key] = self::struct_to_array ( $item );
                }
            }
        }
        return $array;
    }

    public function struct_to_array($item) {
        if (! is_string ( $item )) {
            $item = ( array ) $item;
            if (count ( $item ) == 0) {
                return '';
            }
            foreach ( $item as $key => $val ) {
                $item [$key] = self::struct_to_array ( $val );
            }
        }
        return $item;
    }

    public function rad($d)  
    {  
        return $d * 3.1415926535898 / 180.0;  
    }  

    public function getDistance($lat1, $lng1, $lat2, $lng2)  
    {  
        $EARTH_RADIUS = 6378.137;  
        $radLat1 = $this->rad($lat1);   
        $radLat2 = $this->rad($lat2);  
        $a = $radLat1 - $radLat2;  
        $b = $this->rad($lng1) - $this->rad($lng2);  
        $s = 2 * asin(sqrt(pow(sin($a/2),2) +  
        cos($radLat1)*cos($radLat2)*pow(sin($b/2),2)));  
        $s = $s *$EARTH_RADIUS;  
        $s = round($s * 10000) / 10000;  
        return $s;  
    }
}
