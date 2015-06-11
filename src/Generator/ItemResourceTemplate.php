<?php

namespace Embed\Sample\Generator;

use BEAR\Resource\ResourceObject;

class ItemResourceTemplate implements ItemResourceTemplateInterface {
    /**
     * @var string
     */
    private $rel;
    
    /**
     * @var ResourceObject
     */
    private $template;
    
    /**
     * @var callable
     * mixed -> Jsonserializable
     */
    private $valueSelector;
    
    /**
     * @var callable
     *mixed -> array
     */
    private $keySelector;
    
    public function __construct($rel, ResourceObject $template, callable $valueSelector, callable $keySelector) {
        $this->rel = $rel;
        $this->template = $template;
        $this->valueSelector = $valueSelector;
        $this->keySelector = $keySelector;
    }
    
    /**
     * {@inheritdoc}
     */
    public function asResource($value) {
        return self::asResourceInternal($this->rel, $this->template, $this->keySelector, $this->valueSelector, $value);
    }
    
    private static function asResourceInternal($rel, ResourceObject $ro, $keySelector, $valueSelector, $value) {
        $ro->uri->query = $keySelector($value);
        $ro[$rel] = $valueSelector($value);        
        
        return $ro;
    }
}
