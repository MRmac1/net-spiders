<?php

apf_require_file('url_to_absolute.php');
apf_require_class('Utils_DOM');
apf_require_class('APF_Cache_Factory');
apf_require_class('Seocrawler_Candidate');
apf_require_class('Seocrawler_Proxy');

abstract class Seocrawler_SiteParser implements Seocrawler_SiteParserInterface
{
    protected $batchId;

    protected $siteId;

    protected $siteConfig;

    //abstract public function getLinkType($url, $anchor);


    //abstract public function processDetail($link);


    public function setBatchId($batch_id)
    {
        $this->batchId = $batch_id;
    }

    public function setSiteId($site_id)
    {
        $this->siteId = $site_id;
    }

    public function setConfig($site_config)
    {
        $this->siteConfig = $site_config;
    }

    public function getLinkHash($url)
    {
        // trim anchor
        $pos = strpos($url, '#');
        if ($pos !== false) {
            $url = substr($url, 0, $pos);
        }

        return md5($url);
    }


    /**
     * get http content
     * @param string $url
     */
    protected function getHttpContent($url, $session_id = 0)
    {
        $headers = null;
        $data = null;
        $cookiefile = null;
        $timeout = 10;

        list($proxy, $proxy_type) = Seocrawler_Proxy::getInstance()->getProxy($session_id);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);

        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_ENCODING, "");
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 5.1; Trident/4.0; .NET CLR 2.0.50727)");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        if ($proxy) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy);
            curl_setopt($ch, CURLOPT_PROXYTYPE, $proxy_type);

            print "use proxy: $proxy  type $proxy_type \n";
        }

        if ($headers) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        if ($cookiefile) {
            curl_setopt($ch, CURLOPT_COOKIEJAR, $cookiefile);
            curl_setopt($ch, CURLOPT_COOKIEFILE, $cookiefile);
        }

        if ($data) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }

        $content = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // http code 为300-399时是正常的跳转
        if ($httpcode >= 400) {
            return null;
        }

        return $content;
    }


    protected function absoluteUrl($base_url, $relative_url)
    {

        if (stripos($base_url, "http://") !== 0
            && stripos($base_url, "https://") !== 0) {
                $base_url  = 'http://'.$base_url;
            }

        return url_to_absolute($base_url, $relative_url);
    }


    /**
     * 处理列表页
     * 一般的逻辑就是提取里面的链接，然后存起来
     */
    public function processList($link)
    {
        $url = $link['url'];
        $level = $link['level'];
        $candidate_id = $link['id'];

        // check link type again
        $linkType = $this->getLinkType($url);
        if (!in_array($linkType, array(
            Seocrawler_Const::LINK_TYPE_LIST,
            Seocrawler_Const::LINK_TYPE_ROOT
        ))) {
            print "skip $url in check type \n";
            return true;
        }

        $candidates = array();

        $content = $this->getHttpContent($url, $link['parent_id']);
        $pageDom = Utils_DOM::createDomFromHtml($content);
        $pageDom = Utils_DOM::filterDom($pageDom, $url);

        if (!$pageDom) {
            print "{$url} no pagedom \n";
            return false;
        }

        $aNodes = $pageDom->getElementsByTagName('a');

        $level = $level + 1;
        foreach ($aNodes as $aNode) {
            $title = trim($aNode->textContent);
            if (empty($title)) {
                print "no title link : {$href} \n";
                continue;
            }

            $href = $aNode->getAttribute('href');
            $href = $this->absoluteUrl($url, $href);

            if (stripos($href, 'http') !== 0) {
                print "no http link : {$href} \n";
                continue;
            }

            $hash = $this->getLinkHash($href);

            if (Seocrawler_Candidate::get_instance()->isExists($this->siteId, $this->batchId, $hash)) {
                print " $href  in candidates \n";
                continue;
            }

            $type = $this->getLinkType($href, $title);

            if ($type == Seocrawler_Const:: LINK_TYPE_DETAIL
                || $type == Seocrawler_Const:: LINK_TYPE_LIST) {

                $candidates[] = array(
                    'url' => $href,
                    'title' => $title,
                    'hash' => $hash,
                    'type' => $type,
                    'level' => $level,
                    'parent_id' => $candidate_id,
                );
            }
        }

        // insert it to candidate
        if ($candidates) {
            Seocrawler_Candidate::get_instance()->addCandidateLinks($this->siteId, $this->batchId, $candidates);
        }

        return true;
    }


    public function processPage($link)
    {
        // generally do nothing
        return true;
    }

}
