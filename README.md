# OneDrive_SCF
OneDrive Index with QCloud SCF (https://cloud.tencent.com/product/scf)

# 安装
安装时，在环境变量里什么都不用添加，获得token后，可以复制粘贴到config的refresh_token字段，也可以按128字节分开，添加到环境变量的t1-t7。  
安装好后，可以在环境变量添加以下key做设置：  
sitename       ：网站的名称，不添加会显示为‘请在环境变量添加sitename’  
admin          ：管理密码，不添加时不显示登录页面  
public_path    ：使用API长链接访问时，网盘里公开的路径，不设置时默认为'/'  
private_path   ：使用私人域名访问时，网盘的路径（可以一样），不设置时默认为'/'  
imgup_path     ：设置图床路径，不设置这个值时该目录内容会正常列文件出来，设置后只有上传界面，不显示其中文件（登录后显示）  
passfile       ：自定义密码文件的名字，可以是'.password'，也可以是'aaaa.txt'等等；  
        　       密码是这个文件的内容，可以空格、可以中文；列目录时不会显示，只有知道密码才能查看或下载此文件。  
t1,t2,t3,t4,t5,t6,t7：把refresh_token按128字节切开来放在环境变量，不想再出现ctrl+c、ctrl+v把token也贴到github的事了  

# 更新记录：  
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
