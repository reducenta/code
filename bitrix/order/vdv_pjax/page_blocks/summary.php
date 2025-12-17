<div class="order_summary">
    <div class="top">
        <div class="string price">
            <span>Товаров на:</span>
            <div>
                <?if($arResult['PRODUCTS_BASE_PRICE'] != $arResult['PRODUCTS_PRICE']):?>
                <span class="discount"><?=$arResult['PRODUCTS_BASE_PRICE_DISPLAY']?></span>
                <?endif?>
                <span><?=$arResult['PRODUCTS_PRICE_DISPLAY']?></span>
            </div>
        </div>

        <div class="string delivery">
            <span>Доставка:</span>
            <span>
                 <?
                 $delivery_price = 0;
                 foreach($arResult['DELIVERY_LIST'] as $delivery){
                     if($delivery['CHECKED'] == 1){
                         echo $delivery['PRICE_DISPLAY'];
                         $delivery_price = $delivery['PRICE'];
                     }
                 }
                 ?>
            </span>
        </div>

        <?if($arResult['PRODUCTS_BASE_PRICE'] != $arResult['PRODUCTS_PRICE']):?>
        <div class="string green">
            <span>Экономия:</span>
            <span><?=$arResult['DISCOUNT_VALUE_DISPLAY']?></span>
        </div>
        <?endif?>
        <div class="string itog">
            <span>Итого:</span>
            <span><?=CurrencyFormat($arResult['PRODUCTS_PRICE'] + $delivery_price, "RUB")?></span>
        </div>
    </div>

    <div class="bottom">
        <div class="order_button">
            Оформить заказ
        </div>

        <div class="licence_block filter label_block">
            <input type="checkbox" id="licenses_inline" name="licenses_inline" checked required>
            <label for="licenses_inline">
                Я согласен на <a href="/include/licenses_detail.php" target="_blank">обработку персональных данных</a>
            </label>
        </div>
    </div>

</div>