<div class="options_list delivery">

    <?foreach($arResult['DELIVERY_LIST'] as $delivery):?>
        <input type="radio" name="delivery_id" value="<?=$delivery['ID']?>" <?=($delivery['CHECKED'] == 1)?'checked':''?>>
    <?endforeach?>
    <div class="list_label">Доставка</div>
    <div class="list_items">
        <div class="left">
            <div class="current">
            <span>
                <?
                foreach($arResult['DELIVERY_LIST'] as $delivery){
                    if($delivery['CHECKED'] == 1){
                        echo $delivery['NAME'];
                    }
                }
                ?>
            </span>
                <span>
                <svg width="20" height="10" viewBox="0 0 20 10" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M19.7559 0.225297C19.4305 -0.0750991 18.9028 -0.0750991 18.5774 0.225297L10 8.14288L1.42259 0.225297C1.09716 -0.0750991 0.569501 -0.0750991 0.244072 0.225297C-0.0813574 0.525694 -0.0813574 1.01276 0.244072 1.31316L9.41074 9.7747C9.73617 10.0751 10.2638 10.0751 10.5893 9.7747L19.7559 1.31316C20.0814 1.01272 20.0814 0.525694 19.7559 0.225297Z" fill="#333333"/>
                </svg>
            </span>
            </div>
            <div class="items">
                <?foreach($arResult['DELIVERY_LIST'] as $delivery):?>
                    <div class="item" data-id="<?=$delivery['ID']?>" data-price="<?=$delivery['PRICE_DISPLAY']?>"><?=$delivery['NAME']?></div>
                <?endforeach?>
            </div>
        </div>

    </div>

    <?foreach($arResult['DELIVERY_LIST'] as $delivery):?>
        <?if($delivery['CHECKED'] == 1):?>
            <?if(!empty($delivery['DESC'])):?>
            <div class="delivery_desc">
                <?=$delivery['DESC']?>
            </div>
            <?endif?>
        <?endif?>
    <?endforeach?>

    <?if($arResult['DELIVERY_LIST'][6]['CHECKED'] == 1):?>
        <a class="show_map" href="javascript:void(0)">выбрать пункт самовывоза</a>
    <?endif?>

    <?foreach ($arResult['DELIVERY_ERRORS'] as $error):?>
        <div class="error"><?= $error->getMessage() ?></div>
    <?endforeach; ?>
</div>

<div class="options_list pay_systems">
    <?foreach($arResult['PAY_SYSTEM_LIST'] as $pay_system):?>
        <input type="radio" name="pay_system_id" value="<?=$pay_system['ID']?>" <?=($pay_system['CHECKED'] == 1)?'checked':''?>>
    <?endforeach?>
    <div class="list_label">Способ оплаты</div>
    <div class="list_items">
        <div class="left">
            <div class="current">
            <span>
                <?
                foreach($arResult['PAY_SYSTEM_LIST'] as $pay_system){
                    if($pay_system['CHECKED'] == 1){
                        echo $pay_system['NAME'];
                    }
                }
                ?>
            </span>
                <span>
                <svg width="20" height="10" viewBox="0 0 20 10" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M19.7559 0.225297C19.4305 -0.0750991 18.9028 -0.0750991 18.5774 0.225297L10 8.14288L1.42259 0.225297C1.09716 -0.0750991 0.569501 -0.0750991 0.244072 0.225297C-0.0813574 0.525694 -0.0813574 1.01276 0.244072 1.31316L9.41074 9.7747C9.73617 10.0751 10.2638 10.0751 10.5893 9.7747L19.7559 1.31316C20.0814 1.01272 20.0814 0.525694 19.7559 0.225297Z" fill="#333333"/>
                </svg>
            </span>
            </div>
            <div class="items">
                <?foreach($arResult['PAY_SYSTEM_LIST'] as $pay_system):?>
                    <div class="item" data-id="<?=$pay_system['ID']?>"><?=$pay_system['NAME']?></div>
                <?endforeach?>
            </div>
        </div>
        <div class="right hidden"></div>

    </div>
    <?foreach ($arResult['PAY_SYSTEM_ERRORS'] as $error):?>
        <div class="error"><?= $error->getMessage() ?></div>
    <?endforeach; ?>
</div>