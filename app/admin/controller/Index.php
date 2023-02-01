<?php
namespace app\admin\controller;

use app\admin\BaseController;
use support\Request;

class Index extends BaseController
{
    public function index(Request $request)
    {
        return response('hello webman');
    }

    public function view(Request $request)
    {
        return view('index/view', ['name' => 'webman']);
    }

    public function json(Request $request)
    {
        return json(['code' => self::BIS_SUCCESS_CODE, 'message' => 'ok']);
    }

    public function file(Request $request)
    {
        $file = $request->file('upload');
        if ($file && $file->isValid()) {
            $file->move(public_path().'/files/myfile.'.$file->getUploadExtension());
            return json(['code' => self::BIS_SUCCESS_CODE, 'message' => 'upload success']);
        }
        return json(['code' => 1, 'message' => 'file not found']);
    }
    
}
