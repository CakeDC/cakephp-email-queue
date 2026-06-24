<?php
declare(strict_types=1);

namespace EmailQueue\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Configure;
use Cake\Mailer\Mailer;
use Cake\ORM\TableRegistry;
use EmailQueue\Model\Table\EmailQueueTable;

class PreviewCommand extends Command
{
    /**
     * Previews queued emails.
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io.
     * @return int
     */
    public function execute(Arguments $args, ConsoleIo $io): int
    {
        Configure::write('App.baseUrl', '/');

        $conditions = [];
        $ids = $args->getArguments();
        if ($ids !== []) {
            $conditions['id IN'] = $ids;
        }

        $emailQueue = TableRegistry::getTableLocator()->get('EmailQueue', ['className' => EmailQueueTable::class]);
        $emails = $emailQueue->find()->where($conditions)->all()->toList();

        if (!$emails) {
            $io->out('No emails found');

            return static::CODE_SUCCESS;
        }

        $io->clear();
        foreach ($emails as $i => $email) {
            if ($i) {
                $io->ask('Hit a key to continue');
                $io->clear();
            }
            $io->out('Email :' . $email['id']);
            $this->previewEmail($email->toArray(), $io);
        }

        return static::CODE_SUCCESS;
    }

    /**
     * Preview email
     *
     * @param array $emailData email data
     * @param \Cake\Console\ConsoleIo $io The console io.
     * @return void
     */
    public function previewEmail(array $emailData, ConsoleIo $io): void
    {
        $configName = $emailData['config'];
        $template = $emailData['template'];
        $layout = $emailData['layout'];
        $headers = empty($emailData['headers']) ? [] : (array)$emailData['headers'];
        $theme = empty($emailData['theme']) ? '' : (string)$emailData['theme'];

        $email = new Mailer($configName);

        if (!empty($emailData['attachments'])) {
            $email->setAttachments($emailData['attachments']);
        }

        $email->setTransport('Debug')
            ->setTo($emailData['email'])
            ->setSubject($emailData['subject'])
            ->setEmailFormat($emailData['format'])
            ->addHeaders($headers)
            ->setMessageId(false)
            ->setReturnPath($email->getFrom())
            ->setViewVars($emailData['template_vars']);

        $email->viewBuilder()
            ->setTheme($theme)
            ->setTemplate($template)
            ->setLayout($layout);

        $return = $email->deliver();

        $io->out('Content:');
        $io->hr();
        $io->out($return['message']);
        $io->hr();
        $io->out('Headers:');
        $io->hr();
        $io->out($return['headers']);
        $io->hr();
        $io->out('Data:');
        $io->hr();
        debug($emailData['template_vars']);
        $io->hr();
        $io->out('');
    }

    /**
     * @param \Cake\Console\ConsoleOptionParser $parser The parser to configure.
     * @return \Cake\Console\ConsoleOptionParser
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser
            ->setDescription('Previews queued emails')
            ->addArgument('ids', [
                'help' => 'Optional email ids to preview',
                'required' => false,
            ]);

        return $parser;
    }
}
