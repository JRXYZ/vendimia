<?php
namespace Psr\Http\Message;

class Uri implements UriInterface
{
    private $components = [];

    public function getScheme()
    {
        return $this->getComponent('scheme');
    }

    public function getAuthority()
    {
        $user = $this->getUserInfo();
     
        // Asumimos que siempre hay un host   
        $authority = $this->getComponent('host');

        if ($user) {
            $authority = $user . '@' . $authority;
        }

        return $authority;
    }

    public function getUserInfo()
    {
        $user = $this->getComponent('user');
        $pass = $this->getComponent('pass');

        if ($pass) {
            $user .= ':' . $pass;
        }

        return $user;
    }

    public function getHost()
    {
        return $this->getComponent('host');
    }

    public function getPort()
    {
        return $this->getComponent('port');
    }

    public function getPath()
    {
        return $this->getComponent('path');
    }

    public function getQuery()
    {
        $query = $this->getComponent('query');
        if ($query) {
            return $query;
        }
        else {
            return '';
        }
    }

    public function getFragment()
    {
        return $this->getComponent('fragment');
    } 

    public function withScheme($scheme) {
        $new = clone $this;
        $new -> setComponent('scheme', $scheme);
        return $new;
    }

    public function withUserInfo($user, $password = null)
    {
        $new = clone $this;
        $new->setComponent('user', $user);
        if ($password) {
            $new->setComponent('pass', $password);
        }
        return $new;
    }

    public function withHost($host)
    {
        $new = clone $this;
        $new->setComponent('host', $host);
        return $new;
    }

    public function withPort($port)
    {
        $new = clone $this;
        $new->setComponent('port', $port);
        return $new;
    }

    public function withPath($path)
    {
        $new = clone $this;
        $new->setComponent('path', $path);
        return $new;
    }

    public function withQuery($query)
    {
        $new = clone $this;
        $new->setComponent('query', $query);
        return $new;
    }

    public function withFragment($fragment)
    {
        $new = clone $this;
        $new->setComponent('fragment', $fragment);
        return $new;
    }

    public function __toString()
    {
        return $this->getUri();
    }




    public function __construct($uri = false)
    {
        if ($uri) {
            $this->components = parse_url($uri);
        }
    }

    public function getUri() {
        $scheme = $this->getScheme();
        $authority = $this->getAuthority();
        $path = $this->getPath();
        $fragment = $this->getFragment();
        $query = $this->getQuery();

        $uri = '';
        if ($scheme) {
            $uri .= $scheme . '://';
        }
        if ($authority) {
            $uri .= $authority;
        }
        if ($path) {
            $uri .= '/' . $path;
        }
        if ($query) {
            $uri .= '?' . $query;
        }
        if ($fragment) {
            $uri .= '#' . $fragment;
        }
        return $uri;
    }

    /**
     * Return a URI component.
     */
    private function getComponent($component)
    {
        if (!$this->components) {
            throw new \RuntimeException('Uri is empty.');
        }

        if (array_key_exists($component, $this->components)) {
            return $this->components[$component];
        } else {
            return null;
        }
    }
    private function setComponent($component, $value) 
    {
        $this->components[$component] = $value;
    }
}