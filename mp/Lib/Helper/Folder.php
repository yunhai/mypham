<?php

namespace Mp\Lib\Helper;
use Mp\App;

class Folder {

    public function copy($src, $dest, $mode = 0755, $recursive = true) {
        // If source is not a directory stop processing
        if (!is_dir($src)) {
            return false;
        }

        // If the destination directory does not exist create it
        if (!is_dir($dest)) {
            if (!$this->make($dest, $mode, $recursive)) {
                // If the destination directory could not be created stop processing
                return false;
            }
        }

        // Open the source directory to read in files
        $i = new \DirectoryIterator($src);
        foreach ($i as $f) {
            if ($f->isFile()) {
                copy($f->getRealPath(), $dest . "/" . $f->getFilename());
            } elseif (!$f->isDot() && $f->isDir()) {
                $this->copy($f->getRealPath(), "$dest/$f");
            }
        }

        return true;
    }

    public function ls($target = '') {
        if (is_dir($target)) {
            return scandir($target);
        }
        return [];
    }

    public function isDir($target = '') {
        return is_dir($target);
    }

    public function make($target = '', $mode = 0755, $recursive = true) {
        if (empty($target)) {
            return false;
        }

        return mkdir($target,  $mode, $recursive);
    }
}