<?php

function dy_get_value_package_field($args) {

    //$args param and $args['key'] are already validated on previous steps.

    static $cache = [];
    $key = $args['key'];

    $post_id = null;

    if (array_key_exists('post_id', $args) && is_numeric($args['post_id'])) {
        $candidate = (int) $args['post_id'];

        if ($candidate > 0) {
            $post_id = $candidate;
        }
    }

    $cache_key =  'dy_get_value_package_field_' . $key . '_' . $post_id;

    if (array_key_exists($cache_key, $cache)) {
            return $cache[$cache_key];
    }


    return $cache[$cache_key] = package_field($key, $post_id);
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