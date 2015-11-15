#群发接口

> http://mp.weixin.qq.com/wiki/15/5380a4e6f02f2ffdc7981a8ed7a40753.html

---

##上传图文消息内的图片获取URL

使用 ``` gs_upload_img($file_path) ``` 上传图文消息内的图片获取URL

##上传图文消息素材

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

##预览接口

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

##删除群发

可以通过 ``` gs_del_msg($msg_id) ``` 删除群发


##上传视频素材

要注意此处 ``` $media_id ``` 需通过基础支持中的上传下载多媒体文件来得到

可以通过 ``` gs_upload_video($media_id, $title, $description) ``` 上传视频素材

##查询群发消息发送状态

可以通过 ``` gs_get_status($msg_id) ``` 查询群发消息发送状态


##根据分组进行群发/全部进行群发

``` $is_to_all ``` 决定是否直接发给全部粉丝，否则的话要指定 ``` $group_id ``` 来决定群发到哪个分组

可以通过 ``` gs_send_all($type, $mix_content, $is_to_all = true, $group_id = null) ``` 根据分组进行群发/全部进行群发


##根据OpenID列表群发

可以通过 ``` gs_send_msg($type, $mix_content, $user_list) ``` 根据OpenID列表群发