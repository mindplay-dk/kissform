<?php

namespace mindplay\kissform\Fields;

use mindplay\kissform\Facets\TokenServiceInterface;
use mindplay\kissform\Field;
use mindplay\kissform\InputModel;
use mindplay\kissform\InputRenderer;
use mindplay\kissform\Validators\CheckToken;

class TokenField extends Field
{
    /**
     * @var string
     */
    const TOKEN_UUID = 'c020b766-cddd-4f7a-ba75-4da76f861a62';
    
    /**
     * @var TokenServiceInterface
     */
    private $service;

    /**
     * @param string                $name token name (unique to the form or controller)
     * @param TokenServiceInterface $service
     */
    public function __construct($name, TokenServiceInterface $service)
    {
        parent::__construct(self::TOKEN_UUID . '-' . sha1($name));
        
        $this->service = $service;
    }

    /**
     * {@inheritdoc}
     */
    public function renderInput(InputRenderer $renderer, InputModel $model, array $attr)
    {
        return $renderer->tag(
            'input',
            $attr + [
                'type'  => 'hidden',
                'name'  => $renderer->getName($this),
                'value' => $this->service->createToken($this->getName()),
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function createValidators()
    {
        return [new CheckToken($this->service)];
    }
}
