<?php

namespace App\Mailer;

use Symfony\Component\OptionsResolver\OptionsResolver;

class GoogleMailer extends Mailer
{
    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'transport' => 'smtp',
                'smtp' => function (OptionsResolver $smtpResolver) {
                    $smtpResolver
                        ->setRequired(['username', 'password'])
                        ->setDefaults([
                            'host' => 'smtp.gmail.com',
                            'port' => 587,
                            'encryption' => 'tls',
                        ]);
                },
            ])
            ->setAllowedValues('transport', 'smtp')
            ->remove(['spool', 'sendmail']);
    }
}
