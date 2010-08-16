<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class DotMailerConnect
{

    private $request_url = 'http://apiconnector.com/api.asmx?WSDL';
    private $username;
    private $password;
    private $client;

    function DotMailerConnect( $username = '', $password = '' )
    {
        if (strlen($username) > 0 && strlen($password) > 0)
        {
            $this->username = $username;
            $this->password = $password;
            $this->client = new SoapClient( $this->request_url );
        }
        else
        {
            return false;
        }

    }


    function ListAddressBooks()
    {

        $params = array( 'username' => $this->username , 'password' => $this->password );
        
        try 
        {
            $result = $this->client->ListAddressBooks( $params );
            return $result->ListAddressBooksResult->APIAddressBook;
        }
        Catch(Exception $ex )
        {
            return  false; 
        }
    }


    function AddContactToAddressBook( $email , $addressBookId )
    {
        $AudienceType = "B2C";
        $OptInType    = "Single";
        $EmailType    = "Html";

        $contact = array( 'Email' => $email ,"AudienceType" => $AudienceType,"OptInType" => $OptInType,
                          'EmailType' => $EmailType , "ID" => -1 );

        $params = array( 'username' => $this->username,
                         'password' => $this->password,
                         'contact'  => $contact,
                         'addressbookId' => $addressBookId
                       );

        try 
        {
            $result = $this->client->AddContactToAddressBook( $params );
            return $result; 
        }
        catch( Exception $ex )
        {
            return false; 
        }

        

    }

    function GetContactByEmail( $email )
    {
        $params = array( 'username' => $this->username, 'password' => $this->password , 'email' => $email  );
        
         try 
         {
            $result = $this->client->GetContactByEmail( $params );
            return $result; 
         }
         Catch( Exception $ex )
         {
             return false;
         }
        
    }


}

?>
