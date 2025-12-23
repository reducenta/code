<?php
namespace Catalog\Api\Controller;

use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Loader;

class ApiController extends Controller
{
    protected function init()
    {
        parent::init();
        Loader::includeModule("iblock");
        Loader::includeModule("catalog");
    }

    /**
     * GET /api/categories/{iblockId}
     */
    public function categoriesAction(int $iblockId): array
    {
        return $this->getSections(0, $iblockId);
    }

    private function getSections(int $parentId, int $iblockId): array
    {
        $sections = [];

        $res = \CIBlockSection::GetList(
            ['SORT' => 'ASC'], // сортировка по индексу
            [
                'IBLOCK_ID' => $iblockId,
                'SECTION_ID' => $parentId,
                'ACTIVE' => 'Y'
            ],
            false,
            ['ID', 'NAME', 'DETAIL_PAGE_URL', 'PICTURE']
        );

        while ($section = $res->GetNext()) {
            $section['PICTURE'] = $section['PICTURE']
                ? \CFile::GetPath($section['PICTURE'])
                : null;

            $section['CHILDREN'] = $this->getSections((int)$section['ID'], $iblockId);
            $sections[] = $section;
        }

        return $sections;
    }

    /**
     * GET /api/products/{iblockId}/{categoryId}
     */
    public function productsAction(int $iblockId, int $categoryId): array
    {
        $products = [];

        $res = \CIBlockElement::GetList(
            ['SORT' => 'ASC'], // сортировка по индексу
            [
                'IBLOCK_ID' => $iblockId,
                'SECTION_ID' => $categoryId,
                'INCLUDE_SUBSECTIONS' => 'Y',
                'ACTIVE' => 'Y'
            ],
            false,
            false,
            ['ID', 'NAME', 'DETAIL_PAGE_URL', 'PREVIEW_PICTURE']
        );

        while ($item = $res->GetNext()) {
            $item['PREVIEW_PICTURE'] = $item['PREVIEW_PICTURE']
                ? \CFile::GetPath($item['PREVIEW_PICTURE'])
                : null;

            // минимальная цена
            $minPrice = null;
            $offers = \CCatalogSKU::getOffersList($item['ID'], $iblockId, ['ACTIVE' => 'Y']);

            if (!empty($offers[$item['ID']])) {
                foreach ($offers[$item['ID']] as $offer) {
                    $price = \CPrice::GetBasePrice($offer['ID']);
                    if ($price && ($minPrice === null || $price['PRICE'] < $minPrice)) {
                        $minPrice = (float)$price['PRICE'];
                    }
                }
            } else {
                $price = \CPrice::GetBasePrice($item['ID']);
                $minPrice = $price ? (float)$price['PRICE'] : null;
            }

            $item['PRICE_FROM'] = $minPrice;
            $products[] = $item;
        }

        return $products;
    }

    /**
     * GET /api/product/{iblockId}/{productId}
     */
    public function productAction(int $iblockId, int $productId): ?array
    {
        $res = \CIBlockElement::GetList(
            [],
            [
                'IBLOCK_ID' => $iblockId,
                'ID' => $productId,
                'ACTIVE' => 'Y'
            ],
            false,
            false,
            [
                'ID',
                'NAME',
                'DETAIL_PAGE_URL',
                'DETAIL_PICTURE',
                'PROPERTY_BRAND',
                'PROPERTY_MANUFACTURER',
                'PROPERTY_MATERIAL'
            ]
        );

        if (!($product = $res->GetNext())) {
            $this->addError(new \Bitrix\Main\Error('Товар не найден'));
            return null;
        }

        // Галерея
        $gallery = [];
        if ($product['DETAIL_PICTURE']) {
            $gallery[] = \CFile::GetPath($product['DETAIL_PICTURE']);
        }

        $dbProps = \CIBlockElement::GetProperty($iblockId, $productId, [], ['CODE' => 'MORE_PHOTO']);
        while ($prop = $dbProps->Fetch()) {
            if ($prop['VALUE']) {
                $gallery[] = \CFile::GetPath($prop['VALUE']);
            }
        }

        // Характеристики
        $characteristics = [
            'BRAND' => $product['PROPERTY_BRAND_VALUE'] ?? null,
            'MANUFACTURER' => $product['PROPERTY_MANUFACTURER_VALUE'] ?? null,
            'MATERIAL' => $product['PROPERTY_MATERIAL_VALUE'] ?? null,
        ];

        // Торговые предложения
        $offersList = [];
        $skuInfo = \CCatalogSKU::GetInfoByProductIBlock($iblockId);

        if ($skuInfo) {
            $skuIblockId = $skuInfo['IBLOCK_ID'];

            $skuRes = \CIBlockElement::GetList(
                ['SORT' => 'ASC'],
                [
                    'IBLOCK_ID' => $skuIblockId,
                    'PROPERTY_' . $skuInfo['SKU_PROPERTY_ID'] => $productId,
                    'ACTIVE' => 'Y'
                ],
                false,
                false,
                ['ID', 'NAME']
            );

            while ($sku = $skuRes->GetNext()) {
                $codes = ['ARTNUMBER', 'COLOR_REF', 'SIZES_CLOTHES'];

                $props = [];
                foreach ($codes as $code) {
                    $propRes = \CIBlockElement::GetProperty($skuIblockId, $sku['ID'], [], ['CODE' => $code]);
                    $prop = $propRes->Fetch();
                    $props[$code] = $prop
                        ? (is_array($prop['VALUE']) ? $prop['VALUE'][0] : $prop['VALUE'])
                        : null;
                }

                $offersList[] = [
                    'ID' => $sku['ID'],
                    'NAME' => $sku['NAME'],
                    'ARTICUL' => $props['ARTNUMBER'],
                    'COLOR' => $props['COLOR_REF'],
                    'SIZE' => $props['SIZES_CLOTHES'],
                ];
            }
        }

        return [
            'ID' => $product['ID'],
            'NAME' => $product['NAME'],
            'DETAIL_PAGE_URL' => $product['DETAIL_PAGE_URL'],
            'GALLERY' => $gallery,
            'CHARACTERISTICS' => $characteristics,
            'OFFERS' => $offersList,
        ];
    }
}
