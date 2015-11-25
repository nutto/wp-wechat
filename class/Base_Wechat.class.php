<?php

/**
 * Class Base_Wechat
 * 基础微信接口
 */
class Base_Wechat {

    // 微信号
    // option(wx_self_id)
    protected $self_id;

    // 服务器验证标识
    // option(wx_token)
    protected $token;

    // 消息加解密密钥
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

    // 公众号的全局唯一票据
    protected $access_token;

    // 微信应用ID与应用密钥的hash值,可以帮助判断$app_id和$app_secret是否有改变
    protected $app_confirm_identify;

    // 最近一次调用接口的时候获取到的header信息数组
    public $last_header;

    function __construct(
        $app_id,
        $app_secret,
        $token = null,
        $access_token = null,
        $token_expire = null,
        $token_modified_time = null,
        $app_confirm_identify = null,
        $encoding_aes_key = null,
        $self_id = null
    ) {
        // 获取微信的配置
        $this->app_id = $app_id;
        $this->app_secret = $app_secret;
        $this->self_id = $self_id;
        $this->token = $token;
        $this->encoding_aes_key = $encoding_aes_key;
        $this->access_token = $access_token;
        $this->token_expire = $token_expire;
        $this->token_modified_time = $token_modified_time;
        $this->app_confirm_identify = $app_confirm_identify;
        // 20151109:确保Access Token的有效性
        $this->ensureAccessToken();
    }

    /**
     * -------------------------------------------------------------------------------------------------
     * 工具a
     */

    /**
     * @param $data
     * @param int $args 控制json输出格式,默认不转Unicode
     * @return mixed|string|void
     *
     * 对Json编码的特殊处理函数
     */
    protected function _jsonEncodeDealer($data, $args = JSON_UNESCAPED_UNICODE) {
        return json_encode($data, $args);
    }

    public function jsonEncodeDealer($data, $args = null) {
        return $this->_jsonEncodeDealer($data, $args);
    }

    /**
     * @param array $args Query string 的键值数组
     * @param string $url
     * @return string
     *
     */
    public function _addQueryArg($args, $url) {
        $parsed_url = parse_url($url);

        $addition = '';
        foreach($args as $k => $v) {
            $addition .= $k.'='.$v.'&';
        }
        $parsed_url['query'] .= isset($parsed_url['query']) ? '&'.$addition: $addition;

        $new_url = $parsed_url['scheme'].'://'.$parsed_url['host'].$parsed_url['path'];

        $new_url .= isset($parsed_url['query']) ? '?'.$parsed_url['query'] : null;

        $new_url .= isset($parsed_url['fragment']) ? '#'.$parsed_url['fragment'] : null;

        return $new_url;
    }

    /**
     * @return array
     * @throws Exception
     *
     * 根据配置立刻取access token
     * 不会检查是否有原access token或原access token是否有效
     * 会强制刷新该用户的access token,并将刷新的信息以数组形式返回
     */
    protected function _getAccessToken() {
        $url = 'https://api.weixin.qq.com/cgi-bin/token';
        $arg = array(
            'grant_type'=> 'client_credential',
            'appid'     => $this->app_id,
            'secret'    => $this->app_secret,
        );
        $token_result = json_decode($this->_requestApi($url, $arg));
        if(!$token_result) {
            throw new Exception('Fail to get Access Token!');
        }

        $re = array();

        // 20151115:Base_Wechat分离,得到的信息以数组形式返回
        $this->token_modified_time = time();
        $re['token_modified_time'] = $this->token_modified_time;

        $this->access_token = $token_result->access_token;
        $re['access_token'] = $this->access_token;

        $this->token_expire = $token_result->expires_in;
        $re['token_expire'] = $this->token_expire;

        $re['app_confirm_identify'] = md5($this->app_id.'|'.$this->app_secret);

        return $re;
    }

    /**
     * @param $url
     * @param array $data 发送请求的数据,如果为GET方法数据会成为query string
     * @param string $method 请求的方法 POST | GET
     * @return array|mixed|object
     *
     * 用curl向微信发送请求的函数
     * 返回原始数据
     */
    protected function _requestApi($url, $data = array(), $method = 'GET') {
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
                curl_setopt($request, CURLOPT_URL, $this->_addQueryArg($data, $url));
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
     * @param string $file_path 文件路径
     * @return CURLFile
     *
     * 获取文件路径返回CURL可用的文件资源
     */
    protected function _getFile($file_path) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $file_mime = finfo_file($finfo, realpath($file_path));
        finfo_close($finfo);

        return new CURLFile(realpath($file_path), $file_mime);
    }

    /**
     * @return mixed
     *
     * 获取最后一次请求返回的头部信息
     */
    public function getLastHeader() {
        return $this->last_header;
    }

    /**
     * -------------------------------------------------------------------------------------------------
     * 验证
     */

    /**
     * @throws Exception
     *
     * 确保access token的有效性,如果access token失效,会去尝试获取新的access token
     */
    public function ensureAccessToken() {
        // 没有初始化和超过认证时限都需要重新获取access token
        if(!isset($this->access_token) || !isset($this->token_modified_time) || !isset($this->token_expire) ||
            (time() - $this->token_modified_time) > ($this->token_expire - 60) ||   // 提早60秒获取,避免误差
            $this->app_confirm_identify != md5($this->app_id.'|'.$this->app_secret)) {  // 确认用户没有改变
            $this->_getAccessToken();
        }
    }

    /**
     * @throws Exception
     * 强制获取access token
     */
    public function getAccessToken() {
        return $this->_getAccessToken();
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

        $signature = @$_GET["signature"];
        $timestamp = @$_GET["timestamp"];
        $nonce = @$_GET["nonce"];

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
     * -------------------------------------------------------------------------------------------------
     * 消息处理
     */

    /**
     * @return array
     *
     * 20151109:
     * 接收消息
     * 监听来自微信的xml请求,返回数组化的xml请求数据
     * 键名为tag的标签名,键值为标签的值
     *
     * url:http://mp.weixin.qq.com/wiki/10/79502792eef98d6e0c6e1739da387346.html
     */
    public function listen() {
        $body = file_get_contents('php://input');
        libxml_disable_entity_loader(true);
        return (array)simplexml_load_string($body, 'SimpleXMLElement', LIBXML_NOCDATA);
    }

    /**
     * @param string $to_user
     * @param string $from_user
     * @param array $data 应该传入除了ToUserName, FromUserName, CreateTime, MsgType外还需要填写的标签数据
     *                     传入的键名为标签名,键值为标签的值.标签名请参考微信官方文档
     *      例如需要一个'text'类型的信息,你需要一个这样的$data:
     *                          + [ 'Content' => 'write your content' ]
     * @param string $msg_type
     * @param null $create_time
     * @return string
     *
     * 被动回复消息
     * 通过给出的参数生成并返回接口需要的XML字符串
     *
     * url:http://mp.weixin.qq.com/wiki/14/89b871b5466b19b3efa4ada8e577d45e.html
     */
    public function getResponseMsg($to_user, $from_user, $data, $msg_type = 'text', $create_time = null) {
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

    /**
     * -------------------------------------------------------------------------------------------------
     * 素材管理
     */

    /**
     * @param $type
     * @param $file_path
     * @return array|mixed|object
     *
     * 增加临时素材
     *
     * url:http://mp.weixin.qq.com/wiki/5/963fc70b80dc75483a271298a76a8d59.html
     */
    public function tmpMediaUpload($type, $file_path) {
        return json_decode($this->_requestApi(
            $this->_addQueryArg(array(
                'access_token'  => $this->access_token,
                'type'          => $type,
                ),
                'https://api.weixin.qq.com/cgi-bin/media/upload'),
            array('media'   => $this->_getFile($file_path)),
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
    public function mediaUpload($type, $file_path, $title = null, $introduction= null) {
        $data = array(
            'type'  => $type,
            'media' => $this->_getFile($file_path),
        );
        // 如果是视频要加上description字段
        if($type == 'video') {
            $data['description'] = $this->_jsonEncodeDealer(array(
                'title'         => $title,
                'introduction'  => $introduction,
            ));
        }
        return json_decode($this->_requestApi(
            $this->_addQueryArg(array(
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
    public function addNews($articles) {
        return json_decode($this->_requestApi(
            $this->_addQueryArg(array(
                    'access_token'  => $this->access_token,
                ),
                'https://api.weixin.qq.com/cgi-bin/material/add_news'),
            $this->_jsonEncodeDealer(array( 'articles'  => $articles )),
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
    public function getTmpMedia($media_id) {
        return $this->_requestApi(
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
    public function getMedia($media_id) {
        return $this->_requestApi(
            $this->_addQueryArg(array(
                'access_token'  => $this->access_token,
            ),
                'https://api.weixin.qq.com/cgi-bin/material/get_material'),
            $this->_jsonEncodeDealer(array( 'media_id'  => $media_id, )),
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
    public function deleteMedia($media_id) {
        return json_decode($this->_requestApi(
            $this->_addQueryArg(array(
                'access_token'  => $this->access_token,
            ),
                'https://api.weixin.qq.com/cgi-bin/material/del_material'),
            $this->_jsonEncodeDealer(array( 'media_id'  => $media_id )),
            'POST'
        ));
    }

    /**
     * @param $media_id
     * @param $index
     * @param array $article 文章信息的数组
     *
     *     $article => {
     *          "title" => 'TITLE',
     *          "thumb_media_id" => 'THUMB_MEDIA_ID',
     *          "author" => 'AUTHOR',
     *          "digest" => 'DIGEST',
     *          "show_cover_pic" => 'SHOW_COVER_PIC(0 / 1)',
     *          "content" => 'CONTENT',
     *          "content_source_url" => 'CONTENT_SOURCE_URL'
     *       }
     *
     * @return array|mixed|object
     *
     * 修改永久图文素材
     *
     * url:http://mp.weixin.qq.com/wiki/4/19a59cba020d506e767360ca1be29450.html
     */
    public function updateNews($media_id, $index, $article) {
        return json_decode($this->_requestApi(
            $this->_addQueryArg(array(
                'access_token'  => $this->access_token,
            ),
                'https://api.weixin.qq.com/cgi-bin/material/update_news'),
            $this->_jsonEncodeDealer(array(
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
    public function getMaterialCount() {
        return json_decode($this->_requestApi(
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
    public function getMaterialList($type, $offset, $count) {
        return json_decode($this->_requestApi(
            $this->_addQueryArg(array(
                'access_token'  => $this->access_token,
            ),
                'https://api.weixin.qq.com/cgi-bin/material/batchget_material'),
            $this->_jsonEncodeDealer(array(
                'type'  => $type,
                'offset'     => $offset,
                'count'  => $count,
            )),
            'POST'
        ));
    }

    /**
     * -------------------------------------------------------------------------------------------------
     * 用户管理
     */

    /**
     * @param $name
     * @return array|mixed|object
     *
     * 创建用户分组
     *
     * url:http://mp.weixin.qq.com/wiki/0/56d992c605a97245eb7e617854b169fc.html
     */
    public function createGroup($name) {
        return json_decode($this->_requestApi(
            $this->_addQueryArg(array(
                'access_token'  => $this->access_token,
            ),
                'https://api.weixin.qq.com/cgi-bin/groups/create'),
                $this->_jsonEncodeDealer(array( 'group'  => array( 'name'  => $name, ))),
            'POST'
        ));
    }

    /**
     * @param $openid
     * @return array|mixed|object
     *
     * 查询用户所在分组
     *
     * url:http://mp.weixin.qq.com/wiki/0/56d992c605a97245eb7e617854b169fc.html
     */
    public function getUserGroup($openid) {
        return json_decode($this->_requestApi(
            $this->_addQueryArg(array(
                'access_token'  => $this->access_token,
            ),
                'https://api.weixin.qq.com/cgi-bin/groups/getid'),
            $this->_jsonEncodeDealer(array('openid' => $openid)),
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
    public function updateGroup($group_id, $name) {
        return json_decode($this->_requestApi(
            $this->_addQueryArg(array(
                'access_token'  => $this->access_token,
            ),
                'https://api.weixin.qq.com/cgi-bin/groups/update'),
            $this->_jsonEncodeDealer(array( 'group'  => array('id' => $group_id, 'name'  => $name))),
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
    public function moveUser($openid, $to_groupid) {
        return json_decode($this->_requestApi(
            $this->_addQueryArg(array(
                'access_token'  => $this->access_token,
            ),
                'https://api.weixin.qq.com/cgi-bin/groups/members/update'),
            $this->_jsonEncodeDealer(array( 'openid'  => $openid, 'to_groupid' => $to_groupid)),
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
    public function moveBatchUsers($openid_list, $to_groupid) {
        return json_decode($this->_requestApi(
            $this->_addQueryArg(array(
                'access_token'  => $this->access_token,
            ),
                'https://api.weixin.qq.com/cgi-bin/groups/members/batchupdate'),
            $this->_jsonEncodeDealer(array( 'openid'  => $openid_list, 'to_groupid' => $to_groupid)),
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
    public function delGroup($group_id) {
        return json_decode($this->_requestApi(
            $this->_addQueryArg(array(
                'access_token'  => $this->access_token,
            ),
                'https://api.weixin.qq.com/cgi-bin/groups/delete'),
            $this->_jsonEncodeDealer(array( 'group'  => array('id' => $group_id))),
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
    public function setUserRemark($openid, $remark) {
        return json_decode($this->_requestApi(
            $this->_addQueryArg(array(
                'access_token'  => $this->access_token,
            ),
                'https://api.weixin.qq.com/cgi-bin/user/info/updateremark'),
            $this->_jsonEncodeDealer(array( 'openid'  => $openid, 'remark' => $remark)),
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
    public function getGroups() {
        return json_decode($this->_requestApi(
            $this->_addQueryArg(array(
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
    public function getUserInfo($openid, $lang = 'zh_CN') {
        return json_decode($this->_requestApi(
            $this->_addQueryArg(array(
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
    public function getBatchUsersInfo($user_list) {
        return json_decode($this->_requestApi(
            $this->_addQueryArg(array(
                'access_token'  => $this->access_token,
            ),
                'https://api.weixin.qq.com/cgi-bin/user/info/batchget'),
            $this->_jsonEncodeDealer(array('user_list' => $user_list)),
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
    public function getUserList($next_openid = null) {
        return json_decode($this->_requestApi(
            $this->_addQueryArg(array(
                'access_token'  => $this->access_token,
                'next_openid'  => $next_openid,
            ),
                'https://api.weixin.qq.com/cgi-bin/user/get')
        ));
    }

    /**
     * -------------------------------------------------------------------------------------------------
     * 群发
     * gs for group send
     */

    /**
     * @param $file_path
     * @return array|mixed|object
     *
     * 群发接口
     * 上传图文消息内的图片获取URL
     *
     * url:http://mp.weixin.qq.com/wiki/15/5380a4e6f02f2ffdc7981a8ed7a40753.html
     */
    public function gsUploadImg($file_path) {
        return json_decode($this->_requestApi(
            $this->_addQueryArg(array(
                'access_token'  => $this->access_token,
                ),'https://api.weixin.qq.com/cgi-bin/media/uploadimg'),
            array('media' => $this->_getFile($file_path)),
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
    public function gsUploadNews($articles) {
        return json_decode($this->_requestApi(
            $this->_addQueryArg(array(
                'access_token'  => $this->access_token,
                ),'https://api.weixin.qq.com/cgi-bin/media/uploadnews'),
            $this->_jsonEncodeDealer(array('articles' => $articles)),
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
    public function gsUploadVideo($media_id, $title, $description) {
        return json_decode($this->_requestApi(
            $this->_addQueryArg(array(
                'access_token'  => $this->access_token,
                ),'https://file.api.weixin.qq.com/cgi-bin/media/uploadvideo'),
            $this->_jsonEncodeDealer(array(
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
    public function gsDelMsg($msg_id) {
        return json_decode($this->_requestApi(
            $this->_addQueryArg(array(
                'access_token'  => $this->access_token,
                ),'https://api.weixin.qq.com/cgi-bin/message/mass/delete'),
            $this->_jsonEncodeDealer(array( 'msg_id' => $msg_id, )),
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
    public function gsGetStatus($msg_id) {
        return json_decode($this->_requestApi(
            $this->_addQueryArg(array(
                'access_token'  => $this->access_token,
                ),'https://api.weixin.qq.com/cgi-bin/message/mass/get'),
            $this->_jsonEncodeDealer(array( 'msg_id' => $msg_id, )),
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
    public function gsSendAll($type, $mix_content, $is_to_all = true, $group_id = null) {
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
        return json_decode($this->_requestApi(
            $this->_addQueryArg(array(
                'access_token'  => $this->access_token,
                ),'https://api.weixin.qq.com/cgi-bin/message/mass/sendall'),
            $this->_jsonEncodeDealer($data),
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
    public function gsSendMsg($type, $mix_content, $user_list) {
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
        return json_decode($this->_requestApi(
            $this->_addQueryArg(array(
                'access_token'  => $this->access_token,
                ),'https://api.weixin.qq.com/cgi-bin/message/mass/send'),
            $this->_jsonEncodeDealer($data),
            'POST'
        ));
    }

    /**
     * @param $user
     * @param $mix_content
     * @param $type
     * @param string $use_name 指定推送对象的昵称,如果填写了就会忽略$user变量
     * @param array $card_info 如果要预览卡券信息要填写$card_info,格式应该像下面一样的数组
     *
     *     $card_info = array(
     *         "code"=> '',
     *         "openid"=> '',
     *         "timestamp"=> '',
     *         "signature"=> '',
     *     )
     *
     * @return array|mixed|object
     *
     * 群发接口
     * 预览接口
     * $use_name 决定$user参数代表的意义  true:为openId    false:为微信号
     *
     * url:http://mp.weixin.qq.com/wiki/15/5380a4e6f02f2ffdc7981a8ed7a40753.html
     */
    public function gsPreview($user, $type, $mix_content, $use_name = '', $card_info = array()) {
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
                $data['wxcard'] = array('card_ext'  => $this->_jsonEncodeDealer($card_info));
                break;
        }
        return json_decode($this->_requestApi(
            $this->_addQueryArg(array(
                'access_token'  => $this->access_token,
                ),'https://api.weixin.qq.com/cgi-bin/message/mass/preview'),
            $this->_jsonEncodeDealer($data),
            'POST'
        ));
    }

    /**
     * -------------------------------------------------------------------------------------------------
     * 菜单操作
     */

    /**
     * @param array $menu 菜单项的数组,结构类似如下,详情参见文档
     *
     *     [
     *       {
     *         "type" => "click",
     *         "name" => "今日歌曲",
     *         "key" => "V1001_TODAY_MUSIC"
     *       }
     *       ...
     *     ]
     *
     * @return array|mixed|object
     *
     * 新增自定义菜单
     *
     * url:http://mp.weixin.qq.com/wiki/6/95cade7d98b6c1e1040cde5d9a2f9c26.html
     */
    public function createMenu($menu) {
        return json_decode($this->_requestApi(
            $this->_addQueryArg(array(
                'access_token'  => $this->access_token,
            ),'https://api.weixin.qq.com/cgi-bin/menu/create'),
            $this->_jsonEncodeDealer(array( 'button' => $menu, )),
            'POST'
        ));
    }

    /**
     * @return array|mixed|object
     *
     * 自定义菜单查询接口
     *
     * url:http://mp.weixin.qq.com/wiki/2/07112acf4bb9a19d50c8ae08515a2a6a.html
     */
    public function getMenu() {
        return json_decode($this->_requestApi(
            $this->_addQueryArg(array(
                'access_token'  => $this->access_token,
            ),'https://api.weixin.qq.com/cgi-bin/menu/get')
        ))->menu->button;
    }

    /**
     * @return array|mixed|object
     *
     * 自定义菜单删除接口
     *
     * url:http://mp.weixin.qq.com/wiki/11/51aa2be3cc267a4947216a44b2e25187.html
     */
    public function delMenu() {
        return json_decode($this->_requestApi(
            $this->_addQueryArg(array(
                'access_token'  => $this->access_token,
            ),'https://api.weixin.qq.com/cgi-bin/menu/delete')
        ));
    }

    /**
     * @return array|mixed|object
     *
     * 获取自定义菜单配置接口
     *
     * url:http://mp.weixin.qq.com/wiki/6/51671aa8efcd21493b8a8f505c288706.html
     */
    public function getCurrentMenu() {
        return json_decode($this->_requestApi(
            $this->_addQueryArg(array(
                'access_token'  => $this->access_token,
            ),'https://api.weixin.qq.com/cgi-bin/get_current_selfmenu_info')
        ));
    }

    /**
     * -------------------------------------------------------------------------------------------------
     * 客服接口
     * cs for customer service
     */

    /**
     * @param $account
     * @param $nickname
     * @param $password
     * @return array|mixed|object
     *
     * 添加客服帐号
     *
     * url:http://mp.weixin.qq.com/wiki/14/d9be34fe03412c92517da10a5980e7ee.html
     */
    public function csCreate($account, $nickname, $password) {
        return json_decode($this->_requestApi(
            $this->_addQueryArg(array(
                'access_token'  => $this->access_token,
            ),'https://api.weixin.qq.com/customservice/kfaccount/add'),
            $this->_jsonEncodeDealer(array(
                'kf_account' => $account,
                'nickname' => $nickname,
                'password' => md5($password),
                )),
            'POST'
        ));
    }

    /**
     * @param $account
     * @param $nickname
     * @param $password
     * @return array|mixed|object
     *
     * 修改客服帐号
     *
     * url:http://mp.weixin.qq.com/wiki/14/d9be34fe03412c92517da10a5980e7ee.html
     */
    public function csUpdate($account, $nickname, $password) {
        return json_decode($this->_requestApi(
            $this->_addQueryArg(array(
                'access_token'  => $this->access_token,
            ),'https://api.weixin.qq.com/customservice/kfaccount/update'),
            $this->_jsonEncodeDealer(array(
                'kf_account' => $account,
                'nickname' => $nickname,
                'password' => md5($password),
                )),
            'POST'
        ));
    }

    /**
     * @param $account
     * @param $nickname
     * @param $password
     * @return array|mixed|object
     *
     * 删除客服帐号
     *
     * url:http://mp.weixin.qq.com/wiki/14/d9be34fe03412c92517da10a5980e7ee.html
     */
    public function csDel($account, $nickname, $password) {
        return json_decode($this->_requestApi(
            $this->_addQueryArg(array(
                'access_token'  => $this->access_token,
            ),'https://api.weixin.qq.com/customservice/kfaccount/del'),
            $this->_jsonEncodeDealer(array(
                'kf_account' => $account,
                'nickname' => $nickname,
                'password' => md5($password),
                )),
            'POST'
        ));
    }

    /**
     * @param string $account
     * @param string $file_path 头像文件路径
     * @return array|mixed|object
     *
     * 设置客服帐号的头像
     *
     * url:http://mp.weixin.qq.com/wiki/14/d9be34fe03412c92517da10a5980e7ee.html
     */
    public function csSetAvatar($account, $file_path) {
        return json_decode($this->_requestApi(
            $this->_addQueryArg(array(
                'access_token'  => $this->access_token,
                'kf_account'  => $account,
            ),'https://api.weixin.qq.com/customservice/kfaccount/del'),
            array($this->_getFile($file_path)),
            'POST'
        ));
    }

    /**
     * @param string $touser
     * @param string $type
     * @param array|mixed|string $mix_content
     * @return array|mixed|object
     *
     * 客服接口-发消息
     *
     * url:http://mp.weixin.qq.com/wiki/14/d9be34fe03412c92517da10a5980e7ee.html
     */
    public function csSend($touser, $type, $mix_content) {
        $data = array(
            'touser' => $touser,
            'msgtype' => $type,
            $type => $mix_content
        );

        return json_decode($this->_requestApi(
            $this->_addQueryArg(array('access_token'  => $this->access_token,),
                'https://api.weixin.qq.com/cgi-bin/message/custom/send'),
            $this->_jsonEncodeDealer($data),
            'POST'
        ));
    }

    /**
     * @return array|mixed|object
     *
     * 获取所有客服账号
     *
     * url:http://mp.weixin.qq.com/wiki/14/d9be34fe03412c92517da10a5980e7ee.html
     */
    public function csGetList() {
        return json_decode($this->_requestApi(
            $this->_addQueryArg(array('access_token'  => $this->access_token,
            ),'https://api.weixin.qq.com/cgi-bin/customservice/getkflist')
        ));
    }
};

