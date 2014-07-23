<?php

class Crawler
{
    public function getCounty()
    {
        return array(
            63 => "臺北市",
            66 => "臺中市",
            10017 => "基隆市",
            67 => "臺南市",
            64 => "高雄市",
            65 => "新北市",
            10002 => "宜蘭縣",
            10003 => "桃園縣",
            10020 => "嘉義市",
            10004 => "新竹縣",
            10005 => "苗栗縣",
            10008 => "南投縣",
            10007 => "彰化縣",
            10018 => "新竹市",
            10009 => "雲林縣",
            10010 => "嘉義縣",
            10013 => "屏東縣",
            10015 => "花蓮縣",
            10014 => "臺東縣",
            09020 => "金門縣",
            10016 => "澎湖縣",
            09007 => "連江縣",
        );
    }

    protected $_curl = null;

    public function getCurl()
    {
        if (is_null($this->_curl)) {
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_COOKIEFILE, '');
            curl_setopt($curl, CURLOPT_COOKIESESSION, true);

            curl_setopt($curl, CURLOPT_URL, 'http://luz.tcd.gov.tw/NLPDB2012/');
            $content = curl_exec($curl);

            curl_setopt($curl, CURLOPT_URL, 'http://luz.tcd.gov.tw/NLPDB2012/main2.aspx');
            $content = curl_exec($curl);

            $this->_curl = $curl;

        }
        return $this->_curl;
    }

    public function getTown($county_id)
    {
        $curl = $this->getCurl();
        curl_setopt($curl, CURLOPT_URL, 'http://luz.tcd.gov.tw/NLPDB2012/ws_data.ashx');
        curl_setopt($curl, CURLOPT_POSTFIELDS, 'CMD=GETDATA&FUNC=0101&OBJ=TOWN&COUNTY=' . $county_id);
        $content = curl_exec($curl);
        $doc = new DOMDocument;
        $doc->loadXML($content);

        $ret = array();
        foreach ($doc->getElementsByTagName('FEATURE') as $feature_dom) {
            $value = $feature_dom->getElementsByTagName('VALUE')->item(0)->nodeValue;
            $text = $feature_dom->getElementsByTagName('TEXT')->item(0)->nodeValue;
            if ($value === '') {
                continue;
            }
            $ret[$value] = $text;
        }
        curl_close($curl);
        return $ret;
    }

    public function getPlan($county_id)
    {
        // POST http://luz.tcd.gov.tw/NLPDB2012/ws_data.ashx?CMD=GETDATA&FUNC=0101&OBJ=URBANPLAN&COUNTY=65
        $curl = $this->getCurl();
        curl_setopt($curl, CURLOPT_URL, 'http://luz.tcd.gov.tw/NLPDB2012/ws_data.ashx');
        curl_setopt($curl, CURLOPT_POSTFIELDS, 'CMD=GETDATA&FUNC=0101&OBJ=URBANPLAN&COUNTY=' . $county_id);
        $content = curl_exec($curl);
        $doc = new DOMDocument;
        $doc->loadXML($content);

        $ret = array();
        foreach ($doc->getElementsByTagName('FEATURE') as $feature_dom) {
            $value = $feature_dom->getElementsByTagName('VALUE')->item(0)->nodeValue;
            $text = $feature_dom->getElementsByTagName('TEXT')->item(0)->nodeValue;
            $ret[$value] = $text;
        }
        curl_close($curl);
        return $ret;
    }

    public function getBound($plan_id, $layer)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_COOKIEFILE, '');
        curl_setopt($curl, CURLOPT_COOKIESESSION, true);

        curl_setopt($curl, CURLOPT_URL, 'http://luz.tcd.gov.tw/NLPDB2012/');
        $content = curl_exec($curl);

        curl_setopt($curl, CURLOPT_URL, 'http://luz.tcd.gov.tw/NLPDB2012/main2.aspx');
        $content = curl_exec($curl);
        $doc = new DOMDocument;
        @$doc->loadHTML($content);
        $iframe_dom = $doc->getElementById('myiframe');
        $url = $iframe_dom->getAttribute('src');
        $ret = parse_url($url);
        parse_str($ret['query'], $params);
        $session = $params['session'];
        $ApplicationDefinition = $params["ApplicationDefinition"];

        // POST http://luz.tcd.gov.tw/mapserver2012/fusion/layers/MapGuide/php/LoadMap.php
        // mapid:Library://NLPDB2012/County-MAP.MapDefinition
        // session:e17b0c80-0fc1-11e4-8000-000000000000_en_7F0000010B060B050B04
        // 目的是為了得到 map_name 及產生 session
        //
        curl_setopt($curl, CURLOPT_URL, 'http://luz.tcd.gov.tw/mapserver2012/fusion/layers/MapGuide/php/LoadMap.php');
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, 'session=' . urlencode($session) . '&mapid=' . urlencode('Library://NLPDB2012/County-MAP.MapDefinition'));
        $content = curl_exec($curl);
        $ret = json_decode($content);
        $mapId = $ret->mapId;
        $mapName = $ret->mapName;
        /*     [sessionId] => 2ca46010-10e6-11e4-8000-000000000000_en_7F0000010B060B050B04
    [mapId] => Library://NLPDB2012/County-MAP.MapDefinition
    [metersPerUnit] => 1
    [wkt] => PROJCS["WGS84.PseudoMercator",GEOGCS["LL84",DATUM["WGS84",SPHEROID["WGS84",6378137.000,298.25722293]],PRIMEM["Greenwich",0],UNIT["Degree",0.017453292519943295]],PROJECTION["Popular Visualisation Pseudo Mercator"],PARAMETER["false_easting",0.000],PARAMETER["false_northing",0.000],PARAMETER["central_meridian",0.00000000000000],UNIT["Meter",1.00000000000000]]
    [epsg] => 900913
    [siteVersion] => 2.3.0.5801
    [mapTitle] => County-MAP
    [mapName] => County-MAP53cd286849f99
    [backgroundColor] => #ffffff
    [extent] => Array
        (
            [0] => 12966000
            [1] => 2318900
            [2] => 13868000
            [3] => 3047400
        )

         */

        curl_setopt($curl, CURLOPT_URL, 'http://luz.tcd.gov.tw/NLPDB2012/ws_form.ashx');
        curl_setopt($curl, CURLOPT_POSTFIELDS, 'CMD=GETLAYERS&FUNC=9999&MAPNAME=' . urlencode($mapName));
        $content = curl_exec($curl);
            
        // POST http://luz.tcd.gov.tw/NLPDB2012/ws_search.ashx
        // CMD:ZOOMTOFEAT
        // FUNC:0101
        // LAYER:都市計畫區
        // ID:A0101
        // 取得 layer_id
        curl_setopt($curl, CURLOPT_URL, 'http://luz.tcd.gov.tw/NLPDB2012/ws_search.ashx');
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, 'CMD=ZOOMTOFEAT&FUNC=0101&LAYER=' . ($layer) . '&ID=' . urlencode($plan_id));
        $content = curl_exec($curl);
        //string(300) "<?xml version="1.0" encoding="UTF-8"><FeatureSet xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="FeatureSet-1.0.0.xsd"> <Layer id="7baaf5e0-10ee-11e4-8000-000000000000">  <Class id="Default:都市計畫範圍">   <ID>AQAAAA==</ID>  </Class> </Layer></FeatureSet>"
        $doc = new DOMDocument;
        @$doc->loadXML($content);
        $layer_id = $doc->getElementsByTagName('Layer')->item(0)->getAttribute('id');
        $layer_content = $content;

        // POST http://luz.tcd.gov.tw/mapserver2012/fusion/layers/MapGuide/php/SaveSelection.php
        // mapname:County-MAP53cb3e074edba
        // session:e17b0c80-0fc1-11e4-8000-000000000000_en_7F0000010B060B050B04
        // selection:<?xml version="1.0" encoding="UTF-8"? ><FeatureSet xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="FeatureSet-1.0.0.xsd"> <Layer id="e3af9fc0-0fc1-11e4-8000-000000000000">  <Class id="Default:都市計畫範圍">   <ID>AQAAAA==</ID>  </Class> </Layer></FeatureSet>
        // seq:0.8129159910604358
        // getextents:true
        // 可以得到 minx, miny, maxx, maxy 等資訊並把這個 layer 存進 session
        curl_setopt($curl, CURLOPT_URL, 'http://luz.tcd.gov.tw/mapserver2012/fusion/layers/MapGuide/php/SaveSelection.php');
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, 'mapname=' . urlencode($mapName) . '&session=' . urlencode($session) . '&selection=' . urlencode($layer_content) . '&seq=' . uniqid() . '&getextents=true');
        $content = curl_exec($curl);
        //[extents] => stdClass Object
        //(
        //[minx] => 13520385.337207
        //[miny] => 2870838.0671901
        //[maxx] => 13543889.914143
        //[maxy] => 2901633.1676192
        //)
        $extents = json_decode($content)->extents;


        // GET http://luz.tcd.gov.tw/mapserver2012/mapagent/mapagent.fcgi?
        // session:e17b0c80-0fc1-11e4-8000-000000000000_en_7F0000010B060B050B04
        // mapname:County-MAP53cb3e074edba
        // format:PNG
        // behavior:5
        // clientagent:Fusion Viewer
        // selectioncolor:0x0000FFA0
        // operation:GETDYNAMICMAPOVERLAYIMAGE
        // locale:en
        // clip:1
        // version:2.0.0
        // _olSalt:0.774189971620217
        // setdisplaydpi:96
        // setdisplayheight:339
        // setdisplaywidth:1024
        // setviewcenterx:13532137.625675
        // setviewcentery:2886235.6174046
        // setviewscale:288896.01088811207
        // 取得 bounary 圖片
        $delta_x = $extents->maxx - $extents->minx;
        $delta_y = $extents->maxy - $extents->miny;
        $min_side = min($delta_x, $delta_y);
        $params = array(
            'session' => $session,
            'mapname' => $mapName,
            'format' => 'PNG',
            'behavior' => 5,
            'clientagent' => 'Fusion Viewer',
            'selectioncolor' => '0xFFFFFF00',
            //'selectioncolor' => '0x000000A0',
            'operation' => 'GETDYNAMICMAPOVERLAYIMAGE',
            'locale' => 'en',
            'clip' => 1,
            'version' => '2.0.0',
            '_olSalt' => uniqid(),
            'setdisplaydpi' => 96,
            'setdisplayheight' => 1000 * $delta_y / $min_side,
            'setdisplaywidth' => 1000 * $delta_x / $min_side,
            'setviewcenterx' => ($extents->minx + $extents->maxx) / 2,
            'setviewcentery' => ($extents->miny + $extents->maxy) / 2,
            'setviewscale' => 1.1 * $min_side * 100 / (2.54 * 1000 / 96),
        );
        $terms = array();
        foreach ($params as $k => $v) {
            $terms[] = $k . '=' . urlencode($v);
        }
        $tmpfile = tmpfile();
        curl_setopt($curl, CURLOPT_URL, 'http://luz.tcd.gov.tw/mapserver2012/mapagent/mapagent.fcgi?' . implode('&', $terms));
        curl_setopt($curl, CURLOPT_POST, false);
        curl_setopt($curl, CURLOPT_FILE, $tmpfile);
        $content = curl_exec($curl);
        curl_close($curl);

        return array(
            'fp' => $tmpfile,
            'params' => $params,
        );
    }

    public function transformPoint($image_point, $params)
    {
        $image_height = $params['setdisplayheight'];
        $image_width = $params['setdisplaywidth'];
        $dpi = $params['setdisplaydpi'];
        $view_center = array($params['setviewcenterx'], $params['setviewcentery']);
        $view_scale = $params['setviewscale'];

        $image_center = array($image_width / 2, $image_height / 2);
        $px = 2.54 * $view_scale * 1 / $dpi / 100;
        return array(
            $view_center[0] + $px * ($image_point[0] - $image_center[0]),
            $view_center[1] - $px * ($image_point[1] - $image_center[1]),
        );
    }

    public function getPolygonFromImage($fp, $params)
    {
        $cmd = "./findcontour2json " . escapeshellarg(stream_get_meta_data($fp)['uri']);
        $image_polygons = json_decode(`$cmd`);

        $ret = new StdClass;
        $ret->type = 'MultiPolygon';
        $ret->coordinates = array();
        foreach ($image_polygons as $image_polygon) {
            $view_polygon = array();
            foreach ($image_polygon as $image_point) {
                $view_polygon[] = $this->transformPoint($image_point, $params);
            }
            $view_polygon[] = $view_polygon[0];
            $ret->coordinates[] = array($view_polygon);
        }
        return $ret;
    }

    public function transformWithPostGIS($geojson, $from, $to)
    {
        $config = json_decode(file_get_contents('pgsql.json'));
        $pdo = new PDO("pgsql:host={$config->host};user={$config->user};password={$config->password};dbname={$config->dbname}");
        $sth = $pdo->prepare("WITH multi AS ( SELECT (ST_Dump(ST_GeomFromGeoJSON(:geojson))).geom ) SELECT ST_AsGeoJSON(ST_Transform(ST_SetSRID(ST_Union(geom), :from_srid), :target_srid)) FROM multi");
        $sth->execute(array(
            ':geojson' => json_encode($geojson),
            ':from_srid' => $from,
            ':target_srid' => $to,
        ));
        $ret = $sth->fetchAll();
        $error_info = $pdo->errorInfo();
        if ($error_info[1]) {
            throw new Exception($error_info[2], $error_info[1]);
        }
        return $ret[0][0];
    }
}
