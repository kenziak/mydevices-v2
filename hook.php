<?php

/**
 * Przedefiniowanie menu GLPI
 */
function plugin_mydevices_redefine_menus($menus) {
   $web = Plugin::getWebDir('mydevices');

   $menu_entry = [
      'title' => __('Moje urządzenia', 'mydevices'),
      'page'  => $web . '/front/mydevices.php',
      'icon'  => 'ti ti-devices',
      'links' => []
   ];

   // Upewnij się, że klucze istnieją przed dodaniem menu
   if (!isset($menus['main'])) {
      $menus['main'] = [];
   }

   if (!isset($menus['main']['content'])) {
      $menus['main']['content'] = [];
   }

   // Dodaj do menu Tools
   $menus['main']['content']['mydevices'] = $menu_entry;

   return $menus;
}
