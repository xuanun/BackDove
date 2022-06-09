<?php


namespace App\Http\Controllers;


use Illuminate\Http\Request;

class TestController extends Controller
{
    public function test(Request $request)
    {
        $a = $request->path();
        return response()->json(['code'=>2200,'msg'=>env('VERIFY_TOKEN'),  'data'=>[$a]]);
    }
}
