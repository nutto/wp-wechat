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


##交流与建议

Nutto:<a href=mailto:nutto.pan@gmail.com>nutto.pan@gmail.com</a>

##许可

遵循[GPL3](https://www.gnu.org/licenses/gpl-3.0.txt)

