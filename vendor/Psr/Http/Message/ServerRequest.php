<?php
namespace Psr\Http\Message;

/**
 * Request implementation.
 *
 * {@inheritDoc}
 * @author Oliver Etchebarne
 */
class ServerRequest extends Request implements ServerRequestInterface 
{
    private $serverParams = [];
    private $cookieParams = [];
    private $queryParams = [];
    private $uploadedFiles = [];
    private $parsedBody = [];
    private $attributes = [];

    public function getServerParams()
    {
        return $this->serverParams;
    }

    public function getCookieParams()
    {
        return $this->cookieParams;
    }

    public function withCookieParams(array $cookies)
    {
        $new = clone $this;
        $new->setCookieParams($cookies);
        return $new;
    }

    public function getQueryParams()
    {
        return $this->queryParams;
    }

    public function withQueryParams(array $query) 
    {
        $new = clone $this;
        $new->setQueryParams($query);
        return $new;
    }

    public function getUploadedFiles()
    {
        return $this->uploadedFiles;
    }

    public function withUploadedFiles(array $uploadedFiles)
    {
        $new = clone $this;
        $new->setUploadedFiles($uploadedFiles);
        return $new;
    }

    public function getParsedBody()
    {
        return $this->parsedBody;
    }

    public function withParsedBody($data)
    {
        $new = clone $this;
        $new->setParsedBody($data);
        return $new;
    }

    public function getAttributes()
    {
        return $this->attributes;
    }

    public function getAttribute($name, $default = null)
    {
        if (array_key_exists($name, $this->attributes)) {
            return $this->attributes[$name];
        } else {
            return $default;
        }
    }

    public function withAttribute($name, $value)
    {
        $new = clone $this;
        $new->setAttribute($name, $value);
        return $new;
    }

    public function withoutAttribute($name)
    {
        $new = clone $this;
        $new->unsetAttribute($name);
        return $new;
    }

    /**
     * @see self::withCookieParams()
     */
    protected function setCookieParams(array $cookies)
    {
        $this->cookieParams = $cookies;
        return $this;
    }

    /**
     * @see self::withQueryParams()
     */
    protected function setQueryParams(array $query) {
        $this->queryParams = $query;
        return $this;
    }

    /**
     * @see self::withUploadedFIles()
     */
    protected function setUploadedFiles(array $uploadedFiles)
    {
        $this->uploadedFiles = $uploadedFiles;
        return $this;
    }

    /**
     * @see self::withParsedBody()
     */
    protected function setParsedBody($data) 
    {
        $this->parsedBody = $data;
        return $this;
    }

    /**
     * @see self::withAttribute()
     */
    protected function setAttribute($name, $value) 
    {
        $this->attribute[$name] = $value;
        return $this;
    }

    /**
     * @see self::withoutAttribute()
     */
    protected function unsetAttribute($name) 
    {
        unset ($this->attribute[$name]);
        return $this;
    }
}