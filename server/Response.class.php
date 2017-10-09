<?php
header('Content-type: application/json');

class Response
{
    public static function json($status, $message)
    {
        return json_encode(array(
            "status" => $status,
            "msg" => $message
        ));
    }
    
    public static function success($message)
    {
        return  self::json(200, $message);
    }

    public static function notFound($message)
    {
        return  self::json(404, $message);
    }
}
