# OneDrive_SCF
OneDrive Index with QCloud SCF (https://cloud.tencent.com/product/scf)

分支跟master对比：  
20190809，做好重命名、移动、加密目录（来个前端？）  
20190803，将跳页cache到目录差不多了，加入admin登录，准备重命名等操作（来个前端啊）  
20190719，改/preview为?preview，更符合习惯。改密码输入框居中。  
20190718，加密密码可中文可空格；list_path可以设置中文路径了；文件数>200才读第2次获取nextlink；寻找密码文件时改用递归。  
20190709，去掉scfname的设置，直接从context里读，再次简化安装过程。  
20190629,新增加密功能：没有密码的话不能直接去下级目录，也不能下载文件。  
在config新增sitename，方便改网站名称；  
在title中带上当前文件名；  
支持自定义域名跟API触发同时工作，方便传播（路径容易错乱的问题已经解决了）。  

安装时，在环境变量里什么都不用添加，获得token后，可以复制粘贴到config的refresh_token字段，也可以按128字节分开，添加到环境变量的t1-t7。  

//在环境变量添加：  
/*  
sitename：       网站的名称，不添加会显示为‘请在环境变量添加sitename’  
public_path：    使用API长链接访问时，网盘里公开的路径，不设置时默认为'/'，可以多级带中文  
private_path：   使用私人域名访问时，网盘里的路径，不设置时默认为'/'，可以多级带中文  
passfile：       自定义密码文件的名字，可以是'.password'，也可以是'aaaa.txt'等等；  
        　       密码是这个文件的内容，可以空格、可以中文；列目录时不会显示，只有知道密码才能查看或下载此文件。  
t1,t2,t3,t4,t5,t6,t7：把refresh_token按128字节切开来放在环境变量，不想再出现ctrl+c、ctrl+v把token也贴到github的事了  
\*/

# Demo

正式：[https://service-pgxgvop2-1258064400.ap-hongkong.apigateway.myqcloud.com/release/abcdef/](https://service-pgxgvop2-1258064400.ap-hongkong.apigateway.myqcloud.com/release/abcdef/)

可能在编辑：[https://service-pgxgvop2-1258064400.ap-hongkong.apigateway.myqcloud.com/test/abcdef/](https://service-pgxgvop2-1258064400.ap-hongkong.apigateway.myqcloud.com/test/abcdef/)
