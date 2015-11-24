<?php

/**
 * Class Wechat
 * 微信接口
 */
class WP_Wechat extends Base_Wechat {

    /**
     * Const settings for the whole plugin.
     */
    static $OPTION_GROUP = 'wp_wechat';

    static $OPTION_FIELDS = array(
        'wx_app_id' => array(
            'title' => '微信 APP ID',
            'description' => '开发者中心的 APP ID',
        ),
        'wx_app_secret' => array(
            'title' => '微信 APP Secret',
            'description' => '开发者中心的 APP Secret',
        ),
        'wx_self_id' => array(
            'title' => '公众号微信号',
            'description' => '公众号微信号'
        ),
        'wx_token' => array(
            'title' => 'Token',
            'description' => '服务器回调验证令牌(可选)'
        ),
        'wx_encoding_aes_key' => array(
            'title' => 'Encoding AES Key',
            'description' => '消息加解密密钥(可选)'
        ),
        'wx_access_token' => array(
            'title' => 'Access Token',
            'description' => 'Access Token(系统会自动获取)',
            'readonly' => true
        ),
        'wx_token_expire' => array(
            'title' => 'Access Token Expire',
            'description' => 'Access Token有效时长(单位:秒,系统会自动获取)',
            'readonly' => true
        ),
        'wx_token_modified_time' => array(
            'title' => 'Access Token Modified Time',
            'description' => '最近一次Access Token获取时间(系统会自动获取)',
            'readonly' => true
        ),
        'wx_app_confirm_identify' => array(
            'title' => '用户唯一确认标识',
            'description' => '确认用户身份(系统会自动获取)',
            'readonly' => true
        ),
    );

    function __construct() {
        // 获取微信的配置
        parent::__construct(
            get_option('wx_app_id', null),
            get_option('wx_app_secret', null),
            get_option('wx_token', null),
            get_option('wx_access_token', null),
            get_option('wx_token_expire', null),
            get_option('wx_token_modified_time', null),
            get_option('wx_app_confirm_identify', null),
            get_option('wx_encoding_aes_key', null),
            get_option('wx_self_id', null)
        );
    }

    // 20151115:Base_Wechat分离,获得access的信息后存入配置项
    protected function _getAccessToken() {
        // 更新access token信息
        $access_info = parent::getAccessToken();

        update_option('wx_token_modified_time', $access_info['token_modified_time']);

        update_option('wx_access_token', $access_info['access_token']);

        update_option('wx_token_expire', $access_info['token_expire']);

        update_option('wx_app_confirm_identify', $access_info['app_confirm_identify']);

        return $access_info['access_token'];
    }
};
