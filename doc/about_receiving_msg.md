#接收消息

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

``` $request_arr ```可以获取以下数组

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