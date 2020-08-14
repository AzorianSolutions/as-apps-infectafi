<?php

class Misc
{
    public static function removeTmpFile($filePath) {
        if(file_exists($filePath))
            unlink($filePath);
    }
}