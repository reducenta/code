<?php
namespace App\Filters;

class MaterialFilter
{
    public $values;

    public function filter($builder, $value)
    {
        $this->values = explode(',', $value);

        $builder->where(function($query) {
            foreach($this->values as $i => $item){
                if($i == 0){
                    $query->where('material', $item);
                }else{
                    $query->orWhere('material', $item);
                }
            }
        });

        return $builder;
    }
}
