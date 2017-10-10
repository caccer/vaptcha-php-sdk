# Vaptcha Sdk for PHP

### Step1.环境准备

- Vaptcha SDK PHP版本适用于  php5.2及以上版本，且需要开启`curl`。
- 要使用Vaptcha SDK，您需要一个Vaptcha账号、一个验证单元以及一对VID和Key。请在Vaptcha验证管理后台查看。

### Step2.SDK 获取和安装

- 使用命令从Github获取:

  ```shell
  git clone https://github.com/VaptchaTeam/vaptcha-php-sdk.git
  ```

  [github下载地址](https://github.com)手动下载获取。

- 将sdk文件引入程序中并初始化即可

  example

  ```php
  require_once dirname(__FILE__) . '/lib/vaptcha.class.php';
  ```

- 运行demo

  若在`127.0.0.1`下查看demo，须在验证管理中添加一个domain为`127.0.0.1`的对应验证单元，

  并在`/server/auth.php`中配置`$vid`与`$key`。

  进入sdk路径，在运行如下命令 ：

  ```shell
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

  example

  ```php
  $v->Validate($_POST['challenge'], $_POST['token'], $_POST['sceneId'])
  ```

###  Step4. 代码示例

文件名: auth.php

```Php
<?php
header('Content-type: application/json');
require_once dirname(dirname(__FILE__)).'/lib/vaptcha.class.php';

class Validate
{
    private $vaptcha;

    /**
     * 实例化vaptcha
     */
    public function __construct(){
        $vid = '59c4668157f5a21430878707';
        $key = 'd53ee616bc924485a6240f1db03590a7';
        $this->vaptcha = new Vaptcha($vid, $key);
    }

    /**
     * 获取流水号
     *
     * @return json
     */
    public function getVaptcha(){
        $challenge = $this->vaptcha->GetChallenge();
        return $challenge;
    }

    /**
     * 获取宕机模式
     * method type GET
     * 此处只需获取get请求的数据，让后调用宕机模式的接口将其返回即可。
     * @return json
     */
    public function getDowTime(){
        $data = $_GET['data']; // 客户端sdk以get方式发送数据
        return $this->vpatcha->DownTime();
    }

    /**
     * 提交表单进行二次验证
     *
     * @return void
     */
    public function login(){
        $validatePass = $this->vaptcha->Validate($_POST['challenge'],$_POST['token']);
        if ($validatePass) {
            // 验证通过接下来验证表单或者进行登录等其他操作
            //:TODO
            return json_encode(array(
                "status" => 200,
                "msg" => '登录成功'
            ));
        } else {
            // 验证失败返回错误信息
            return json_encode(array(
                "status" => 401,
                "msg" => '验证错误'
            ));
        }
    }
}

/**
 * 路由 为客户端提供接口
 * auth.php/getVaptcha => 获取 vid 和 challenge
 * auth.php/getDowTime => 宕机模式接口，此接口必须为get请求
 */
$route = substr($_SERVER['PATH_INFO'], 1);

if($route !== 'getVaptcha' && $route !== 'getDowTime' && $route !== 'login'){
    echo Response::notFound('not found '.$route);
    die();
}

$validate = new Validate();
echo $validate->$route();

/**
 * 客户端验证成功，由成功回调得到token以及challenge
 * 提交表单时将token以及challenge一并提交给服务端
 * 
 * 服务端二次验证
 * 处理表单数据时 ，通过前端提供的token，以及challenge进行二次验证
 * 
 * 处理流程
 * 1. 客户端 => 提交请求 => 服务端实例化sdk，并返回vid challenge => 客户端初始化vaptcha
 * 2. 开始验证 => 验证成功 => 返回 token challenge => 客户端携带 token challenge 提交表单数据
 * 3. 后端获得数据 => 调用sdk验证token 及 challenge => 
 *      true => 验证表单等后续操作
 *      false => 返回错误信息 
 */
```



