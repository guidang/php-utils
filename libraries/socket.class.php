<?php

/**
 * Socket 类
 * Class Socket
 */

class Socket {
    private $config = array();
    public $connection = null;
    public $connected = false;

    /**
     * 初始化
     * Socket constructor.
     */
    public function __construct($options=array()) {
        $config['persistent']= isset($options['persistent']) ? $options['persistent'] : false;
        $config['host']      = isset($options['host']) ? $options['host'] : '127.0.0.1';
        $config['protocol']  = isset($options['protocol']) ? $options['protocol'] : 'tcp';
        $config['port']      = isset($options['port']) ? $options['port'] : 10090;
        $config['timeout']   = isset($options['timeout']) ? $options['timeout'] : 300;

        $this->config = $config;
    }


    /**
     * 连接
     * @return bool
     */
    public function connect() {
        // if ($this->connection != null) {
        //     $this->disconnect();
        // }

        if ($this->config['persistent'] == true) {
            $tmp = null;
            if($this->config['protocol'] == 'tcp'){
                $this->connection = @pfsockopen($this->config['host'], $this->config['port'], $errNum, $errStr, $this->config['timeout']);
            }else{
                $this->connection = @pfsockopen("udp://".$this->config['host'], $this->config['port'], $errNum, $errStr, $this->config['timeout']);
            }
        }else{
            if($this->config['protocol'] == 'udp'){
                $this->connection = fsockopen("udp://".$this->config['host'], $this->config['port'], $errNum, $errStr, $this->config['timeout']);
            }else{
                $this->connection = fsockopen($this->config['host'], $this->config['port'], $errNum, $errStr, $this->config['timeout']);
            }
        }
        if (!empty($errNum) || !empty($errStr)) {
            $this->error($errStr, $errNum);
        }
        $this->connected = is_resource($this->connection);
        return $this->connected;
    }

    /**
     * 错误日志
     * @param $errStr
     * @param $errNum
     */
    public function error($errStr, $errNum) {
    
    }

    /**
     * 发送数据
     * @param $data
     * @return bool|int
     */
    public function write($data) {
        if (!$this->connected) {
            if (!$this->connect()) {
                return false;
            }
        }
        $_msg   = pack("na*", strlen($data), $data);

        return fwrite($this->connection, $_msg, strlen($_msg));
    }

    /**
     * 发送数据 Byte
     * @param $data
     * @param $len
     * @return bool|int
     */
    public function writeByte($data, $len) {
        if (!$this->connected) {
            if (!$this->connect()) {
                return false;
            }
        }
        return fwrite($this->connection, $data, $len);
    }

    /**
     * 读取数据
     * @param int $length
     * @return bool|string
     */
    public function read($length=1024) {
        if (!$this->connected) {
            if (!$this->connect()) {
                return false;
            }
        }
        if (!feof($this->connection)) {
            return fread($this->connection, $length);
        } else {
            return false;
        }
        $this->disconnect();
    }

    /**
     * 关闭连接
     * @return bool
     */
    public function disconnect() {
        if (!is_resource($this->connection)) {
            $this->connected = false;
            return true;
        }
        $this->connected = !fclose($this->connection);
        if (!$this->connected) {
            $this->connection = null;
        }
        return !$this->connected;
    }

    /**
     * 销毁
     */
    public function __destruct() {
        $this->disconnect();
    }
}