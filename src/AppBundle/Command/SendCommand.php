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

    /** @var $logger LoggerInterface */
    private $logger;

    private $santas = [];

    protected function configure()
    {
        $this->setName('app:send-santa-mails')
            ->setDescription('Sends mails to secret santas')
            ->addOption('test', null, InputOption::VALUE_NONE, "Don't send any E-Mails")
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
        $this->logger->info('--------------------------------------');
        $this->logger->info('Secret Santa Mail: SendCommand started');

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $output->writeln([
            'Secret Santa Mail Service',
            '=========================',
            ''
        ]);

        if($this->testMode) {
            $output->writeln('- Test Mode -');
            $this->logger->debug('Test Mode');
        }

        $configUsers = $this->getContainer()->getParameter('santas');

        $inputUsers = $input->getOption('user');

        $secretSantaMail = new SecretSantaMail($this->logger);

        if(!empty($configUsers)) {
            $this->logger->debug('Add Users from santas.yml');
            foreach ($configUsers as $name => $email) {
                $secretSantaMail->addUser($name, $email);
            }
        }
        if(!empty($inputUsers)){
            $this->logger->debug('Add Users from command line option');
            foreach ($inputUsers as $index => $userRow) {
                $user = explode(':', $userRow);
                $secretSantaMail->addUser($user[0], $user[1]);
            }

        }

        if($secretSantaMail->validate()) {

            $participants = $secretSantaMail->getParticipants();

            $output->writeln('We have ' . count($participants) . ' secret santas this year.');

            $secretSantaMail->shuffle($output);

            // Refresh data after shuffling
            $participants = $secretSantaMail->getParticipants();

            $mailer = $this->getContainer()->get('mailer');
            
            foreach ($participants as $santa) {

                $santaName = $santa['name'];
                $santaEmail = $santa['email'];
                $targetName = $santa['recipient']['name'];

                $mail = \Swift_Message::newInstance()
                    ->setSubject($this->getContainer()->getParameter('mail_title'))
                    ->setFrom(
                        $this->getContainer()->getParameter('mail_from'),
                        $this->getContainer()->getParameter('mail_from_name'))
                    ->setTo($santaEmail, $santaName)
                    ->setBody(
                        $this->getContainer()->get('templating')->render('emails/santa.html.twig', array(
                            'name' => $santaName,
                            'target_name' => $targetName,
                        )),
                         'text/html'
                    )
                    ->addPart(
                        $this->getContainer()->get('templating')->render(
                            'emails/santa.txt.twig',
                            array(
                                'name' => $santaName,
                                'target_name' => $targetName,
                            )
                        ),
                        'text/plain'
                    );

                if(!$this->testMode){
                    $this->logger->debug('Try to send mail to '.$santaEmail.'');

                    $mailer->send($mail);

                    $this->logger->info('Santa Mail for '.$santaName.' sent.');

                    $output->writeln('Santa Mail for '.$santaName.' sent.');
                }
                else {
                    $this->logger->info('(Test-Mode) No Santa Mail for '.$santaName.' (Target: '.$targetName.') sent.');

                    $output->writeln('(Test-Mode) No Santa Mail for '.$santaName.' (Target: '.$targetName.') sent.');
                }
            }

            $output->writeln("Done! Let's go shopping.");


            $this->logger->debug("Results: ", $participants);
        }
    }
}