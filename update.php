<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
@require('../../../wp-config.php');
 require_once('DotMailerConnect.php'); 
$email = $_POST['dotMailer_email'];
if ($_POST['datafields'] != "")
{$datafields = $_POST['datafields'];  

       

    
    
    $mailObj = new DotMailerConnect( get_option( 'dotmailer_username') ,get_option('dotmailer_password') );
    
    $result = $mailObj->GetContactByEmail($email);

    if( $result === false  )
    {
            $result = $mailObj->AddContactToAddressBook($email, get_option('dotmailer_address_book'),$datafields);
            if( $result !== FALSE )
            {
                echo 'Thank you for registering to our newsletter.';
            }else{
                echo "There was a problem signing you up.";
               
            }
    }
    else
    {
        echo 'This email is already registered.';
    }
}else{
    
        $mailObj = new DotMailerConnect( get_option( 'dotmailer_username') ,get_option('dotmailer_password') );
    
    $result = $mailObj->GetContactByEmail($email);

    if( $result === false  )
    {
            $result = $mailObj->AddContactToAddressBook($email, get_option('dotmailer_address_book'));
            if( $result !== FALSE )
            {
                echo 'Thank you for registering to our newsletter.';
            }else{
                echo "There was a problem signing you up.";
            }
    }
    else
    {
        echo 'This email is already registered.';
    }
    
    
}



?>
