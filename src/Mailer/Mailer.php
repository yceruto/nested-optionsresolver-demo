<?php

namespace App\Mailer;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\NullLogger;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Stopwatch\Stopwatch;

class Mailer
{
    private $options;
    private $logger;

    public function __construct(array $options = [])
    {
        $resolver = new OptionsResolver();
        $resolver
            ->setDefaults([
                'transport' => null,
                'spool' => function (OptionsResolver $spoolResolver) {
                    $spoolResolver
                        ->setDefined(['type', 'path'])
                        ->setAllowedTypes('path', 'string')
                        ->setAllowedValues('type', ['file', 'memory']);
                },
                'sendmail' => function (OptionsResolver $sendmailResolver) {
                    $sendmailResolver
                        ->setDefined('command')
                        ->setAllowedTypes('command', 'string');
                },
                'smtp' => function (OptionsResolver $smtpResolver, Options $parent) {
                    $smtpResolver
                        ->setDefined(['username', 'password'])
                        ->setDefaults([
                            'host' => 'localhost',
                            'port' => function (Options $options) {
                                return 'ssl' === $options['encryption'] ? 456 : 25;
                            },
                            'encryption' => null,
                            'profiling' => $parent['logging'],
                            'timeout' => 30,
                        ])
                        ->setAllowedTypes('username', 'string')
                        ->setAllowedTypes('password', 'string')
                        ->setAllowedTypes('host', 'string')
                        ->setAllowedTypes('port', ['null', 'int'])
                        ->setAllowedValues('encryption', [null, 'ssl', 'tls'])
                        ->setAllowedTypes('profiling', 'bool');
                },
                'logging' => true,
            ])
            ->setAllowedValues('transport', [null, 'spool', 'sendmail', 'smtp'])
            ->setAllowedTypes('logging', 'bool')
        ;

        $this->configureOptions($resolver);

        $this->options = $resolver->resolve($options);

        if ($this->options['logging']) {
            $this->logger = new Logger('mailer');
            $this->logger->pushHandler(new StreamHandler(\dirname(__DIR__, 2).'/var/log/mailer.log'));
        } else {
            $this->logger = new NullLogger();
        }
    }

    public function sendMail($from, $to, string $subject, string $body): int
    {
        switch ($this->options['transport']) {
            case 'spool':
                if ('file' === $this->options['spool']['type']) {
                    $spool = new \Swift_FileSpool($this->options['spool']['path']);
                } else {
                    $spool = new \Swift_MemorySpool();
                }
                $transport = new \Swift_SpoolTransport($spool);

                $this->logger->debug('Sending email using "Spool" transport', $this->options['spool']);
                break;
            case 'sendmail':
                $transport = new \Swift_SendmailTransport();
                $transport->setCommand($this->options['sendmail']['command']);

                $this->logger->debug('Sending email using "Sendmail" transport', $this->options['sendmail']);
                break;
            case 'smtp':
                $transport = new \Swift_SmtpTransport();
                $transport->setHost($this->options['smtp']['host']);
                $transport->setPort($this->options['smtp']['port']);
                $transport->setEncryption($this->options['smtp']['encryption']);
                $transport->setUsername($this->options['smtp']['username']);
                $transport->setPassword($this->options['smtp']['password']);
                $transport->setTimeout($this->options['smtp']['timeout']);

                $this->logger->debug('Sending email using "SMTP" transport', $this->options['smtp']);

                if ($this->options['smtp']['profiling']) {
                    $stopwatch = new Stopwatch(true);
                    $stopwatch->start('smtp', 'transport');
                }
                break;
            default:
                $transport = new \Swift_NullTransport();
                $this->logger->debug('Sending email using "Null" transport');
        }

        $result = $transport->send((new \Swift_Message())
            ->setFrom($from)
            ->setTo($to)
            ->setSubject($subject)
            ->setBody($body))
        ;

        if ('smtp' === $this->options['transport'] && $this->options['smtp']['profiling']) {
            $this->logger->debug(sprintf('SMTP profiling %s', $stopwatch->stop('smtp')));
        }

        return $result;
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
    }
}
