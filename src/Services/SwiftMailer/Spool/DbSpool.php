<?php

namespace Librinfo\EmailBundle\Services\SwiftMailer\Spool;

use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Librinfo\EmailBundle\Services\SwiftMailer\Spool\SpoolStatus;
use Librinfo\EmailBundle\Services\Tracking;
use Librinfo\EmailBundle\Services\InlineAttachments;

/**
 * Class DbSpool
 */
class DbSpool extends \Swift_ConfigurableSpool
{
    /**
     * @var Router
     */
    protected $router;

    /**
     * @var EntityManager
     */
    protected $manager;

    /**
     * @var string
     */
    protected $environment;

    /**
     * @var EntityRepository
     */
    protected $repository;
    
    /**
     *
     * @var integer
     */
    protected $pauseTime;

    /**
     * @param Router $router
     * @param EntityManager $manager
     * @param string $environment
     */
    public function __construct(Router $router, EntityManager $manager, $environment)
    {
        $this->router = $router;
        $this->manager = $manager;
        $this->environment = $environment;
        $this->repository = $this->manager->getRepository('LibrinfoEmailBundle:Email');
    }

    /**
     * Starts this Spool mechanism.
     */
    public function start()
    {
        
    }

    /**
     * Stops this Spool mechanism.
     */
    public function stop()
    {
        
    }

    /**
     * Tests if this Spool mechanism has started.
     *
     * @return boolean
     */
    public function isStarted()
    {
        return true;
    }

    /**
     * Queues a message.
     *
     * @param \Swift_Mime_Message $message The message to store
     * @return boolean Whether the operation has succeeded
     * @throws \Swift_IoException if the persist fails
     */
    public function queueMessage(\Swift_Mime_Message $message)
    {
        $email = $this->repository->findOneBy(array("messageId" => $message->getId()));
        $email->setMessage(base64_encode(serialize($message)));
        $email->setStatus(SpoolStatus::STATUS_READY);
        $email->setEnvironment($this->environment);
        $this->updateEmail($email);

        return true;
    }

    /**
     * Sends messages using the given transport instance.
     *
     * @param \Swift_Transport $transport         A transport instance
     * @param string[]        &$failedRecipients An array of failures by-reference
     *
     * @return int The number of sent emails
     */
    public function flushQueue(\Swift_Transport $transport, &$failedRecipients = null)
    {
        if (!$transport->isStarted())
        {
            $transport->start();
        }

        $emails = $this->repository->findBy(
                array("status" => SpoolStatus::STATUS_READY, "environment" => $this->environment), null
        );

        if (!count($emails))
        {
            return 0;
        }

        $failedRecipients = (array) $failedRecipients;
        $count = 0;
        $time = time();

        foreach ($emails as $email)
        {
            $email->setStatus(SpoolStatus::STATUS_PROCESSING);

            $this->updateEmail($email);

            $message = unserialize(base64_decode($email->getMessage()));

            $addresses = explode(';', $email->getFieldTo());

            foreach ($addresses as $address)
            {
                $message->setTo(trim($address));
                $content = $email->getContent();
                
                if ($email->getTracking())
                {
                    $tracker = new Tracking($this->router);
                    $content = $tracker->addTracking($content, $address, $email->getId());
                }
                
                $attachmentsHandler = new InlineAttachments();
                $content = $attachmentsHandler->handle($content, $message);

                $message->setBody($content);
                
                try {
                    $count += $transport->send($message, $failedRecipients);
                    sleep($this->pauseTime);
                } catch (\Swift_TransportException $e) {
                    $email->setStatus(SpoolStatus::STATUS_READY);
                    $this->updateEmail($email);
                    dump($e->getMessage());
                }
            }
            $email->setStatus(SpoolStatus::STATUS_COMPLETE);

            $this->updateEmail($email);

            if ($this->getTimeLimit() && (time() - $time) >= $this->getTimeLimit())
            {
                break;
            }
        }
        return $count;
    }

    public function updateEmail($email)
    {
        $this->manager->persist($email);
        $this->manager->flush();
    }

    public function setPauseTime($pauseTime)
    {
        $this->pauseTime = $pauseTime;
    }

}
