<?php

namespace Embed\Sample\Generator;

use BEAR\Resource\ResourceObject;

class ItemResourceGenerator {
    /**
     * @var ItemResourceTemplateInterface
     */
    private $template;
     
    /**
     * @var array
     */
    private $values;
    
    public function __construct(ItemResourceTemplateInterface $template, array $values) {
        $this->template = $template;
        $this->values = $values;
    }
    
    /**
     * {@inheritdoc}
     */
    public function resources() {
        $template = $this->template;
        
        foreach ($this->values as $v) {
            yield $template->asResource($v);
        }
    }
}
