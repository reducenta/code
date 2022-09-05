<?php
namespace App\Filters;

class ColorFilter
{

    public $values;

    public function filter($builder, $value)
    {
        $builder->leftJoin('colors', 'colors.id', '=', 'offers.color');

        $this->values = explode(',', $value);

        $builder->where(function($query) {
            foreach($this->values as $i => $item){
                if($i == 0){
                    $query->where('colors.name', $item);
                }else{
                    $query->orWhere('colors.name', $item);
                }
            }
        });

        return $builder;
    }
}
