<?php

interface Seocrawler_SiteParserInterface
{

    public function setBatchId($batch_id);

    public function setConfig($site_config);
    
    public function getLinkType($url, $anchor);

    public function getLinkHash($url);

    public function processDetail($link);

    public function processList($link);

    public function processPage($link);

}
