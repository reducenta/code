<?php

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;

use PhpImap\Mailbox;

class GrabCommand extends Command
{

    protected static $defaultName = 'mail:grab';

    protected function configure()
    {
        $this
            ->setDescription('Составление списка email из отправленных');
    }

    const EMAIL = 'name@email.ru';
    const PASSWORD = '***';

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $mailbox_folder = 'Mails/' . self::EMAIL;

        if(!file_exists($mailbox_folder)){
            mkdir($mailbox_folder);
        }

        $mailbox = new Mailbox(
            '{imap.mail.ru:993/imap/ssl}Отправленные', // IMAP server and mailbox folder
            self::EMAIL, // Username for the before configured mailbox
            self::PASSWORD, // Password for the before configured username
            null, // Directory, where attachments will be saved (optional)
            'UTF-8', // Server encoding (optional)
            true, // Trim leading/ending whitespaces of IMAP path (optional)
            false // Attachment filename mode (optional; false = random filename; true = original filename)
        );


        try {
            $mailsIds = $mailbox->searchMailbox('SINCE "21 January 2022" BEFORE "21 June 2022"');
        } catch(PhpImap\Exceptions\ConnectionException $ex) {
            echo "IMAP connection failed: " . implode(",", $ex->getErrors('all'));
            die();
        }

        if(!$mailsIds) {
            die('Mailbox is empty');
        }

        $progressBar = new ProgressBar($output, count($mailsIds));

        foreach($mailsIds as $id){
            $headers = $mailbox->getMailHeader($id);

            foreach($headers->to as $email => $name){
                if(strpos($email, 'sovetapteka.ru') == false){
                    file_put_contents($mailbox_folder . '/list.txt', $email . PHP_EOL, FILE_APPEND);
                }
            }

            $progressBar->advance();
        }

        $progressBar->finish();

        return Command::SUCCESS;
    }

}