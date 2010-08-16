<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
@require('../../../wp-config.php');

$email = $_POST['dotmailer_email'];

    require_once('DotMailerConnect.php');
    
    $mailObj = new DotMailerConnect( get_option( 'dotmailer_username') ,get_option('dotmailer_password') );
    
    $result = $mailObj->GetContactByEmail($email);

    if( $result == false  )
    {
            $result = $mailObj->AddContactToAddressBook($email, get_option('dotmailer_address_book'));
            if( $resutl !== FALSE )
            {
                echo 'Thank you for registering to our newsletter.';
            }
    }
    else
    {
        echo 'This email is already registered.';
    }




?>
