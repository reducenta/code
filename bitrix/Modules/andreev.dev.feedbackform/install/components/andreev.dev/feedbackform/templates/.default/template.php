<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
?>

<?if(!empty($arResult['MESSAGE'])):?>
<p><?=$arResult['MESSAGE']?></p>
<?endif?>

<?if(empty($arResult['STATUS']) || $arResult['STATUS'] == 'fail'):?>
<form method="post" action="" enctype="multipart/form-data">
    <input type="text" name="name" placeholder="Ваше имя"><br>
    <span>Заполнять не обязательно</span><br>
    <input type="text" name="phone" placeholder="Номер телефона"><br>
    <input type="text" name="email" placeholder="Email"><br>
    <textarea name="message" placeholder="Введите сообщение"></textarea><br>
    <input type="submit" value="Отправить">
</form>
<?endif?>
