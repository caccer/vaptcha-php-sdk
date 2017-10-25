# Vaptcha Sdk for PHP

### Step1.环境准备

- Vaptcha SDK PHP版本适用于  php5.3及以上版本，且需要开启`curl`。
- 要使用Vaptcha SDK，您需要一个Vaptcha账号、一个验证单元以及一对VID和Key。请在Vaptcha验证管理后台查看。

### Step2.SDK 获取和安装

- 使用命令从Github获取:

  ```shell
  git clone https://github.com/vaptcha/vaptcha-php-sdk.git
  ```

  [github下载地址](https://github.com/vaptcha/vaptcha-php-sdk)手动下载获取。

- 推荐使用composer

  > composer 是php的包管理工具， 通过composer.json里的配置管理依赖的包，同时可以在使用类时自动加载对应的包, 在你的composer.json中添加如下依赖

  执行

  ```shell
  composer require Vaptcha/vaptcha-sdk;
  ```

  使用 Composer 的 autoload 引入

  ```php
  require_once('vendor/autoload.php');

  use Vaptcha\Vaptcha;

  $vaptcha = new Vaptcha($vid, $key);
  ```

  ​

- 运行demo

  若在`127.0.0.1`下查看demo，须在验证管理中添加一个domain为`127.0.0.1`的对应验证单元，

  并在`/server/auth.php`中配置`$vid`与`$key`。

  进入sdk路径，在运行如下命令 ：

  ```shell
  composer require
  php -S 127.0.0.1:8080
  ```

  打开[http://127.0.0.1:8080/demo](http://127.0.0.1:8080/demo)即可访问

### Step3.SDK接口

使用接口前需先实例化`Vaptcha`

```php
$v = new Vaptcha($vid, $key); // 实例化sdk，$vid 和 $key 为验证单元中的VID和Key
```

SDK提供以下三个接口：

- 获取流水号接口 `GetChallenge()`

  example:

  ```php
  return $v->GetVaptcha(); //返回json字符串
  ```

- 宕机模式接口 `DownTime($data)`

  example:

  ```php
  $data = $_GET['data'];
  return $v->DownTime($data);
  ```

- 二次验证接口 `Validate($challenge, $token[, $sceneId])`

  参数说明: 

  `$challenge`： 必填，客户端验证通过后返回的流水号

  `$token`： 必填， 客户端验证通过后返回的令牌

  `$sceneId`： 选填，默认为所有场景(此参数为后台配置的验证单元场景)

  example:

  ```php
  $v->Validate($_POST['challenge'], $_POST['token'], $_POST['sceneId'])
  ```
