#WordPress 微信插件

__本插件旨在帮助用户快速构建,解析和发送微信接口中的消息数据,简化在WP中微信的开发难度__


##安装与使用

###安装

直接放入WP的Plugins文件夹,并启动插件即可.

###快速使用

在WP的options项中的微信设置页面填写好必要信息后,实例化 ``` WP_Wechat ``` 类,即可通过该类的一些方法实现大部分(持续补充)微信的接口功能.

```
$wx = new WP_Wechat();

// 获取用户列表
$user_list = $wx->get_user_list(); 
```

详细接口可以查看文档

##文档

[获取access token和确保access token的可用性](https://github.com/nutto/wp-wechat/blob/master/doc/about_access_token.md)
[检测消息可靠性和响应服务器验证请求](https://github.com/nutto/wp-wechat/blob/master/doc/about_valid.md)
[变量与一些工具函数](https://github.com/nutto/wp-wechat/blob/master/doc/about_variables_and_tools.md)
[接收消息](https://github.com/nutto/wp-wechat/blob/master/doc/about_receiving_msg.md)
[发送被动消息](https://github.com/nutto/wp-wechat/blob/master/doc/about_sending_msg.md)
[获取用户列表](https://github.com/nutto/wp-wechat/blob/master/doc/about_users.md)
[用户分组管理](https://github.com/nutto/wp-wechat/blob/master/doc/about_groups.md)
[素材管理](https://github.com/nutto/wp-wechat/blob/master/doc/about_materials.md)
[群发接口](https://github.com/nutto/wp-wechat/blob/master/doc/about_group_sending.md)

##交流与建议

Nutto:<a href=mailto:nutto.pan@gmail.com>nutto.pan@gmail.com</a>

##许可

遵循[GPL3](https://www.gnu.org/licenses/gpl-3.0.txt)

