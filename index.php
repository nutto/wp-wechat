<?php
/*
Plugin Name: EC-WeChat
Plugin URI:  http://http://www.easecloud.cn/
Description: This Plugin is designed for using wechat API more conveniently
Version:     0.1
Author:      Nutto
Author URI:  http://http://www.easecloud.cn/
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Domain Path: null
Text Domain: null
*/

/**
 * 引入类文件
 */
foreach(glob(plugin_dir_path(__FILE__ ).'class/*.class.php') as $f) {
    include_once $f;
}

// 下面步骤是建立一个配置页面,并且注册好配置项

/** Step 1.*/
function wechat_plugin_menu() {
    // 注册配置页面的渲染函数和信息
    add_options_page( 'ec-Wechat Plugin Options', 'EC Wechat Plugin', 'manage_options', 'ec-wechat', 'wechat_plugin_options' );

    // 注册配置项函数
    add_action( 'admin_init', 'register_ec_wechat_plugin_settings' );
}

/** Step 2 挂上初始化函数 */
add_action( 'admin_menu', 'wechat_plugin_menu' );

/** Step 3. */
function wechat_plugin_options() {
    // 确保权限
    if ( !current_user_can( 'manage_options' ) )  {
        wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
    }

    // 引入配置页面
    include_once plugin_dir_path(__FILE__ ).'/option-page.php';
}

/** Step 4. */
function register_ec_wechat_plugin_settings() {
    // 注册所有的配置项
    register_setting( 'ec-wechat-plugin-settings-group', 'wx_app_id' );
    register_setting( 'ec-wechat-plugin-settings-group', 'wx_app_secret' );
    register_setting( 'ec-wechat-plugin-settings-group', 'wx_self_id' );
    register_setting( 'ec-wechat-plugin-settings-group', 'wx_token' );
    register_setting( 'ec-wechat-plugin-settings-group', 'wx_encoding_AES_key' );
}