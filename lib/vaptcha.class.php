<?php
require_once dirname(__FILE__).'/config.php';

class Vaptcha
{
    private $_vid;
    private $_key;
    private $_publicKey;
    private $_lastCheckDownTime = 0;
    private $_isDown = false;

    //宕机模式通过签证
    private static $_passedSignatures;

    public function __construct($vid, $key)
    {
        date_default_timezone_set("UTC");
        $this->_vid = $vid;
        $this->_key = $key;
    }

    /**
     * 获取流水号
     *
     * @param string $sceneId 场景id
     * @return void
     */
    public function GetChallenge($sceneId = "") 
    {
        $url = API_URL.GET_CHALLENGE_URL;
        $now = time() * 1000;
        $query = "id=$this->_vid&scene=$sceneId&time=$now";
        $signature = $this->HMACSHA1($this->_key, $query);
        if (!$this->_isDown)
        {
            $challenge = self::ReadContentFormGet("$url?$query&signature=$signature");
            if($challenge === REQUEST_UESD_UP)
            {
                $this->_lastCheckDownTime = $now;
                $this->_isDown = true;
                self::$_passedSignatures = array();
                return $this->GetDownTimeCaptcha();
            }
            if(!$challenge) {
                if($this->GetIsDwon()) {
                    $this->_lastCheckDownTime = $now;
                    $this->_isDown = true;
                    self::$_passedSignatures = array();
                }
                return $this->GetDownTimeCaptcha();
            } 
            return json_encode(array(
                "vid" =>  $this->_vid,
                "challenge" => $challenge
            ));
        }
        else
        {
        if($now - $this->_lastCheckDownTime > DOWNTIME_CHECK_TIME) 
            {
                $this->_lastCheckDownTime = $now;
                $challenge = self::ReadContentFormGet("$url?$query&signature=$signature");
                if($challenge && $challenge != REQUEST_UESD_UP)
                {
                    $this->_isDown = false;
                    self::$_passedSignatures = array();
                    return json_encode(array(
                        "vid" =>  $this->_vid,
                        "challenge" => $challenge
                    ));
                }
            }
            return $this->GetDowniTimeCaptcha();
        }
    }

    /**
     * 二次验证
     *
     * @param [string] $challenge 流水号
     * @param [sring] $token 验证信息
     * @param string $sceneId 场景ID 不填则为默认场景
     * @return void
     */
    public function Validate($challenge, $token, $sceneId = "")
    {
        if ($this->_isDown)
            return $this->DownTimeValidate($token);
        else
            return $this->NormalValidate($challenge, $token, $sceneId);
    }

    private function GetPublicKey()
    {
        return self::ReadContentFormGet(PUBLIC_KEY_PATH);
    }

    private function GetIsDwon()
    {
        return !!self::ReadContentFormGet(IS_DOWN_PATH) == 'true';
    }

    public function DownTime($data)
    {
        if(!$data)
            return json_encode(array("error" => "params error"));
        if(!$this->_publicKey)
            $this->_publicKey = $this->GetPublicKey();
        $datas = explode(',', $data);
        switch($datas[0])
        {
            case 'request': 
                return $this->GetDownTimeCaptcha();
            case 'getsignature':
                if(count($datas) < 2)
                    return json_encode(array("error" => "params error"));
                else
                {
                    $time = (int)$datas[1];
                    if((bool)$time)
                        return $this->GetSignature($time);
                    else 
                        return json_encode(array("error" => "params error"));
                }
            case 'check':
                if(count($datas) < 5)
                    return json_encode(array("error" => "params error"));
                else 
                {
                    $time1 = (int)$datas[1];
                    $time2 = (int)$datas[2];
                    $signature = $datas[3];
                    $captcha = $datas[4];
                    if((bool)$time1 && (bool)$time2)
                        return $this->DownTimeCheck($time1, $time2, $signature, $captcha);
                    return json_encode(array("error" => "parms error"));
                }
            default: 
                return json_encode(array("error" => "parms error"));
        }
    }

    private function GetSignature($time)
    {
        $now = time() * 1000;
        if (($now - $time) > REQUEST_ABATE_TIME)
            return null;
        $signature = md5($now.$this->_key);
        return json_encode(array(
            'time' => $now,
            'signature' => $signature
        ));
    }

    /**
     * 宕机模式验证
     *
     * @param [int] $time1
     * @param [int] $time2
     * @param [string] $signature
     * @param [string] $captcha
     * @return void
     */
    private function DownTimeCheck($time1, $time2, $signature, $captcha)
    {
        $now = time() * 1000;
        if ($now - $time1 > REQUEST_ABATE_TIME || 
            $signature != md5($time2.$this->_key) || 
            $now - $time2 < VALIDATE_WAIT_TIME)
            return json_encode(array("result" => "false"));
        $trueCaptcha = substr(md5($time1.$this->_key), 0, 3);
        if ($trueCaptcha == strtolower($captcha)) 
            return json_encode(array(
                "result" => "false",
                'token' => $now.md5($now.$this->_key.'vaptcha')
            ));
        else 
            return json_encode(array("result" => "false"));        
    }

    private function NormalValidate($challenge, $token, $sceneId)
    {
        if(!$token || !$challenge || $token != md5($this->_key.'vaptcha'.$challenge))
            return false;
        $url = API_URL.VALIDATE_URL;
        $now = time() * 1000;
        $query = "id=$this->_vid&scene=$sceneId&token=$token&time=$now";
        $signature = $this->HMACSHA1($this->_key, $query);
        $response = self::PostValidate($url, "$query&signature=$signature");
        return 'success' == $response;
    }

    private function DownTimeValidate($token)
    {
        $strs = explode(',', $token);
        if(count($strs) < 2) 
            return false;
        else 
        {
            $time = (int)$strs[0];
            $signature = $strs[1];
            $now = time() * 1000;
            if ($now - $time > VALIDATE_PASS_TIME)
                return false;
            else
            {
                $signatureTrue = md5($time.$this->_key.'vaptcha');
                if ($sigantureTrue)
                {
                    if (in_array($signature, self::$_passedSignatures))
                        return false;
                    else
                    {
                        array_push(self::$_passedSignatures, $signature);
                        $length = count(self::$_passedSignatures);
                        if ($length > MAX_LENGTH)
                            array_splice(self::$_passedSignatures, 0, $length - MAX_LENGTH + 1);
                        return true;
                    }
                }
                else 
                    return false;
            }
        }
    }

    private function GetDownTimeCaptcha()
    {
        $time = time() * 1000;
        $md5 = md5($time.$this->_key);
        $captcha = substr($md5, 0, 3);
        $verificationKey = substr($md5,30);
        $url = md5($captcha.$verificationKey.$this->_publicKey).PIC_POST_FIX;
        $url = DOWN_TIME_PATH.$url;
        return json_encode(array(
            "time" => $time,
            "url" => $url
        ));
    }

    private static function PostValidate($url, $data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);  
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HEADER, false);  
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('ContentType:application/x-www-form-urlencoded'));  
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);  
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5*1000);  
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }

    private static function ReadContentFormGet($url)
    {
        $ch = curl_init();  
        curl_setopt($ch, CURLOPT_URL, $url); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);  
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5*1000);  
        $return = curl_exec($ch);  
        curl_close($ch);
        return $return;
    }

    private function HMACSHA1($key, $text)
    {
        $result = hash_hmac('sha1', $text, $key, true);
        $result = str_replace(array('/', '+', '='), '', base64_encode($result));
        return $result;
    }
}