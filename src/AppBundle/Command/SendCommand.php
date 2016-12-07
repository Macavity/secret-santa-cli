<?php
namespace AppBundle\Command;

use AppBundle\Model\SecretSantaMail;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Psr\Log\LoggerInterface;

class SendCommand extends ContainerAwareCommand
{
    private $testMode = false;
    private $logger;

    private $santas = [];

    protected function configure()
    {
        $this->setName('app:send-santa-mails')
            ->setDescription('Sends mails to secret santas')
            ->addOption('test', null, InputOption::VALUE_NONE, "Don't send any E-Mails")
            ->addOption('file', 'f', null, "Specify an import config file in yaml format")
            ->addOption(
                'user',
                'u',
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL,
                "Specify multiple users seperated by colon: -u user1:email1 -u user2:email2..",
                []);

    }


    protected function initialize (InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        $this->logger = $this->getContainer()->get('logger');
        $this->testMode = $input->getOption('test');


    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln([
            'Secret Santa Mail Service',
            '=========================',
            ''
        ]);

        $inputFile = $input->getOption('file');
        $inputUsers = $input->getOption('user');

        $santaMail = new SecretSantaMail();

        if(!empty($inputFile)) {
            // TODO
        }
        if(!empty($inputUsers)){

            foreach ($inputUsers as $index => $userRow) {
                $user = explode(':', $userRow);
                $santaMail->addUser($user[0], $user[1]);
            }

        }

        if($santaMail->validate()) {

            $santaMail->shuffle($output);
            
            $participants = $santaMail->getParticipants();

            
            foreach ($participants as $santa) {
                $mail = \Swift_Message::newInstance()
                    ->setSubject($this->getContainer()->getParameter('mail_title'))
                    ->setFrom(
                        $this->getContainer()->getParameter('mail_from'),
                        $this->getContainer()->getParameter('mail_from_name'))
                    ->setTo($santa['email'], $santa['name'])
                    ->setBody(
                        $this->renderView('emails/santa.twig', array(
                            'name' => $santa['name'],
                            'target' => $santa['recipient']['name']
                        ))
                    );
            }
            


            $output->writeln(print_r($santaMail->getParticipants(), true));
        }
    }
}