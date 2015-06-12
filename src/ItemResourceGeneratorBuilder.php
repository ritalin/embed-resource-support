<?php

namespace Embed\Sample;

use BEAR\Resource\ResourceObject;
use BEAR\Resource\FactoryInterface;
use Embed\Sample\Generator\ItemResourceTemplate;
use Embed\Sample\Generator\ItemResourceGenerator;

class ItemResourceGeneratorBuilder
{
    public static function from(FactoryInterface $factory, ResourceObject $parent, $path)
    {
        $uri = clone $parent->uri;
        $uri->path = $path;
        $uri->query = [];

        $self = new self();
        $self->ro = $factory->newInstance($uri);
        $self->ro->uri = $uri;

        $self->valueSelector = function ($v) { return $v; };

        return $self;
    }

    /**
     * @var string
     */
    private $rel = 'value';

    /**
     * @var ResourceObject
     */
    private $ro;

    /**
     * @var callable
     *               mixed -> Jsonserializable
     */
    private $valueSelector;

    public function rel($rel)
    {
        $this->rel = $rel;

        return $this;
    }

    public function valueConvertWith(callable $valueSelector)
    {
        $this->valueSelector = $valueSelector;

        return $this;
    }

    public function build(array $values, callable $keySelector)
    {
        $template = new ItemResourceTemplate(
            $this->rel,
            $this->ro,
            $this->valueSelector,
            $keySelector
        );

        return new ItemResourceGenerator($template, $values);
    }
}
