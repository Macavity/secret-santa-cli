<?php
namespace AppBundle\Model;

use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Console\Output\OutputInterface;

class SecretSantaMail {

    private $participants = [];

    private $item_value = 5;
    private $mail_from = 'Santa < santa@yourdomain.com >';
    private $mail_title = 'Secret Santa';

    private $sent_emails = array();

    public function getParticipants ()
    {
        return $this->participants;
    }

    /**
     * Run
     * runs the secret santa script on an array of users.
     * Everyone is assigned their secret santa and emailed with who they need to buy for.
     * @return bool
     */
    public function send(){

        $this->validate();
        $this->shuffle();

        //$this->sendEmails($matched);


        return true;
    }

    public function addUser ($name, $email)
    {
        if($this->validateMail($email)) {
            $this->participants[$email] = [
                'name' => $name,
                'email' => $email,
                'recipient' => false,
            ];
        }
    }

    public function addUsers($newUsers)
    {
        foreach ($newUsers as $user) {
            $this->addUser($user['name'], $user['email']);
        }
    }

    private function validateMail($email)
    {
        if(array_key_exists($email, $this->participants)){
            return false;
        }
        return true;
    }

    /**
     * Validate recipients array
     */
    public function validate(){

        if(count($this->participants) < 2) {
            throw new \Exception("Not enough Participants");
        }

        return true;
    }

    public function shuffle(OutputInterface $output)
    {
        $leftOverReceivers  = $this->participants;


        foreach($this->participants as $userMail => $user){

            $output->writeln('Santa: '.$user['name']);

            $potentialReceivers = $leftOverReceivers;
            unset($potentialReceivers[$userMail]);

            if(count($potentialReceivers) === 0) {
                throw new \Exception("Bad Luck, try again");
            }

            $potentialReceivers = array_keys($potentialReceivers);

            $output->writeln([
                'potReceivers:' . join(', ', $potentialReceivers)
            ]);

            $target = array_rand($potentialReceivers);
            $targetMail = $potentialReceivers[$target];

            $output->writeln('target: '.$targetMail);

            unset($leftOverReceivers[$targetMail]);

            $this->participants[$userMail]['recipient'] = [
                'name' => $this->participants[$targetMail]['name'],
                'email' => $this->participants[$targetMail]['email'],

            ];
        }
    }
    
}