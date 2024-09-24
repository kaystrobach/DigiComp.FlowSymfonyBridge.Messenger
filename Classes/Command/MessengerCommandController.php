<?php

namespace DigiComp\FlowSymfonyBridge\Messenger\Command;

use DigiComp\FlowSymfonyBridge\Messenger\EventListener\StopWorkerOnRestartSignalListener;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Neos\Flow\ObjectManagement\DependencyInjection\DependencyProxy;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\Command\ConsumeMessagesCommand;
use Symfony\Component\Messenger\RoutableMessageBus;

class MessengerCommandController extends CommandController
{
    use RunSymfonyCommandTrait;

    #[Flow\Inject(name: 'DigiComp.FlowSymfonyBridge.Messenger:RoutableMessageBus', lazy: false)]
    protected RoutableMessageBus $routableBus;

    #[Flow\Inject(name: 'DigiComp.FlowSymfonyBridge.Messenger:ReceiversContainer', lazy: false)]
    protected ContainerInterface $receiverContainer;

    #[Flow\Inject(name: 'DigiComp.FlowSymfonyBridge.Messenger:EventDispatcher', lazy: false)]
    protected EventDispatcherInterface $eventDispatcher;

    #[Flow\Inject]
    protected LoggerInterface $logger;

    #[Flow\InjectConfiguration]
    protected array $configuration;

    #[Flow\Inject(name: 'DigiComp.FlowSymfonyBridge.Messenger:RestartSignalCachePool', lazy: false)]
    protected CacheItemPoolInterface $restartSignalCachePool;

    /**
     * Consumes messages and dispatches them to the message bus
     *
     * To receive from multiple transports, pass each name:
     * <info>messenger:consume receiver1 receiver2</info>
     *
     * Options are:
     *   --limit limits the number of messages received
     *   --failure-limit stop the worker when the given number of failed messages is reached
     *   --memory-limit stop the worker if it exceeds a given memory usage limit. You can use shorthand
     *       byte values [K, M, or G]
     *   --time-limit stop the worker when the gien time limit (in seconds) is reached. If a message is beeing handled,
     *       the worker will stop after the processing is finished
     *   --bus specify the message bus to dispatch received messages to instead of trying to determine it automatically.
     *       This is required if the messages didn't originate from Messenger
     *
     * Optional arguments are -q (quiet) and -v[v[v]] (verbosity)
     */
    public function consumeCommand(): void
    {
        if ($this->receiverContainer instanceof DependencyProxy) {
            $this->receiverContainer->_activateDependency();
        }
        if ($this->eventDispatcher instanceof DependencyProxy) {
            $this->eventDispatcher->_activateDependency();
        }
        $command = new ConsumeMessagesCommand(
            $this->routableBus,
            $this->receiverContainer,
            $this->eventDispatcher,
            $this->logger,
            \array_keys($this->configuration['transports'])
        );
        $this->run($command);
    }

    /**
     * List all available receivers
     */
    public function listReceiversCommand(): void
    {
        foreach (\array_keys($this->configuration['transports']) as $transportName) {
            $this->outputLine('- ' . $transportName);
        }
    }

    /**
     * Stop workers after their current message
     *
     * Each worker command will finish the message they are currently processing
     * and then exit. Worker commands are *not* automatically restarted: that
     * should be handled by a process control system.
     */
    public function stopWorkersCommand(): void
    {
        $cacheItem = $this->restartSignalCachePool->getItem(
            StopWorkerOnRestartSignalListener::RESTART_REQUESTED_TIMESTAMP_KEY
        );
        $cacheItem->set(\microtime(true));
        $this->restartSignalCachePool->save($cacheItem);

        //TODO: Add the possibility to wait until all are exited
    }
}
