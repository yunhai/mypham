<?php
namespace Mp\Lib\Helper;

use Mp\App;
use Mp\Model\Mail;

class Mailer
{
    private $config = [];

    public function __construct()
    {
        if (empty($this->__config)) {
            $helper = App::mp('config');

            $file = [
                $helper->appLocation() . 'Config' . DS . 'mailer'
            ];

            $this->config = App::mp('config')->load($file);
        }
    }

    public function config($config = 'default')
    {
        if (is_string($config)) {
            $config = $this->config['mailer'][$config];
        }

        $func = $config['deliver'];

        return $this->$func($config);
    }

    private function phpmailer($config = [])
    {
        extract($config);
        $mailer = new \PHPMailer();

        if ($transport == 'smtp') {
            $mailer->isSMTP(); // Set mailer to use SMTP
            $mailer->Host = $host; // Specify main and backup SMTP servers
            $mailer->SMTPAuth = true; // Enable SMTP authentication
            $mailer->Username = $username; // SMTP username
            $mailer->Password = $password; // SMTP password
            $mailer->SMTPSecure = $protocol; // Enable TLS encryption, `ssl` also accepted
            $mailer->Port = $port;
        }

        $mailer->CharSet = $charset;

        return $mailer;
    }

    public function delay($mailId = 0, $variable = [], $info = [], $delay = 15)
    {
        if ($mailId) {
            $info = $this->mailTemplate($mailId, $variable, $info);
            if ($info['status'] === 0) {
                return true;
            }
        }

        $info['delay'] = $delay;

        return $this->logInfo($info);
    }

    public function mailTemplate($mailId = 0, $variable = [], $info = [])
    {
        if ($mailId) {
            $service = new \Mp\Service\MailTemplate();
            $default = $service->code($mailId);

            $default = array_filter($default);

            if (!empty($default['status']) && !empty($default['content'])) {
                $default['content'] = App::mp('view')->renderString($default['content'], $variable);
            }
            
            $info = array_merge($default, $info);
        }

        return $info;
    }

    public function send($mailer = null, $mailId = 0, $variable = [], $info = [])
    {
        try {
            $info = $this->mailTemplate($mailId, $variable, $info);

            if (empty($info['status'])) {
                return true;
            }

            $this->makeSend($mailer, $info);
            $this->logInfo($info);
        } catch (exception $e) {
            //save to unsent mail.
            //abort('send mail error');
        }

        return true;
    }

    private function makeSend($mailer = null, $info = [])
    {
        $attr = [
            'from' => 'setFrom',
            'to' => 'addAddress',
            'replyTo' => 'addReplyTo',
            'cc' => 'addCC',
            'bcc' => 'addBCC',
        ];

        $mailer->SMTPDebug = 3;
        $mailer->SMTPAuth = true;

        foreach ($attr as $key => $f) {
            if (isset($info[$key])) {
                $params = explode(',', $info[$key]);

                call_user_func_array(array($mailer, $f), $params);
            }
        }

        $mailer->Subject = $info['title'];
        $mailer->Body = nl2br($info['content']);

        $mailer->isHTML(true);
        $mailer->send();

        if ($mailer->send()) {
            return true;
        }

        $this->logInfo($info);
        $this->logError($info, $mailer->ErrorInfo);

        return false;
    }

    private function logInfo($mail = [])
    {
        $service = new \Mp\Service\Mail();
        $service->save($mail);

        return true;
    }

    private function logError($mail, $error = '')
    {
    }
}
