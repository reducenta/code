<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';

$client = new \Http\Adapter\Guzzle7\Client();
$cdek = new \CdekSDK2\Client($client);

$t = \CdekSDK2\Actions\Offices::FILTER;

$cdek->setAccount('xxx');
$cdek->setSecure('xxx');

switch ($_REQUEST['action']){
    case 'get_position_info':

        $ip = $_SERVER['REMOTE_ADDR'];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.sypexgeo.net/json/' . $ip);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $answer = json_decode(curl_exec($ch), true);

        preg_match('/(\d{3}xxx):(\d{3}xxx)/', $answer['city']['post'], $matches);
        if(!empty($matches)){
            $answer['city']['post'] = str_replace('x', '0', $matches[1]);
        }

        curl_close($ch);

        break;
    case 'get_pvz_by_postal_code':

        $result = $cdek->offices()->getFiltered(['postal_code' => $_REQUEST['post_code']]);
        if ($result->isOk()) {
            $pvzlist = $cdek->formatResponseList($result, \CdekSDK2\Dto\PickupPointList::class);
            foreach($pvzlist->items as $item) {
                $answer[] = [
                    'code' => $item->code,
                    'name' => $item->name,
                    'address' => $item->location->address,
                    'address_detail' => $item->location->address_full,
                    'lat' => $item->location->latitude,
                    'lon' => $item->location->longitude,
                    'phone' => $item->phones[0]->number,
                    'shedule' => $item->work_time,
                ];
            }
        }
        break;
    case 'get_city_by_id':
        $result = $cdek->cities()->getFiltered(['code' => $_REQUEST['id']]);
        if ($result->isOk()) {
            $cities = $cdek->formatResponseList($result, \CdekSDK2\Dto\CityList::class);
            foreach($cities->items as $city)
            $answer[] = [
                'lat' => $city->latitude,
                'lon' => $city->longitude
            ];

        }
        break;
    case 'get_pvz_by_id':

        $result = $cdek->offices()->getFiltered(['city_code' => $_REQUEST['id']]);
        if ($result->isOk()) {
            $pvzlist = $cdek->formatResponseList($result, \CdekSDK2\Dto\PickupPointList::class);
            foreach($pvzlist->items as $item) {
                $answer[] = [
                    'code' => $item->code,
                    'name' => $item->name,
                    'address' => $item->location->address,
                    'address_detail' => $item->location->address_full,
                    'lat' => $item->location->latitude,
                    'lon' => $item->location->longitude,
                    'phone' => $item->phones[0]->number,
                    'shedule' => $item->work_time,
                ];
            }
        }

        break;
    case 'get_city_suggestions':
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.cdek.ru/city/getListByTerm/jsonp.php?q=' . $_POST['q']);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $answer = json_decode(curl_exec($ch), true);
        curl_close($ch);
        break;
}

header("Content-type: application/json");
exit(json_encode($answer));
