#素材管理

---

##增加临时素材

> http://mp.weixin.qq.com/wiki/5/963fc70b80dc75483a271298a76a8d59.html

* ``` tmp_media_upload($type, $file_dir) ```

1. ``` $type ``` 素材类型

2. ``` $file_path ``` 上传的文件路径(注意不要出现中文路径,否则会出现错误)

##增加永久素材

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


##增加图文素材

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


##获取素材

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


##删除永久素材

> http://mp.weixin.qq.com/wiki/5/e66f61c303db51a6c0f90f46b15af5f5.html

使用 ``` delete_media($media_id) ``` 删除永久素材


##修改永久图文素材

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


##获取素材总数

使用 ``` get_material_count() ``` 获取素材总数


##获取素材列表

使用 ``` get_material_list($type, $offset, $count) ``` 获取素材列表