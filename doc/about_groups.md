#用户分组管理

---

> http://mp.weixin.qq.com/wiki/0/56d992c605a97245eb7e617854b169fc.html


##创建用户分组

使用 ``` create_group($openid) ``` 创建用户分组


##查询所有分组

使用 ``` get_groups() ``` 查询所有分组


##查询用户所在分组

使用 ``` get_user_group($openid) ``` 查询用户所在分组


##修改分组名

使用 ``` update_group($group_id, $name) ``` 修改分组名


##删除分组

使用 ``` del_group($group_id) ``` 删除分组


##移动用户分组

使用 ``` move_user($openid, $to_groupid) ``` 移动用户分组


##批量移动用户分组

使用 ``` move_batch_users($openid_list, $to_groupid) ``` 批量移动用户分组

例子:

```
$ec_wechat = new EC_Wechat();

$openid_list = array('openid', 'openid');

$ec_wechat->move_batch_users($openid_list, 100);
```