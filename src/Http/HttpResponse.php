<?php

namespace FTPApp\Http;

use FTPApp\Http\Exception\InvalidArgumentHttpException;

/**
 * Class HttpResponse represents an http response with a basic methods.
 */
class HttpResponse
{
    /** @var int */
    public $statusCode;

    /** @var mixed */
    public $content;

    /** @var array $headers */
    public $headers;

    /**
     * HttpResponse constructor.
     *
     * @param null  $content
     * @param int   $statusCode
     * @param array $headers
     */
    public function __construct($content = null, $statusCode = 200, $headers = [])
    {
        $this->statusCode = $statusCode;
        $this->content    = $content;
        $this->headers    = $headers;
    }

    /**
     * @param string $name
     * @param string $value
     *
     * @throws InvalidArgumentHttpException
     *
     * @return $this
     */
    public function addHeader($name, $value)
    {
        if (array_key_exists($name, $this->headers)) {
            throw new InvalidArgumentHttpException("Cannot add Http header [$name], it already exists.");
        }

        $this->headers[$name] = $value;

        return $this;
    }

    /**
     * @param string $name
     * @param string $value
     *
     * @throws InvalidArgumentHttpException
     *
     * @return $this
     */
    public function setHeader($name, $value)
    {
        if (!array_key_exists($name, $this->headers)) {
            throw new InvalidArgumentHttpException("Http header [$name] doesn't exists to overwrite their value.");
        }

        $this->headers[$name] = $value;

        return $this;
    }

    /**
     * @return $this
     */
    public function send()
    {
        $this->sendContent($this->content);

        return $this;
    }

    /**
     * @return $this
     */
    public function sendJSON()
    {
        $this->sendContent(json_encode($this->content));

        return $this;
    }

    /**
     * @return $this
     */
    public function removeXPoweredByHeader()
    {
        if (!headers_sent() && $this->hasResponseHeader('X-Powered-By')) {
            header_remove('X-Powered-By');
        }

        return $this;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function removeHeader($name)
    {
        if (!array_key_exists($name, $this->getResponseHeaders())) {
            throw new InvalidArgumentHttpException("Http header [$name] doesn't exists to remove.");
        }

        header_remove($name);

        return $this;
    }

    /**
     * Gets all headers that's will be send with the response.
     *
     * @return array
     */
    public function getResponseHeaders()
    {
        return array_merge($this->headers, $this->getReadyHeaders());
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasResponseHeader($name)
    {
        return array_key_exists($name, $this->getResponseHeaders());
    }

    /**
     * @return $this
     */
    public function cleanContent()
    {
        if (ob_get_contents()) {
            ob_clean();
        }

        return $this;
    }

    /**
     * Clears all ready headers.
     *
     * @return $this
     */
    public function clearReadyHeaders()
    {
        if (headers_sent() || empty(headers_list())) {
            return $this;
        }

        foreach (($this->getReadyHeaders()) as $name => $value) {
            header_remove($name);
        }

        return $this;
    }

    /**
     * Sends all Http headers if not already sent.
     *
     * @return void
     */
    protected function sendRawHeaders()
    {
        if (headers_sent() && empty($this->headers)) {
            return;
        }

        /*
        if ($this->content) {
            $this->addHeader('Content-type', 'text/plain');
        }*/

        foreach ($this->headers as $name => $value) {
            header(sprintf("%s: %s", ucfirst(strtolower($name)), $value), false);
        }
    }

    /**
     * Sets the Http status code.
     */
    protected function setResponseCode()
    {
        http_response_code($this->statusCode);
    }

    /**
     * @return array
     */
    protected function getReadyHeaders()
    {
        $headers = [];

        foreach (headers_list() as $header) {
            $parts = explode(' ', $header);
            $headers[substr($parts[0], 0, -1)] = $parts[1];
        }

        return $headers;
    }

    /**
     * @param mixed $content
     */
    protected function sendContent($content)
    {
        $this->sendRawHeaders();
        $this->setResponseCode();
        echo $content;
        exit();
    }
}