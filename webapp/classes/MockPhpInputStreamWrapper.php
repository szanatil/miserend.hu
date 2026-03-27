<?php

/**
 * Custom stream wrapper to mock php://input for testing
 * This allows mocking the php://input stream in unit tests
 */
class MockPhpInputStreamWrapper {
    public static $content = '';
    private $position = 0;

    public function stream_open($path, $mode, $options, &$opened_path) {
        $this->position = 0;
        return true;
    }

    public function stream_read($count) {
        if ($this->position >= strlen(self::$content)) {
            return false;
        }
        $data = substr(self::$content, $this->position, $count);
        $this->position += strlen($data);
        return $data;
    }

    public function stream_eof() {
        return $this->position >= strlen(self::$content);
    }

    public function stream_tell() {
        return $this->position;
    }

    public function stream_seek($offset, $whence = SEEK_SET) {
        switch ($whence) {
            case SEEK_SET:
                $this->position = $offset;
                break;
            case SEEK_CUR:
                $this->position += $offset;
                break;
            case SEEK_END:
                $this->position = strlen(self::$content) + $offset;
                break;
        }
        return true;
    }

    public function stream_stat() {
        return [];
    }

     /**
      * Helper method to mock php://input stream with specific content
      */
     static public function mockPhpInput($content) {
         // Unregister the existing php:// wrapper if it exists (to override the built-in one)
         $wrappers = stream_get_wrappers();
         if (in_array('php', $wrappers)) {
             stream_wrapper_unregister('php');
         }
         // Register our custom stream wrapper for php://input
         stream_wrapper_register('php', MockPhpInputStreamWrapper::class);
         MockPhpInputStreamWrapper::$content = $content;
     }

    /**
     * Helper method to restore php://input stream
     */
    static public function restorePhpInput() {
        $wrappers = stream_get_wrappers();
        if (in_array('php', $wrappers)) {
            stream_wrapper_unregister('php');
        }
    }
}
