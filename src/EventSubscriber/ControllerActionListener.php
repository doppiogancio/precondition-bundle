<?php

namespace DoppioGancio\PreconditionBundle\EventSubscriber;

use Doctrine\Common\Annotations\Reader;
use DoppioGancio\PreconditionBundle\Annotation\Precondition;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

class ControllerActionListener implements EventSubscriberInterface
{
    private Reader $reader;
    private ExpressionLanguage $expressionLanguage;

    /**
     * @param Reader $reader
     */
    public function __construct(Reader $reader)
    {
        $this->reader = $reader;
        $this->expressionLanguage = new ExpressionLanguage();
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::CONTROLLER => ['onKernelController'],
        ];
    }

    public function onKernelController(ControllerEvent $event): void
    {
        $request = $event->getRequest();

        $controller = $event->getController();
        if (!is_array($controller)) {
            return;
        }

        [$controller, $method] = $controller;

        $preconditions = $this->getPreconditions($controller, $method);

        if (count($preconditions) === 0) {
            return ;
        }

        $this->evaluatePreconditions(
            $preconditions,
            $this->getControllerMethodArguments($controller, $method, $request)
        );
    }

    private function getPreconditions($controller, string $method): array
    {
        $reflectedController = new \ReflectionClass($controller);
        $reflectedMethod = $reflectedController->getMethod($method);

        $annotations = $this->reader->getMethodAnnotations($reflectedMethod);

        $preconditions = [];
        foreach ($annotations as $a) {
            if ($a instanceof Precondition) {
                $preconditions[] = $a;
            }
        }
        return $preconditions;
    }

    private function getControllerMethodArguments($controller, string $method, Request $request): array
    {
        $reflectedController = new \ReflectionClass($controller);
        $reflectedMethod = $reflectedController->getMethod($method);

        $arguments = $request->attributes->get('_route_params');
        foreach ($reflectedMethod->getParameters() as $parameter) {
            if ($parameter->getClass() !== null && $parameter->getClass()->isInstance($request)) {
                $arguments[$parameter->getName()] = $request;
            } else {
                $arguments[$parameter->getName()] = $request->attributes->get($parameter->getName());
            }
        }

        return $arguments;
    }

    /**
     * @param array $preconditions
     * @param array $arguments
     */
    private function evaluatePreconditions(array $preconditions, array $arguments): void
    {
        foreach ($preconditions as $precondition) {
            $res = $this->expressionLanguage->evaluate($precondition->getCondition(), $arguments);
            if ($res === true) {
                continue;
            }

            throw new PreconditionFailedHttpException(
                $precondition->getErrorMessage() ?? $precondition->getCondition()
            );
        }
    }
}