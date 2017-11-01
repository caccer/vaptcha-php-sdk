<?php
require dirname(dirname(__FILE__)).'/vendor/autoload.php';
require_once dirname(__FILE__).'/Response.class.php';

use Vaptcha\Vaptcha;

class Validate
{
    private $vaptcha;

    /**
     * 实例化vaptcha
     */
    public function __construct(){
        $vid = '59f81632a485d5214ce59045';
        $key = 'fb119a233f5b4514b79a5752ee3b20ae';
        $this->vaptcha = new Vaptcha($vid, $key);
    }

    /**
     * 获取流水号
     *
     * @return json
     */
    public function getVaptcha(){
        $challenge = $this->vaptcha->getChallenge();
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
        return $this->vaptcha->downTime($data);
    }

    /**
     * 提交表单进行二次验证
     *
     * @return void
     */
    public function login(){
        $request = $_POST; // 获取表单数据 
        $validatePass = $this->vaptcha->validate($request['challenge'], $request['token']);
        if ($validatePass) {
            // 验证通过接下来验证表单或者进行登录等其他操作
            //:TODO
            return Response::success('登录成功');
        } else {
            // 验证失败返回错误信息
            return Response::json(401, '验证错误');
        }
    }
}

/**
 * 路由 为客户端提供接口
 * index.php/getVaptcha => 获取 vid 和 challenge
 * index.php/getDowTime => 宕机模式接口，此接口必须为get请求
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