<?php
namespace App\Filters;

class EquipmentFilter
{
    public $values;

    public function filter($builder, $value)
    {
        $this->values = explode(',', $value);

        $builder->where(function($query) {
            foreach($this->values as $i => $item){
                if($i == 0){
                    $query->where('equipment', $item);
                }else{
                    $query->orWhere('equipment', $item);
                }
            }
        });

        return $builder;
    }
}
