<?php
//======以下代码是从UCenter用户中心后台拷贝出来粘贴到下面即可===============
/**
 * 操作步骤如下
 * 1.进入UCenter用户中心后台
 * 2.找到左侧菜单[应用管理]
 * 3.点击[添加新应用]按钮
 * 4.进入新应用界面,[应用类型]选择[其他]
 * 5.[应用名称]这里可以取个好记的名字
 * 6.[应用的主URL]这里填写[http://域名|IP:PORT/index.php/UCenter/Api]
 * 7.[通信密钥]可以填写自己的密钥,不填写保存后会自动生成
 * 8.[是否开启同步登录/是否接受通知]选择[是]单选按钮
 * 9.保存成功后,返回[应用列表]后[通信情况]是红色的[通信失败]
 * 10.进入编辑,把UCenter后台生成的配置文件拷贝到UCenter模块的Conf目录中的config.php文件中保存即可(可参考我写的文件).
 * 11.再次保存后,返回应用列表会变成绿色的[通信成功]字样
 */
//参考以下是测试生成的代码
define('UC_CONNECT', 'mysql');
define('UC_DBHOST', '47.92.253.53');
define('UC_DBUSER', 'root');
define('UC_DBPW', 'Sw**19851021');
define('UC_DBNAME', 'bbs');
define('UC_DBCHARSET', 'utf8');
define('UC_DBTABLEPRE', '`bbs`.pre_ucenter_');
define('UC_DBCONNECT', '0');
define('UC_KEY', '2a5fEIUaK6OWERixlYbd/sgiHt0kCvJqj7i5zds');
define('UC_API', 'http://bbs.qlxjr.com/uc_server');
define('UC_CHARSET', 'utf-8');
define('UC_IP', '');
define('UC_APPID', '9');
define('UC_PPP', '20');