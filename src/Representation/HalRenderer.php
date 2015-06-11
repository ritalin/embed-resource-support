<?php
/**
 * This file is part of the BEAR.Resource package
 *
 * @license http://opensource.org/licenses/MIT MIT
 */
namespace Embed\Sample\Representation;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\RenderInterface;
use BEAR\Resource\RequestInterface;
use BEAR\Resource\ResourceObject;
use BEAR\Resource\Uri;
use BEAR\Sunday\Extension\Router\RouterInterface;
use Doctrine\Common\Annotations\Reader;
use Nocarrier\Hal;

use Embed\Sample\Generator\ItemResourceGenerator;

/**
 * HAL(Hypertext Application Language) renderer
 */
class HalRenderer implements RenderInterface
{
    /**
     * @var Reader
     */
    private $reader;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @param Reader          $reader
     * @param RouterInterface $router
     */
    public function __construct(Reader $reader, RouterInterface $router)
    {
        $this->reader = $reader;
        $this->router = $router;
    }

    /**
     * {@inheritdoc}
     */
    public function render(ResourceObject $ro) {
        return $this->renderInternal($ro, $ro);
    }
    
    private function renderInternal(ResourceObject $parent, ResourceObject $ro)
    {
        list($ro, $body) = $this->valuate($parent, $ro);

        $method = 'on' . ucfirst($ro->uri->method);
        $hasMethod = method_exists($ro, $method);
        if (! $hasMethod) {
            $ro->view = ''; // OPTIONS request no view

            return '';
        }
        $links = ($hasMethod) ? $this->reader->getMethodAnnotations(new \ReflectionMethod($ro, $method), Link::class) : [];
        /* @var $links Link[] */
        /* @var $ro ResourceObject */
        $linkValue = $body + $ro->uri->query;
        $hal = $this->getHal($ro->uri, $linkValue, $links);
        $ro->view = $hal->asJson(true) . PHP_EOL;
        $ro->headers['content-type'] = 'application/hal+json';

        return $ro->view;
    }

    /**
     * @param \BEAR\Resource\ResourceObject $ro
     */
    private function valuateElements(ResourceObject &$parent, ResourceObject $ro)
    {
        foreach ($ro->body as $key => &$element) {
            if ($element instanceof RequestInterface) {
                unset($ro->body[$key]);
                $view = $this->renderInternal($ro, $element());
                $parent->body['_embedded'][$key] = json_decode($view);
            }
            else if ($element instanceof ItemResourceGenerator) {
                unset($ro->body[$key]);
                $parent->body['_embedded'][$key] = $this->valuateItemResources($element);
            }
        }
    }
    
    private function valuateItemResources(ItemResourceGenerator $generator) {
        $items = [];
        foreach ($generator->resources() as $ro) {
            $items[] = json_decode($this->renderInternal($ro, $ro));
        }
        
        return $items;
    }
    
    /**
     * @param Uri   $uri
     * @param array $linkValue
     * @param array $links
     *
     * @return Hal
     */
    private function getHal(Uri $uri, array $linkValue, array $links)
    {
        $query = $uri->query ? '?' . http_build_query($uri->query) : '';
        $path = $uri->path . $query;
        $selfLink = $this->getReverseMatchedLink($path);
        $hal = new Hal($selfLink, $linkValue);
        $this->getHalLink($linkValue, $links, $hal);

        return $hal;
    }

    /**
     * @param string $uri
     *
     * @return mixed
     */
    private function getReverseMatchedLink($uri)
    {
        $urlParts = parse_url($uri);
        $routeName = $urlParts['path'];
        isset($urlParts['query']) ? parse_str($urlParts['query'], $value) : $value = [];
        $reverseUri = $this->router->generate($routeName, $value);
        if (is_string($reverseUri)) {
            return $reverseUri;
        }

        return $uri;
    }

    /**
     * @param ResourceObject $ro
     *
     * @return array
     */
    private function valuate(ResourceObject $parent, ResourceObject $ro)
    {
        // evaluate all request in body.
        if (is_array($ro->body)) {
            $this->valuateElements($parent, $ro);
        }
        // HAL
        $body = $ro->body ?: [];
        if (is_scalar($body)) {
            $body = ['value' => $body];

            return [$ro, $body];
        }

        return[$ro, $body];
    }

    /**
     * @param array $body
     * @param array $links
     * @param Hal   $hal
     *
     * @internal param Uri $uri
     */
    private function getHalLink(array $body, array $links, Hal $hal)
    {
        foreach ($links as $link) {
            if (!$link instanceof Link) {
                continue;
            }
            $uri = uri_template($link->href, $body);
            $reverseUri = $this->getReverseMatchedLink($uri);
            $hal->addLink($link->rel, $reverseUri);
        }
    }
}
