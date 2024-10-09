<?php

namespace DigiComp\FlowSymfonyBridge\Messenger\Transport;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Messenger\Transport\TransportFactoryInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;

#[Flow\Scope('singleton')]
class TransportsContainer implements ContainerInterface
{
    #[Flow\InjectConfiguration]
    protected array $configuration;

    #[Flow\Inject(lazy: false)]
    protected ObjectManagerInterface $objectManager;

    #[Flow\Inject(name: 'DigiComp.FlowSymfonyBridge.Messenger:TransportFactory', lazy: false)]
    protected TransportFactoryInterface $transportFactory;

    /**
     * @var TransportInterface[]
     */
    protected array $transports;

    public function get(string $id)
    {
        if (! isset($this->configuration['transports'][$id])) {
            throw new \InvalidArgumentException('Unknown transport name: ' . $id);
        }
        if (! isset($this->transports[$id])) {
            $transportDefinition = \array_merge([
                'dsn' => '',
                'options' => [],
                'serializer' => $this->configuration['defaultSerializerName'],
                // TODO: Probably this has to be setup elsewhere, as the transport does not care by itself
                'retry_strategy' => [ // TODO: Make the default configurable
                    'max_retries' => 3,
                    // milliseconds delay
                    'delay' => 1000,
                    // causes the delay to be higher before each retry
                    // e.g. 1 second delay, 2 seconds, 4 seconds
                    'multiplier' => 2,
                    'max_delay' => 0,
                    // override all of this with a service that
                    // implements Symfony\Component\Messenger\Retry\RetryStrategyInterface
                    'service' => null
                ],
            ], $this->configuration['transports'][$id]);
            $this->transports[$id] = $this->transportFactory->createTransport(
                $transportDefinition['dsn'],
                $transportDefinition['options'],
                $this->objectManager->get($transportDefinition['serializer'])
            );
        }
        return $this->transports[$id];
    }

    public function has(string $id): bool
    {
        return isset($this->configuration['transports'][$id]);
    }
}
