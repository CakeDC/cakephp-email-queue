<?php
declare(strict_types=1);

namespace EmailQueue\Test\TestCase;

use Cake\TestSuite\TestCase;
use EmailQueue\EmailQueuePlugin;

class PluginTest extends TestCase
{
    public function testPluginLoads(): void
    {
        $this->assertInstanceOf(EmailQueuePlugin::class, new EmailQueuePlugin());
    }
}
