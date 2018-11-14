<?php

namespace Zttp;

class Zttp
{
    static function __callStatic($method, $args)
    {
        return PendingZttpRequest::new()->{$method}(...$args);
    }
}

class PendingZttpRequest
{
    function __construct()
    {
        $this->beforeSendingCallbacks = collect();
        $this->bodyFormat = 'json';
        $this->options = [
            'http_errors' => false,
        ];
    }

    static function new(...$args)
    {
        return new self(...$args);
    }

    function withOptions($options)
    {
        return tap($this, function ($request) use ($options) {
            return $this->options = array_merge_recursive($this->options, $options);
        });
    }

    function withoutRedirecting()
    {
        return tap($this, function ($request) {
            return $this->options = array_merge_recursive($this->options, [
                'allow_redirects' => false,
            ]);
        });
    }

    function withoutVerifying()
    {
        return tap($this, function ($request) {
            return $this->options = array_merge_recursive($this->options, [
                'verify' => false,
            ]);
        });
    }

    function asJson()
    {
        return $this->bodyFormat('json')->contentType('application/json');
    }

    function asFormParams()
    {
        return $this->bodyFormat('form_params')->contentType('application/x-www-form-urlencoded');
    }

    function asMultipart()
    {
        return $this->bodyFormat('multipart');
    }

    function bodyFormat($format)
    {
        return tap($this, function ($request) use ($format) {
            $this->bodyFormat = $format;
        });
    }

    function contentType($contentType)
    {
        return $this->withHeaders(['Content-Type' => $contentType]);
    }

    function accept($header)
    {
        return $this->withHeaders(['Accept' => $header]);
    }

    function withHeaders($headers)
    {
        return tap($this, function ($request) use ($headers) {
            return $this->options = array_merge_recursive($this->options, [
                'headers' => $headers,
            ]);
        });
    }

    function withBasicAuth($username, $password)
    {
        return tap($this, function ($request) use ($username, $password) {
            return $this->options = array_merge_recursive($this->options, [
                'auth' => [$username, $password],
            ]);
        });
    }

    function withDigestAuth($username, $password)
    {
        return tap($this, function ($request) use ($username, $password) {
            return $this->options = array_merge_recursive($this->options, [
                'auth' => [$username, $password, 'digest'],
            ]);
        });
    }

    function timeout($seconds)
    {
        return tap($this, function () use ($seconds) {
            $this->options['timeout'] = $seconds;
        });
    }

    function beforeSending($callback)
    {
        return tap($this, function () use ($callback) {
            $this->beforeSendingCallbacks[] = $callback;
        });
    }

    function get($url, $queryParams = [])
    {
        return $this->send('GET', $url, [
            'query' => $queryParams,
        ]);
    }

    function post($url, $params = [])
    {
        return $this->send('POST', $url, [
            $this->bodyFormat => $params,
        ]);
    }

    function patch($url, $params = [])
    {
        return $this->send('PATCH', $url, [
            $this->bodyFormat => $params,
        ]);
    }

    function put($url, $params = [])
    {
        return $this->send('PUT', $url, [
            $this->bodyFormat => $params,
        ]);
    }

    function delete($url, $params = [])
    {
        return $this->send('DELETE', $url, [
            $this->bodyFormat => $params,
        ]);
    }

    function send($method, $url, $options)
    {
        try {
            return new ZttpResponse($this->buildClient()->request($method, $url, $this->mergeOptions([
                'query' => $this->parseQueryParams($url),
            ], $options)));
        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            throw new ConnectionException($e->getMessage(), 0, $e);
        }
    }

    function buildClient()
    {
        return new \GuzzleHttp\Client(['handler' => $this->buildHandlerStack()]);
    }

    function buildHandlerStack()
    {
        return tap(\GuzzleHttp\HandlerStack::create(), function ($stack) {
            $stack->push($this->buildBeforeSendingHandler());
        });
    }

    function buildBeforeSendingHandler()
    {
        return function ($handler) {
            return function ($request, $options) use ($handler) {
                return $handler($this->runBeforeSendingCallbacks($request), $options);
            };
        };
    }

    function runBeforeSendingCallbacks($request)
    {
        return tap($request, function ($request) {
            $this->beforeSendingCallbacks->each->__invoke(new ZttpRequest($request));
        });
    }

    function mergeOptions(...$options)
    {
        return array_merge_recursive($this->options, ...$options);
    }

    function parseQueryParams($url)
    {
        return tap([], function (&$query) use ($url) {
            parse_str(parse_url($url, PHP_URL_QUERY), $query);
        });
    }
}

class ZttpRequest
{
    function __construct($request)
    {
        $this->request = $request;
    }

    function url()
    {
        return (string) $this->request->getUri();
    }

    function method()
    {
        return $this->request->getMethod();
    }

    function body()
    {
        return (string) $this->request->getBody();
    }

    function headers()
    {
        return collect($this->request->getHeaders())->mapWithKeys(function ($values, $header) {
            return [$header => $values[0]];
        })->all();
    }
}

class ZttpResponse
{
    use \Illuminate\Support\Traits\Macroable {
        __call as macroCall;
    }

    function __construct($response)
    {
        $this->response = $response;
    }

    function body()
    {
        return (string) $this->response->getBody();
    }

    function json()
    {
        return json_decode($this->response->getBody(), true);
    }

    function header($header)
    {
        return $this->response->getHeaderLine($header);
    }

    function headers()
    {
        return collect($this->response->getHeaders())->mapWithKeys(function ($v, $k) {
            return [$k => $v[0]];
        })->all();
    }

    function status()
    {
        return $this->response->getStatusCode();
    }

    function isSuccess()
    {
        return $this->status() >= 200 && $this->status() < 300;
    }

    function isOk()
    {
        return $this->isSuccess();
    }

    function isRedirect()
    {
        return $this->status() >= 300 && $this->status() < 400;
    }

    function isClientError()
    {
        return $this->status() >= 400 && $this->status() < 500;
    }

    function isServerError()
    {
        return $this->status() >= 500;
    }

    function __toString()
    {
        return $this->body();
    }

    function __call($method, $args)
    {
        if (static::hasMacro($method)) {
            return $this->macroCall($method, $args);
        }

        return $this->response->{$method}(...$args);
    }
}

class ConnectionException extends \Exception {}

function tap($value, $callback) {
    $callback($value);
    return $value;
}
