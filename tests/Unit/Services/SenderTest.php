<?php

/*
 * This file is part of the Blast Project package.
 *
 * Copyright (C) 2015-2017 Libre Informatique
 *
 * This file is licenced under the GNU LGPL v3.
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Librinfo\EmailBundle\Services\Test\Unit;

use PHPUnit\Framework\TestCase;
use Librinfo\EmailBundle\Services\Sender;
use Librinfo\EmailBundle\Entity\Email;

class SenderTest extends TestCase
{
    /**
     * @var Sender
     */
    protected $object;
    protected $managerMock;
    protected $tracker;
    protected $inlineAttachmentsHandlerMock;
    protected $directMailerMock;
    protected $spoolMailerMock;
    protected $mail;
    protected $messageMock;

    protected function setUp()
    {
        $this->spoolMailerMock = $this->createMock(\Swift_Mailer::class);
        $this->directMailerMock = $this->createMock(\Swift_Mailer::class);
        $this->messageMock = $this->createMock(\Swift_Message::class);
        $this->inlineAttachmentsHandlerMock = $this->createMock('Librinfo\EmailBundle\Services\InlineAttachments');
        $this->managerMock = $this->createMock('Doctrine\ORM\EntityManager');

        $this->mail = new Email();
        $this->mail->setFieldFrom('testfrom@test.com');
        $this->mail->setFieldSubject('test');
        $this->mail->setIsTest(false);
        $this->mail->setTextContent('textcontent');
        $this->mail->setContent('MailContent');

        $this->spoolMailerMock->method('send')->willReturn('spoolmail send');
        $this->directMailerMock->method('send')->willReturn('directmail send');
        $this->object = new Sender($this->managerMock, $this->tracker, $this->inlineAttachmentsHandlerMock, $this->directMailerMock, $this->spoolMailerMock);
    }

    protected function tearDown()
    {
    }

    /**
     * @covers \Librinfo\EmailBundle\Services\Sender::send
     */
    public function testSend()
    {
        //testing return spoolmailer
        $this->mail->setFieldTo('testto@test.com;testto2@test.com');
        $test = $this->spoolMailerMock->send($this->messageMock);
        $test2 = $this->object->send($this->mail);
        $this->assertEquals($test, $test2, 'spoolmail send');

        //testing return directmailer
        $this->mail->setFieldTo('testto@test.com');
        $test3 = $this->directMailerMock->send($this->messageMock);
        $test4 = $this->object->send($this->mail);
        $this->assertEquals($test3, $test4, 'directmail send');

        //testing return directmailer with IsTest true
        $this->mail->setIsTest(true);
        $this->mail->setTestAddress('emaildetest@test.com');

        $testisTest = $this->object->send($this->mail);
        $test5 = $this->directMailerMock->send($this->messageMock);
        $this->assertEquals($test5, $testisTest, 'directmail send');
    }
}
