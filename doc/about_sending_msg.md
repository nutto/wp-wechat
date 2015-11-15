#发送被动消息


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