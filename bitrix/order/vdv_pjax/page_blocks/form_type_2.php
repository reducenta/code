<?php
$fields = ['COMPANY', 'COMPANY_ADR', 'INN', 'KPP', 'FAX'];
?>
<div class="form_area_content" data-type="2">
<div class="company_info">
    <div class="top">
        <?for($i=0; $i<=1; $i++):?>
            <?$name = $fields[$i]?>
            <div class="form-group">
                <label class="<?=($arResult['PROPERTIES'][2][$name]['IS_REQUIRED'] == 1)?'required':''?>"><?=$arResult['PROPERTIES'][2][$name]['NAME']?></label>
                <input id="<?=$arResult['PROPERTIES'][2][$name]['FORM_LABEL'] ?>" type="text" name="<?=$arResult['PROPERTIES'][2][$name]['FORM_NAME'] ?>" value="<?=$arResult['PROPERTIES'][2][$name]['VALUE'] ?>">
                <? foreach ($arResult['PROPERTIES'][2][$name]['ERRORS'] as $error):
                    /** @var Error $error */
                    ?>
                    <div class="error"><?= $error->getMessage() ?></div>
                <? endforeach; ?>
            </div>
        <?endfor?>
    </div>
    <div class="bottom">
        <?for($i=2; $i<=4; $i++):?>
            <?$name = $fields[$i]?>
            <div class="form-group">
                <label class="<?=($arResult['PROPERTIES'][2][$name]['IS_REQUIRED'] == 1)?'required':''?>"><?=$arResult['PROPERTIES'][2][$name]['NAME']?></label>
                <input id="<?=$arResult['PROPERTIES'][2][$name]['FORM_LABEL'] ?>" type="text" name="<?=$arResult['PROPERTIES'][2][$name]['FORM_NAME'] ?>" value="<?=$arResult['PROPERTIES'][2][$name]['VALUE'] ?>">
                <? foreach ($arResult['PROPERTIES'][2][$name]['ERRORS'] as $error):
                    /** @var Error $error */
                    ?>
                    <div class="error"><?= $error->getMessage() ?></div>
                <? endforeach; ?>
            </div>
        <?endfor?>
    </div>
</div>

<div class="vdv_order_mains">
    <div class="personal_info">
        <?foreach($arResult['PROPERTIES'][2] as $name => $arProp):?>
            <?if(($name !== 'LOCATION') && ($name !== 'ADDRESS') && (in_array($name, $fields) === false)):?>
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

        <input id="property_LOCATION" type="hidden" name="properties[LOCATION]" value="<?=$arResult['PROPERTIES'][2]['LOCATION']['LOCATION_DATA']['code']?>">

        <div class="form-group" style="position: relative">
            <label class="required">Город</label>
            <input id="location_search" type="text" autocomplete="off" value="<?=$arResult['PROPERTIES'][2]['LOCATION']['LOCATION_DATA']['name']?>">

            <?foreach ($arResult['PROPERTIES'][2]['LOCATION']['ERRORS'] as $error):?>
                <div class="error"><?= $error->getMessage() ?></div>
            <?endforeach; ?>

            <div class="locations_list">
            </div>
        </div>

        <?
        $address_show = false;
        foreach($arResult['DELIVERY_LIST'] as $item){
            if($item['CHECKED'] == 1){
                if(in_array($item['ID'], [2,4,6]) == true){
                    $address_show = true;
                    break;
                }
            }
        }
        $address_show = true;
        ?>

        <?if($address_show):?>
        <div class="form-group">
            <label class="<?=($arResult['PROPERTIES'][2]['ADDRESS']['IS_REQUIRED'] == 2)?'required':''?>"><?=$arResult['PROPERTIES'][2]['ADDRESS']['NAME']?></label>
            <input id="<?=$arResult['PROPERTIES'][2]['ADDRESS']['FORM_LABEL'] ?>" type="text" name="<?=$arResult['PROPERTIES'][2]['ADDRESS']['FORM_NAME'] ?>" value="<?=$arResult['PROPERTIES'][2]['ADDRESS']['VALUE'] ?>">
            <?foreach ($arResult['PROPERTIES'][2]['ADDRESS']['ERRORS'] as $error):?>
                <div class="error"><?= $error->getMessage() ?></div>
            <?endforeach; ?>
        </div>
        <?endif?>

        <? include(__DIR__ . '/options.php');?>
    </div>

    <? include(__DIR__ . '/summary.php');?>


</div>

</div>