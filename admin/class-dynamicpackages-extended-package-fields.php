<?php

function dy_get_value_package_field($args) {

    //$args param and $args['key'] are already validated on previous steps.

    static $cache = [];
    $key = $args['key'];


    $cache_key =  'dy_get_value_package_field_' . $key;

    if (array_key_exists($cache_key, $cache)) {
            return $cache[$cache_key];
    }


    return $cache[$cache_key] = package_field($key);
}

class dy_input_package_field extends dy_input_abstract {

      protected static function get_value($args) {

            return dy_get_value_package_field($args);
      }
}

class dy_select_package_field extends dy_select_abstract {

      protected static function get_value($args) {

            return dy_get_value_package_field($args);
      }
}

class dy_textarea_package_field extends dy_textarea_abstract {

      protected static function get_value($args) {

            return dy_get_value_package_field($args);
      }
}