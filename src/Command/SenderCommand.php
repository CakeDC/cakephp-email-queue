<?php
declare(strict_types=1);

namespace EmailQueue\Command;

use Cake\Collection\Collection;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Configure;
use Cake\Mailer\Mailer;
use Cake\Network\Exception\SocketException;
use Cake\ORM\TableRegistry;
use EmailQueue\Model\Table\EmailQueueTable;

class SenderCommand extends Command
{
    /**
     * Sends queued emails.
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io.
     * @return int
     */
    public function execute(Arguments $args, ConsoleIo $io): int
    {
        return $this->sendQueuedEmails($args, $io);
    }

    /**
     * Sends queued emails in a batch.
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io.
     * @return int
     */
    public function sendQueuedEmails(Arguments $args, ConsoleIo $io): int
    {
        $stagger = (int)$args->getOption('stagger');
        if ($stagger > 0) {
            sleep(rand(0, $stagger));
        }

        Configure::write('App.baseUrl', '/');
        $emailQueue = TableRegistry::getTableLocator()->get('EmailQueue', ['className' => EmailQueueTable::class]);
        $emails = $emailQueue->getBatch((int)$args->getOption('limit'));

        $count = count($emails);
        foreach ($emails as $e) {
            $configName = $e->config === 'default' ? $args->getOption('config') : $e->config;
            $template = $e->template === 'default' ? $args->getOption('template') : $e->template;
            $layout = $e->layout === 'default' ? $args->getOption('layout') : $e->layout;
            $headers = empty($e->headers) ? [] : (array)$e->headers;
            $theme = empty($e->theme) ? '' : (string)$e->theme;
            $viewVars = empty($e->template_vars) ? [] : $e->template_vars;
            $errorMessage = null;
            $sent = false;

            try {
                $email = $this->newEmail($configName);

                if (!empty($e->from_email) && !empty($e->from_name)) {
                    $email->setFrom($e->from_email, $e->from_name);
                }

                $transport = $email->getTransport();

                if ($transport && $transport->getConfig('additionalParameters')) {
                    $from = key($email->getFrom());
                    $transport->setConfig(['additionalParameters' => "-f $from"]);
                }

                if (!empty($e->attachments)) {
                    $email->setAttachments($e->attachments);
                }

                $email
                    ->setTo($e->email)
                    ->setSubject($e->subject)
                    ->setEmailFormat($e->format)
                    ->addHeaders($headers)
                    ->setViewVars($viewVars)
                    ->setMessageId(false)
                    ->setReturnPath($email->getFrom());

                $email->viewBuilder()
                    ->setLayout($layout)
                    ->setTheme($theme)
                    ->setTemplate($template);

                $email->deliver();
                $sent = true;
            } catch (SocketException $exception) {
                $io->err($exception->getMessage());
                $errorMessage = $exception->getMessage();
            }

            if ($sent) {
                $emailQueue->success($e->id);
                $io->out('<success>Email ' . $e->id . ' was sent</success>');
            } else {
                $emailQueue->fail($e->id, $errorMessage);
                $io->out('<error>Email ' . $e->id . ' was not sent</error>');
            }
        }
        if ($count > 0) {
            $locks = (new Collection($emails))->extract('id')->toList();
            $emailQueue->releaseLocks($locks);
        }

        return static::CODE_SUCCESS;
    }

    /**
     * Returns a new mailer instance.
     *
     * @param array|string $config array of configs, or string to load configs from app.php
     * @return \Cake\Mailer\Mailer
     */
    protected function newEmail(array|string $config): Mailer
    {
        return new Mailer($config);
    }

    /**
     * @param \Cake\Console\ConsoleOptionParser $parser The parser to configure.
     * @return \Cake\Console\ConsoleOptionParser
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser
            ->setDescription('Sends queued emails in a batch')
            ->addOption('limit', [
                'short' => 'l',
                'help' => 'How many emails should be sent in this batch?',
                'default' => '50',
            ])
            ->addOption('template', [
                'short' => 't',
                'help' => 'Name of the template to be used to render email',
                'default' => 'default',
            ])
            ->addOption('layout', [
                'short' => 'w',
                'help' => 'Name of the layout to be used to wrap template',
                'default' => 'default',
            ])
            ->addOption('stagger', [
                'short' => 's',
                'help' => 'Seconds to maximum wait randomly before proceeding (useful for parallel executions)',
                'default' => '0',
            ])
            ->addOption('config', [
                'short' => 'c',
                'help' => 'Name of email settings to use as defined in email.php',
                'default' => 'default',
            ]);

        return $parser;
    }
}
