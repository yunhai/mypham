<?php
namespace Mp\Lib\Helper;

use Mp\App;

class Common
{
    public function sendEmail($mailId, $variable = [], $mailInfo = [], $config = 'smtp')
    {
        $mailer = new \Mp\Lib\Helper\Mailer();

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
