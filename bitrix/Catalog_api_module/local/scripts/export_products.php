<?php
$_SERVER["DOCUMENT_ROOT"] = realpath(__DIR__ . "/../..");
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

use Bitrix\Main\Loader;

Loader::includeModule('iblock');
Loader::includeModule('catalog');

// ------------------------
// настройки
// ------------------------
$IBLOCK_ID = 2; // каталог "Одежда"
$EMAIL = $argv[1] ?? 'test@example.com';

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['SERVER_NAME'] ?? 'localhost';
$baseUrl = $scheme . '://' . $host;


// ------------------------
// Получаем товары
// ------------------------
$items = [];

$res = CIBlockElement::GetList(
    [],
    [
        'IBLOCK_ID' => $IBLOCK_ID,
        'ACTIVE' => 'Y'
    ],
    false,
    false,
    ['ID', 'NAME', 'DETAIL_PAGE_URL', 'IBLOCK_SECTION_ID']
);

while ($item = $res->GetNext()) {

    // путь категорий: Обувь / Тапочки
    $sections = [];
    if ($item['IBLOCK_SECTION_ID']) {
        $nav = CIBlockSection::GetNavChain(
            $IBLOCK_ID,
            $item['IBLOCK_SECTION_ID'],
            ['ID', 'NAME']
        );
        while ($sec = $nav->GetNext()) {
            $sections[] = $sec['NAME'];
        }
    }
    $categoryPath = implode(' / ', $sections);

    // торговые предложения и минимальная цена
    $offers = CCatalogSKU::getOffersList($item['ID'], $IBLOCK_ID, ['ACTIVE' => 'Y']);
    $offersCount = 0;
    $minPrice = null;

    if (!empty($offers[$item['ID']])) {
        foreach ($offers[$item['ID']] as $offer) {
            $offersCount++;
            $price = CPrice::GetBasePrice($offer['ID']);
            if ($price && ($minPrice === null || $price['PRICE'] < $minPrice)) {
                $minPrice = (float)$price['PRICE'];
            }
        }
    } else {
        $price = CPrice::GetBasePrice($item['ID']);
        if ($price) {
            $minPrice = (float)$price['PRICE'];
        }
    }

    $items[] = [
        'ID' => $item['ID'],
        'NAME' => $item['NAME'],
        'CATEGORY' => $categoryPath,
        'URL' => $baseUrl . $item['DETAIL_PAGE_URL'],
        'OFFERS_COUNT' => $offersCount,
        'PRICE_FROM' => $minPrice,
    ];
}

// ------------------------
// Сортировка: категория, потом товар
// ------------------------
usort($items, function ($a, $b) {
    if ($a['CATEGORY'] === $b['CATEGORY']) {
        return strcmp($a['NAME'], $b['NAME']);
    }
    return strcmp($a['CATEGORY'], $b['CATEGORY']);
});

// ------------------------
// Excel (PhpSpreadsheet)
// ------------------------
require $_SERVER["DOCUMENT_ROOT"] . "/local/php_interface/vendor/autoload.php";

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

$headers = [
    'A1' => 'ID',
    'B1' => 'Наименование',
    'C1' => 'Категория',
    'D1' => 'Ссылка',
    'E1' => 'Кол-во ТП',
    'F1' => 'Цена от',
];

foreach ($headers as $cell => $title) {
    $sheet->setCellValue($cell, $title);
}

// жирные заголовки
$sheet->getStyle('A1:F1')->getFont()->setBold(true);

$row = 2;
foreach ($items as $item) {
    $sheet->setCellValue("A{$row}", $item['ID']);
    $sheet->setCellValue("B{$row}", $item['NAME']);
    $sheet->setCellValue("C{$row}", $item['CATEGORY']);
    $sheet->setCellValue("D{$row}", $item['URL']);
    $sheet->setCellValue("E{$row}", $item['OFFERS_COUNT']);
    $sheet->setCellValue("F{$row}", $item['PRICE_FROM']);

    // гиперссылка
    $sheet->getCell("D{$row}")
        ->getHyperlink()
        ->setUrl($item['URL']);

    $row++;
}

$lastRow = $row - 1;

// рамки
$sheet->getStyle("A1:F{$lastRow}")
    ->getBorders()
    ->getAllBorders()
    ->setBorderStyle(Border::BORDER_THIN);

// автофильтр (сортировка в Excel)
$sheet->setAutoFilter("A1:F{$lastRow}");

// автоширина колонок
foreach (range('A', 'F') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// ------------------------
// Сохранение в /upload/exports
// ------------------------
$dir = $_SERVER["DOCUMENT_ROOT"] . "/upload/exports";
if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
}

$filePath = $dir . "/products_" . date('Ymd_His') . ".xlsx";
$writer = new Xlsx($spreadsheet);
$writer->save($filePath);

// ------------------------
// Отправка почтой
// ------------------------
CEvent::Send(
    "PRODUCT_EXPORT",
    SITE_ID,
    [
        "EMAIL" => $EMAIL,
        "FILE_PATH" => $filePath
    ],
    "Y",
    "",
    [$filePath]
);

echo "Файл сохранён: {$filePath}\n";
echo "Файл отправлен на {$EMAIL}\n";

/*
====================================
НАСТРОЙКА ПОЧТОВОГО ШАБЛОНА BITRIX:

1. Админка → Настройки → Почтовые события
2. Добавить тип события:
   PRODUCT_EXPORT
   Описание: Выгрузка товаров в Excel

3. Добавить почтовый шаблон:
   Тип: PRODUCT_EXPORT
   Кому: #EMAIL#
   Тема: Выгрузка товаров каталога
   Текст:
     Во вложении файл с выгрузкой товаров.

4. В настройках шаблона включить:
   "Отправлять файлы во вложении".

====================================
*/
