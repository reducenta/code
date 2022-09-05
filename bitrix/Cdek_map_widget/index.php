<?php
$APPLICATION->SetAdditionalCSS('/include/cdek_map_widget/leaflet/leaflet.css');
$APPLICATION->SetAdditionalCSS('/include/cdek_map_widget/style.css');
$APPLICATION->AddHeadScript('/include/cdek_map_widget/leaflet/leaflet.js');
$APPLICATION->AddHeadScript('/include/cdek_map_widget/script.js');
?>



<div class="pvz_change_window">
    <div class="city_name">
        <input type="text" name="city" autocomplete="off" placeholder="Ваш город">
        <div class="search_result">
            <div class="city_item blank">
                <div class="city"></div>
                <div class="region"></div>
            </div>
        </div>
    </div>
    <div id="map"></div>
</div>




