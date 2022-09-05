<?php
namespace App\Filters;

class PriceFilter
{
    public $values;

    public function filter($builder, $value)
    {
        $this->values = explode('-', $value);

        if(count($this->values) > 1){
            $builder->where(function($query) {
                if($this->values[1] == 'limitless'){
                    $query->where('offers.price', '>', $this->values[0]);
                }else{
                    $query->whereBetween('offers.price', $this->values);
                }

            });
        }

        return $builder;
    }
}
