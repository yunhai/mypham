<?php
namespace Mp\Lib\Helper;

use Mp\App;

class Common
{
    public function sendEmail($mailId, $variable = [], $mailInfo = [], $delay = 0, $config = 'default')
    {
        $mailer = new \Mp\Lib\Helper\Mailer();

        if ($delay) {
            return $mailer->delay($mailId, $variable, $mailInfo, $delay);
        }

        $deliver = $mailer->config($config);

        return $mailer->send($deliver, $mailId, $variable, $mailInfo);
    }

    public function subcribe($info = [])
    {
        $data = [
            'email' => $info['email'],
            'fullname' => $info['fullname'],
        ];

        $service = new \Mp\Service\MailRecipient();
        return $service->subcribe($data);
    }
}
