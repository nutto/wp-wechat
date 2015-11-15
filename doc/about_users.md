#获取用户列表


> http://mp.weixin.qq.com/wiki/0/d0e07720fc711c02a3eab6ec33054804.html

使用 ``` get_user_list($next_openid = null) ``` 获取用户列表,不给参数获取所有的用户


#获取用户基本信息

---

> http://mp.weixin.qq.com/wiki/14/bb5031008f1494a59c6f71fa0f319c66.html

##获取用户基本信息

使用 ``` get_user_info($openid, $lang = 'zh_CN ') ``` 获取用户基本信息


##批量获取用户基本信息

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


#设置备注名

---

> http://mp.weixin.qq.com/wiki/1/4a566d20d67def0b3c1afc55121d2419.html

使用 ``` set_user_remark($openid, $remark) ``` 设置备注名