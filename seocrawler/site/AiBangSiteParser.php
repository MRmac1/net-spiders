<?php

apf_require_class('Seocrawler_SiteParserInterface');
apf_require_class('Seocrawler_SiteParser');
apf_require_class('Seocrawler_Const');
apf_require_class('Seocrawler_Site_BaseSiteParser');

class Seocrawler_Site_AiBangSiteParser extends Seocrawler_Site_BaseSiteParser implements Seocrawler_SiteParserInterface
{
    var $xml_url = 'http://openapi.aibang.com/bus/stats?app_key=';

    public function getLinkType($url, $anchor)
    {
        $parseUrl = parse_url($this->siteConfig['root_url']);

        if($parseUrl['host']){
            $baseUrl = $parseUrl['host'];
        }

        $url_len = strlen($url);
        if($url_len > 1024){
            echo "url len 1024 : ".$url_len;
        }

        if( $url == "http://".$baseUrl."/bus/".$this->siteConfig['city_key']."/line_1.html" ) {
            return Seocrawler_Const:: LINK_TYPE_ROOT;
        }
        if(preg_match('#http://'.$baseUrl.'/'.$this->siteConfig['city_key'].'/line-(.*?)#i', $url)) {
            return Seocrawler_Const:: LINK_TYPE_DETAIL;
        }
        if(preg_match('#^http://'.$baseUrl.'/bus/'.$this->siteConfig['city_key'].'/line_\d+.html#i', $url)) {
            return Seocrawler_Const:: LINK_TYPE_LIST;
        }
        return Seocrawler_Const:: LINK_TYPE_OTHER;
    }

    public function processDetail($link){
        $this->crawler_db_master = APF_DB_Factory::get_instance()->get_pdo("crawler_db_master");

        $content = $this->getHttpContent($link['url'],$link['id']);

        if(empty($content)){
            return false;
        }

        $fetchArr = array(
            'line_name' => ".//div[@class='select_again fb mb10']//span[@class='red mr10']//text()",
            'intro' => ".//*[@class='line_info']//p//text()",
            'station_list' => ".//*[@class='line_detail']//a//@name",
        );
        $return = $this->getXpathSource($fetchArr,$content);

        //插入线路
        $line_id = $this->insertLine($return);
        if($line_id){
            //插入站点
            $station_ids = $this->insertStation($return,$line_id);
            //插入关系
            $relation_ids = $this->insertRelation($line_id,$station_ids);
        }else{
            echo 'page is not Found';
        }
        return $relation_ids;
    }

    private function insertLine($data) {
        if(empty($data)){
            return false;
        }
        $line_data = array(
                'city_id'   => $this->siteConfig['city_id'],
                'line_name' => $data['line_name'][0],
                'intro' => $data['intro'][0],
                'atime'     => time(),
            );
        //验证
        $select_sql = "SELECT `id` FROM `seo_bus_line` WHERE `city_id` = {$line_data['city_id']} AND `line_name` = '{$line_data['line_name']}'";
        $stmt1 = $this->crawler_db_master->prepare($select_sql);
        $stmt1->execute();
        $ret1  = $stmt1->fetch();
        if($ret1)
        {
            return $ret1['id'];
        }

        //插入
        $insertSql = "INSERT INTO seo_bus_line (`city_id`, `line_name`, `intro` ,`atime`) VALUES ( :city_id, :line_name, :intro ,:atime )";
        $stmt = $this->crawler_db_master->prepare($insertSql);
        $rs = $stmt->execute($line_data);
        if(!$rs){
             echo $insertSql;
        }
        return $rs ? $this->crawler_db_master->lastInsertId() : false;
    }


    private function insertStation($data,$lid) {
        if(empty($data) || empty($lid)){
            return false;
        }

        foreach($data['station_list'] as $k => $v){
            list($lat,$lng) = $this->getMapxy($v);
            $item = array(
                    'city_id'       => $this->siteConfig['city_id'],
                    'station_name'  => $v,
                    'lat'           => $lat,
                    'lng'           => $lng,
                    'atime'         => time(),
                );

            //验证
            $select_sql = "SELECT `id` FROM seo_bus_stations WHERE `city_id` = {$item['city_id']} AND `station_name` = '{$item['station_name']}'";

            $stmt1 = $this->crawler_db_master->prepare($select_sql);
            $stmt1->execute();
            $ret1  = $stmt1->fetch();
            if($ret1)
            {
                $ids[] = $ret1['id'];
                continue;
            }
            //插入
            $insertSql = "INSERT INTO seo_bus_stations (`city_id`,`station_name`, `lat`, `lng`, `atime`) VALUES (:city_id , :station_name, :lat, :lng, :atime)";
            $stmt = $this->crawler_db_master->prepare($insertSql);
            $rs = $stmt->execute($item);

            $ids[] = $rs ? $this->crawler_db_master->lastInsertId() : false;
        }
        return $ids;
    }

    private function insertRelation($lid,$station_ids) {
        if(empty($lid) || count($station_ids) < 1){
            return false;
        }

        foreach($station_ids as $k => $v){
            $item = array(
                    'line_id'     => $lid,
                    'station_id'  => $v,
                    'order'       => $k+1,
                    'atime'       => time(),

                );
            //验证
            $select_sql = "SELECT `id` FROM seo_bus_relation WHERE `line_id` = {$item['line_id']} AND `station_id` = {$item['station_id']} AND `order_id` = {$item['order']}";

            $stmt1 = $this->crawler_db_master->prepare($select_sql);
            $stmt1->execute();
            $ret1  = $stmt1->fetch();
            if($ret1)
            {
                $ids[] = $ret1['id'];
                continue;
            }
            //插入
            $insert_sql = "INSERT INTO seo_bus_relation (`line_id`, `station_id`,`order_id`, `atime`) VALUES (:line_id, :station_id, :order, :atime)";
            $stmt = $this->crawler_db_master->prepare($insert_sql);
            $rs = $stmt->execute($item);
            $ids[] = $rs ? $this->crawler_db_master->lastInsertId() : false;
        }

        return $ids;
    }

    private function getMapxy($key) {
        if(empty($key)){
            return array(0,0);
        }
        $xml_url = $this->xml_url;
        $xml_url .= md5($this->siteConfig['city_name'].rand(1,100000000000)).'&city='.$this->siteConfig['city_name'];
        $xml_url .= '&q='.$key;

        $content = $this->getHttpContent($xml_url);
        $data = $this->xml_to_array($content);

        if(!is_array($data['stats'])){
            return array(0,0);
        }
        if( isset($data['stats']['stat'][0]['xy']) ){
            $result = explode(',',$data['stats']['stat'][0]['xy']);
        }elseif(isset($data['stats']['stat']['xy'])){
            $result = explode(',',$data['stats']['stat']['xy']);
        }
        return $result;

    }
}
