<?php

namespace Librinfo\EmailBundle\Entity\OuterExtension;

trait HasEmail
{
    /**
     * @var Email
     */
    private $email;

    /**
     * Get email
     *
     * @return Email
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set email
     *
     * @param object $email
     *
     * @return Object
     */
    public function setEmail($email)
    {
        $this->email = $email;
        return $this;
    }

}