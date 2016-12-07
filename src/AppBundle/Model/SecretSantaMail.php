<?php
namespace AppBundle\Model;

use Symfony\Component\Config\Definition\Exception\Exception;

class SecretSantaMail {

    private $participants = [];

    private $item_value = 5;
    private $mail_from = 'Santa < santa@yourdomain.com >';
    private $mail_title = 'Secret Santa';

    private $sent_emails = array();

    /**
     * Run
     * runs the secret santa script on an array of users.
     * Everyone is assigned their secret santa and emailed with who they need to buy for.
     * @return bool
     */
    public function send(){

        if(!$this->validate()){

        }
        $receivers = $this->shuffle();

        $this->sendEmails($matched);


        return true;
    }

    public function addUsers($newUsers) {

        foreach ($newUsers as $user) {
            if(array_key_exists($user['email'], $this->participants)){
                continue;
            }
            $this->participants[$user['email']] = $user['name'];
        }

    }

    /**
     * Validate recipients array
     */
    private function validate(){

        if(count($this->participants) < 2) {
            return false;
        }

        return true;
    }

    public function setTitle($title){
        $this->mail_title = $title;
    }

    public function setAmount($price){
        $this->item_value = $price;
    }

    public function setFrom($name,$email){
        $this->mail_from = "{$name} < {$email} >";
    }

    private function shuffle(){

        $givers     = $this->participants;
        $receavers  = $users_array;

        //Foreach giver
        foreach($givers as $uid => $user){

            $not_assigned = true;

            //While a user hasn't been assigned their secret santa
            while($not_assigned){
                //Randomly pick a person for the user to buy for
                $choice = rand(0, sizeof($receavers)-1);
                //If randomly picked user is NOT themselves
                if($user['email'] !== $receavers[$choice]['email']){
                    //Assign the user the randomly picked user
                    $givers[$uid]['giving_to'] = $receavers[$choice];
                    //And remove them from the list
                    unset($receavers[$choice]);
                    //Correct array
                    $receavers = array_values($receavers);
                    //exit loop
                    $not_assigned = false;
                }else{
                    //If we are the laster user left and have been given ourselfs
                    if(sizeof($receavers) == 1){
                        //Swap with someone else (in this case the first guy who got assigned.
                        //Steal first persons, person and give self to them.
                        $givers[$uid]['giving_to'] = $givers[0]['giving_to'];
                        $givers[0]['giving_to'] = $givers[$uid];
                        $not_assigned = false;
                    }
                }
            }
        }
        //Return array of matched users
        return $givers;
    }

    /**
     * Send Emails
     * Emails all matched users with details of who they should be buying for.
     * @param $matched users
     */
    private function sendEmails($assigned_users){
        //For each user
        foreach($assigned_users as $giver){
            //Send the following email
            $email_body = "Hello {$giver['name']}, 
				For Secret Santa this year you will be buying a present for {$giver['giving_to']['name']} ({$giver['giving_to']['email']})

				Presents should all be around £{$this->item_value},

				Good luck and Merry Christmas,
				Santa
				";
            //Log that its sent
            $this->sent_emails[] = $giver['email'];
            //Send em via normal PHP mail method
            mail($giver['email'], $this->mail_title, $email_body, "From: {$this->mail_from}\r\n");
        }
    }

    /**
     * Get Sent Emails
     * Return the list of emails that have been sent via the script
     * @return Array of emails
     */
    public function getSentEmails(){
        return $this->sent_emails;
    }
}