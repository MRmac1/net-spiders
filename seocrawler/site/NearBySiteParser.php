<?php

apf_require_class('Seocrawler_SiteParserInterface');
apf_require_class('Seocrawler_SiteParser');
apf_require_class('Seocrawler_Const');
apf_require_class('Seocrawler_Site_BaseSiteParser');

class Seocrawler_Site_NearBySiteParser extends Seocrawler_Site_BaseSiteParser implements Seocrawler_SiteParserInterface
{
    protected $xml_url = 'http://api.map.baidu.com/place/v2/search?&radius=2000&output=xml&page_size=5&page_num=1';

    protected $limit = 10;

    protected $cursor;

    public function getLinkType($url, $anchor)
    {
        return Seocrawler_Const:: LINK_TYPE_OTHER;
    }

    public function processDetail($link){

        $this->crawler_db_master = APF_DB_Factory::get_instance()->get_pdo("crawler_db_master");
        $this->anjuke_db_master = APF_DB_Factory::get_instance()->get_pdo("master");
        while(true){
            $this->cursor = $this->batchId.'_'.$this->siteConfig['site_code'];
            $offset = $this->redis->get($this->cursor)?$this->redis->get($this->cursor):0;
            echo $offset."\n";
            $comm_info = $this->getCommXy($offset);
            $lastInfo = end($comm_info);
            $offset = $lastInfo['CommId'];
            if(is_array($comm_info)){
                foreach($comm_info as $ck => $cv){
                    $this->processNearBy( $cv );
                }
            }
            $this->redis->set($this->cursor,$offset);
            sleep(3);
        }
    }

    private function getCommXy( $offset = 1 ) {
        $select_sql = "SELECT C.CommId,C.CityId,B.lat,B.lng FROM ajk_communitys as C LEFT JOIN map_communities_baidu as B on C.CommId = B.comm_id WHERE C.TypeFlag = 0 AND C.CommId > ".$offset." order by C.CommId asc limit ".$this->limit;
        $stmt1 = $this->anjuke_db_master->prepare($select_sql);
        $stmt1->execute();
        return $ret1  = $stmt1->fetchAll();
    }

    private function processNearBy($data) {
        if(empty($data)){
            return false;
        }
        if( !$data['lat'] || !$data['lng']){
            return false;
        }
        $location = $data['lat'].','.$data['lng'];
        $apiInfo = $this->getMapxy($location);
        foreach( $apiInfo as $apk => $apv ){
            foreach($apv as $ak => $av){
                if($av) $this->insertData($apk , $av , $data);
            }
        }
        return true;
    }

    private function insertData( $type , $data , $pInfo) {

        $line_data = array(
                'city_id'   => $pInfo['CityId'],
                'type_id' => $type,
                'comm_id' => $pInfo['CommId'],
                'title'   => $data['name'],
                'uid'     => md5($data['uid']),
                'lat'     => $data['location']['lat'],
                'lng'     => $data['location']['lng'],
                'atime'     => time(),
            );

        //验证
        $select_sql = "SELECT `id` FROM seo_community_nearby WHERE uid = '{$line_data['uid']}' AND comm_id = '{$pInfo['CommId']}'";
        $stmt1 = $this->crawler_db_master->prepare($select_sql);
        $stmt1->execute();
        $ret1  = $stmt1->fetch();

        if($ret1)
        {
            return $ret1['id'];
        }

        //插入
        $insertSql = "INSERT INTO seo_community_nearby ( city_id, type_id, comm_id, title, uid, lat, lng, atime ) VALUES ( :city_id, :type_id, :comm_id, :title, :uid, :lat, :lng, :atime)";
        $stmt = $this->crawler_db_master->prepare($insertSql);
        $rs = $stmt->execute($line_data);
        if(!$rs){
             echo $insertSql;
        }
        return $rs ? $this->crawler_db_master->lastInsertId() : false;
    }

    private function getMapxy($location) {
        $key = $this->getCommKey();
        $types = $this->getCommType();

        foreach($types as $k => $type){
            $xml_url = $this->xml_url;
            $xml_url .= '&query='.$type;
            $xml_url .= '&ak='.$key;
            $xml_url .= '&location='.$location;
            $content = $this->getHttpContent($xml_url);
            $data = $this->xml_to_array($content);
            if(!is_array($data['results'])){
                print 'no results by comm';
                $result[$k] = array();
            }
            if( isset($data['results']['result'][0]) ){
                $result[$k] = $data['results']['result'];
            }else{
                $result[$k] = array();
            }
            unset($xml_url);
        }
        return $result;
    }

    private function getCommType() {
        return array(
            0 => '公园',
            1 => '景点',
            2 => '道路',
            3 => '地标',
        );
    }

    private function getCommKey() {
        $keyArr = array(
            0 => '38f253055653825d3dc8971b3f27b6a8',
            1 => '24bbf4b760b14cb19497a1ad19321528',
                  
        );
        return $keyArr[array_rand($keyArr)];
    }
}
