#客服接口

> http://mp.weixin.qq.com/wiki/14/d9be34fe03412c92517da10a5980e7ee.html


##添加客服帐号


使用 ```csCreate($account, $nickname, $password)``` 可以添加客服帐号


##修改客服帐号

使用 ```csUpdate($account, $nickname, $password)``` 可以修改客服帐号


##删除客服帐号

使用 ```csDel($account, $nickname, $password)``` 可以删除客服帐号


##设置客服帐号的头像

使用 ```csSetAvatar($account, $file_path)``` 可以设置客服帐号的头像


##获取所有客服账号

使用 ```csGetList()``` 可以获取所有客服账号


##客服接口-发消息

使用 ```csSend($touser, $type, $mix_content)``` 可以发送客服消息