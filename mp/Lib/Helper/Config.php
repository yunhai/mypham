<?php
namespace Mp\Lib\Helper;

use Mp\App;
use Mp\Lib\Utility\Hash;
use Mp\Service\Setting;

class Config
{
    public $app = [];
    private $type = 'php';

    public function __construct()
    {
        $path = ROOT . 'config/';
        $files = [
            $path . 'db',
            $path . 'app'
        ];

        $this->app = self::load($files, false);
    }

    public function write($string = null, $value = '', $merge = true)
    {
        if ($merge) {
            $prev = $this->get($string);
            $value = Hash::merge($prev, $value);
        }

        $this->app = Hash::insert($prev, $string, $value);

        return true;
    }

    public function get($string = null)
    {
        if (is_null($string)) {
            return $this->app;
        }

        return Hash::get($this->app, $string);
    }

    public function check($string = '')
    {
        return Hash::check($this->app, $string);
    }

    public function option($tenant = '', $app = [], $locale = '')
    {
        $path = ROOT . 'config/';

        $files = [
            $path . $locale,
        ];
        $app = self::load($files);

        $model = new Setting();
        $setting = Hash::expand($model->all());

        $this->app = Hash::merge($this->app, $app, $setting);
    }

    public function appLocation()
    {
        $request = App::mp('request');

        if (empty($request->app)) {
            return '';
        }

        return TENANT_PATH . $request->tenant . DS . $request->app . DS;
    }

    public function load($list = [])
    {
        $result = [];
        foreach ($list as $file) {
            $file .= '.' . $this->type;
            if (file_exists($file)) {
                if ($this->type == 'yaml') {
                    $tmp = yaml_parse_file($file);
                } else {
                    $tmp = include($file);
                }

                $result = array_merge($result, $tmp);
            }
        }

        return $result;
    }
}
