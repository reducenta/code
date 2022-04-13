<?php
namespace App\Filters;

use Illuminate\Support\Facades\Log;

class HotelFilter
{
    public $values;

    public function filter($builder, $value)
    {
        $this->values = explode(',', $value);

        $builder->where(function($query) {
            foreach($this->values as $i => $item){

                switch ($item){
                    default:
                    case 'yes':
                        $bool = 1;
                        break;
                    case 'no':
                        $bool = 0;
                        break;
                }

                if($i == 0){
                    $query->where('hotel', $bool);
                }else{
                    $query->orWhere('hotel', $bool);
                }
            }
        });

        return $builder;
    }
}
