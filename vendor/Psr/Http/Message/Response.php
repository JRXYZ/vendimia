<?php
namespace Psr\Http\Message;

/**
 * Response implementation.
 *
 * {@inheritDoc}
 * @author Oliver Etchebarne
 */
class Response extends Message implements ResponseInterface 
{
    protected $statusCode;
    protected $reasonPhrase;

    public function getStatusCode()
    {
        return $this->statusCode;
    }

    public function withStatus($code, $reasonPhrase = '') {
        $new = clone $this;
        $new->setStatus($code, $reasonPhrase);
        return $new;
    }

    public function getReasonPhrase()
    {
        return $this->reasonPhrase;
    }

    // Mutable accesors.

    /**
     * @see self::withStatus()
     */
    public function setStatus($code, $reasonPhrase = '')
    {
        $this->statusCode = $code;
        $this->reasonPhrase = $reasonPhrase;

        return $this;
    }
}