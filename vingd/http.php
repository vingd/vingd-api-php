<?php
/**
 * Extremely simple HTTP/HTTPS client API for PHP, based on cURL.
 *
 * Modified WordPress WP_Http class.
 * 
 * @version 1.0
 * @date 2009-02-04
 * 
 */

class Http {
    private $curl = null;
    
    private $defaults = array(
        'method' => 'GET', 'blocking' => true, 'sslverify' => false,
        'sslkey' => null, 'sslcert' => null,
        'auth' => null, 'username' => null, 'password' => null,
        'timeout' => 5, 'redirection' => 5, 'httpversion' => '1.1',
        'headers' => array('user-agent' => 'VingdAPI'),
        'body' => null, 'cookies' => array()
    );
    
    
    function __construct() {
        // create a new cURL resource
        $this->curl = curl_init();
    }
    
    function __destruct() {
        // close cURL resource, and free up system resources
        if ($this->curl) curl_close($this->curl);
    }
    
    private function parse($options) {
        $result = $this->defaults;
        foreach ($options as $key => $value) {
            if (array_key_exists($key, $result)) $result[$key] = $value;
        }
        return $result;
    }
    
    public function request($url, $options = array()) {
        if (!$this->curl) throw new Exception('cURL not initialized.');
        
        $o = $this->parse($options);
        
        if (isset($o['headers']['User-Agent'])) {
            $o['user-agent'] = $o['headers']['User-Agent'];
            unset($o['headers']['User-Agent']);
        } else if(isset($o['headers']['user-agent'])) {
            $o['user-agent'] = $o['headers']['user-agent'];
            unset($o['headers']['user-agent']);
        }
        
        // cURL extension will sometimes fail when the timeout is less than 1 as
        // it may round down to 0, which gives it unlimited timeout.
        if ($o['timeout'] > 0 && $o['timeout'] < 1)
            $o['timeout'] = 1;
        
        // set cURL options
        curl_setopt($this->curl, CURLOPT_URL, $url);
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, $o['sslverify']);
        curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, $o['sslverify']);
        curl_setopt($this->curl, CURLOPT_USERAGENT, $o['user-agent']);
        curl_setopt($this->curl, CURLOPT_CONNECTTIMEOUT, $o['timeout']);
        curl_setopt($this->curl, CURLOPT_MAXREDIRS, $o['redirection']);
        
        if (isset($o['user-agent'])) {
            curl_setopt($this->curl, CURLOPT_USERAGENT, $o['user-agent']);
        }
        if (isset($o['sslkey'])) {
            curl_setopt($this->curl, CURLOPT_SSLKEY, $o['sslkey']);
        }
        if (isset($o['sslcert'])) {
            curl_setopt($this->curl, CURLOPT_SSLCERT, $o['sslcert']);
        }
        
        switch ($o['method']) {
            case 'GET':
                curl_setopt($this->curl, CURLOPT_HTTPGET, true);
                break;
            case 'HEAD':
                curl_setopt($this->curl, CURLOPT_NOBODY, true);
                break;
            case 'POST':
                curl_setopt($this->curl, CURLOPT_POST, true);
                curl_setopt($this->curl, CURLOPT_POSTFIELDS, $o['body']);
                break;
            case 'PUT':
                curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PUT');  
                curl_setopt($this->curl, CURLOPT_POSTFIELDS, $o['body']);
                break;
            default:
                curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, $o['method']);
                curl_setopt($this->curl, CURLOPT_POSTFIELDS, $o['body']);
        }
        
        if (true === $o['blocking'])
            curl_setopt($this->curl, CURLOPT_HEADER, true);
        else
            curl_setopt($this->curl, CURLOPT_HEADER, false);
        
        if (!empty($o['headers'])) {
            // cURL expects full header strings in each element
            $headers = array();
            foreach ($o['headers'] as $name => $value) {
                $headers[] = "{$name}: $value";
            }
            curl_setopt($this->curl, CURLOPT_HTTPHEADER, $headers);
        }
        
        if ($o['httpversion'] == '1.0')
            curl_setopt($this->curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
        else
            curl_setopt($this->curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        
        // We don't need to return the body, so don't.
        // Just execute request and return.
        if (!$o['blocking']) {
            curl_exec($this->curl);
            return array(
                'headers' => array(),
                'body' => '',
                'response' => array('code' => false, 'message' => false),
                'cookies' => array()
            );
        }
        
        switch ($o['auth']) {
            case 'basic':
                curl_setopt($this->curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
                break;
            case 'digest':
                curl_setopt($this->curl, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
                break;
            default:
                break;
        }
        
        if (!is_null($o['username']) && !is_null($o['password'])) {
            curl_setopt($this->curl, CURLOPT_USERPWD, "{$o['username']}:{$o['password']}");
        }
        
        $theResponse = curl_exec($this->curl);
        
        if (!empty($theResponse)) {
            $parts = explode("\r\n\r\n", $theResponse);
            $headerLength = curl_getinfo($this->curl, CURLINFO_HEADER_SIZE);
            $theHeaders = trim(substr($theResponse, 0, $headerLength));
            $theBody = substr($theResponse, $headerLength);
            if (false !== strrpos($theHeaders, "\r\n\r\n")) {
                $headerParts = explode("\r\n\r\n", $theHeaders);
                $theHeaders = $headerParts[count($headerParts)-1];
            }
            $theHeaders = $this->processHeaders($theHeaders);    
        } else {
            if ($this->curl_error = curl_error($this->curl))
                throw new Exception($this->curl_error);
            if (in_array(
                curl_getinfo($this->curl, CURLINFO_HTTP_CODE), array(301, 302)
            )) {
                throw new Exception('Too many redirects.');
            }
        }
        
        $response = $theHeaders['response'];
        
        return array(
            'headers' => $theHeaders['headers'],
            'body' => $theBody,
            'response' => $response,
            'cookies' => $theHeaders['cookies']
        );
    }
    
    /**
     * Transform header string into an array.
     *
     * If an array is given then it is assumed to be raw header data with
     * numeric keys with the headers as the values. No headers must be passed
     * that were already processed.
     *
     * @param string|array $headers
     * @return array
     *         Processed string headers. If duplicate headers are encountered,
     *        Then a numbered array is returned as the value of that header-key.
     */
    private function processHeaders($headers) {
        // split headers, one per array element
        if (is_string($headers)) {
            // tolerate line terminator: CRLF = LF (RFC 2616 19.3)
            $headers = str_replace("\r\n", "\n", $headers);
            // unfold folded header fields. LWS = [CRLF] 1*( SP | HT ) <US-ASCII
            // SP, space (32)>, <US-ASCII HT, horizontal-tab (9)> (RFC 2616 2.2)
            $headers = preg_replace('/\n[ \t]/', ' ', $headers);
            // create the headers array
            $headers = explode("\n", $headers);
        }
        
        $response = array('code' => 0, 'message' => '');
        
        $cookies = array();
        $newheaders = array();
        foreach ($headers as $tempheader) {
            if (empty($tempheader))
                continue;
            
            if (false === strpos($tempheader, ':')) {
                list(, $iResponseCode, $strResponseMsg) = explode(' ', $tempheader, 3);
                $response['code'] = $iResponseCode;
                $response['message'] = $strResponseMsg;
                continue;
            }
            
            list($key, $value) = explode(':', $tempheader, 2);
            
            if (!empty($value)) {
                $key = strtolower($key);
                if (isset($newheaders[$key])) {
                    $newheaders[$key] = array($newheaders[$key], trim($value));
                } else {
                    $newheaders[$key] = trim($value);
                }
                if ('set-cookie' == strtolower($key))
                    //$cookies[] = new WP_Http_Cookie( $value );
                    $cookies[] = $value;
            }
        }
        
        return array(
            'response' => $response,
            'headers' => $newheaders,
            'cookies' => $cookies
        );
    }
}


?>