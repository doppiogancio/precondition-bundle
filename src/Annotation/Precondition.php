<?php

namespace DoppioGancio\PreconditionBundle\Annotation;

use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * @Annotation
 * @NamedArgumentConstructor
 * @Target({"METHOD"})
 * @Attributes({
 *   @Attribute("condition", type = "string"),
 *   @Attribute("errorMessage", type = "string"),
 * })
 */
class Precondition
{
    private string $condition;
    private ?string $errorMessage;

    /**
     * @param string $condition
     * @param string|null $errorMessage
     */
    public function __construct(string $condition, ?string $errorMessage = null)
    {
        $this->condition = $condition;
        $this->errorMessage = $errorMessage;
    }

    /**
     * @return string
     */
    public function getCondition(): string
    {
        return $this->condition;
    }

    /**
     * @return string|null
     */
    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }
}