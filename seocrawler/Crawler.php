<?php

apf_require_class('APF_DB_Factory');

apf_require_class('Seocrawler_Const');
apf_require_class('Seocrawler_Candidate');

class Seocrawler_Crawler
{

    public function run($site , $city_id, $batch_id = 1 ,$forceUpdate = false ,$direction=false)
    {
        if($forceUpdate){
            $cityConfig = Seocrawler_Candidate::get_instance()->initCityConfig();
        }


        $site_configs = Seocrawler_Candidate::get_instance()->getCityConfig($site,$city_id);

        if(!$site){
             print "no site to do \n";
             exit();
        }

        if(!$site_configs){
             print "no city like that \n";
             exit();
        }
        foreach( $site_configs as $site_id => $site_config){
            $offset++;
            $site_code = $site_config['site_code'];
            $root_url = $site_config['root_url'];

            if( $site_code ){
                $class_name = "Seocrawler_Site_{$site_code}SiteParser";
            }else{
                $class_name = "Seocrawler_Site_BaseSiteParser";
            }

            apf_require_class($class_name);

            $crawler_parser = new $class_name();
            $crawler_parser->setBatchId($batch_id);
            $crawler_parser->setSiteId($site_id);
            $crawler_parser->setConfig($site_config);

            if($direction){
                $result = $crawler_parser->processDetail($link);
                exit;
            }

            $result = Seocrawler_Candidate::get_instance()->initRootLink($site_id, $batch_id, $root_url, $crawler_parser->getLinkHash($root_url));
            $links = Seocrawler_Candidate::get_instance()->getCandidateLinks($site_id, $batch_id);

            print "get next links to grab \n";

            if (!$links) {
                print "nothing to do no links \n";
                /*if($offset == count($site_configs)){
                    exit();
                }*/
            }

            $OKLinks = array();
            $FAILEDLinks = array();

            foreach ($links as $link) {

                if ($link['type'] == Seocrawler_Const::LINK_TYPE_LIST
                    || $link['type'] == Seocrawler_Const::LINK_TYPE_ROOT ) {
                    print "process list {$link['url']} \n";
                    $result = $crawler_parser->processList($link);
                } elseif ($link['type'] == Seocrawler_Const::LINK_TYPE_DETAIL) {
                    print "process detail {$link['url']} \n";
                    $result = $crawler_parser->processDetail($link);
                } else {
                    $result = $crawler_parser->processPage($link);
                }

                if ($result) {
                    $OKLinks[] = $link['hash'];
                } else {
                    $FAILEDLinks[] = $link['hash'];
                }
            }


            if ($OKLinks) {
                Seocrawler_Candidate::get_instance()->updateStatus($site_id, $batch_id, $OKLinks, Seocrawler_Const::STATUS_OK);
            }

            if ($FAILEDLinks) {
                Seocrawler_Candidate::get_instance()->updateStatus($site_id, $batch_id, $FAILEDLinks, Seocrawler_Const::STATUS_FAILED);
            }
        }

    }




}
