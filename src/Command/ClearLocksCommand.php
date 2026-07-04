<?php
declare(strict_types=1);

namespace EmailQueue\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\ORM\TableRegistry;
use EmailQueue\Model\Table\EmailQueueTable;

class ClearLocksCommand extends Command
{
    /**
     * Clears all locked emails in the queue.
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io.
     * @return int
     */
    public function execute(Arguments $args, ConsoleIo $io): int
    {
        TableRegistry::getTableLocator()
            ->get('EmailQueue', ['className' => EmailQueueTable::class])
            ->clearLocks();

        return static::CODE_SUCCESS;
    }

    /**
     * @param \Cake\Console\ConsoleOptionParser $parser The parser to configure.
     * @return \Cake\Console\ConsoleOptionParser
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser->setDescription('Clears all locked emails in the queue, useful for recovering from crashes');

        return $parser;
    }
}
