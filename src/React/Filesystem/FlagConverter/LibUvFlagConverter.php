<?php

namespace React\Filesystem\FlagConverter;

use React\Filesystem\FilesystemInterface;

class LibUvFlagConverter implements FlagConverterInterface
{
    private $flags;
        
    public function __construct()
    {
        $this->flags = array(
            FilesystemInterface::FLAG_RDONLY => \UV::O_RDONLY,
            FilesystemInterface::FLAG_WRONLY => \UV::O_WRONLY,
            FilesystemInterface::FLAG_CREAT => \UV::O_CREAT,
            FilesystemInterface::FLAG_APPEND => \UV::O_APPEND
            );
    }
    
    public function convertFlags($flags)
    {
        $res = 0;
        
        foreach ($this->flags as $fsFlag => $uvFlag) {
            if (($flags & $fsFlag)) {
                $res |= $uvFlag;
            }
        }
        return $res;
    }
}
