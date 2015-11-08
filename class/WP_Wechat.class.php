<?php

/**
 * Class Wechat
 * 微信接口
 */
class WP_Wechat {

    /**
     * Const settings for the whole plugin.
     */
    const OPTION_GROUP = 'wp_wechat';

    const OPTION_FIELDS = array(
        'wx_app_id' => array(
            'title' => '微信 APP ID',
            'description' => '请填写微信公众平台后台获取的 APP ID',
        ),
        'wx_app_secret' => array(
            'title' => '微信 APP Secret'
        ),
        'wx_self_id' => array(),
        'wx_token' => array(),
        'wx_encoding_aes_key' => array(),
    );

    // 微信号
    // option(wx_self_id)
    protected $self_id;

    // 微信配置文件路径
    protected $wechat_ini_file_path;

    // 服务器验证标识
    // option(wx_token)
    protected $token;

    // 消息加密密钥
    // option(wx_encoding_aes_key)
    protected $encoding_aes_key;

    // 微信应用ID
    // option(wx_app_id)
    protected $app_id;

    // 应用密钥
    // option(wx_app_secret)
    protected $app_secret;

    // 最近一次获取access toke的时间
    protected $token_modified_time;

    // access toke 的时限
    protected $token_expire;

    // 最近一次调用接口的时候获取到的header信息数组
    protected $last_header;

    // 公众号的全局唯一票据
    protected $access_token;

    function __construct() {

        // 获取微信的配置
        $this->app_id = get_option('wx_app_id');
        $this->app_secret = get_option('wx_app_secret');
        $this->self_id = get_option('wx_self_id');
        $this->token = get_option('wx_token');
        $this->encoding_aes_key = get_option('wx_encoding_aes_key');

        // 获取微信配置文件路径
        $upload_dir = wp_upload_dir();
        $this->access_token_file_path = $upload_dir['basedir'].'/wechat-option.ini';

        // 关于access token的信息以文件的形式存放在插件根目录的"wechat-option.ini"文件内
        // TODO: 此处需要重写，不要写入文件系统里面，直接存放在数据库里面，因为不一定有写入权限
        if(file_exists($this->access_token_file_path)) {
            $pre_options = json_decode(file_get_contents($this->access_token_file_path));

            // 要appId和appSecret更新了的话就强制更新Access Token
            if($pre_options->wx_app_id != $this->app_id || $pre_options->wx_app_secret != $this->app_secret) {
                $this->_get_access_token();
            }
            else {
                $this->access_token = $pre_options->wx_access_token;
                $this->token_expire = $pre_options->wx_token_expire;
                $this->token_modified_time = $pre_options->wx_token_modified_time;
                $this->ensure_access_token();
            }
        }
        else {
            $this->_get_access_token();
        }
    }

    /**
     * @throws Exception
     *
     * 确保access token的有效性,如果access token失效,会去尝试获取新的access token
     */
    public function ensure_access_token() {
        // 没有初始化和超过认证时限都需要重新获取access token
        if(!isset($this->access_token) || !isset($this->token_modified_time) || !isset($this->token_expire) ||
            (time() - $this->token_modified_time) > ($this->token_expire - 60)) { // 提早60秒获取,避免误差
            $this->_get_access_token();
        }
    }

    /**
     * @throws Exception
     * 强制获取access token
     */
    public function get_access_token() {
        return $this->_get_access_token();
    }

    /**
     * @return mixed
     * @throws Exception
     *
     * 根据配置获取access token
     * 不会检查是否有原access token或原access token是否有效
     * 会强制刷新该用户的access token,并将相关的信息以JSON格式存放在
     * 插件根目录的"wechat-option.ini"文件内
     */
    protected function _get_access_token() {
        $url = 'https://api.weixin.qq.com/cgi-bin/token';
        $arg = array(
            'grant_type'=> 'client_credential',
            'appid'     => $this->app_id,
            'secret'    => $this->app_secret,
        );
        $token_result = json_decode($this->_request_api($url, $arg));
        if(!$token_result) {
            throw new Exception('Fail to get Access Token!');
        }

        // 提取access token信息
        $pre_options['wx_token_modified_time'] = time();
        $this->token_modified_time = $pre_options['wx_token_modified_time'];

        $pre_options['wx_access_token'] = $token_result->access_token;
        $this->access_token = $pre_options['wx_access_token'];

        $pre_options['wx_token_expire'] = $token_result->expires_in;
        $this->token_expire = $pre_options['wx_token_expire'];

        // 记录下appId和appSecret,日后用作更新检测

        $pre_options['wx_app_id'] = $this->app_id;

        $pre_options['wx_app_secret'] = $this->app_secret;

        // access token信息记录到文件
        if(file_put_contents($this->access_token_file_path, json_encode($pre_options)) === false) {
            throw new Exception('Fail to write the "wechat-option.ini" file!');
        }
        return $this->access_token;
    }

    /**
     * @param $url
     * @param array $data
     * @param string $method
     * @return array|mixed|object
     *
     * 用curl向微信发送请求的函数
     * 返回原始数据
     */
    protected function _request_api($url, $data = array(), $method = 'GET') {
        $request = curl_init();
        $request_options = array(
//                CURLOPT_HEADER          => TRUE,   // 返回不带头部
            CURLOPT_NOBODY          => FALSE,
//                CURLOPT_FOLLOWLOCATION  => true,    // 跟随跳转
            CURLOPT_RETURNTRANSFER  => true,    // 执行curl_exec()的结果作为返回值返回,而非直接打印出来
            CURLOPT_TIMEOUT         => 500,
            CURLOPT_SSL_VERIFYPEER  => false,
            CURLOPT_SSL_VERIFYHOST  => false,
        );
        curl_setopt_array($request, $request_options);
        switch($method) {
            case 'POST':
                curl_setopt($request, CURLOPT_URL, $url);
                curl_setopt($request, CURLOPT_CUSTOMREQUEST, 'POST');
                curl_setopt($request, CURLOPT_POSTFIELDS, $data);
                break;
            case 'GET':
                curl_setopt($request, CURLOPT_CUSTOMREQUEST, 'GET');
                curl_setopt($request, CURLOPT_URL, add_query_arg($data, $url));
                break;
        }
        $body = curl_exec($request);
        $header = curl_getinfo($request);
        curl_close($request);

        // 将最近一次调用接口获取的header存下
        $this->last_header = $header;

        return $body;
    }

    /**
     * @return SimpleXMLElement
     *
     * 接收消息
     * 监听来自微信的xml请求,返回对象化的xml请求数据
     * 键名为tag的名
     *
     * url:http://mp.weixin.qq.com/wiki/10/79502792eef98d6e0c6e1739da387346.html
     */
    public function listen() {
        $body = file_get_contents('php://input');
        libxml_disable_entity_loader(true);
        return simplexml_load_string($body, 'SimpleXMLElement', LIBXML_NOCDATA);
    }

    /**
     * @throws Exception
     *
     * 检测消息可靠性
     *
     * url:http://mp.weixin.qq.com/wiki/17/2d4265491f12608cd170a95559800f2d.html
     */
    public function valid() {
        // 验证消息来源
        if($this->_checkSignature()){
            // 带echostr说明是服务器验证请求,返回echostr的值作为响应
            if(isset($_GET["echostr"])) {
                echo $_GET["echostr"];
                exit;
            }
            return true;
        }
        return false;
    }

    /**
     * @return bool
     * @throws Exception
     *
     * 检查签名
     */
    protected function _checkSignature() {
        // you must define TOKEN by yourself
        if (!isset($this->token)) {
            throw new Exception('TOKEN is not defined!');
        }

        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];

        $token = $this->token;
        $tmpArr = array($token, $timestamp, $nonce);
        // use SORT_STRING rule
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );

        if( $tmpStr == $signature ){
            return true;
        }else{
            return false;
        }
    }

    /**
     * @param $to_user
     * @param $from_user
     * @param $data
     * @param string $msg_type
     * @param null $create_time
     * @return string
     *
     * 被动回复消息
     * 通过给出的参数生成并返回接口需要的XML字符串
     *
     * url:http://mp.weixin.qq.com/wiki/14/89b871b5466b19b3efa4ada8e577d45e.html
     */
    public function get_response_msg($to_user, $from_user, $data, $msg_type = 'text', $create_time = null) {
        $create_time = $create_time ?: time();
        $xml = "<xml>
            <ToUserName><![CDATA[$to_user]]></ToUserName>
            <FromUserName><![CDATA[$from_user]]></FromUserName>
            <CreateTime>$create_time</CreateTime>
            <MsgType><![CDATA[$msg_type]]></MsgType>
        ";
        switch($msg_type) {
            case 'text':
                $xml .= "
                    <Content><![CDATA[{$data['Content']}]]></Content>
                    ";
                break;
            case 'image':
                $xml .= "
                    <Image>
                        <MediaId><![CDATA[{$data['MediaId']}]]></MediaId>
                    </Image>
                    ";
                break;
            case 'voice':
                $xml .= "
                    <Voice>
                        <MediaId><![CDATA[{$data['MediaId']}]]></MediaId>
                    </Voice>
                    ";
                break;
            case 'video':
                $xml .= "
                    <Video>
                        <MediaId><![CDATA[{$data['MediaId']}]]></MediaId>
                        <Title><![CDATA[{$data['Title']}]]></Title>
                        <Description><![CDATA[{$data['Description']}]]></Description>
                    </Video>
                    ";
                break;
            case 'music':
                $xml .= "
                    <Music>
                        <Title><![CDATA[{$data['Title']}]]></Title>
                        <Description><![CDATA[{$data['Description']}]]></Description>
                        <MusicUrl><![CDATA[{$data['MusicUrl']}]]></MusicUrl>
                        <HQMusicUrl><![CDATA[{$data['HQMusicUrl']}]]></HQMusicUrl>
                        <ThumbMediaId><![CDATA[{$data['ThumbMediaId']}]]></ThumbMediaId>
                    </Music>
                    ";
                break;
            case 'news':
                $ArticleCount = sizeof($data);
                if($ArticleCount > 0) {
                    $xml .= "
                    <ArticleCount>$ArticleCount</ArticleCount>
                    <Articles>
                    ";

                    foreach($data as $d) {
                        $xml.= "
                        <item>
                            <Title><![CDATA[{$d['Title']}]]></Title>
                            <Description><![CDATA[{$d['Description']}]]></Description>
                            <PicUrl><![CDATA[{$d['PicUrl']}]]></PicUrl>
                            <Url><![CDATA[{$d['Url']}]]></Url>
                        </item>
                        ";
                    }

                    $xml .= '</Articles>';
                }
                break;
        }

        return  $xml.'</xml>';
    }

    public function get_last_header() {
        return $this->last_header;
    }

    protected function _get_file($file_path) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $file_mime = finfo_file($finfo, realpath($file_path));
        finfo_close($finfo);

        return new CURLFile(realpath($file_path), $file_mime);
    }

    /**
     * @param $type
     * @param $file_path
     * @return array|mixed|object
     *
     * 增加临时素材
     *
     * url:http://mp.weixin.qq.com/wiki/5/963fc70b80dc75483a271298a76a8d59.html
     */
    public function tmp_media_upload($type, $file_path) {
        return json_decode($this->_request_api(
            add_query_arg(array(
                'access_token'  => $this->access_token,
                'type'          => $type,
                ),
                'https://api.weixin.qq.com/cgi-bin/media/upload'),
            array('media'   => $this->_get_file($file_path)),
            'POST'
        ));
    }

    /**
     * @param $type
     * @param $file_path
     * @param null $title
     * @param null $introduction
     * @return array|mixed|object
     *
     * 增加永久素材
     * 返回会带有资源的URL和meida_id
     *
     * url:http://mp.weixin.qq.com/wiki/14/7e6c03263063f4813141c3e17dd4350a.html
     */
    public function media_upload($type, $file_path, $title = null, $introduction= null) {
        $data = array(
            'type'  => $type,
            'media' => $this->_get_file($file_path),
        );
        // 如果是视频要加上description字段
        if($type == 'video') {
            $data['description'] = json_encode(array(
                'title'         => $title,
                'introduction'  => $introduction,
            ));
        }
        return json_decode($this->_request_api(
            add_query_arg(array(
                    'access_token'  => $this->access_token,
                ),
                'https://api.weixin.qq.com/cgi-bin/material/add_material'),
            $data,
            'POST'
        ));
    }

    /**
     * @param $articles
     * @return array|mixed|object
     *
     * 新增永久图文素材
     *
     * 调用方式:
     *	$article = array(
     *	    "title"=> 'TITLE',
     *	    "thumb_media_id"=> 'THUMB_MEDIA_ID',
     *	    "author"=> 'AUTHOR',
     *	    "digest"=> 'DIGEST',
     *	    "show_cover_pic"=> 'SHOW_COVER_PIC',
     *	    "content"=> 'CONTENT',
     *	    "content_source_url"=> 'CONTENT_SOURCE_URL'
     *	);
     *
     *	$articles[] = $article;
     *	$articles[] = $article;
     *
     *	add_news($articles);
     *
     * url:http://mp.weixin.qq.com/wiki/14/7e6c03263063f4813141c3e17dd4350a.html
     */
    public function add_news($articles) {
        return json_decode($this->_request_api(
            add_query_arg(array(
                    'access_token'  => $this->access_token,
                ),
                'https://api.weixin.qq.com/cgi-bin/material/add_news'),
            json_encode(array( 'articles'  => $articles )),
            'POST'
        ));
    }


    /**
     * @param $media_id
     * @return array|mixed|object
     *
     * 获取临时素材
     *
     * url:http://mp.weixin.qq.com/wiki/11/07b6b76a6b6e8848e855a435d5e34a5f.html
     */
    public function get_tmp_media($media_id) {
        return $this->_request_api(
            'https://api.weixin.qq.com/cgi-bin/media/get',
            array(
                'access_token'  => $this->access_token,
                'media_id'          => $media_id,
            )
        );
    }

    /**
     * @param $media_id
     * @return array|mixed|object
     *
     * 获取永久素材
     *
     * url:http://mp.weixin.qq.com/wiki/4/b3546879f07623cb30df9ca0e420a5d0.html
     */
    public function get_media($media_id) {
        return $this->_request_api(
            add_query_arg(array(
                'access_token'  => $this->access_token,
            ),
                'https://api.weixin.qq.com/cgi-bin/material/get_material'),
            json_encode(array( 'media_id'  => $media_id, )),
            'POST'
        );
    }

    /**
     * @param $media_id
     * @return array|mixed|object
     *
     * 删除永久素材
     *
     * url:http://mp.weixin.qq.com/wiki/5/e66f61c303db51a6c0f90f46b15af5f5.html
     */
    public function delete_media($media_id) {
        return json_decode($this->_request_api(
            add_query_arg(array(
                'access_token'  => $this->access_token,
            ),
                'https://api.weixin.qq.com/cgi-bin/material/del_material'),
            json_encode(array( 'media_id'  => $media_id )),
            'POST'
        ));
    }

    /**
     * @param $media_id
     * @param $index
     * @param $article
     * @return array|mixed|object
     *
     * 修改永久图文素材
     *
     * url:http://mp.weixin.qq.com/wiki/4/19a59cba020d506e767360ca1be29450.html
     */
    public function update_news($media_id, $index, $article) {
        return json_decode($this->_request_api(
            add_query_arg(array(
                'access_token'  => $this->access_token,
            ),
                'https://api.weixin.qq.com/cgi-bin/material/update_news'),
            json_encode(array(
                'media_id'  => $media_id,
                'index'     => $index,
                'articles'  => $article,
            )),
            'POST'
        ));
    }

    /**
     * @return array|mixed|object
     *
     * 获取素材总数
     *
     * url:http://mp.weixin.qq.com/wiki/16/8cc64f8c189674b421bee3ed403993b8.html
     */
    public function get_material_count() {
        return json_decode($this->_request_api(
            'https://api.weixin.qq.com/cgi-bin/material/get_materialcount',
            array(
                'access_token'  => $this->access_token,
            )
        ));
    }

    /**
     * @param $type
     * @param $offset
     * @param $count
     * @return array|mixed|object
     *
     * 获取素材列表
     *
     * url:http://mp.weixin.qq.com/wiki/12/2108cd7aafff7f388f41f37efa710204.html
     */
    public function get_material_list($type, $offset, $count) {
        return json_decode($this->_request_api(
            add_query_arg(array(
                'access_token'  => $this->access_token,
            ),
                'https://api.weixin.qq.com/cgi-bin/material/batchget_material'),
            json_encode(array(
                'type'  => $type,
                'offset'     => $offset,
                'count'  => $count,
            )),
            'POST'
        ));
    }

    /**
     * @param $name
     * @return array|mixed|object
     *
     * 创建用户分组
     *
     * url:http://mp.weixin.qq.com/wiki/0/56d992c605a97245eb7e617854b169fc.html
     */
    public function create_group($name) {
        return json_decode($this->_request_api(
            add_query_arg(array(
                'access_token'  => $this->access_token,
            ),
                'https://api.weixin.qq.com/cgi-bin/groups/create'),
                json_encode(array( 'group'  => array( 'name'  => $name, ))),
            'POST'
        ));
    }

    /**
     * @param $openid
     * @return array|mixed|object
     *
     *
     * 查询用户所在分组
     *
     * url:http://mp.weixin.qq.com/wiki/0/56d992c605a97245eb7e617854b169fc.html
     */
    public function get_user_group($openid) {
        return json_decode($this->_request_api(
            add_query_arg(array(
                'access_token'  => $this->access_token,
            ),
                'https://api.weixin.qq.com/cgi-bin/groups/getid'),
            json_encode(array('openid' => $openid)),
            'POST'
        ));
    }

    /**
     * @param $group_id
     * @param $name
     * @return array|mixed|object
     *
     * 修改分组名
     *
     * url:http://mp.weixin.qq.com/wiki/0/56d992c605a97245eb7e617854b169fc.html
     */
    public function update_group($group_id, $name) {
        return json_decode($this->_request_api(
            add_query_arg(array(
                'access_token'  => $this->access_token,
            ),
                'https://api.weixin.qq.com/cgi-bin/groups/update'),
            json_encode(array( 'group'  => array('id' => $group_id, 'name'  => $name))),
            'POST'
        ));
    }

    /**
     * @param $openid
     * @param $to_groupid
     * @return array|mixed|object
     *
     * 移动用户分组
     *
     * url:http://mp.weixin.qq.com/wiki/0/56d992c605a97245eb7e617854b169fc.html
     */
    public function move_user($openid, $to_groupid) {
        return json_decode($this->_request_api(
            add_query_arg(array(
                'access_token'  => $this->access_token,
            ),
                'https://api.weixin.qq.com/cgi-bin/groups/members/update'),
            json_encode(array( 'openid'  => $openid, 'to_groupid' => $to_groupid)),
            'POST'
        ));
    }

    /**
     * @param $openid_list
     * @param $to_groupid
     * @return array|mixed|object
     *
     * 批量移动用户分组
     *
     * url:http://mp.weixin.qq.com/wiki/0/56d992c605a97245eb7e617854b169fc.html
     */
    public function move_batch_users($openid_list, $to_groupid) {
        return json_decode($this->_request_api(
            add_query_arg(array(
                'access_token'  => $this->access_token,
            ),
                'https://api.weixin.qq.com/cgi-bin/groups/members/batchupdate'),
            json_encode(array( 'openid'  => $openid_list, 'to_groupid' => $to_groupid)),
            'POST'
        ));
    }

    /**
     * @param $group_id
     * @return array|mixed|object
     *
     * 删除分组
     *
     * url:http://mp.weixin.qq.com/wiki/0/56d992c605a97245eb7e617854b169fc.html
     */
    public function del_group($group_id) {
        return json_decode($this->_request_api(
            add_query_arg(array(
                'access_token'  => $this->access_token,
            ),
                'https://api.weixin.qq.com/cgi-bin/groups/delete'),
            json_encode(array( 'group'  => array('id' => $group_id))),
            'POST'
        ));
    }

    /**
     * @param $openid
     * @param $remark
     * @return array|mixed|object
     *
     * 设置备注名
     *
     * url:http://mp.weixin.qq.com/wiki/1/4a566d20d67def0b3c1afc55121d2419.html
     */
    public function set_user_remark($openid, $remark) {
        return json_decode($this->_request_api(
            add_query_arg(array(
                'access_token'  => $this->access_token,
            ),
                'https://api.weixin.qq.com/cgi-bin/user/info/updateremark'),
            json_encode(array( 'openid'  => $openid, 'remark' => $remark)),
            'POST'
        ));
    }

    /**
     * @return array|mixed|object
     *
     * 查询所有分组
     *
     * url:http://mp.weixin.qq.com/wiki/0/56d992c605a97245eb7e617854b169fc.html
     */
    public function get_groups() {
        return json_decode($this->_request_api(
            add_query_arg(array(
                'access_token'  => $this->access_token,
            ),
                'https://api.weixin.qq.com/cgi-bin/groups/get')
        ));
    }

    /**
     * @param $openid
     * @param string $lang
     * @return array|mixed|object
     *
     * 获取用户基本信息
     *
     * url:http://mp.weixin.qq.com/wiki/14/bb5031008f1494a59c6f71fa0f319c66.html
     */
    public function get_user_info($openid, $lang = 'zh_CN ') {
        return json_decode($this->_request_api(
            add_query_arg(array(
                'access_token'  => $this->access_token,
                'openid'  => $openid,
                'lang'  => $lang,
            ),
                'https://api.weixin.qq.com/cgi-bin/user/info')
        ));
    }

    /**
     * @param $user_list
     * @return array|mixed|object
     *
     * 批量获取用户基本信息
     *
     * url:http://mp.weixin.qq.com/wiki/14/bb5031008f1494a59c6f71fa0f319c66.html
     */
    public function get_batch_users_info($user_list) {
        return json_decode($this->_request_api(
            add_query_arg(array(
                'access_token'  => $this->access_token,
            ),
                'https://api.weixin.qq.com/cgi-bin/user/info/batchget'),
            json_encode(array('user_list' => $user_list)),
            'POST'
        ));
    }

    /**
     * @param null $next_openid
     * @return array|mixed|object
     *
     * 获取用户列表
     *
     * url:http://mp.weixin.qq.com/wiki/0/d0e07720fc711c02a3eab6ec33054804.html
     */
    public function get_user_list($next_openid = null) {
        return json_decode($this->_request_api(
            add_query_arg(array(
                'access_token'  => $this->access_token,
                'next_openid'  => $next_openid,
            ),
                'https://api.weixin.qq.com/cgi-bin/user/get')
        ));
    }

    /**
     * @param $file_path
     * @return array|mixed|object
     *
     * 群发接口
     * 上传图文消息内的图片获取URL
     *
     * url:http://mp.weixin.qq.com/wiki/15/5380a4e6f02f2ffdc7981a8ed7a40753.html
     */
    public function gs_upload_img($file_path) {
        return json_decode($this->_request_api(
            add_query_arg(array(
                'access_token'  => $this->access_token,
                ),'https://api.weixin.qq.com/cgi-bin/media/uploadimg'),
            array('media' => $this->_get_file($file_path)),
            'POST'
        ));
    }

    /**
     * @param $articles
     * @return array|mixed|object
     *
     * 群发接口
     * 上传图文消息素材
     *
     * url:http://mp.weixin.qq.com/wiki/15/5380a4e6f02f2ffdc7981a8ed7a40753.html
     */
    public function gs_upload_news($articles) {
        return json_decode($this->_request_api(
            add_query_arg(array(
                'access_token'  => $this->access_token,
                ),'https://api.weixin.qq.com/cgi-bin/media/uploadnews'),
            json_encode(array('articles' => $articles)),
            'POST'
        ));
    }

    /**
     * @param $media_id
     * @param $title
     * @param $description
     * @return array|mixed|object
     *
     * 群发接口
     * 上传视频素材
     * 要注意此处media_id需通过基础支持中的上传下载多媒体文件来得到
     *
     * url:http://mp.weixin.qq.com/wiki/15/5380a4e6f02f2ffdc7981a8ed7a40753.html
     */
    public function gs_upload_video($media_id, $title, $description) {
        return json_decode($this->_request_api(
            add_query_arg(array(
                'access_token'  => $this->access_token,
                ),'https://file.api.weixin.qq.com/cgi-bin/media/uploadvideo'),
            json_encode(array(
                'media_id' => $media_id,
                'title' => $title,
                'description' => $description,
            )),
            'POST'
        ));
    }

    /**
     * @param $msg_id
     * @return array|mixed|object
     *
     * 群发接口
     * 删除群发
     *
     * url:http://mp.weixin.qq.com/wiki/15/5380a4e6f02f2ffdc7981a8ed7a40753.html
     */
    public function gs_del_msg($msg_id) {
        return json_decode($this->_request_api(
            add_query_arg(array(
                'access_token'  => $this->access_token,
                ),'https://api.weixin.qq.com/cgi-bin/message/mass/delete'),
            json_encode(array( 'msg_id' => $msg_id, )),
            'POST'
        ));
    }

    /**
     * @param $msg_id
     * @return array|mixed|object
     *
     * 群发接口
     * 查询群发消息发送状态
     *
     * url:http://mp.weixin.qq.com/wiki/15/5380a4e6f02f2ffdc7981a8ed7a40753.html
     */
    public function gs_get_status($msg_id) {
        return json_decode($this->_request_api(
            add_query_arg(array(
                'access_token'  => $this->access_token,
                ),'https://api.weixin.qq.com/cgi-bin/message/mass/get'),
            json_encode(array( 'msg_id' => $msg_id, )),
            'POST'
        ));
    }

    /**
     * @param $mix_content
     * @param $type
     * @param bool|false $is_to_all
     * @param null $group_id
     * @return array|mixed|object
     *
     * 群发接口
     * 根据分组进行群发/全部进行群发
     *
     * url:http://mp.weixin.qq.com/wiki/15/5380a4e6f02f2ffdc7981a8ed7a40753.html
     */
    public function gs_send_all($type, $mix_content, $is_to_all = true, $group_id = null) {
        $data = array(
            'filter'    => array(
                'is_to_all' => $is_to_all,
                'group_id' => $group_id,
            ),
            'msgtype'   => $type,
        );
        switch($type) {
            case 'mpnews':
                $data['mpnews'] = array('media_id'  => $mix_content);
                break;
            case 'text':
                $data['text'] = array('content'  => $mix_content);
                break;
            case 'voice':
                $data['voice'] = array('media_id'  => $mix_content);
                break;
            case 'image':
                $data['image'] = array('media_id'  => $mix_content);
                break;
            case 'mpvideo':
                $data['mpvideo'] = array('media_id'  => $mix_content);
                break;
            case 'wxcard':
                $data['wxcard'] = array('card_id'  => $mix_content);
                break;
        }
        return json_decode($this->_request_api(
            add_query_arg(array(
                'access_token'  => $this->access_token,
                ),'https://api.weixin.qq.com/cgi-bin/message/mass/sendall'),
            json_encode($data),
            'POST'
        ));
    }

    /**
     * @param $mix_content
     * @param $user_list
     * @param $type
     * @return array|mixed|object
     *
     * 群发接口
     * 根据OpenID列表群发
     *
     * url:http://mp.weixin.qq.com/wiki/15/5380a4e6f02f2ffdc7981a8ed7a40753.html
     */
    public function gs_send_msg($type, $mix_content, $user_list) {
        $data = array(
            'touser'    => $user_list,
            'msgtype'   => $type,
        );
        switch($type) {
            case 'mpnews':
                $data['mpnews'] = array('media_id'  => $mix_content);
                break;
            case 'text':
                $data['text'] = array('content'  => $mix_content);
                break;
            case 'voice':
                $data['voice'] = array('media_id'  => $mix_content);
                break;
            case 'image':
                $data['image'] = array('media_id'  => $mix_content);
                break;
            case 'mpvideo':
                $data['mpvideo'] = array('media_id'  => $mix_content);
                break;
            case 'wxcard':
                $data['wxcard'] = array('card_id'  => $mix_content);
                break;
        }
        return json_decode($this->_request_api(
            add_query_arg(array(
                'access_token'  => $this->access_token,
                ),'https://api.weixin.qq.com/cgi-bin/message/mass/send'),
            json_encode($data),
            'POST'
        ));
    }

    /**
     * @param $user
     * @param $mix_content
     * @param $type
     * @param $use_name
     * @param $card_info
     * @return array|mixed|object
     *
     * 群发接口
     * 预览接口
     * $use_name 决定$user参数代表的意义  true:为openId    false:为微信号
     * 如果要预览卡券信息要填写$card_info,格式应该像下面一样的数组
     * $card_info = array(
     * "code"=> '',
     * "openid"=> '',
     * "timestamp"=> '',
     * "signature"=> '',
     * )
     *
     * url:http://mp.weixin.qq.com/wiki/15/5380a4e6f02f2ffdc7981a8ed7a40753.html
     */
    public function gs_preview($user, $type, $mix_content, $use_name = false, $card_info = array()) {
        $data = array();
        if($use_name) {
            $data['towxname'] = $user;
        }
        else {
            $data['touser'] = $user;
        }
        $data['msgtype'] = $type;

        switch($type) {
            case 'mpnews':
                $data['mpnews'] = array('media_id'  => $mix_content);
                break;
            case 'text':
                $data['text'] = array('content'  => $mix_content);
                break;
            case 'voice':
                $data['voice'] = array('media_id'  => $mix_content);
                break;
            case 'image':
                $data['image'] = array('media_id'  => $mix_content);
                break;
            case 'mpvideo':
                $data['mpvideo'] = array('media_id'  => $mix_content);
                break;
            case 'wxcard':
                $data['wxcard'] = array('card_id'  => $mix_content);
                $data['wxcard'] = array('card_ext'  => json_encode($card_info));
                break;
        }
        return json_decode($this->_request_api(
            add_query_arg(array(
                'access_token'  => $this->access_token,
                ),'https://api.weixin.qq.com/cgi-bin/message/mass/preview'),
            json_encode($data),
            'POST'
        ));
    }
};

