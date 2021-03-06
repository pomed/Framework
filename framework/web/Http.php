<?php

namespace Asymptix\web;

/**
 * Http protocol functionality and other connected tools.
 *
 * @category Asymptix PHP Framework
 * @author Dmytro Zarezenko <dmytro.zarezenko@gmail.com>
 * @copyright (c) 2009 - 2015, Dmytro Zarezenko
 *
 * @git https://github.com/Asymptix/Framework
 * @license http://opensource.org/licenses/MIT
 */
class Http {

    const POST = "POST";
    const GET = "GET";

    /**
     * Perform HTTP redirect with saving POST params in session.
     *
     * @param string $url URL redirect to.
     * @param array<mixed> $postData List of post params to save.
     */
    public static function httpRedirect($url = "", $postData = array()) {
        if (preg_match("#^http[s]?://.+#", $url)) { // absolute url
            http_redirect($url);
        } else { // same domain (relative url)
            if (!empty($postData)) {
                if (is_array($postData)) {
                    if (!isset($_SESSION['_post']) || !is_array($_SESSION['_post'])) {
                        $_SESSION['_post'] = array();
                    }

                    foreach ($postData as $fieldName => $fieldValue) {
                        $_SESSION['_post'][$fieldName] = serialize($fieldValue);
                    }
                } else {
                    throw new \Exception("Wrong POST data.");
                }
            }
            http_redirect("http://" . $_SERVER['SERVER_NAME'] . "/" . $url);
        }
    }

    /**
     * Returns clients IP-address.
     *
     * @return string
     */
    public static function getIP() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) { //check ip from share internet
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) { //to check ip is pass from proxy
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'];
        }
        return "";
    }

    /**
     * Returns browser parameters data.
     *
     * @return array
     */
    public static function getBrowser() {
        $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : "";
        $browserFullName = $browserShortName = 'Unknown';
        $platform = 'Unknown';
        $version = 'Unknown';

        // Detect platform (operation system)
        if (preg_match('/linux/i', $userAgent)) {
            $platform = 'linux';
        } elseif (preg_match('/macintosh|mac os x/i', $userAgent)) {
            $platform = 'mac';
        } elseif (preg_match('/windows|win32/i', $userAgent)) {
            $platform = 'windows';
        }

        // Detect browser
        if (preg_match('/MSIE/i', $userAgent) && !preg_match('/Opera/i', $userAgent)) {
            $browserFullName = 'Internet Explorer';
            $browserShortName = "MSIE";
        } elseif (preg_match('/Firefox/i', $userAgent)) {
            $browserFullName = 'Mozilla Firefox';
            $browserShortName = "Firefox";
        } elseif (preg_match('/Chrome/i', $userAgent)) {
            $browserFullName = 'Google Chrome';
            $browserShortName = "Chrome";
        } elseif (preg_match('/Safari/i', $userAgent)) {
            $browserFullName = 'Apple Safari';
            $browserShortName = "Safari";
        } elseif (preg_match('/Opera/i', $userAgent)) {
            $browserFullName = 'Opera';
            $browserShortName = "Opera";
        } elseif (preg_match('/Netscape/i', $userAgent)) {
            $browserFullName = 'Netscape';
            $browserShortName = "Netscape";
        }

        // Detect browser version number
        $known = array('Version', $browserShortName, 'other');
        $pattern = '#(?<browser>' . join('|', $known) .
                ')[/ ]+(?<version>[0-9.|a-zA-Z.]*)#';
        if (!preg_match_all($pattern, $userAgent, $matches)) {
            // we have no matching number just continue
        }

        // see how many we have
        $i = count($matches['browser']);
        if ($i != 1) {
            //we will have two since we are not using 'other' argument yet
            //see if version is before or after the name
            if (strripos($userAgent, "Version") < strripos($userAgent, $browserShortName)) {
                $version = $matches['version'][0];
            } elseif (isset($matches['version'][1])) {
                $version = $matches['version'][1];
            } else {
                $version = "N/A";
            }
        } else {
            $version = $matches['version'][0];
        }

        // check if we have a number
        if ($version == null || $version == "") {
            $version = "?";
        }

        return array(
            'userAgent' => $userAgent,
            'name' => $browserFullName,
            'version' => $version,
            'platform' => $platform,
            'pattern' => $pattern
        );
    }

    /**
     * Gets the address that the provided URL redirects to,
     * or FALSE if there's no redirect.
     *
     * @param string $url
     * @return mixed String with redirect URL or FALSE if no redirect.
     */
    public static function getRedirectUrl($url) {
        $url_parts = @parse_url($url);
        if (!$url_parts) {
            return false;
        }
        if (!isset($url_parts['host'])) { //can't process relative URLs
            return false;
        }

        if (!isset($url_parts['path'])) {
            $url_parts['path'] = '/';
        }

        $sock = fsockopen($url_parts['host'], (isset($url_parts['port']) ? (int) $url_parts['port'] : 80), $errno, $errstr, 30);
        if (!$sock) {
            return false;
        }

        $request = "HEAD " . $url_parts['path'] . (isset($url_parts['query']) ? '?' . $url_parts['query'] : '') . " HTTP/1.1\r\n";
        $request .= 'Host: ' . $url_parts['host'] . "\r\n";
        $request .= "Connection: Close\r\n\r\n";
        fwrite($sock, $request);
        $response = '';
        while (!feof($sock)) {
            $response.= fread($sock, 8192);
        }
        fclose($sock);

        if (preg_match('/^Location: (.+?)$/m', $response, $matches)) {
            if (substr($matches[1], 0, 1) == "/") {
                return $url_parts['scheme'] . "://" . $url_parts['host'] . trim($matches[1]);
            } else {
                return trim($matches[1]);
            }
        } else {
            return false;
        }
    }

    /**
     * Follows and collects all redirects, in order, for the given URL.
     *
     * @param string $url
     * @return array
     */
    public static function getAllRedirects($url) {
        $redirects = array();
        while ($newurl = self::getRedirectUrl($url)) {
            if (in_array($newurl, $redirects)) {
                break;
            }
            $redirects[] = $newurl;
            $url = $newurl;
        }

        return $redirects;
    }

    /**
     * Gets the address that the URL ultimately leads to.
     * Returns $url itself if it isn't a redirect.
     *
     * @param string $url
     * @return string
     */
    public static function getFinalUrl($url) {
        $redirects = self::getAllRedirects($url);
        if (count($redirects) > 0) {
            return array_pop($redirects);
        } else {
            return $url;
        }
    }

    /**
     * Executes CURL async request.
     *
     * @param string $url URL.
     * @param array $params List of request params.
     * @param string $type Type of the request (GET, POST, ...).
     * @param int $timeout Timeout in seconds.
     *
     * @return type
     */
    public static function curlRequestAsync($url, $params, $type = self::POST, $timeout = 30) {
        $postParams = array();
        foreach ($params as $key => &$val) {
            if (is_array($val)) {
                $val = implode(',', $val);
            }
            $postParams[] = $key . '=' . urlencode($val);
        }
        $postString = implode('&', $postParams);

        $parts = parse_url($url);

        $port = isset($parts['port']) ? (integer)$parts['port'] : 80;

        $fp = fsockopen($parts['host'], $port, $errno, $errstr, $timeout);

        // Data goes in the path for a GET request
        if ($type == self::GET) {
            $parts['path'].= '?' . $postString;
        }

        $request = "$type " . $parts['path'] . " HTTP/1.1\r\n";
        $request.= "Host: " . $parts['host'] . "\r\n";

        if ($type == self::POST) {
            $request.= "Content-Type: application/x-www-form-urlencoded\r\n";
            $request.= "Content-Length: " . strlen($postString) . "\r\n";
        }
        $request.= "Connection: Close\r\n";
        $request.= "\r\n";

        // Data goes in the request body for a POST request
        if ($type == self::POST && isset($postString)) {
            $request.= $postString;
        }

        fwrite($fp, $request);

        $response = "";
        while (!feof($fp) && $result = fgets($fp)) {
            $response.= $result;
        }

        fclose($fp);

        list($respHeader, $respBody) = preg_split("/\R\R/", $response, 2);

        $headers = array_map(array('self', "pair"), explode("\r\n", $respHeader));
        $headerList = array();
        foreach ($headers as $value) {
            $headerList[$value['key']] = $value['value'];
        }

        return array(
            'request' => $request,
            'response' => array(
                'header' => $respHeader,
                'headerList' => $headerList,
                'body' => trim(http_chunked_decode($respBody))
            ),
            'errno' => $errno,
            'errstr' => $errstr
        );
    }

}