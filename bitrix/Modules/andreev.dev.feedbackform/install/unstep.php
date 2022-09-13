<?php
if(!check_bitrix_sessid()){
    return;
}
CAdminMessage::ShowNote('Модуль удален')
?>

<form action="<? echo($APPLICATION->GetCurPage()); ?>">
    <input type="hidden" name="lang" value="<? echo(LANG); ?>" />
    <input type="submit" value="Список модулей">
</form>