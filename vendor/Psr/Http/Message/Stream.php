<?php
namespace Psr\Http\Message;

/**
 * Stream implementation.
 *
 * {@inheritDoc}
 * @author Oliver Etchebarne
 */
class Stream implements StreamInterface 
{
    /** Handler to stream */
    protected $resource;

    public function __toString()
    {
        return $this->getContents();
    }

    public function close()
    {
        $resource = $this->detach();
        fclose($resource);
    }

    public function detach() {
        $resource = $this->resource;
        unset ($this->resource);
        return $resource;
    }

    public function getSize()
    {
        $stat = fstat($this->resource);
        
        if ($stat === false) {
            // Este stream no soporta fstat.
            return null;
        }

        return $stat[7];
    }

    public function tell()
    {
        return ftell($this->resource);
    }

    public function eof()
    {
        return feof($this->resource);
    }

    public function isSeekable() {
        return $this->getMetadata('seekable');
    }

    public function seek($offset, $whence = SEEK_SET) {
        if (!$this->isSeekable()) {
            throw new \RuntimeException('Stream is not seekable.');
        }

        $result = fseek($this->resource, $offset, $whence);

        if ($result !== 0) {
            throw new \RuntimeException('Error seeking stream.');
        }
    }

    public function rewind()
    {
        rewind($this->resource);
    }

    public function isWritable()
    {
        $mode = $this->getMetadata('mode');
        return strpos($mode, 'w') !== false;
    }

    public function write($string) {

        $result = fwrite($this->resource, $string);
        if ($result === false) {
            throw new \RuntimeException('Error writing to stream.');
        }
    }

    public function isReadable()
    {
        $mode = $this->getMetadata('mode');
        return strpos($mode, 'r') !== false;
    }

    public function read($length)
    {
        $result = fread($this->resource, $length);
        if ($result === false) {
            throw new \RuntimeException('Error reading from stream.');
        }
        return $result;
    }

    public function getContents() {
        $result = stream_get_contents($this->resource, -1, 0);
        if ($result === false) {
            throw new \RuntimeException('Error reading from stream.');
        }
        return $result;
    }

    public function getMetadata($key = null) 
    {
        $metadata = stream_get_meta_data($this->resource);

        if (isnull($key)) {
            return $metadata;
        }

        if (array_key_exists($key, $metadata)) {
            return $metadata[$key];
        }

        return null;
     }

    /* Not in the implementation */
    public function __construct($stream, $mode = 'r+') {
        $resource = null;

        if (is_string($stream)) {
            // Creamos el nuevo resource
            $resource = fopen($stream, $mode);
        }
        elseif (is_resource($stream) || get_resource_type($stream) === 'stream') {
            $resource = $stream;
            $stream = null;
        }

        $this->stream = $stream;
        $this->resource = $resource;
    }
}
