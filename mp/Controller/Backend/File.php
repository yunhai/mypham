<?php
// cors: https://gist.github.com/yashuarc/10080747
namespace Mp\Controller\Backend;

use Mp\App;
use Mp\Core\Controller\Backend;

class File extends Backend
{
    private $__service = null;
    public function __construct($model = 'file', $table = 'file', $alias = 'file', $template = '')
    {
        parent::__construct($model, $table, $alias, $template);
    }

    public function navigator()
    {
        $request = App::mp('request');

        switch ($request->query['action']) {
            case 'upload':
                    $this->upload();
                break;
        }
    }

    public function upload()
    {
        $request = App::mp('request');
        $response = App::mp('response');

        $service = App::load('file', 'service');

        $module = $request->name['target'];
        $single = empty($request->name['multiple']);

        $result = $service->upload($single, $module);

        if ($request->is('ajax')) {
            $response->type('js');

            $this->layout = false;
            if ($single) {
                $result = current($result);
                $result = current($result);
            }

            echo json_encode($result, JSON_UNESCAPED_UNICODE);
        }
    }
}
