#检测消息可靠性和响应服务器验证请求


> http://mp.weixin.qq.com/wiki/17/2d4265491f12608cd170a95559800f2d.html

* ```valid()  ``` 会通过请求中的 ``` signature ```, ``` timestamp ```, ``` nonce ``` 字段验证消息的可靠性,返回 ``` true ```或者 ``` false ```,如果请求中带有 ``` echostr ``` 会认为是服务器验证的请求,验证通过后会直接输出 ``` echostr ``` 的值作为响应

**应该在微信的callback页面的最前面调用 ``` valid() ``` 函数并完成必要的验证的逻辑操作**