<?php

namespace App\Mailer;

use Symfony\Component\OptionsResolver\OptionsResolver;

class SendmailMailer extends Mailer
{
    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'transport' => 'sendmail',
                'sendmail' => function (OptionsResolver $sendmailResolver) {
                    $sendmailResolver->setDefault('command', '/usr/sbin/sendmail -bs');
                },
            ])
            ->setAllowedValues('transport', 'sendmail')
            ->remove(['spool', 'smtp'])
        ;
    }
}
