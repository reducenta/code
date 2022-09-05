<?php

use Symfony\Component\Console\Helper\ProgressBar;

class Update extends Bitrix
{
    const xml_local_name = 'Assets/update.xml';
    const xml_url = 'https://divine-light.ru/download/xml-7bf7d3622cb2745e5e99ca58db3290ec.18660-catalog/';
    const price_combile_db = 'Assets/prices.sqlite';

    const xml_reload_period = 60 * 60 * 24;
    const iblock_id = 25;
    const default_user = 1;
    const default_upload_section = 413;
    const sale_section = 414;

    public $xml;

    public $sections;
    public $manufacturers;
    public $properties;
    public $places;
    public $props_list;
    public $props_typeL_enums;

    public $update_start;
    public $updated_end;
    public $prices_db;

    public $links2index_file;

    public function __construct($save_xml = true)
    {
        parent::__construct();

        $this->update_start = new DateTime();
        $this->update_start->setTimestamp(time());

        $this->sections = require_once 'Config/sections.php';
        $this->properties = require_once 'Config/properties.php';
        $this->places = require_once 'Config/places.php';

        $this->prices_db = new SQLite3(self::price_combile_db);
        $this->links2index_file = $_SERVER['DOCUMENT_ROOT'] . '/upload/indexnow.txt';

        $rsBrands = CIBlockElement::GetList(array(), array('IBLOCK_ID' => 12, 'PROPERTY_INCLUDE_UPLOAD_VALUE' => 'Y'), false, false, array('ID', 'NAME'));
        while($brand = $rsBrands->Fetch()){
            $this->manufacturers[$brand['ID']] = strtolower($brand['NAME']);
        }

        $this->props_list = [];
        $rs = CIBlockProperty::GetList(array(), array('IBLOCK_ID' => self::iblock_id));
        while($property = $rs->Fetch()){
            $this->props_list[] = [
                'id' => $property['ID'],
                'name' => $property['NAME'],
                'code' => $property['CODE'],
                'type' => $property['PROPERTY_TYPE']
            ];
        }

        //Получим все варианты свойств типа "Список"
        $this->build_propsL_list();

        if($save_xml){
            if($this->save_xml()){
                $this->_open_xml();
            }
        }else{
            if(file_exists(self::xml_local_name)){
                $this->_open_xml();
            }
        }
    }

    private function _open_xml(){
        $this->xml = new XMLReader();
        $this->xml->open(self::xml_local_name);
    }

    public function build_propsL_list(){
        $lists = CIBlockProperty::GetList(array(), array('IBLOCK_ID' => self::iblock_id, 'PROPERTY_TYPE' => 'L'));
        while($list = $lists->Fetch()){
            $property_enums = CIBlockPropertyEnum::GetList(Array(), Array("PROPERTY_ID" => $list['ID']));
            while($property_enum = $property_enums->Fetch()){
                $this->props_typeL_enums[$list['ID']][$property_enum['ID']] = $property_enum['VALUE'];
            }
        }
    }

    /**
     * Сохраняет файл выгрузки
     * @return bool
     */
    public function save_xml()
    {
        if(!file_exists(self::xml_local_name) || ((filectime(self::xml_local_name) + self::xml_reload_period) <= time())){
            if(file_put_contents(self::xml_local_name, file_get_contents(self::xml_url)) !== false){
                //$this->send('xml сохранен');
                return true;
            }else{
                return false;
            }
        }else{
            return true;
        }

    }

    /**
     * Конвертирует xml товара в массив
     * @param $item
     * @return array
     */
    private function xml2array($item)
    {
        $props = [];
        foreach($item->param as $prop){
            foreach($prop->attributes() as $name) {

                $name = $name->__toString();
                $value = $prop->__toString();

                $props[] = [
                    'name' => $name,
                    'value' => $value
                ];
            }
        }

        $json = json_encode($item);
        $array = json_decode($json,TRUE);

        $attributes = $array['@attributes'];
        unset($array['@attributes'], $array['param']);

        return array_merge($attributes, $array, ['props' => $props]);
    }

    public function is_product_exist($name, $manufacturer_id){

        $rs = CIBlockElement::GetList(Array(), Array("IBLOCK_ID" => self::iblock_id, "NAME" => $name, "PROPERTY_PROP_MANUFACTURER" => $manufacturer_id), false, false, Array('ID', 'DETAIL_PAGE_URL'));
        if($rs->SelectedRowsCount() > 0){
            $product = $rs->GetNext();
            return [
                'ID' => $product['ID'],
                'URL' => $product['DETAIL_PAGE_URL']
            ];
        }else{
            return false;
        }
    }

    private function get_section($id)
    {
        $section = array_search($id, $this->sections);
        if($section !== false){
            return $section;
        }else{
            return self::default_upload_section;
        }
    }

    public function find_property_value($props, $name){
        foreach($props as $item){
            if($item['name'] == $name){
                return $item['value'];
            }
        }
        return false;
    }



    private function set_properties($offer_array)
    {
        $return = [];
        foreach($this->props_list as $property){
            switch ($property['code']){
                case 'prop_photos':
                    if(is_array($offer_array['picture'])){
                        unset($offer_array['picture'][0]);
                        foreach($offer_array['picture'] as $pic){
                            $return['prop_photos'][] = CFile::MakeFileArray($pic);
                        }
                    }
                    break;

                case 'prop_manufacturer':
                    $brand_id = array_search(strtolower($offer_array['vendor']), $this->manufacturers);
                    if($brand_id !== false){
                        $return[$property['code']] = $brand_id;
                    }

                    break;

                case 'length':
                case 'prop_height':
                case 'prop_diameter':
                    $value = $this->find_property_value($offer_array['props'], $this->properties[$property['id']]);
                    if($value !== false){
                        $return[$property['code']] = $value/10;
                    }
                    break;

                case 'prop_warranty':
                    $return['prop_warranty'] = '24 месяца';
                    break;

                case 'prop_location':

                    $ip = $this->find_property_value($offer_array['props'], 'степень защиты ip');
                    if(($ip > 51) || ($ip == 44)){
                        $return[$property['code']] = 104;
                    }else{
                        $return[$property['code']] = $this->places[$this->find_property_value($offer_array['props'], 'стиль')];
                    }

                    break;

                default:

                    switch($property['type']){
                        case 'L':

                            $value = $this->find_property_value($offer_array['props'], $this->properties[$property['id']]);
                            if(in_array($value, $this->props_typeL_enums[$property['id']])){
                                $return[$property['code']] = array_search($value, $this->props_typeL_enums[$property['id']]);
                            }else{
                                $ibpenum = new CIBlockPropertyEnum;
                                $new_value_id = $ibpenum->Add(Array('PROPERTY_ID' => $property['id'], 'VALUE' => $value));
                                $return[$property['code']] = $new_value_id;
                                $this->props_typeL_enums[$property['id']][$new_value_id] = $value;
                            }

                            break;

                        case 'N':
                        case 'S':
                            $return[$property['code']] = $this->find_property_value($offer_array['props'], $this->properties[$property['id']]);
                            break;

                    }

                    break;
            }
        }

        $return['CML2_ARTICLE'] = $offer_array['vendorCode'];

        return $return;
    }

    public function _set_discount($product_id, $price){
        $Conditions["CLASS_ID"] = "CondGroup";
        $Conditions["DATA"]["All"] = "AND";
        $Conditions["DATA"]["True"] = "True";
        $Conditions["CHILDREN"] = "";
        $arSD = [
            "LID" => "s1",
            "NAME" => 'automatic_discount_' . $product_id,
            "LAST_DISCOUNT" => "N",
            "ACTIVE" => "Y",
            'ACTIONS' => [
                'CLASS_ID' => 'CondGroup',
                'DATA' => ['All' => 'AND'],
                'CHILDREN' => [
                    [
                        'CLASS_ID' => 'ActSaleBsktGrp',
                        'DATA' => [
                            'Type' => 'Closeout',
                            'Value' => $price,
                            'Unit' => 'CurEach',
                            'Max' => 1,
                            'All' => 'AND',
                            'True' => 'True'
                        ],
                        'CHILDREN' => [
                            [
                                'CLASS_ID' => 'CondIBElement',
                                'DATA' => [
                                    'logic' => 'Equal',
                                    'value' => $product_id
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            "CONDITIONS" => $Conditions,
            "USER_GROUPS" => array(2), // группы пользователей, можно просмотреть в настройки -> пользователи -> группы
            "CURRENCY" => "RUB"
        ];

        return CSaleDiscount::Add($arSD);
    }

    public function _add2sale_section($id){
        $IDS = array();
        $rsElement = CIBlockElement::GetElementGroups($id);
        while($element = $rsElement->Fetch()){
            $IDS[] = $element['ID'];
        }
        array_push($IDS, self::sale_section);
        CIBlockElement::SetElementSection($id, $IDS);
        \Bitrix\Iblock\PropertyIndex\Manager::updateElementIndex(self::iblock_id, $id);
        return true;
    }

    public function _remove_from_sale_section($id){
        $IDS = array();
        $rsElement = CIBlockElement::GetElementGroups($id);
        while($element = $rsElement->Fetch()){
            $IDS[] = $element['ID'];
        }

        if(in_array(self::sale_section, $IDS) == true){
            unset($IDS[array_search(self::sale_section, $IDS)]);
            CIBlockElement::SetElementSection($id, $IDS);
            \Bitrix\Iblock\PropertyIndex\Manager::updateElementIndex(self::iblock_id, $id);
        }


        return true;
    }

    public function get_weight($props){
        $weight_value = $this->find_property_value($props, 'вес');
        if($weight_value !== false){
            return $weight_value * 1000;
        }else{
            return 0;
        }
    }

    public function save_link2index($url)
    {
        $link = 'https://svet-magazin.ru' . $url;
        file_put_contents($this->links2index_file, $link . PHP_EOL, FILE_APPEND);
        return true;
    }

    public function process($output)
    {
        $processed = 0;
        $added = 0;
        $updated = 0;

        $xml = new XMLReader();
        $xml->open(self::xml_local_name);
        $bar_count = 0;
        while($xml->read()) {
            if ($xml->localName == 'offer') {
                $bar_count++;
            }
        }

        $progressBar = new ProgressBar($output, $bar_count);
        $progressBar->setFormat('%current%/%max% %percent:3s%% [%bar%]% %elapsed:6s%/%estimated:-6s%');


        while($this->xml->read()) {
            if ($this->xml->localName == 'offer') {

                $processed++;
                $progressBar->advance();

                $offer_array = $this->xml2array(simplexml_load_string($this->xml->readOuterXML(), 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NOBLANKS));

                //Производитель.
                //Если производитель нам не нужен - пропускаем этот товар -----------
                $manufacturer_id = array_search(strtolower($offer_array['vendor']), $this->manufacturers);
                if($manufacturer_id == false){
                    continue;
                }
                //-------------------------------------------------------------------


                if(!empty($offer_array['vendorCode'])){

                    if(!empty($only) && ($only != $offer_array['vendorCode'])) continue;

                    $this_product_exist = $this->is_product_exist($offer_array['vendorCode'], $manufacturer_id);

                    if($this_product_exist === false){

                        $name = $offer_array['vendorCode'];

                        if(is_array($offer_array['picture'])){
                            $detail_picture = CFile::MakeFileArray($offer_array['picture'][0]);
                        }else{
                            $detail_picture = CFile::MakeFileArray($offer_array['picture']);
                        }

                        $preview_text = trim(str_replace($name, '', $offer_array['name']));

                        $arLoadProductArray = Array(
                            "MODIFIED_BY"    	=> self::default_user,
                            "IBLOCK_SECTION_ID" => $this->get_section($offer_array['categoryId']),
                            "IBLOCK_ID"      	=> self::iblock_id,
                            "PROPERTY_VALUES"	=> $this->set_properties($offer_array),
                            "NAME"           	=> $name,
                            "CODE" 				=> Cutil::translit($name, 'ru'),
                            "ACTIVE"         	=> "Y",
                            "DETAIL_PICTURE" 	=> $detail_picture,
                            'PREVIEW_TEXT'      => $preview_text,
                            'PREVIEW_TEXT_TYPE' => 'text',
                            'DETAIL_TEXT'       => $offer_array['description'],
                            'DETAIL_TEXT_TYPE'  => 'text'
                        );

                        $el = new CIBlockElement;

                        if($PRODUCT_ID = $el->Add($arLoadProductArray)){

                            if($offer_array['available'] === false){
                                $quantity = 0;
                            }else{
                                $quantity = $offer_array['catalogQuantity'];
                            }

                            $arFields = array(
                                "ID" => $PRODUCT_ID,
                                "QUANTITY" => $quantity,
                                "WEIGHT" => $this->get_weight($offer_array['props'])
                            );

                            CCatalogProduct::Add($arFields);

                            //Смотрим что с oldprice

                            $price = $this->prices_db->querySingle("SELECT price FROM prices WHERE vendor_name = '" . $offer_array['vendor'] . "' AND vendor_code = '" . $offer_array['vendorCode'] . "'");
                            $old_price = $this->prices_db->querySingle("SELECT old_price FROM prices WHERE vendor_name = '" . $offer_array['vendor'] . "' AND vendor_code = '" . $offer_array['vendorCode'] . "'");

                            if(($price !== null) || ($old_price !== null)){
                                if($old_price > 0){
                                    $this->_set_discount($PRODUCT_ID, $price);
                                    $this->_add2sale_section($PRODUCT_ID);

                                    $new_price = $old_price;
                                }else{
                                    $new_price = $price;
                                }

                                $arFields = Array(
                                    "PRODUCT_ID" => $PRODUCT_ID,
                                    "CATALOG_GROUP_ID" => 1,
                                    "PRICE" => $new_price,
                                    "CURRENCY" => "RUB",
                                );

                                $res = CPrice::GetList(
                                    array(),
                                    array(
                                        "PRODUCT_ID" => $PRODUCT_ID,
                                        "CATALOG_GROUP_ID" => 1
                                    )
                                );

                                CPrice::Add($arFields);
                            }

                            $added++;
                        }


                    }else{

                        $OFFER_ID = $this_product_exist['ID'];

                        //Количество--------------------------------------

                        if($offer_array['quantity'] === false){
                            $quantity = 0;
                        }else{
                            $quantity = $offer_array['quantity'];
                        }

                        $PRICE_TYPE_ID = 1;
                        CCatalogProduct::Update($OFFER_ID, array('QUANTITY' => $quantity, "WEIGHT" => $this->get_weight($offer_array['props'])));

                        //ищем цены в sqlite

                        $price = $this->prices_db->querySingle("SELECT price FROM prices WHERE vendor_name = '" . $offer_array['vendor'] . "' AND vendor_code = '" . $offer_array['vendorCode'] . "'");
                        $old_price = $this->prices_db->querySingle("SELECT old_price FROM prices WHERE vendor_name = '" . $offer_array['vendor'] . "' AND vendor_code = '" . $offer_array['vendorCode'] . "'");

                        //Смотрим что с oldprice
                        if(($price !== null) || ($old_price !== null)){

                            //Скидки -----------------------------------------
                            $dbProductDiscounts = CSaleDiscount::GetList(array(), array('NAME' => 'automatic_discount_' . $OFFER_ID), false, false, array('ID'));
                            if($dbProductDiscounts->SelectedRowsCount() > 0){
                                $discount = $dbProductDiscounts->Fetch();
                                CSaleDiscount::Delete($discount['ID']);
                                $this->_remove_from_sale_section($OFFER_ID);
                            }

                            if($old_price > 0){
                                $this->_set_discount($OFFER_ID, $price);
                                $this->_add2sale_section($OFFER_ID);
                                $new_price = $old_price;
                            }else{
                                $new_price = $price;
                            }

                            $arFields = Array(
                                "PRODUCT_ID" => $OFFER_ID,
                                "CATALOG_GROUP_ID" => $PRICE_TYPE_ID,
                                "PRICE" => $new_price,
                                "CURRENCY" => "RUB",
                            );

                            $res = CPrice::GetList(
                                array(),
                                array(
                                    "PRODUCT_ID" => $OFFER_ID,
                                    "CATALOG_GROUP_ID" => $PRICE_TYPE_ID
                                )
                            );

                            if ($arr = $res->Fetch()){
                                CPrice::Update($arr["ID"], $arFields);
                            }else{
                                CPrice::Add($arFields);
                            }

                            $this->save_link2index($this_product_exist['URL']);
                        }


                        $updated++;

                    }
                }

            }
        }

        $progressBar->finish();

        $execute_time = (new DateTime())->setTimestamp(time())->diff($this->update_start)->format('%h час, %i мин, %s сек');

        $formatter = new IntlDateFormatter(
            'ru-RU',
            IntlDateFormatter::LONG,
            IntlDateFormatter::SHORT,
            'Europe/Moscow'
        );

        $order = [
            'DATE_TIME' => $formatter->format($this->update_start->getTimestamp()),
            'TIME' => $execute_time,
            'PROCESSED' => $processed,
            'ADDED' => $added,
            'UPDATED' => $updated
        ];

        CEvent::Send("UPDATE_REPORT", "s1", $order, "N");



    }
}