<?php
abstract class RequestService
{
    public function sendResponse($result, $message, $code = 200)
    {
        $response = [
            'message' => $message,
            'code' => $code,
            'data' => $result,
        ];
        return response()->json($response, $code);
    }
}