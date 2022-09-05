<?php

namespace App\Filters;

class ProductFilter extends AbstractFilter
{
    protected $filters = [
        //offers
        'price' => PriceFilter::class,
        'color' => ColorFilter::class,
        'size' => SizeFilter::class,

        //products
        'composition' => CompositionFilter::class,
        'material' => MaterialFilter::class,
        'hotel' => HotelFilter::class,
        'equipment' => EquipmentFilter::class,
        'density' => DensityFilter::class,

        //служебные
        'section' => SectionFilter::class,
        'sort' => SortFilter::class,
    ];
}
