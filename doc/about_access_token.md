#获取access token和确保access token的可用性

---

* ``` get_access_token() ``` 强制获取新的access token

* ``` ensure_access_token() ``` 检查当前的accesss token的可用性,当当前的access token失效的时候会自动获取新的access token

* 当 ``` EC_Wechat ``` 类被实例化的时候会确保access token的可用性

__[注意]:可用性是通过比较获取access token时返回的时间,时限和新旧帐号Hash值来确保,如果在一个access token的有效期内通过其他方法获取了新的access token将会导致程序错误__

