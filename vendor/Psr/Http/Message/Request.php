<?php
namespace Psr\Http\Message;

/**
 * Request implementation.
 *
 * {@inheritDoc}
 * @author Oliver Etchebarne
 */
class Request extends Message implements RequestInterface 
{
    private $method = '';
    private $requestTarget = '/';
    private $uri;

    public function getRequestTarget()
    {
        return $this->requestTarget;
    }

    public function withRequestTarget($requestTarget)
    {
        $new = clone $this;
        $new->setRequestTarget($requestTarget);
        return $new;
    }

    public function getMethod() {
        return $this->method;
    }

    public function withMethod($method)
    {
        $new = clone $this;
        $new->setMethod($method);
        return $new;
    }

    public function getUri() 
    {
        return $this->uri;
    }

    public function withUri(UriInterface $uri, $preserveHost = false)
    {
        $new = clone $this;
        $new->setUri($uri, $preserveHost);
        return $new;
    }



    /**
     * @see self::withRequestTarget()
     */
    protected function setRequestTarget($requestTarget)
    {
        $this->requestTarget = $requestTarget;
        return $this;
    }

    /**
     * @see self::withMethod()
     */
    protected function setMethod($method)
    {
        $this->method = $method;
        return $this;
    }

    /**
     * @see self::withUri()
     */
    protected function setUri(UriInterface $uri, $preserveHost = false)
    {
        $hostHeader = $this->hasHeader('host');
        $hostComponent = $uri->getHost();
        if ($preserveHost) {

            if (!$hostHeader && $hostComponent) {
                $this->setHeader('Host', $hostHeader);
            }
        }
        else {
            if ($hostComponent) {
                $this->setHeader('Host', $hostComponent);
            }
        }

        $this->uri = $uri;

        return $this; 
    }
}
