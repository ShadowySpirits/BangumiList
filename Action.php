<?php

class BangumiAPI
{
    private static $bangumiAPI = null;

    private static $apiUrl = 'https://api.bgm.tv';

    private $myCollection;

    private $collectionApi = '';


    public static function GetInstance()
    {
        if (BangumiAPI::$bangumiAPI == null) {
            BangumiAPI::$bangumiAPI = new BangumiAPI();
        }
        return BangumiAPI::$bangumiAPI;
    }

    private function __construct()
    {
    }

    public function initCollectionApi($userID)
    {
        $this->collectionApi = BangumiAPI::$apiUrl . '/user/' . $userID . '/collection?cat=playing';
    }

    public function getCollection($userID, $hasCache, $cacheTime)
    {
        if (empty($userID)) return false;
        $this->initCollectionApi($userID);
        $FilePath = __DIR__ . '/bgm-list.json';
        if ($hasCache && file_exists($FilePath) && time() - filemtime($FilePath) < $cacheTime) {
            $file = fopen($FilePath, 'r');
            if (!$this->verifyCollection(fread($file, filesize($FilePath)))) {
                $data = BangumiAPI::curl_get_contents($this->collectionApi);
                if (!$this->verifyCollection($data)) return false;
                file_put_contents($FilePath, $data, LOCK_EX);
            }
            fclose($file);
        } else {
            $data = BangumiAPI::curl_get_contents($this->collectionApi);
            if (!$this->verifyCollection($data)) return false;
            file_put_contents($FilePath, $data, LOCK_EX);
        }
        return true;
    }

    public function verifyCollection($data)
    {
        $content = json_decode($data);
        if (empty($content)) return false;
        $index = 0;
        foreach ($content as $value) {
            $this->myCollection[$index++] = $value;
        }
        return true;
    }

    public function printCollecion()
    {
        echo '<style>a.bangumi{line-height:20px;white-space:nowrap;box-shadow:0 3px 8px rgba(0,0,0,0.2);width:45%;margin:1.5%;float:left;overflow:hidden;display:block;height:100%;background:#fff;color:#14191e;font-family:-apple-system,BlinkMacSystemFont,Helvetica Neue,PingFang SC,Microsoft YaHei,Source Han Sans SC,Noto Sans CJK SC,WenQuanYi Micro Hei,sans-serif;border-radius:5px;padding-right:5px}a.bangumi:hover{color:#14191e;opacity:.8;filter:saturate(150%);-webkit-filter:saturate(150%);-moz-filter:saturate(150%);-o-filter:saturate(150%);-ms-filter:saturate(150%)}a.bangumi img{width:80px;height:112px;display:inline-block;float:left;margin: 0 10px 0 0!important;padding: 0!important;}a.bangumi .textBox{text-overflow:ellipsis;overflow:hidden;position:relative;z-index:1;height:100%;margin:inherit;line-height:24px}a.bangumi div.progressBG{height:20px;width:100%;background-color:gray;display:inline-block;border-radius:4px;position:absolute;bottom:3px}a.bangumi div.progressFG{height:20px;background-color:#0078d7;border-radius:4px;position:absolute;bottom:0;z-index:1}a.bangumi div.progressText{width:100%;height:20px;text-align:center;color:#fff;line-height:20px;font-size:15px;position:absolute;bottom:0;z-index:2}@media screen and (max-width:1000px){a.bangumi{width:95%}}</style>';
        foreach ($this->myCollection as $value) {
            $epsNum = '未知';
            if (@$value->subject->eps) {
                $epsNum = $value->subject->eps;
            }
            $progressNum = $value->ep_status;
            $myProgress = $progressNum . '/' . $epsNum;
            $name = $value->name;
            $nameCN = $value->subject->name_cn;
            if (@!$nameCN) {
                $nameCN = $name;
            }
            $air_date = $value->subject->air_date;
            $url = str_replace('http://', 'https://', $value->subject->url);
            $imgUrl = str_replace('http://', 'https://', $value->subject->images->common);
            if ($epsNum == '未知') {
                $progressWidth = 100;
            } else {
                $progressWidth = $progressNum / $epsNum * 100;
                if ($progressWidth > 100) {
                    $progressWidth = 100;
                }
            }
            echo "
<a href=" . $url . " target='_blank' class='bangumi' style='border-bottom: 0px;'>
    <img src='$imgUrl'/>
    <div class='textBox'>$nameCN<br>
        $name<br>
        首播日期：$air_date<br><br>
        <div class='progressBG'>
            <div class='progressText'>进度:$myProgress</div>
            <div class='progressFG' style='width:" . $progressWidth . "%;'>
            </div>
        </div>
    </div>
</a>
          ";
        }
        echo '<div style="clear:both"></div>';
    }

    private static function curl_get_contents($_url)
    {
        $myCurl = curl_init($_url);
        curl_setopt($myCurl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($myCurl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($myCurl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($myCurl, CURLOPT_HEADER, false);
        $content = curl_exec($myCurl);
        curl_close($myCurl);
        return $content;
    }
}

class BangumiList_Action extends Widget_Abstract_Contents implements Widget_Interface_Do
{

    public function action()
    {
        $options = Helper::options();
        $userID = trim($options->plugin('BangumiList')->userID);
        $hasCache = $options->plugin('BangumiList')->hasCache;
        $cacheTime = trim($options->plugin('BangumiList')->cacheTime);

        $bangumi = BangumiAPI::GetInstance();
        if ($bangumi->getCollection($userID, $hasCache, $cacheTime)) {
            $bangumi->printCollecion();
        } else {
            echo '没有追番记录';
        }
    }
}