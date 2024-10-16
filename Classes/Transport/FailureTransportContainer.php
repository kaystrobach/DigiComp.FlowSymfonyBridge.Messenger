<?php

namespace DigiComp\FlowSymfonyBridge\Messenger\Transport;

use InvalidArgumentException;
use Neos\Flow\Annotations as Flow;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceProviderInterface;

#[Flow\Scope('singleton')]
class FailureTransportContainer implements ContainerInterface, ServiceProviderInterface
{
    #[Flow\InjectConfiguration]
    protected array $configuration;

    #[Flow\Inject(name: 'DigiComp.FlowSymfonyBridge.Messenger:ReceiversContainer', lazy: false)]
    protected ContainerInterface $receiverContainer;

    public function get(string $id): mixed
    {
        $transportsToFailureTransportMapping = $this->getTransportsToFailureTransportsMapping();
        if (!isset($transportsToFailureTransportMapping[$id])) {
            throw new InvalidArgumentException(sprintf('"%s" has no failure transport mapping or does not exist!', $id));
        }
        return $this->receiverContainer->get($transportsToFailureTransportMapping[$id]);
    }

    public function has(string $id): bool
    {
        $transportsToFailureTransportMapping = $this->getTransportsToFailureTransportsMapping();
        if (!isset($transportsToFailureTransportMapping[$id])) {
            return false;
        }
        $failureTransport = $transportsToFailureTransportMapping[$id];
        return $this->receiverContainer->has($failureTransport);
    }

    public function getProvidedServices(): array
    {
        if (!isset($this->configuration['transports'])) {
            return [];
        }

        $failureTransportNames = [];
        if (isset($this->configuration['failureTransport'])) {
            $failureTransportNames[$this->configuration['failureTransport']] = $this->configuration['failureTransport'];
        }
        foreach ($this->configuration['transports'] as $transportDefinition) {
            if (isset($transportDefinition['failureTransport'])) {
                $failureTransportNames[$transportDefinition['failureTransport']] = $transportDefinition['failureTransport'];
            }
        }

        $failureTransportDefinitions = [];
        foreach ($this->configuration['transports'] as $transportName => $transportDefinition) {
            if (isset($failureTransportNames[$transportName])) {
                $failureTransportDefinitions[$transportName] = $transportDefinition;
            }
        }

        return $failureTransportDefinitions;
    }

    private function getTransportsToFailureTransportsMapping(): array
    {
        if (!isset($this->configuration['transports'])) {
            return [];
        }

        $defaultFailureTransport = null;
        if (isset($this->configuration['failureTransport'])) {
            $defaultFailureTransport = $this->configuration['failureTransport'];
        }

        $transportsToFailureTransportMapping = [];
        foreach ($this->configuration['transports'] as $transportName => $transportDefinition) {
            if (isset($transportDefinition['failureTransport'])) {
                $transportsToFailureTransportMapping[$transportName] = $transportDefinition['failureTransport'];
                continue;
            }
            if ($defaultFailureTransport !== null) {
                $transportsToFailureTransportMapping[$transportName] = $defaultFailureTransport;
            }
        }

        return $transportsToFailureTransportMapping;
    }
}
