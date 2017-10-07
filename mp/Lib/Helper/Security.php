<?php

namespace Mp\Lib\Helper;

use Mp\App;

class Security {

    public function random($length = 8, $level = 0) {
        switch ($level) {
            case 5:
                $chars = "!#$%&()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[]^_`abcdefghijklmnopqrstuvwxyz{|}~";
                break;
            case 1:
                $chars = "abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ0123456789!@#$%^&*()_-=+;:,.?";
                break;
            default:
                $chars = "abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ0123456789";
                break;
        }

        return substr(str_shuffle($chars), 0, $length);
    }

    public function simpleUnique() {
        return dechex(time());
    }

    public function unique() {
        return sha1(uniqid(rand(), true));
    }

    public function hash($string = '', $cost = 11) {
        return password_hash($string, PASSWORD_BCRYPT, compact('cost'));
    }

    public function encrypt($message, $key, $encode = 1) {
        $method = 'aes-256-ctr';
        $nonceSize = openssl_cipher_iv_length($method);
        $nonce = openssl_random_pseudo_bytes($nonceSize);

        $ciphertext = openssl_encrypt($message, $method, $key, OPENSSL_RAW_DATA, $nonce);

        // Now let's pack the IV and the ciphertext together
        // Naively, we can just concatenate
        $ciphertext = $nonce.$ciphertext;
        if ($encode) {
            while ($encode--) {
                $ciphertext = base64_encode($ciphertext);
            }
        }

        return $ciphertext;
    }

    public function decrypt($message, $key, $encoded = 1) {
        if ($encoded) {
            while ($encoded--) {
                $message = base64_decode($message, true);
                if ($message === false) {
                    break;
                }
            }
        }

        $method = 'aes-256-ctr';
        $nonceSize = openssl_cipher_iv_length($method);
        $nonce = mb_substr($message, 0, $nonceSize, '8bit');
        $ciphertext = mb_substr($message, $nonceSize, null, '8bit');

        return openssl_decrypt($ciphertext, $method, $key, OPENSSL_RAW_DATA, $nonce);
    }
}