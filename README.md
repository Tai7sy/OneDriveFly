# OneDrive_SCF
OneDrive Index with QCloud SCF (https://cloud.tencent.com/product/scf)  
最新更新：(https://github.com/qkqpttgf/OneDrive_SCF)  

# 安装
安装时，在环境变量里什么都不用添加，获得token后，可以复制粘贴到config的refresh_token字段，也可以按128字节分开，添加到环境变量的t1-t7。  
安装好后，可以在环境变量添加以下key做设置：  
sitename       ：网站的名称，不添加会显示为‘请在环境变量添加sitename’  
admin          ：管理密码，不添加时不显示登录页面且无法登录  
public_path    ：使用API长链接访问时，显示网盘文件的路径，不设置时默认为根目录  
private_path   ：使用自定义域名访问时，显示网盘文件的路径，不设置时默认为根目录  
imgup_path     ：设置图床路径，不设置这个值时该目录内容会正常列文件出来，设置后只有上传界面  
passfile       ：自定义密码文件的名字，可以是'.password'，也可以是'aaaa.txt'等等；  
        　       密码是这个文件的内容，可以空格、可以中文；列目录时不会显示，只有知道密码才能查看或下载此文件。  
t1,t2,t3,t4,t5,t6,t7：把refresh_token按128字节切开来放在环境变量，方便更新版本  

# 更新记录：  
20190911，修复文件(夹)名字中有#会打不开的问题。  
20190910，管理操作全部转用ajax（请来个前端）。  
20190909，解决直接用自定义域名获取token时无限循环的bug；调整MSAPI函数，准备把管理操作做成xhr。  
20190908，小改上传进度显示，视频播放尝试一下DPlayer。  
20190907，会onprogress了，上传过程有进度了，不用等每小块传完才有进度。  
20190905，在检测到没有token后javascript直接跳微软登录授权，简化操作；在预览页面，把url框编码，复制到聊天框后不会断开可以直接点击。  
20190904，修改安装时微软回调uri为scfonedrive.github.io，简化安装操作。  
20190903，支持世纪互联版本。  
20190902，重新申请注册微软应用，更改安装时的URL，以同时支持商业版与个人版，更新到此版本需要重新获取token。  
20190901，同时上传多个文件。  
20190829，UTC时间换算成+8区时间，小尺寸图片预览时不扩大了。  
20190825，将上传大文件用的url存在onedrive临时文件，中断上传后可以获取进度继续上传，上传完后删除临时文件。  
20190824，大文件分片段顺序上传，每小片上传完后显示进度。  
20190823，临时百度学习了一天，用ajax跟xhr做了大文件上传（ajax跟xhr哪个要jquery？）  
20190819，解决imgup_path没做设置时根目录变成图床目录的问题。  
20190818，管理界面DIV小改动，加个遮罩层。  
20190817，日志开篇就打印，后面不打印了，去掉全局$event1，游客图床目录不去OD查文件。  
20190816，设置游客上传目录，可以上传<4M的文件，不设置这个值时该目录内容会正常列文件出来，设置后只有上传界面。游客上传的文件会被重命名为MD5加后缀，管理登录后上传的文件不会被重命名。  
20190814，新建文本文件及目录  
20190812，登录后在预览TXT时可以编辑保存（4M大小限制）。  
20190811，登录后小文件（<4M）上传，因为API网关传送给SCF的event字符串最长为6291456，上传时只能base64后上传，不然00会变20内容出错，亲自试过4.04M/4237481字节的文件上传没问题，再大（4.3M）API网关就拒绝工作。  
20190809，做好重命名、移动、加密目录、删除（来个前端？）  
20190803，将跳页cache到目录差不多了，加入admin登录，准备重命名等操作（来个前端啊）  
20190719，改/preview为?preview，更符合习惯。改密码输入框居中。  
20190718，加密密码可中文可空格；list_path可以设置中文路径了；文件数>200才读第2次获取nextlink；寻找密码文件时改用递归。  
20190709，去掉scfname的设置，直接从context里读，再次简化安装过程。  
20190629，新增加密功能：没有密码的话不能直接去下级目录，也不能下载文件。  
          在config新增sitename，方便改网站名称；  
          在title中带上当前文件名；  
          支持自定义域名跟API触发同时工作，方便传播（路径容易错乱的问题已经解决了）。  

# Demo
正式：  
[https://service-pgxgvop2-1258064400.ap-hongkong.apigateway.myqcloud.com/release/abcdef/](https://service-pgxgvop2-1258064400.ap-hongkong.apigateway.myqcloud.com/release/abcdef/)  
可能正在编辑，甚至会有ERROR：  
[https://service-pgxgvop2-1258064400.ap-hongkong.apigateway.myqcloud.com/test/abcdef/](https://service-pgxgvop2-1258064400.ap-hongkong.apigateway.myqcloud.com/test/abcdef/)
