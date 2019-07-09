# QDrive
OneDrive Index with QCloud SCF (https://cloud.tencent.com/product/scf)

分支跟master对比：  
20190709，去掉scfname的设置，直接从context里读，再次简化安装过程。
//去掉这个base64，可自行在onedrive里面放一个favicon.ico文件。20190701，作为一个(假)网站，怎么能没有icon，但直接放个favicon.ico是下载不下来，显示不了的，所以要base64一下。  
20190629,新增加密功能：没有密码的话不能直接去下级目录，也不能下载文件。  
在config新增sitename，方便改网站名称；  
在title中带上当前文件名；  
支持自定义域名跟API触发同时工作，方便传播（路径容易错乱的问题已经解决了）。  

//环境变量添加：  
/*  
sitename：网站的名称，不添加会显示为‘请在环境变量添加sitename’  
public_path：使用API长链接访问时，网盘里公开的路径，不设置时默认为'/'  
private_path：使用私人域名访问时，网盘的路径（可以一样），不设置时默认为'/'  
passfile：自定义密码文件名，可以是'.password'，也可以是'aaaa.txt'等等，列目录时不会显示，只有知道密码才能下载此文件。  
pagesplitnum：设置每页显示数量。  
//t1,t2~t7：把refresh_token按128字节切开来放在环境变量，不想再出现ctrl+c、ctrl+v把token也贴到github的事了  
\*/ 

# Demo

[https://service-pgxgvop2-1258064400.ap-hongkong.apigateway.myqcloud.com/release/abcdef/](https://service-pgxgvop2-1258064400.ap-hongkong.apigateway.myqcloud.com/release/abcdef/)
