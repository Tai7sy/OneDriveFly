# OneDrive_SCF
OneDrive Index with QCloud SCF (https://cloud.tencent.com/product/scf)  
只能在腾讯无服务器云函数SCF使用。  
API网关跟SCF分开收费，每小时不足0.01元就不产生帐单。  
所以，如果自用的话，**可能可以一直免费用下去**。  
用户比较多的话，或被人DDCC，就产生费用了。  
充值一块钱先用着吧，被人DDCC了也就1块，扣完结束。  

最新更新：(https://github.com/juan-525/OneDrive_SCF)  
QQ群：943919989  

# Demo
目前游客可以上传大文件：  
[https://service-pgxgvop2-1258064400.ap-hongkong.apigateway.myqcloud.com/release/abcdef/](https://service-pgxgvop2-1258064400.ap-hongkong.apigateway.myqcloud.com/release/abcdef/)  
目前显示英文，可能正在编辑，甚至会有ERROR：  
[https://service-pgxgvop2-1258064400.ap-hongkong.apigateway.myqcloud.com/test/abcdef/](https://service-pgxgvop2-1258064400.ap-hongkong.apigateway.myqcloud.com/test/abcdef/)  
安装过程视频：  
[https://service-pgxgvop2-1258064400.ap-hongkong.apigateway.myqcloud.com/test/abcdef/%E6%97%A0%E6%9C%8D%E5%8A%A1%E5%99%A8%E5%87%BD%E6%95%B0SCF%E6%90%AD%E5%BB%BAOneDrive.mp4?preview](https://service-pgxgvop2-1258064400.ap-hongkong.apigateway.myqcloud.com/test/abcdef/%E6%97%A0%E6%9C%8D%E5%8A%A1%E5%99%A8%E5%87%BD%E6%95%B0SCF%E6%90%AD%E5%BB%BAOneDrive.mp4?preview)  

# 更新记录：
20200226，腾讯API网关采用新域名，新创建的url，本程序无法从中获取所在区域的代码，故本版本先写死Region值为"ap-shanghai"，选择其他地区请替换scfapi.php中所有的"ap-shanghai"为你的云函数所在地区。[官方地域列表](https://cloud.tencent.com/document/api/583/17238#.E5.9C.B0.E5.9F.9F.E5.88.97.E8.A1.A8)<br>
20191221，将javascript中上传上限改100G。从preview点设置，再点返回时可以返回preview而不是下载了。  
20191123，流量要收费了，游客上传也就不经过SCF了，可以上传大文件。  
20191122，感谢 Deomntisa 小可爱，学会z-index。  
20191121，将登录移左上角，管理菜单横向展开；对于国际版，安装时可以使用自己申请的应用ID跟机密。  
20191117，多域名机制还是不对，修改。  
20191116，SCF要国际版，提倡中英双文，安装过程加入设置30s运行时间  
20191113，原domain_path格式不好在API中提交，修改机制;分文件显示图标;  
20191112，SCFAPI改POST方式，将main里面一些代码拿出来放function，将管理操作的DIV做个css  
20191108，SCFAPI加入namespace，抛弃config与oauth，直接用SERVER（野路子，不要学）  
20191104，世纪互联版本写入环境变量，管理登录后显示可以更新  
20191103，加入SCF的API，安装过程更自动化，程序可以一键从github更新自己，可以不登录腾讯控制台改变环境变量。  
20191026，调整javascript，排序之类只在目录时显示，增加bat,mov格式预览，调整登录登出。  
20191018，开分支，游客可以上传大文件，最终重命名为md5（由游客浏览器算出，可以被构造），此功能不考虑放主支。  
20191012，时间跟大小的排序可以正反多次点击。修复图床无法计算出md5文件名的bug。  
20191009，在header中Set-Cookie，管理登录从javascript跳改302跳，目录密码不用javascript设置。  
20190930，可以隐藏管理的登录页面了（请自己记住设置的值）。  
20190920，在文件列表点击“文件”、“修改时间”、“大小”几个字，可以从小到大排序。  
20190917，新增多个域名对应多个目录的设置（比private_path优先），新增显示缩略图按钮（整体结构不变，我自己看得都丑），代码缩进重新弄  
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
20190816，设置游客上传目录，可以上传小于4M的文件，不设置这个值时该目录内容会正常列文件出来，设置后只有上传界面。游客上传的文件会被重命名为MD5加后缀，管理登录后上传的文件不会被重命名。  
20190814，新建文本文件及目录  
20190812，登录后在预览TXT时可以编辑保存（4M大小限制）。  
20190811，登录后小文件（小于4M）上传，因为API网关传送给SCF的event字符串最长为6291456，上传时只能base64后上传，不然00会变20内容出错，亲自试过4.04M/4237481字节的文件上传没问题，再大（4.3M）API网关就拒绝工作。  
20190809，做好重命名、移动、加密目录、删除（来个前端？）  
20190803，将跳页cache到目录差不多了，加入admin登录，准备重命名等操作（来个前端啊）  
20190719，改/preview为?preview，更符合习惯。改密码输入框居中。  
20190718，加密密码可中文可空格；list_path可以设置中文路径了；文件数>200才读第2次获取nextlink；寻找密码文件时改用递归。  
20190709，去掉scfname的设置，直接从context里读，再次简化安装过程。  
20190629，新增加密功能：没有密码的话不能直接去下级目录，也不能下载文件。  
          在config新增sitename，方便改网站名称；  
          在title中带上当前文件名；  
          支持自定义域名跟API触发同时工作，方便传播（路径容易错乱的问题已经解决了）。  

# 安装
安装前，在环境变量里添加SecretId与SecretKey（在 https://console.cloud.tencent.com/cam/capi 这里生成），  
获得token后，程序会自动按128字节分开，添加到环境变量的t1-t7(个人帐户只到t4)，  

必填环境变量：  
SecretId       ：腾讯云API 的 SecretId。  
SecretKey      ：腾讯云API 的 SecretKey。  

安装时程序自动填写：  
Region         ：目前已写死为"ap-shanghai"，选择其他地区请替换scfapi.php中所有的"ap-shanghai"为你的云函数所在地区。[官方地域列表](https://cloud.tencent.com/document/api/583/17238#.E5.9C.B0.E5.9F.9F.E5.88.97.E8.A1.A8)<br>
Onedrive_ver   ：Onedrive版本  
language       ：程序显示的语言  
t1,t2,t3,t4,t5,t6,t7：把refresh_token按128字节切开来放在环境变量，方便更新版本。  

有选择地添加以下某些环境变量来做设置：  
sitename       ：网站的名称，不添加会显示为‘请在环境变量添加sitename’。  
admin          ：管理密码，不添加时不显示登录页面且无法登录。  
adminloginpage ：管理登录的页面不再是'?admin'，而是此设置的值。如果设置，登录按钮及页面隐藏。  
public_path    ：使用API长链接访问时，显示网盘文件的路径，不设置时默认为根目录；  
           　　　不能是private_path的上级（public看到的不能比private多，要么看到的就不一样）。  
private_path   ：使用自定义域名访问时，显示网盘文件的路径，不设置时默认为根目录。  
domain_path    ：格式为a1.com:/dir/path1|b1.com:/path2，比private_path优先。  
imgup_path     ：设置图床路径，不设置这个值时该目录内容会正常列文件出来，设置后只有上传界面，不显示其中文件（登录后显示）。  
passfile       ：自定义密码文件的名字，可以是'pppppp'，也可以是'aaaa.txt'等等；  
        　       密码是这个文件的内容，可以空格、可以中文；列目录时不会显示，只有知道密码才能查看或下载此文件。  
