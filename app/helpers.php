<?php


    function jsonResponse($code, $data)
    {
        return response()->json([
            'code' => $code,
            'data' => $data,
        ]);
    }

