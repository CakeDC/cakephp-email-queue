<?php
declare(strict_types=1);

namespace EmailQueue\Test\TestCase\Command;

use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOutput;
use Cake\Mailer\Transport\MailTransport;
use Cake\Network\Exception\SocketException;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use EmailQueue\Command\ClearLocksCommand;
use EmailQueue\Command\SenderCommand;
use EmailQueue\Model\Table\EmailQueueTable;
use EmailQueue\Test\Fixture\EmailQueueFixture;
use TestApp\Mailer\TestMailer;

/**
 * SenderCommand Test Case.
 */
class SenderCommandTest extends TestCase
{
    /**
     * @var \Cake\Console\ConsoleOutput
     */
    protected ConsoleOutput $out;

    /**
     * @var \Cake\Console\ConsoleIo
     */
    protected ConsoleIo $io;

    /**
     * Fixtures.
     *
     * @var array<class-string>
     */
    protected array $fixtures = [
        EmailQueueFixture::class,
    ];

    /**
     * @var \EmailQueue\Model\Table\EmailQueueTable
     */
    protected EmailQueueTable $EmailQueue;

    /**
     * setUp method.
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->out = new ConsoleOutput();
        $this->io = new ConsoleIo($this->out, $this->out);

        $this->EmailQueue = TableRegistry::getTableLocator()
            ->get('EmailQueue', ['className' => EmailQueueTable::class]);
    }

    /**
     * @return \EmailQueue\Command\SenderCommand&\PHPUnit\Framework\MockObject\MockObject
     */
    protected function createSenderMock(): SenderCommand
    {
        return $this->getMockBuilder(SenderCommand::class)
            ->onlyMethods(['newEmail'])
            ->getMock();
    }

    /**
     * @return \Cake\Console\Arguments
     */
    protected function createSenderArguments(): Arguments
    {
        return new Arguments([], [
            'limit' => '10',
            'template' => 'default',
            'layout' => 'default',
            'config' => 'default',
            'stagger' => '0',
        ], []);
    }

    public function testMainAllWin(): void
    {
        $sender = $this->createSenderMock();
        $email = new TestMailer();
        $email->setTo('you@example.com')
            ->setSubject('About');

        $sender->expects($this->exactly(3))
            ->method('newEmail')
            ->willReturn($email);

        $sender->sendQueuedEmails($this->createSenderArguments(), $this->io);

        $emails = $this->EmailQueue
            ->find()
            ->where(['id IN' => [1, 2, 3]])
            ->all()
            ->toList();

        $this->assertEquals(1, $emails[0]['send_tries']);
        $this->assertEquals(2, $emails[1]['send_tries']);
        $this->assertEquals(3, $emails[2]['send_tries']);

        $this->assertFalse($emails[0]['locked']);
        $this->assertFalse($emails[1]['locked']);
        $this->assertFalse($emails[2]['locked']);

        $this->assertTrue($emails[0]['sent']);
        $this->assertTrue($emails[1]['sent']);
        $this->assertTrue($emails[2]['sent']);
    }

    public function testMainAllFail(): void
    {
        $sender = $this->createSenderMock();
        $transport = $this->getMockBuilder(MailTransport::class)
            ->onlyMethods(['send'])
            ->getMock();

        $transport->expects($this->exactly(3))
            ->method('send')
            ->willThrowException(new SocketException('fail'));

        $email = new TestMailer();
        $email->setTo('you@example.com')
            ->setSubject('About')
            ->setTransport($transport);

        $sender->expects($this->exactly(3))
            ->method('newEmail')
            ->with('default')
            ->willReturn($email);

        $sender->sendQueuedEmails($this->createSenderArguments(), $this->io);

        $emails = $this->EmailQueue
            ->find()
            ->where(['id IN' => [1, 2, 3]])
            ->all()
            ->toList();

        $this->assertEquals(2, $emails[0]['send_tries']);
        $this->assertEquals(3, $emails[1]['send_tries']);
        $this->assertEquals(4, $emails[2]['send_tries']);

        $this->assertFalse($emails[0]['locked']);
        $this->assertFalse($emails[1]['locked']);
        $this->assertFalse($emails[2]['locked']);

        $this->assertFalse($emails[0]['sent']);
        $this->assertFalse($emails[1]['sent']);
        $this->assertFalse($emails[2]['sent']);
    }

    public function testClearLocks(): void
    {
        $this->EmailQueue->getBatch();
        $command = new ClearLocksCommand();
        $command->execute(new Arguments([], [], []), $this->io);
        $this->assertEmpty($this->EmailQueue->findByLocked(true)->toArray());
    }
}
