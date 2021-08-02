<?php

apf_require_class('APF_DB_Factory');

apf_require_class('Seocrawler_Queue');
apf_require_class('Seocrawler_Const');

class Seocrawler_Candidate
{

    private static $instance;

    private function __construct()
    {
    }

    public static function get_instance()
    {
        if (!self::$instance) {
            self::$instance = new Seocrawler_Candidate();
        }

        return self::$instance;
    }


    public function updateStatus($site_id, $batch_id, $hashes, $status)
    {
        $hash_str = implode(', ', array_pad(array(), count($hashes), '?'));
        $sql = "update candidates set status = ? where site_id = ? and batch_id = ? and hash in ({$hash_str}) ";

        $params = array_merge(
            array($status, $site_id, $batch_id),
            $hashes
        );

        $this->update(
            $sql,
            $params
        );

        print "update link status " . $status . " OK : " . join(',', $hashes) . " \n";
    }


    public function addCandidateLinks($site_id, $batch_id, $url_array)
    {
        if (!$url_array) {
            return 0;
        }

        $params = array();
        foreach ($url_array as $url_item) {
            $url = $url_item['url'];
            $url_hash = $url_item['hash'];
            $title = $url_item['title'];
            $type = $url_item['type'];
            $level = $url_item['level'];
            $parent_id = $url_item['parent_id'];

            $params[] = $site_id;
            $params[] = $url_hash;
            $params[] = $url;
            $params[] = $title;
            $params[] = $type;
            $params[] = Seocrawler_Const::STATUS_PENDING;
            $params[] = $batch_id;
            $params[] = $level;
            $params[] = $parent_id;
        }

        $place_holder = join(',', array_pad(array(), count($url_array), '(?, ?, ?, ?, ?, ?, ?, ?, ?, current_timestamp())'));

        $sql = "insert into candidates (site_id, hash, url, title, type, status, batch_id, level, parent_id, created_time) " .
            " values $place_holder " ;

        return $this->update(
            $sql,
            $params
        );
    }


    public function isExists($site_id, $batch_id, $url_hash)
    {
        $redis = APF_Cache_Factory::get_instance()->get_redis('crawler_redis');

        $key = 'crawler_h_' . $site_id . '_' . $url_hash;

        if (($value = $redis->get($key)) && $value >= $batch_id) {
            return true;
        }

        $redis->set($key, $batch_id);

        return false;
    }


    /**
     * 获得即将处理的链接
     *
     */
    public function getCandidateLinks($site_id, $batch_id, $num = 5)
    {
        $counter = 0;
        $ids = array();
        do {
            $id = Seocrawler_Queue::getInstance()->pop($site_id, $batch_id);
            //$id = 58035;
            if (empty($id)) {
                break;
            }
            $ids[] = $id;
            $counter ++;
        } while ($counter < $num);

        if(!count( $ids )){
           return false;
        }

        $place_holder = join(',', array_pad(array(), $counter, '?'));

        $sql = "select id, type, hash, level, url, parent_id, title from candidates " .
            " where id in ( $place_holder ) " .
            " order by id asc ";

        return $this->select(
            $sql,
            $ids
        );

    }


    public function getCandidateIds($num = 100)
    {
        $num = intval($num);
        if ($num <= 0) {
            $num = 5;
        }

        $top_id = Seocrawler_Queue::getInstance()->getTopId();

        $sql = "select site_id, batch_id, id from candidates " .
                " where status = ? and id > ?" .
                " order by id asc limit $num";

        return $this->select(
            $sql,
            array(
                Seocrawler_Const::STATUS_PENDING,
                $top_id,
            )
        );
    }


    public function initRootLink($site_id, $batch_id, $root_url, $root_url_hash)
    {
        $sql = "select id from candidates where site_id = ? and batch_id = ? and type = ? ";
        $exists = $this->select(
            $sql,
            array($site_id, $batch_id, Seocrawler_Const::LINK_TYPE_ROOT)
        );


        if (!$exists) {
            $sql = "insert into candidates (site_id, hash, url, title, type, status, batch_id, created_time) " .
                " values  (?, ?, ?, ?, ?, ?, ?, current_timestamp()) " ;

            return $this->update(
                $sql,
                array(
                    $site_id,
                    $root_url_hash,
                    $root_url,
                    '',
                    Seocrawler_Const::LINK_TYPE_ROOT,
                    Seocrawler_Const::STATUS_PENDING,
                    $batch_id
                )
            );
        }

        return 0;
    }

    private function update($sql, $params)
    {
        $stmt = APF_DB_Factory::get_instance()->get_pdo("crawler_db_master")->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount();
    }

    private function select($sql, $params, $force_master = false)
    {
        if ($force_master) {
            $db = "crawler_db_master";
        } else {
            $db = "crawler_db_slave";
        }

        $stmt = APF_DB_Factory::get_instance()->get_pdo($db)->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function getCityConfig($site,$city_id)
    {
        if(!$site){
            return false;
        }
        $city_id = intval($city_id);
        if($city_id){
            $sql = "select * from seo_city_conf " .
                    " where site_code = ? and city_id = ? " .
                    " order by id asc";

            $result =  $this->select(
                $sql,
                array(
                    $site,
                    $city_id,
                )
            );
        }else{
            $sql = "select * from seo_city_conf " .
                    " where site_code = ? " .
                    " order by id asc";

            $result =  $this->select(
                $sql,
                array(
                    $site,
                )
            );
        }
        if(!$result){
            return false;
        }else{
            foreach($result as $rk => $rv){
                $data[$rv['id']] = $rv;
                if($rv['other']){
                    $data[$rv['id']]['other'] = unserialize($rv['other']);
                }
            }
        }
        return $data;
    }


    public function initCityConfig()
    {
        $city_config = APF::get_instance()->get_config( 'init_city' ,'cityconfig' );
        if( $city_config ){
            $sqls = explode( "\n", $city_config );
        }
        foreach( $sqls as $insertSql ){
            if($insertSql){
                $stmt = APF_DB_Factory::get_instance()->get_pdo("crawler_db_master")->prepare($insertSql);
                $rs = $stmt->execute();
            }
        }
        return $rs;
    }

}
