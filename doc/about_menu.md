#自定义菜单管理

##自定义菜单创建接口

> http://mp.weixin.qq.com/wiki/6/95cade7d98b6c1e1040cde5d9a2f9c26.html

使用 ```createMenu($menu)``` 可以创建自定义菜单

```$menu``` 为菜单项目的数组,下面举例,详情参见手册

```
$menu = [
            {	
              "type" => "click",
              "name" => "今日歌曲",
              "key" => "V1001_TODAY_MUSIC"
            },
            {
            "name" => "菜单",
            "sub_button" => [
                {	
                   "type" => "view",
                   "name" => "搜索",
                   "url" => "http => //www.soso.com/"
                },
                {
                   "type" => "view",
                   "name" => "视频",
                   "url" => "http => //v.qq.com/"
                },
                {
                   "type" => "click",
                   "name" => "赞一下我们",
                   "key" => "V1001_GOOD"
                }]
            }
        ];

createMenu($menu);
```

上面的代码就可以获得一个如下的菜单

```
* 今日歌曲
* 菜单
    + 搜索
    + 视频
    + 赞一下我们
```

##自定义菜单查询接口

> http://mp.weixin.qq.com/wiki/2/07112acf4bb9a19d50c8ae08515a2a6a.html

使用 ```getMenu()``` 可以查询菜单


##自定义菜单删除接口

> http://mp.weixin.qq.com/wiki/11/51aa2be3cc267a4947216a44b2e25187.html

使用 ```delMenu()``` 可以删除菜单


##获取自定义菜单配置接口

> http://mp.weixin.qq.com/wiki/6/51671aa8efcd21493b8a8f505c288706.html

使用 ```getCurrentMenu()``` 可以获取自定义菜单配置接口