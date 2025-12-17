<?
use Bitrix\Sale\Order;
$APPLICATION->AddHeadScript($templateFolder . '/jquery.pjax.js');
$APPLICATION->AddHeadScript($templateFolder . '/jquery.maskedinput.min.js');
?>





<?if(empty($arResult)):?>
<div class="empty_basket">
    <img src="/bitrix/components/bitrix/sale.basket.basket/templates/.default/images/empty_cart.svg" alt="">
    <div class="text">Ваша корзина пуста</div>
    <div class="desc"><a href="/">Нажмите здесь</a>, чтобы продолжить покупки</div>
</div>
<?endif?>

<div id="vdv_order" class="order_form">


    <div class="stub">
        <i class="fa fa-spinner fa-spin" aria-hidden="true"></i>
    </div>

    <?if (isset($arResult['ID']) && $arResult['ID'] > 0):?>

    <?
    if(!empty($_POST['user_description'])) {
        CSaleOrder::Update($arResult['ID'], ['USER_DESCRIPTION' => $_POST['user_description']]);
    }

    send_order_letters($arResult['ID']);



    ?>



    <div class="order_done">
        <img src="/bitrix/templates/aspro_next/components/bitrix/sale.order.ajax/v1/images/done.gif">
        <div class="caption">
            Ваш заказ <b><?=$arResult['ID']?></b> от <?=$arResult['DATE_STATUS']->toString()?> успешно создан.</div>
    </div>



    <?else:?>
    <form method="post" action="" enctype="multipart/form-data" data-pjax>

        <?php if (count($component->errorCollection) > 0): ?>
            <div class="errors_list">
                <?php foreach ($component->errorCollection as $error):
                    /**
                     * @var Error $error
                     */
                    ?>
                    <div class="error"><?= $error->getMessage() ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

            <div class="person_types radio_group">
                <?foreach($arResult['PERSON_TYPES'] as $person_type):?>
                    <div class="vdv_order_radio <?=($person_type['CHECKED'] == 1)?'checked':''?>">
                        <input type="radio" name="person_type_id" value="<?=$person_type['ID']?>" <?=($person_type['CHECKED'] == 1)?'checked="true"':''?>>
                        <span><?=$person_type['NAME']?></span>
                    </div>
                <?endforeach?>
            </div>

            <div class="person_form_area">
                <?
                foreach($arResult['PERSON_TYPES'] as $person_type){
                    if($person_type['CHECKED'] == 1){
                        include (__DIR__ . '/page_blocks/form_type_' . $person_type['ID'] . '.php');
                    }
                }
                ?>

            </div>

        <input type="hidden" name="save" value="">

    </form>
    <?endif?>
</div>

