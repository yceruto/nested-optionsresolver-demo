<?php

namespace App\Mailer;

use Symfony\Component\OptionsResolver\OptionsResolver;

class NullMailer extends Mailer
{
    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setAllowedValues('transport', null)
            ->remove(['spool', 'sendmail', 'smtp'])
        ;
    }
}
