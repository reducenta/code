<?php

/**
 * Класс для работы с картинками
 * Картинки кэшируются и автоматически конвертируются в webp
 * Подходит для сайтов где нужны разные размеры картинок
 * Например интернет-магазины
 */

namespace App\Components;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Illuminate\Http\Request;


class Thumb
{

    public static function store(Request $request, $field_key = 'image'){
        $stored_img = $request->file($field_key)->store('images', 'public');
        if(Storage::disk('public')->mimeType($stored_img) != 'image/webp'){
            $webp_img = explode('.', $stored_img)[0] . '.webp';

            if(Storage::disk('public')->exists($stored_img)){

                $img = Image::make(Storage::disk('public')->path($stored_img))->encode('webp', 75);

                $img->save(Storage::disk('public')->path($webp_img));
                Storage::disk('public')->delete($stored_img);

                return $webp_img;
            }
        }else{
            return $stored_img;
        }


    }

    public static function get($src, $width){
        return self::fit2width($src, $width);
    }

    public static function crop($src, $width, $height)
    {
        $cache_file = 'cache/' . $width . '.' . $height . '.' . basename($src);
        //Если ресайз отсутствует
        if(Storage::disk('public')->missing($cache_file)){
            $img = Image::make(Storage::disk('public')->path($src))->crop($width, $height);

            $img->save(Storage::disk('public')->path($cache_file));
        }

        return Storage::url($cache_file);
    }

    public static function fit2height($src, $height)
    {
        $cache_file = 'cache/auto.' . $height . '.' . basename($src);
        //Если ресайз отсутствует
        if(Storage::disk('public')->missing($cache_file)){
            $img = Image::make(Storage::disk('public')->path($src))->resize(null, $height, function ($constraint) {
                $constraint->aspectRatio();
            });

            $img->save(Storage::disk('public')->path($cache_file));
        }

        return Storage::url($cache_file);
    }

    public static function fit2width($src, $width)
    {
        $cache_file = 'cache/' . $width . '.auto.' . basename($src);
        //Если ресайз отсутствует
        if(Storage::disk('public')->missing($cache_file)){
            $img = Image::make(Storage::disk('public')->path($src))->resize($width, null, function ($constraint) {
                $constraint->aspectRatio();
            });

            $img->save(Storage::disk('public')->path($cache_file));
        }

        return Storage::url($cache_file);
    }

    public static function fit2square($src, $width, $height = null)
    {

        if(empty($height)){
            $height = $width;
        }

        $cache_file = 'cache/' . $width . '.' . $height . '.' . basename($src);

        //Если ресайз отсутствует
        if(Storage::disk('public')->missing($cache_file)) {
            $img = Image::make(Storage::disk('public')->path($src))->fit($width, $height);
            $img->save(Storage::disk('public')->path($cache_file));
        }

        return Storage::url($cache_file);
    }

    public static function delete($src){

        $origin = basename($src);
        $files = Storage::disk('public')->files('cache');

        foreach($files as $file){
            $cache_basename = basename($file);
            if(strpos($cache_basename, $origin) !== false){
                Storage::disk('public')->delete($file);
            }
        }

        return true;
    }
}
