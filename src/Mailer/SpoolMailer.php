<?php

namespace App\Mailer;

use Symfony\Component\OptionsResolver\OptionsResolver;

class SpoolMailer extends Mailer
{
    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'transport' => 'spool',
                'spool' => function (OptionsResolver $smtpResolver) {
                    $smtpResolver
                        ->setDefaults([
                            'type' => 'file',
                            'path' => \dirname(__DIR__, 2).'/var/swiftmailer/spool/',
                        ]);
                },
            ])
            ->setAllowedValues('transport', 'spool')
            ->remove(['sendmail', 'smtp'])
        ;
    }
}
