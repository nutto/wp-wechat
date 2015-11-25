#用户分组管理


> http://mp.weixin.qq.com/wiki/0/56d992c605a97245eb7e617854b169fc.html


##创建用户分组

使用 ``` createGroup($openid) ``` 创建用户分组


##查询所有分组

使用 ``` getGroups() ``` 查询所有分组


##查询用户所在分组

使用 ``` getUserGroup($openid) ``` 查询用户所在分组


##修改分组名

使用 ``` updateGroup($group_id, $name) ``` 修改分组名


##删除分组

使用 ``` delGroup($group_id) ``` 删除分组


##移动用户分组

使用 ``` moveUser($openid, $to_groupid) ``` 移动用户分组


##批量移动用户分组

使用 ``` moveBatchUsers($openid_list, $to_groupid) ``` 批量移动用户分组

例子:

```
$ec_wechat = new EC_Wechat();

$openid_list = array('openid', 'openid');

$ec_wechat->moveBatchUsers($openid_list, 100);
```