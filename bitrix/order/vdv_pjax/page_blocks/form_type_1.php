<div class="form_area_content" data-type="1">
    <div class="vdv_order_mains">
        <div class="personal_info">
            <?foreach($arResult['PROPERTIES'][1] as $name => $arProp):?>
                <?if(($name !== 'LOCATION') && ($name !== 'ADDRESS')):?>
                    <div class="form-group">
                        <label class="<?=($arProp['IS_REQUIRED'] == 1)?'required':''?>"><?=$arProp['NAME']?></label>
                        <input id="<?=$arProp['FORM_LABEL'] ?>" type="text" name="<?=$arProp['FORM_NAME'] ?>" value="<?=$arProp['VALUE'] ?>">
                        <?foreach ($arProp['ERRORS'] as $error):?>
                            <div class="error"><?= $error->getMessage() ?></div>
                        <?endforeach; ?>
                    </div>
                <?endif?>
            <?endforeach?>

            <div class="form-group">
                <label>Комментарий</label>
                <textarea name="user_description"><?=$_POST['user_description']?></textarea>
            </div>

        </div>


        <div class="order_options">

            <input id="property_LOCATION" type="hidden" name="properties[LOCATION]" value="<?=$arResult['PROPERTIES'][1]['LOCATION']['LOCATION_DATA']['code']?>">

            <div class="form-group" style="position: relative">
                <label class="required">Город</label>
                <input id="location_search" type="text" autocomplete="off" value="<?=$arResult['PROPERTIES'][1]['LOCATION']['LOCATION_DATA']['name']?>">

                <?foreach ($arResult['PROPERTIES'][1]['LOCATION']['ERRORS'] as $error):?>
                    <div class="error"><?= $error->getMessage() ?></div>
                <?endforeach; ?>

                <div class="locations_list">
                </div>
            </div>


            <?
            foreach($arResult['DELIVERY_LIST'] as $item){
                if($item['CHECKED'] == 1){
                    if(in_array($item['ID'], [2,4,6]) == true){
                        $address_show = true;
                        break;
                    }
                }
            }
            //В случае свет-магазина адрес доставки нужен всегда
            $address_show = true;
            
            ?>

            <?if($address_show):?>
            <div class="form-group">
                <label class="<?=($arResult['PROPERTIES'][1]['ADDRESS']['IS_REQUIRED'] == 1)?'required':''?>"><?=$arResult['PROPERTIES'][1]['ADDRESS']['NAME']?></label>
                <input id="<?=$arResult['PROPERTIES'][1]['ADDRESS']['FORM_LABEL'] ?>" type="text" name="<?=$arResult['PROPERTIES'][1]['ADDRESS']['FORM_NAME'] ?>" value="<?=$arResult['PROPERTIES'][1]['ADDRESS']['VALUE'] ?>">
                <?foreach ($arResult['PROPERTIES'][1]['ADDRESS']['ERRORS'] as $error):?>
                    <div class="error"><?= $error->getMessage() ?></div>
                <?endforeach; ?>
            </div>
            <?endif?>

            <? include(__DIR__ . '/options.php');?>
        </div>


        <? include(__DIR__ . '/summary.php');?>

    </div>
</div>
