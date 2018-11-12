<?php


var_dump(locationHref());
echo json_encode($_SERVER);

function locationHref(){
    return getHostInfo() . getUrl();
}

function getHostInfo(){
    // $scheme = $_SERVER['REQUEST_SCHEME'];
    $secure = getIsSecureConnection();
    $scheme = $secure ? 'https' : 'http';
    if (isset($_SERVER['HTTP_HOST'])) {
        $hostInfo = $scheme . '://' . $_SERVER['HTTP_HOST'];
    } elseif (isset($_SERVER['SERVER_NAME'])) {
        $hostInfo = $scheme . '://' . $_SERVER['SERVER_NAME'];
        $port = $secure ? getSecurePort() : getPort();
        if (($port !== 80 && !$secure) || ($port !== 443 && $secure)) {
            $hostInfo .= ':' . $port;
        }
    }
    return $hostInfo;
}

function getIsSecureConnection()
{
    if (isset($_SERVER['HTTPS']) && (strcasecmp($_SERVER['HTTPS'], 'on') === 0 || $_SERVER['HTTPS'] == 1)) {
        return true;
    }
    $secureProtocolHeaders = [
        'X-Forwarded-Proto' => ['https'],
        'Front-End-Https' => ['on'],
    ];
    foreach ($secureProtocolHeaders as $header => $values) {
        $headerValue = isset($_SERVER['HTTP_'.$header])?$_SERVER['HTTP_'.$header]:null;
        if ($headerValue !== null) {
            foreach ($values as $value) {
                if (strcasecmp($headerValue, $value) === 0) {
                    return true;
                }
            }
        }
    }

    return false;
}

function getSecurePort(){
    return isset($_SERVER['SERVER_PORT']) ? (int) $_SERVER['SERVER_PORT'] : 443;
}

function getPort(){
    return isset($_SERVER['SERVER_PORT']) ? (int) $_SERVER['SERVER_PORT'] : 80;
}

function getUrl(){
    $requestUri = '';
    if (isset($_SERVER['HTTP_X-Rewrite-Url'])) {
        $requestUri = $_SERVER['HTTP_X-Rewrite-Url'];
    } elseif (isset($_SERVER['REQUEST_URI'])) {
        $requestUri = $_SERVER['REQUEST_URI'];
        if ($requestUri !== '' && $requestUri[0] !== '/') {
            $requestUri = preg_replace('/^(http|https):\/\/[^\/]+/i', '', $requestUri);
        }
    } elseif (isset($_SERVER['ORIG_PATH_INFO'])) { // IIS 5.0 CGI
        $requestUri = $_SERVER['ORIG_PATH_INFO'];
        if (!empty($_SERVER['QUERY_STRING'])) {
            $requestUri .= '?' . $_SERVER['QUERY_STRING'];
        }
    }
    return $requestUri;
}
