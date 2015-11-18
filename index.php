<?php
/*
Plugin Name: WP WeChat
Plugin URI:  http://http://www.easecloud.cn/
Description: This Plugin is designed for using wechat API more conveniently
Version:     0.1
Author:      Nutto
Author URI:  http://http://www.easecloud.cn/
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Domain Path: null
Text Domain: wp_wechat
*/

define('WXD', 'wp_wechat');

/**
 * 引入类文件
 * TODO: 存疑，动态引入库是否会有安全性考虑？
 */
foreach(glob(__DIR__.'/class/*.class.php') as $class_file) {
    include_once $class_file;
}

/**
 * 翻译支持
 */
add_action('plugins_loaded', function() {
    load_textdomain(WXD, __DIR__.'/languages/zh_CN.mo');
});


// 下面步骤是建立一个配置页面,并且注册好配置项

/**
 * Step 1. Add admin menu for plugin settings
 */
add_action('admin_menu', function () {

    // 注册配置页面的渲染函数和信息
    add_options_page(
        __('Wechat Options', WXD),                  // page_title
        __('Wechat Options', WXD),                  // menu_title
        'manage_options',                           // capability
        'options-wechat',                           // menu_slug
        'wechat_plugin_options'                     // function (callback)
    );

    function wechat_plugin_options() {
        include_once 'option-page.php';
    }
});

/**
 * Step 2. Register the plugin settings.
 * Referring: https://codex.wordpress.org/Settings_API
 */
add_action('admin_init', function() {

    add_settings_section(
        'section-basic',                            // id
        __('Basic Settings', WXD),                  // title
        function() {                                // callback
            _e('Set the basic Wechat account info here.', WXD);
        },
        'options-wechat'                            // page
    );

    // 预留高级选项卡
//    add_settings_section(
//        'section-advanced',                         // id
//        __('Advanced Settings', WXD),       // title
//        function() {},                              // callback
//        'options-wechat'                            // page
//    );

    foreach(WP_Wechat::$OPTION_FIELDS as $field_name => $args) {

        register_setting(WP_Wechat::$OPTION_GROUP, $field_name);

        // Referring: https://codex.wordpress.org/Function_Reference/add_settings_field
        add_settings_field(
            $field_name,                            // id
            @$args['title'] ?: $field_name,         // title
            'field_renderer',                       // callback
            'options-wechat',                       // page
            'section-basic',                        // section
            array_merge(array(
                'field_name' => $field_name,
                'label_for' => $field_name,
            ), $args)                               // $args
        );

        // TODO: 还有 Sanitization 和 Validation 可以完善

    }

    function field_renderer($args) {
        $field_name = $args['field_name'];
        $field_title = @$args['title'] ?: $field_name;
        $field_type = @$args['type'] ?: 'text';
        $field_class = @$args['class'] ?: 'regular-text ltr';
        $field_description = @$args['description'] ?: '';
        ?>
        <input class="<?php echo $field_class; ?>"
               type="<?php echo $field_type;?>"
               id="<?php echo $field_name;?>"
               value="<?php form_option($field_name);?>"
               name="<?php echo $field_name;?>"
               aria-describedby="<?php echo $field_name;?>-description"
               <?php echo @$args['readonly'] ? 'readonly' : ''; ?>
            />
        <p class="description"
           id="<?php echo $field_name;?>-description"><?php
            echo $field_description;
            ?></p>
        <?php
    }

});

