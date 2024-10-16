<?php

namespace DigiComp\FlowSymfonyBridge\Messenger\Command;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Command\FailedMessagesRemoveCommand;
use Symfony\Component\Messenger\Command\FailedMessagesRetryCommand;
use Symfony\Component\Messenger\Command\FailedMessagesShowCommand;

class FailedCommandController extends CommandController
{
    use RunSymfonyCommandTrait;

    #[Flow\Inject(name: 'DigiComp.FlowSymfonyBridge.Messenger:ReceiversContainer', lazy: false)]
    protected ContainerInterface $receiverContainer;

    #[Flow\InjectConfiguration]
    protected array $configuration;

    /**
     * Show one or more messages from the failure transport
     *
     * The <info>%command.name%</info> shows message that are pending in the failure transport.
     *
     * <info>php %command.full_name%</info>
     *
     * Or look at a specific message by its id:
     *
     * <info>php %command.full_name% {id}</info>
     *
     * Optional arguments are -q (quiet) -v[v[v]] (verbosity) and --force (do not ask)
     */
    public function showCommand(): void
    {
        $command = new FailedMessagesShowCommand(
            $this->configuration['failureTransport'],
            $this->receiverContainer->get($this->configuration['failureTransport'])
        );
        $this->run($command);
    }

    /**
     * Remove given messages from the failure transport
     *
     * The <info>%command.name%</info> removes given messages that are pending in the failure transport.
     *
     * <info>php %command.full_name% {id1} [{id2} ...]</info>
     *
     * The specific ids can be found via the messenger:failed:show command.
     *
     * Optional arguments are -q (quiet) -v[v[v]] (verbosity) and --force (do not ask)
     */
    public function removeCommand(): void
    {
        $command = new FailedMessagesRemoveCommand(
            $this->configuration['failureTransport'],
            $this->receiverContainer->get($this->configuration['failureTransport'])
        );
        $this->run($command);
    }

    /**
     * Retry one or more messages from the failure transport
     *
     * The command will interactively ask if each message should be retried
     * or discarded.
     *
     * Some transports support retrying a specific message id, which comes
     * from the <info>messenger:failed:show</info> command.
     *
     * <info>php %command.full_name% {id}</info>
     *
     * Or pass multiple ids at once to process multiple messages:
     *
     * <info>php %command.full_name% {id1} {id2} {id3}</info>
     *
     * Optional arguments are -q (quiet) -v[v[v]] (verbosity) and --force (do not ask)
     *
     * @noinspection PhpParamsInspection
     */
    public function retryCommand(): void
    {
        $command = new FailedMessagesRetryCommand(
            $this->configuration['failureTransport'],
            $this->receiverContainer->get($this->configuration['failureTransport']),
            $this->objectManager->get('DigiComp.FlowSymfonyBridge.Messenger:RoutableMessageBus'),
            $this->objectManager->get('DigiComp.FlowSymfonyBridge.Messenger:EventDispatcher'),
            $this->objectManager->get(LoggerInterface::class)
        );
        $this->run($command);
    }
}
