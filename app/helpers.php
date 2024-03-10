<?php

if (!function_exists('jsonResponse')) {
    /**
     * Generate a JSON response.
     *
     * @param  int  $code
     * @param  mixed  $data
     * @return \Illuminate\Http\JsonResponse
     */
    function jsonResponse($code, $data)
    {
        return response()->json([
            'code' => $code,
            'data' => $data,
        ]);
    }
}
