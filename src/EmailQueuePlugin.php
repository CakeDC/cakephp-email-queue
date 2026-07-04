<?php
declare(strict_types=1);

namespace EmailQueue;

use Cake\Console\CommandCollection;
use Cake\Core\BasePlugin;
use EmailQueue\Command\ClearLocksCommand;
use EmailQueue\Command\PreviewCommand;
use EmailQueue\Command\SenderCommand;

/**
 * Plugin for EmailQueue
 */
class EmailQueuePlugin extends BasePlugin
{
    /**
     * Register console commands.
     *
     * @param \Cake\Console\CommandCollection $commands The command collection to update.
     * @return \Cake\Console\CommandCollection
     */
    public function console(CommandCollection $commands): CommandCollection
    {
        return $commands
            ->add('email_queue sender', SenderCommand::class)
            ->add('email_queue preview', PreviewCommand::class)
            ->add('email_queue clear_locks', ClearLocksCommand::class);
    }
}
