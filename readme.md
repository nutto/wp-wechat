#WordPress 微信插件

>    Plugin Name: EC-WeChat
>    Plugin URI:  http://http://www.easecloud.cn/
>    Description: This Plugin is designed for using wechat API more conveniently
>    Version:     0.1
>    Author:      Nutto
>    Author URI:  http://http://www.easecloud.cn/
>    License:     GPL3
>    License URI: https://www.gnu.org/licenses/gpl-3.0.txt

__本接口旨在帮助用户快速构建,解析和发送微信接口中的消息数据,对于接口的返回和错误不作处理__


##获取access token和确保access token的可用性

---

* ``` get_access_token() ``` 强制获取新的access token

* ``` ensure_access_token() ``` 检查当前的accesss token的可用性^[注],当当前的access token失效的时候会获取新的access token

* 当 ``` EC_Wechat ``` 类被实例化的时候会确保access token的可用性^[注]

__[注]:可用性是通过比较获取access token时返回的时间和时限来确保,如果在一个access token的有效期内通过其他方法获取了新的access token将会导致程序错误__


##检测消息可靠性和响应服务器验证请求

---

> http://mp.weixin.qq.com/wiki/17/2d4265491f12608cd170a95559800f2d.html

* ```valid()  ``` 会通过请求中的 ``` signature ```, ``` timestamp ```, ``` nonce ``` 字段验证消息的可靠性,返回 ``` true ```或者 ``` false ```,如果请求中带有 ``` echostr ``` 会认为是服务器验证的请求,验证通过后会直接输出 ``` echostr ``` 的值作为响应

**应该在微信的callback页面的最前面调用 ``` valid() ``` 函数并完成必要的验证的逻辑操作**

##接收消息

---

> http://mp.weixin.qq.com/wiki/10/79502792eef98d6e0c6e1739da387346.html

无论普通消息和事件推送消息都可以通过 ``` listen() ``` 来捕获消息并获得对消息数组化的返回

例如:

```
$ec_wechat = new EC_Wechat();

$request_arr = $ec_wechat->listen();

```

用body为以下内容的请求POST这个页面

```
 <xml>
 <ToUserName><![CDATA[i am to user]]></ToUserName>
 <FromUserName><![CDATA[i am from user]]></FromUserName>
 <CreateTime>1348831860</CreateTime>
 <MsgType><![CDATA[text]]></MsgType>
 <Content><![CDATA[this is a test request]]></Content>
 <MsgId>1234567890123456</MsgId>
 </xml>
```

``` $request_obj ```可以获取以下数组

```
array (size=6)
  'ToUserName' => string 'i am to user' (length=12)
  'FromUserName' => string 'i am from user' (length=14)
  'CreateTime' => string '1348831860' (length=10)
  'MsgType' => string 'text' (length=4)
  'Content' => string 'this is a test request' (length=22)
  'MsgId' => string '1234567890123456' (length=16)
```

数组的键名为XML的tag标签名,值为tag里的值

##发送被动消息

---

> http://mp.weixin.qq.com/wiki/14/89b871b5466b19b3efa4ada8e577d45e.html

* 使用 ``` get_response_msg($to_user, $from_user, $data, $msg_type = 'text', $create_time = null) ``` 能快速构造符合返回接口规范的XML字符串

不同类型的消息的公共部分通过特定参数进行统一构造

不同类型的消息的差异部分通过合理构造 ``` $data ``` 数组来构造

例如:

构造简单的文本消息

```
$ec_wechat = new EC_Wechat();

$data['Content'] = 'i am a test';

$response_str = $ec_wechat->get_response_msg('Nutto', 'Easecloud', $data);
```

``` $response_str ``` 将会获得

```
<xml>
    <ToUserName><![CDATA[Nutto]]></ToUserName>
    <FromUserName><![CDATA[Easecloud]]></FromUserName>
    <CreateTime>1446786975</CreateTime>
    <MsgType><![CDATA[text]]></MsgType>
    <Content><![CDATA[i am a test]]></Content>
</xml>
```

``` $data ``` 参数的结构要符合微信文档,键名为XML的tag名

``` $data ``` 设计成只需要对要输入值的tag传值

再举一个例子:

构造多图文消息

```
$ec_wechat = new EC_Wechat();

$article = array(
    'Title' => 'test article Title',
    'Description' => 'test article Description',
    'PicUrl' => 'https://ss0.bdstatic.com/5aV1bjqh_Q23odCf/static/superman/img/logo/bd_logo1_31bdc765.png',
    'Url' => 'https://www.baidu.com/',
);

$articles_data[] = $article;

$article['Title'] = 'test article Title2';
$article['Description'] = 'test article Description2';

$articles_data[] = $article;

$response_str = $ec_wechat->get_response_msg('Nutto', 'Easecloud', $articles_data, 'news')
```

``` $response_str ``` 将会获得

```
<xml>
    <ToUserName><![CDATA[Nutto]]></ToUserName>
    <FromUserName><![CDATA[Easecloud]]></FromUserName>
    <CreateTime>1446787548</CreateTime>
    <MsgType><![CDATA[news]]></MsgType>

    <ArticleCount>2</ArticleCount>
    <Articles>
        <item>
            <Title><![CDATA[test article Title]]></Title>
            <Description><![CDATA[test article Description]]></Description>
            <PicUrl><![CDATA[https://ss0.bdstatic.com/5aV1bjqh_Q23odCf/static/superman/img/logo/bd_logo1_31bdc765.png]]></PicUrl>
            <Url><![CDATA[https://www.baidu.com/]]></Url>
        </item>
        <item>
            <Title><![CDATA[test article Title2]]></Title>
            <Description><![CDATA[test article Description2]]></Description>
            <PicUrl><![CDATA[https://ss0.bdstatic.com/5aV1bjqh_Q23odCf/static/superman/img/logo/bd_logo1_31bdc765.png]]></PicUrl>
            <Url><![CDATA[https://www.baidu.com/]]></Url>
        </item>
    </Articles>
</xml>
```

直接将 ``` $response_str ``` 输出就能实现回复

```
echo $response_str;

//按需结束
exit;
```

##获取调用接口时返回的头部

---

* 调用 ``` get_last_header() ``` 能获取最近一次调用接口时返回的header信息的数组


##素材管理

---

###增加临时素材

> http://mp.weixin.qq.com/wiki/5/963fc70b80dc75483a271298a76a8d59.html

* ``` tmp_media_upload($type, $file_dir) ```

1. ``` $type ``` 素材类型

2. ``` $file_path ``` 上传的文件路径(注意不要出现中文路径,否则会出现错误)

###增加永久素材

> http://mp.weixin.qq.com/wiki/14/7e6c03263063f4813141c3e17dd4350a.html

* ``` media_upload($type, $file_path, $title = null, $introduction= null) ```

1. ``` $type ``` 素材类型

2. ``` $file_path ``` 上传的文件路径(注意不要出现中文路径,否则会出现错误)

3. ``` $title ``` 如果上传类型为视频的时候才需要填写

4. ``` $introduction ``` 如果上传类型为视频的时候才需要填写


例子:

上传一个音频
```
$ec_wechat = new EC_Wechat();

$path = '\tmp\a.mp3';

$response = $ec_wechat->media_upload('voice', $path);
```

``` $response ``` 可以获得

```
object(stdClass)[134]
  public 'media_id' => string 'media_id' (length=43)
```


###增加图文素材

可以通过 ``` add_news($articles) ``` 来增加图文素材

例子:

```
$ec_wechat = new EC_Wechat();

$article = array(
    "title" => 'test',
    "thumb_media_id" => 'image_media_id',
    "author" => 'Nutto',
    "digest" => 'just test',
    "show_cover_pic" => 1,
    "content" => 'i am a test article',
    "content_source_url" => 'http://www.baidu.com'
);

$articles[]  = $article;
$articles[]  = $article;

$response = $ec_wechat->add_news($articles);
```

``` $response ``` 会获得

```
object(stdClass)[131]
  public 'media_id' => string 'meida_id' (length=43)
```


###获取素材

获取临时素材,使用 ``` get_tmp_media($media_id) ```

获取永久素材,使用 ``` get_media($media_id) ```

两个接口的返回都是数据直接返回

例子:

获取一个永久素材
```
$response_data =  $ec_wechat->get_media('media_id');

// 通过指定content_type让内容直接呈现
$header = $ec_wechat->get_last_header();
header('Content-Type:'.$header['content_type']);

echo $response_data;
```


###删除永久素材

> http://mp.weixin.qq.com/wiki/5/e66f61c303db51a6c0f90f46b15af5f5.html

使用 ``` delete_media($media_id) ``` 删除永久素材


###修改永久图文素材

> http://mp.weixin.qq.com/wiki/4/19a59cba020d506e767360ca1be29450.html

使用 ``` update_news($media_id, $index, $article) ``` 修改永久图文素材

例子:

原本的图文

```
//php
$ec_wechat = new EC_Wechat();

$response =  json_decode($ec_wechat->get_media('yds-iFh02pkQGwFMCX0eGYvakDPEnjLLlhKT69WY-rM'));

var_dump($re);

// result
object(stdClass)[1864]
  public 'news_item' =>
    array (size=2)
      0 =>
        object(stdClass)[1862]
          public 'title' => string 'test' (length=4)
          public 'author' => string 'Nutto' (length=5)
          public 'digest' => string '' (length=0)
          public 'content' => string 'i am a test article' (length=19)
          public 'content_source_url' => string 'http://www.baidu.com' (length=20)
          public 'thumb_media_id' => string '1108501821221' (length=13)
          public 'show_cover_pic' => int 1
          public 'url' => string 'http://mp.weixin.qq.com/s?__biz=MzAxMzQ3NDU5Nw==&mid=400258867&idx=1&sn=41f230814ce9bc89fd85d54597bc0820#rd' (length=107)
      1 =>
        object(stdClass)[1865]
          public 'title' => string 'test' (length=4)
          public 'author' => string 'Nutto' (length=5)
          public 'digest' => string '' (length=0)
          public 'content' => string 'i am a test article' (length=19)
          public 'content_source_url' => string 'http://www.baidu.com' (length=20)
          public 'thumb_media_id' => string '1108501821221' (length=13)
          public 'show_cover_pic' => int 1
          public 'url' => string 'http://mp.weixin.qq.com/s?__biz=MzAxMzQ3NDU5Nw==&mid=400258867&idx=2&sn=838016c7b082d769edcf4e326b710bbb#rd' (length=107)
```

修改后

```
//php
$article = array(
    "title" => 'changed test',
    "thumb_media_id" => 'yds-iFh02pkQGwFMCX0eGUAnl-EAx2HAk4BsecHwfwg',
    "author" => 'Easecloud',
    "digest" => 'changed',
    "show_cover_pic" => 1,
    "content" => 'i am a changed test article',
    "content_source_url" => 'http://www.baidu.com'
);

$response = $ec_wechat->update_news('yds-iFh02pkQGwFMCX0eGYvakDPEnjLLlhKT69WY-rM', 0, $article);
```

图文变成

```
object(stdClass)[1864]
  public 'news_item' =>
    array (size=2)
      0 =>
        object(stdClass)[1862]
          public 'title' => string 'changed test' (length=12)
          public 'author' => string 'Easecloud' (length=9)
          public 'digest' => string '' (length=0)
          public 'content' => string 'i am a changed test article' (length=27)
          public 'content_source_url' => string 'http://www.baidu.com' (length=20)
          public 'thumb_media_id' => string '1108501821221' (length=13)
          public 'show_cover_pic' => int 1
          public 'url' => string 'http://mp.weixin.qq.com/s?__biz=MzAxMzQ3NDU5Nw==&mid=400258867&idx=1&sn=41f230814ce9bc89fd85d54597bc0820#rd' (length=107)
      1 =>
        object(stdClass)[1865]
          public 'title' => string 'test' (length=4)
          public 'author' => string 'Nutto' (length=5)
          public 'digest' => string '' (length=0)
          public 'content' => string 'i am a test article' (length=19)
          public 'content_source_url' => string 'http://www.baidu.com' (length=20)
          public 'thumb_media_id' => string '1108501821221' (length=13)
          public 'show_cover_pic' => int 1
          public 'url' => string 'http://mp.weixin.qq.com/s?__biz=MzAxMzQ3NDU5Nw==&mid=400258867&idx=2&sn=838016c7b082d769edcf4e326b710bbb#rd' (length=107)
```


###获取素材总数

使用 ``` get_material_count() ``` 获取素材总数


###获取素材列表

使用 ``` get_material_list($type, $offset, $count) ``` 获取素材列表



##用户分组管理

---

> http://mp.weixin.qq.com/wiki/0/56d992c605a97245eb7e617854b169fc.html


###创建用户分组

使用 ``` create_group($openid) ``` 创建用户分组


###查询所有分组

使用 ``` get_groups() ``` 查询所有分组


###查询用户所在分组

使用 ``` get_user_group($openid) ``` 查询用户所在分组


###修改分组名

使用 ``` update_group($group_id, $name) ``` 修改分组名


###删除分组

使用 ``` del_group($group_id) ``` 删除分组


###移动用户分组

使用 ``` move_user($openid, $to_groupid) ``` 移动用户分组


###批量移动用户分组

使用 ``` move_batch_users($openid_list, $to_groupid) ``` 批量移动用户分组

例子:

```
$ec_wechat = new EC_Wechat();

$openid_list = array('openid', 'openid');

$ec_wechat->move_batch_users($openid_list, 100);
```


##获取用户列表

---

> http://mp.weixin.qq.com/wiki/0/d0e07720fc711c02a3eab6ec33054804.html

使用 ``` get_user_list($next_openid = null) ``` 获取用户列表,不给参数获取所有的用户


##获取用户基本信息

---

> http://mp.weixin.qq.com/wiki/14/bb5031008f1494a59c6f71fa0f319c66.html

###获取用户基本信息

使用 ``` get_user_info($openid, $lang = 'zh_CN ') ``` 获取用户基本信息


###批量获取用户基本信息

使用 ``` get_batch_users_info($user_list) ``` 批量获取用户基本信息

例子:

```
$user = array(
    'openid'    => 'oJfmdsyxnpXQJiKCkpnJ4fIKHLrs',
    'lang'      => 'zh-CN',
);

$user_list[] = $user;
$user_list[] = $user;

$response = $ec_wechat->get_batch_users_info($user_list);
```

``` $response ``` 可以获得:

```
object(stdClass)[1864]
  public 'user_info_list' =>
    array (size=2)
      0 =>
        object(stdClass)[1862]
          public 'subscribe' => int 1
          public 'openid' => string 'oJfmdsyxnpXQJiKCkpnJ4fIKHLrs' (length=28)
          public 'nickname' => string 'Nutto.Pan' (length=9)
          public 'sex' => int 1
          public 'language' => string 'zh_CN' (length=5)
          public 'city' => string 'Foshan' (length=6)
          public 'province' => string 'Guangdong' (length=9)
          public 'country' => string 'China' (length=5)
          public 'headimgurl' => string 'http://wx.qlogo.cn/mmopen/22kg5V61lttaUCB9RRpBeUbD4sUibSFrtoTibNAfBibA6WDyfA69O9estOOgmCR4DIGUJQ2F1TfAl3c92njg3Yzc1vWKjhILl9R/0' (length=127)
          public 'subscribe_time' => int 1429079785
          public 'remark' => string '' (length=0)
          public 'groupid' => int 0
      1 =>
        object(stdClass)[1865]
          public 'subscribe' => int 1
          public 'openid' => string 'oJfmdsyxnpXQJiKCkpnJ4fIKHLrs' (length=28)
          public 'nickname' => string 'Nutto.Pan' (length=9)
          public 'sex' => int 1
          public 'language' => string 'zh_CN' (length=5)
          public 'city' => string 'Foshan' (length=6)
          public 'province' => string 'Guangdong' (length=9)
          public 'country' => string 'China' (length=5)
          public 'headimgurl' => string 'http://wx.qlogo.cn/mmopen/22kg5V61lttaUCB9RRpBeUbD4sUibSFrtoTibNAfBibA6WDyfA69O9estOOgmCR4DIGUJQ2F1TfAl3c92njg3Yzc1vWKjhILl9R/0' (length=127)
          public 'subscribe_time' => int 1429079785
          public 'remark' => string '' (length=0)
          public 'groupid' => int 0
```


##设置备注名

---

> http://mp.weixin.qq.com/wiki/1/4a566d20d67def0b3c1afc55121d2419.html

使用 ``` set_user_remark($openid, $remark) ``` 设置备注名


##群发接口

> http://mp.weixin.qq.com/wiki/15/5380a4e6f02f2ffdc7981a8ed7a40753.html

---

###上传图文消息内的图片获取URL

使用 ``` gs_upload_img($file_path) ``` 上传图文消息内的图片获取URL

###上传图文消息素材

可以通过 ``` gs_upload_news($articles) ``` 上传图文消息素材

例子:

```
$ec_wechat = new EC_Wechat();

$article = array(
    "title" => 'test',
    "thumb_media_id" => 'image_media_id',
    "author" => 'Nutto',
    "digest" => 'just test',
    "show_cover_pic" => 1,
    "content" => 'i am a test article',
    "content_source_url" => 'http://www.baidu.com'
);

$articles[]  = $article;
$articles[]  = $article;

$response = $ec_wechat->gs_upload_news($articles);
```

``` $response ``` 会获得

```
object(stdClass)[131]
  public 'type' => string 'news' (length=4)
  public 'media_id' => string 'cNh_wZsQfAzjcy_ZK-YUXdQMxF7fQ8fT7bSftlgxGxqKRohUbj8q4G3238hBMyft' (length=64)
  public 'created_at' => int 1446884820
```

###预览接口

可以通过 ``` gs_preview($user, $type, $mix_content, $use_name = false, $card_info = array()) ``` 预览消息

``` $mix_content ``` 会根据 ``` $type ``` 的不同而表现出不同的意义
``` $use_name ``` 决定 ``` $user ``` 参数代表的意义  true:为openId    false:为微信号
如果要预览卡券信息要填写 ``` $card_info ``` ,格式应该像下面一样的数组

```
$card_info = array(
"code"=> '',
"openid"=> '',
"timestamp"=> '',
"signature"=> '',
)
```

例子:

预览一个图文

```
$response = $ec_wechat->gs_preview('oJfmdsyxnpXQJiKCkpnJ4fIKHLrs', 'mpnews', 'cNh_wZsQfAzjcy_ZK-YUXdQMxF7fQ8fT7bSftlgxGxqKRohUbj8q4G3238hBMyft');
```

预览一个图文

```
$response = $ec_wechat->gs_preview('oJfmdsyxnpXQJiKCkpnJ4fIKHLrs', 'voice', 'Z74wZErTH4tcvRoGDjIIMfbtIMkbKMEJ5l67DmUSo06bJNemgmRJIFbPV-vVfRVS');
```

###删除群发

可以通过 ``` gs_del_msg($msg_id) ``` 删除群发


###上传视频素材

要注意此处 ``` $media_id ``` 需通过基础支持中的上传下载多媒体文件来得到

可以通过 ``` gs_upload_video($media_id, $title, $description) ``` 上传视频素材

###查询群发消息发送状态

可以通过 ``` gs_get_status($msg_id) ``` 查询群发消息发送状态


###根据分组进行群发/全部进行群发

``` $is_to_all ``` 决定是否直接发给全部粉丝，否则的话要指定 ``` $group_id ``` 来决定群发到哪个分组

可以通过 ``` gs_send_all($type, $mix_content, $is_to_all = true, $group_id = null) ``` 根据分组进行群发/全部进行群发


###根据OpenID列表群发

可以通过 ``` gs_send_msg($type, $mix_content, $user_list) ``` 根据OpenID列表群发


