<?php
if(!check_bitrix_sessid()){
    return;
}
?>

<?if($errorException = $APPLICATION->GetException()):?>
    <?=CAdminMessage::ShowMessage($errorException->GetString())?>
<?else:?>
    <?CAdminMessage::ShowNote('Модуль успешно установлен. Для хранения записей формы создан Highload блок.')?>
<?endif?>

<form action="<? echo($APPLICATION->GetCurPage()); ?>">
    <input type="hidden" name="lang" value="<? echo(LANG); ?>" />
    <input type="submit" value="Список модулей">
</form>