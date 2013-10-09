<?php
/*
  Plugin Name: dotMailer Sign-up Form
  Plugin URI:  http://www.dotmailer.co.uk/api/prebuilt_integrations/wordpress.aspx
  Description: Add a "Subscribe to Newsletter" widget to your WordPress powered website that will insert your contact in one of your dotMailer address book.
  Version: 2.1
  Author: Ben Staveley
  Author URI: http://www.dotmailer.co.uk/
 */


/*  Copyright 2013  dotMailer (email : support@dotMailer.co.uk)

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation; either version 2 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

require_once ( plugin_dir_path(__FILE__) . 'functions.php' );
require_once ( plugin_dir_path(__FILE__) . 'dm_widget.php' );
register_uninstall_hook(__FILE__, "dotMailer_widget_uninstall");
register_deactivation_hook(__FILE__, 'dotMailer_widget_deactivate');

function dotMailer_widget_deactivate() {
    delete_option('dm_API_credentials');
    delete_option('dm_API_messages');
    delete_option('dm_API_address_books');
    delete_option('dm_API_data_fields');
}

function dotMailer_widget_install() {
    add_option('dm_API_credentials', "", "");
    add_option('dm_API_messages', "", "");
    add_option('dm_API_address_books', "", "");
    add_option('dm_API_data_fields', "", "");
}

function dotMailer_widget_uninstall() {
    delete_option('dm_API_credentials');
    delete_option('dm_API_messages');
    delete_option('dm_API_address_books');
    delete_option('dm_API_data_fields');
}

add_action('admin_enqueue_scripts', 'settings_head_scripts');
add_action('wp_enqueue_scripts', 'widget_head');
add_action('widgets_init', 'register_my_widget');

function register_my_widget() {
    register_widget('DM_Widget');
}

function widget_head() {

    wp_enqueue_script('jquerysc', "http://code.jquery.com/jquery-1.9.0.js");
    wp_enqueue_script('widgetUI', "http://code.jquery.com/ui/1.10.0/jquery-ui.js");
    wp_enqueue_script('widgetjs', plugins_url("/js/widget.js", ( __FILE__)));
    wp_register_style('widgetCss', "http://code.jquery.com/ui/1.10.0/themes/base/jquery-ui.css");
    wp_register_style('main', plugins_url("/css/dotmailer.css", ( __FILE__)));
    wp_enqueue_style('main');
    wp_enqueue_style('widgetCss');
}

function settings_head_scripts() {
    wp_enqueue_script('jquery');
    wp_enqueue_script('pickerUI', plugins_url("/js/jquery-ui-1.10.3.custom.min.js", ( __FILE__)));
    wp_enqueue_script('adminheadjs', plugins_url("/js/adminheadjs.js", ( __FILE__)));
    wp_register_style('main', plugins_url("/css/dotmailer.css", ( __FILE__)));
    wp_register_style('pickerCss', plugins_url("/css/jquery-ui-1.8.24.custom.css", ( __FILE__)));
    wp_register_style('adminCss', plugins_url("/css/admin.css", ( __FILE__)));
    wp_enqueue_style('main');
    wp_enqueue_style('adminCss');
}

function dm_create_menu_page() {

    add_menu_page(
            'dotMailer Sign-up Form Options', // The title to be displayed on the corresponding page for this menu
            'dotMailer', // The text to be displayed for this actual menu item
            'manage_options', // Which type of users can see this menu
            'dm_form_settings', // The unique ID - that is, the slug - for this menu item
            'dm_settings_menu_display', // The name of the function to call when rendering the menu for this page
            ''
    );
}

// end dm_create_menu_page

function manage_dm_newsletter() {
    $creds = get_option('dm_API_credentials');
    $msgs = get_option('dm_API_messages');
    if (empty($creds)) {
        ?>
        <div>
        <?php echo "<p class='error_message'>The dotMailer sign-up plugin cannot be activated.Please use dotMailer settings from your admin area to customise your form.</p>"; ?>
        </div>
            <?php
            return;
        }
        if (empty($msgs)) {
            ?>
        <div>
        <?php echo "<p class='error_message'>The dotMailer sign-up plugin cannot be activated.No messages have been set up.Please use the messages tab to set them up.</p>"; ?>
        </div>
            <?php
            return;
        }
        require_once 'DotMailerConnect.php';
        $messages_option = get_option('dm_API_messages');
        if (isset($messages_option)) {

            $email_invalid_error = $messages_option['dm_API_invalid_email'];
            $form_success_message = $messages_option['dm_API_success_message'];
            $form_failure_message = $messages_option['dm_API_failure_message'];
            $form_subscribe_button = $messages_option['dm_API_subs_button'];
            $nobook_message = $messages_option['dm_API_nobook_message'];
        }

        //Form submitted
        if (isset($_POST['dm_submit_btn'])) {

            $formErrors = array();

            if (!isset($_POST['books'])) {

                $formErrors['no_newsletters'] = $nobook_message;
            }

            if (isset($_POST['dotMailer_email'])) {

                $email = clean($_POST['dotMailer_email']);
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $formErrors['email_invalid'] = $email_invalid_error;
                }

                if (isset($_POST['datafields'])) {
                    $dataFields_posted = $_POST['datafields'];


                    $required = returnRequiredFields($dataFields_posted);
                    if (isset($required)) {
                        $validation_errors = validateRequiredFields($required);

                        if ($validation_errors != NULL) {
                            foreach ($validation_errors as $field => $validation_error) {
                                $formErrors[$field] = $validation_error;
                            }
                        }
                    }
                } else {
                    $dataFields_posted = array();
                }
            }


            if (empty($formErrors)) {

                $books = $_POST['books'];

                $email = clean($_POST['dotMailer_email']);

                if (!empty($dataFields_posted)) {
                    foreach ($dataFields_posted as $fieldName => $field) {
                        $variable = createDataFields($field[0], $field[1]);
                        $add_keys[] = $fieldName;
                        $add_values[] = $variable;
                    }
                    $valuesArray = new stdClass();
                    $valuesArray->Keys = $add_keys;
                    $valuesArray->Values = $add_values;
                }


                $dm_api_credentials = get_option('dm_API_credentials');


                $api = new DotMailerConnect($dm_api_credentials['dm_API_username'], $dm_api_credentials['dm_API_password']);
                //check contact status first

                $contact_status = $api->getStatusByEmail($email)->GetContactStatusByEmailResult;

                $suppressed_statuses = array("UnSubscribed", "HardBounced", "Suppressed");

                if ($contact_status !== FALSE) {//contact already exists
                    if (in_array($contact_status, $suppressed_statuses)) {
                        //attempt to re-subscribe
                        foreach ($books as $book) {
                            if (isset($valuesArray)) {
                                $result[] = $api->reSubscribeContact($email, $book, $valuesArray);
                            } else {
                                $result[] = $api->reSubscribeContact($email, $book);
                            }
                        }
                    } else {
                        //attempt to update
                        foreach ($books as $book) {
                            if (isset($valuesArray)) {
                                $result[] = $api->AddContactToAddressBook($email, $book, $valuesArray);
                            } else {
                                $result[] = $api->AddContactToAddressBook($email, $book);
                            }
                        }
                    }
                } else {
                    //attempt to subscribe
                    if (isset($valuesArray)) {
                        $result[] = $api->AddContactToAddressBook($email, $book, $valuesArray);
                    } else {
                        $result[] = $api->AddContactToAddressBook($email, $book);
                    }
                }

                if (!in_array(FALSE, $result)) {
                    $failure_message = "<p class='success'>{$form_success_message}</p>";
                } else {
                    $success_message = "<p class='error_message'>{$form_failure_message}.</p>";
                }
            }
        }
        ?> 
    <div>
    <?php
    $messages_options = get_option('dm_API_messages');

    if (isset($messages_options)) {
        $form_header = $messages_options['dm_API_form_title'];
    }
    ?> 


        <h2 style="font-weight: bold; font-size: 1.1em;" class="widgettitle" ><?php echo $form_header; ?></h2>


        <form id="dotMailer_news_letter"  style="margin:5px 0 10px 0;" method="post" action =" <?php echo $_SERVER['PHP_SELF']; ?>" >
            <p>Please complete the fields below:</p>
            <label for="dotMailer_email">Your email address*:</label></br>
            <input class="email" type="text" id="dotMailer_email" name="dotMailer_email" /> </br>
    <?php
    if (isset($formErrors['email_invalid'])) {
        echo "<p class='error_message'>" . $formErrors['email_invalid'] . "</p>";
    }
    ?>
            <?php
            if (get_option('dm_API_data_fields') != "")
                $dmdatafields = get_option('dm_API_data_fields');

            foreach ($dmdatafields as $key => $value) {
                writeFormLine($value['type'], $value['name'], $value['label'], $value['isRequired']);
                if (isset($formErrors[$value['name']])) {
                    echo "<p class='error_message'>" . $formErrors[$value['name']] . "</p>";
                }
            }




            $dmaddressbooks = get_option('dm_API_address_books');
            if (empty($dmaddressbooks)) {
                writeFormBooks(-1, "All contacts", "");
            } elseif (count($dmaddressbooks) == 1) {

                foreach ($dmaddressbooks as $key => $value) {
                    writeFormBooks($value['id'], $value['label'], "");
                }
            } else {
                echo( "<p style='margin:10px 0 10px 0 ; font-weight:bold;'>Subscribe to:</p>");
                foreach ($dmaddressbooks as $key => $value) {
                    writeFormBooks($value['id'], $value['label'], $value['isVisible']);
                }
            }

            if (isset($formErrors['no_newsletters'])) {

                echo "<p class='error_message'>" . $formErrors['no_newsletters'] . "</p>";
            }
            ?>

            <input type="submit"  name="dm_submit_btn" value="<?php echo $form_subscribe_button; ?>" style="margin-top:5px;"/>


        </form>
        <div id="form_errors">
            <?php
            if (isset($failure_message)) {

                echo $failure_message;
            }
            if (isset($success_message)) {

                echo $success_message;
            }
            ?> 




            <?php
            ?>
        </div>






        <?php
    }

    add_action('admin_menu', 'dm_create_menu_page');
    add_action('admin_init', 'plugin_admin_init');

    function plugin_admin_init() {
        register_setting('dm_API_credentials', 'dm_API_credentials', 'dm_API_credentials_validate');
        register_setting('dm_API_messages', 'dm_API_messages', 'dm_API_messages_validate');
        register_setting('dm_API_address_books', 'dm_API_address_books', 'dm_API_books_validate');
        register_setting('dm_API_data_fields', 'dm_API_data_fields', 'dm_API_fields_validate');
        add_settings_section('credentials_section', 'Main settings', 'api_credentials_section', 'credentials_section');
        add_settings_section('messages_section', 'Message settings', 'api_messages_section', 'messages_section');
        add_settings_section('address_books_section', 'Address book settings', 'api_address_books_section', 'address_books_section');
        add_settings_section('data_fields_section', 'Contact data field settings', 'api_data_fields_section', 'data_fields_section');
        add_settings_field('dm_API_username', 'Your API username', 'dm_API_username_input', 'credentials_section', 'credentials_section');
        add_settings_field('dm_API_password', 'Your API password', 'dm_API_password_input', 'credentials_section', 'credentials_section');
        add_settings_field('dm_API_form_title', 'Form header', 'dm_API_form_title_input', 'messages_section', 'messages_section');
        add_settings_field('dm_API_invalid_email', 'Invalid email error message', 'dm_API_invalid_email_input', 'messages_section', 'messages_section');
        add_settings_field('dm_API_fill_required', 'Required field missing error message', 'dm_API_fill_required_input', 'messages_section', 'messages_section');
        add_settings_field('dm_API_success_message', 'Submission success message', 'dm_API_success_message_input', 'messages_section', 'messages_section');
        add_settings_field('dm_API_failure_message', 'Submission failure message', 'dm_API_failure_message_input', 'messages_section', 'messages_section');
        add_settings_field('dm_API_nobook_message', 'No newsletter selected message', 'dm_API_nobook_message_input', 'messages_section', 'messages_section');
        add_settings_field('dm_API_subs_button', 'Form subscribe button', 'dm_API_subs_button_input', 'messages_section', 'messages_section');
        add_settings_field('dm_API_address_books', '', 'dm_API_address_books_input', 'address_books_section', 'address_books_section');
        add_settings_field('dm_API_data_fields', '', 'dm_API_data_fields_input', 'data_fields_section', 'data_fields_section');
    }

    function api_credentials_section() {
        echo "<div class='inside'><h4>Change your API credentials:</h4>";
    }

    function api_messages_section() {
        echo "<div class='inside'><h4>Customise form messages:</h4>";
    }

    function api_address_books_section() {
        echo "<div class='inside'>";
    }

    function api_data_fields_section() {
        echo "<div class='inside'>";
    }

    function dm_API_form_title_input() {

        $options = get_option('dm_API_messages');
        if (isset($options['dm_API_form_title'])
        ) {
            echo "<input id='dm_form_title' name='dm_API_messages[dm_API_form_title]' size='40' type='text' value='{$options['dm_API_form_title']}' />";
        } else {
            echo "<input id='dm_form_title' name='dm_API_messages[dm_API_form_title]' size='40' type='text' value='Subscribe to our newsletter'  />";
        }
    }

    function dm_API_invalid_email_input() {

        $options = get_option('dm_API_messages');
        if (isset($options['dm_API_invalid_email'])
        ) {
            echo "<input id='dm_invalid_email' name='dm_API_messages[dm_API_invalid_email]' size='40' type='text' value='{$options['dm_API_invalid_email']}' />";
        } else {
            echo "<input id='dm_invalid_email' name='dm_API_messages[dm_API_invalid_email]' size='40' type='text' value = 'Please use a valid email address'   />";
        }
    }

    function dm_API_fill_required_input() {

        $options = get_option('dm_API_messages');
        if (isset($options['dm_API_fill_required'])
        ) {
            echo "<input id='dm_fill_required' name='dm_API_messages[dm_API_fill_required]' size='40' type='text' value='{$options['dm_API_fill_required']}' />";
        } else {
            echo "<input id='dm_fill_required' name='dm_API_messages[dm_API_fill_required]' size='40' type='text' value='Please fill all the required fields'  />";
        }
    }

    function dm_API_nobook_message_input() {

        $options = get_option('dm_API_messages');
        if (isset($options['dm_API_nobook_message'])
        ) {
            echo "<input id='dm_nobook_message' name='dm_API_messages[dm_API_nobook_message]' size='40' type='text' value='{$options['dm_API_nobook_message']}' />";
        } else {
            echo "<input id='dm_nobook_message' name='dm_API_messages[dm_API_nobook_message]' size='40' type='text' value='Please select one newsletter'  />";
        }
    }

    function dm_API_success_message_input() {

        $options = get_option('dm_API_messages');
        if (isset($options['dm_API_success_message'])
        ) {
            echo "<input id='dm_success_message' name='dm_API_messages[dm_API_success_message]' size='40' type='text' value='{$options['dm_API_success_message']}' />";
        } else {
            echo "<input id='dm_success_message' name='dm_API_messages[dm_API_success_message]' size='40' type='text' value='You have now subscribed to our newsletter'  />";
        }
    }

    function dm_API_failure_message_input() {

        $options = get_option('dm_API_messages');
        if (isset($options['dm_API_failure_message'])
        ) {
            echo "<input id='dm_failure_message' name='dm_API_messages[dm_API_failure_message]' size='40' type='text' value='{$options['dm_API_failure_message']}' />";
        } else {
            echo "<input id='dm_failure_message' name='dm_API_messages[dm_API_failure_message]' size='40' type='text' value='There was a problem signing you up.'  />";
        }
    }

    function dm_API_subs_button_input() {

        $options = get_option('dm_API_messages');
        if (isset($options['dm_API_subs_button'])
        ) {
            echo "<input  id='dm_subs_button' name='dm_API_messages[dm_API_subs_button]' size='40' type='text' value='{$options['dm_API_subs_button']}' />";
        } else {
            echo "<input  id='dm_subs_button' name='dm_API_messages[dm_API_subs_button]' size='40' type='text' value='Subscribe'  />";
        }
    }

    function dm_API_username_input() {
        $options = get_option('dm_API_credentials');
        if (isset($options['dm_API_username'])
        ) {
            echo "<input id='dm_username' name='dm_API_credentials[dm_API_username]' size='40' type='text' value='{$options['dm_API_username']}' />";
        } else {
            echo "<input id='dm_username' name='dm_API_credentials[dm_API_username]' size='40' type='text'  />";
        }
    }

    function dm_API_password_input() {
        $options = get_option('dm_API_credentials');
        if (isset($options['dm_API_password'])
        ) {
            echo "<input id='dm_password' name='dm_API_credentials[dm_API_password]' size='40' type='password' value='{$options['dm_API_password']}' />";
        } else {
            echo "<input id='dm_password' name='dm_API_credentials[dm_API_password]' size='40' type='password'  />";
        }
    }

    function dm_API_address_books_input() {


        if (isset($_SESSION['connection']) && $_SESSION['connection'] !== FALSE) {


            $dm_account_books = unserialize($_SESSION['dm_account_books']);
        }

        if (isset($_GET['order'])) {
            if ($_GET['order'] == 'asc') {
                uasort($dm_account_books, 'bookSortAsc');
                $neworder = "&order=desc";
            } elseif ($_GET['order'] == 'desc') {
                uasort($dm_account_books, 'bookSortDesc');
                $neworder = "&order=asc";
            }
        } else {

            $neworder = "&order=desc";
        }
        ?>
        <table class="wp-list-table widefat fixed " cellspacing="0">
            <thead>
                <tr>
                    <th scope="col"  class="manage-column column-cb check-column " style=""><input class="multiselector" type="checkbox"/></th>
                    <th scope="col" id="addressbook" class="manage-column column-addressbook sortable desc" style=""><a href="?page=dm_form_settings&tab=my_address_books<?php if (isset($neworder)) echo $neworder; ?>"><span>Address Books</span><span class="sorting-indicator"></span></a></th>
                    <th scope="col" id="changelabel" class="manage-column column-changelabel" style="">Change label</th>
                    <th scope="col" id="visible" class="manage-column column-visible" style="text-align: center;">Visible?</th>			
                </tr>
            </thead>
            <tfoot>
                <tr>
                    <th scope="col" id="cb" class="manage-column column-cb check-column" style=""><input class="multiselector" type="checkbox"/></th>
                    <th scope="col" id="addressbook" class="manage-column column-addressbook sortable desc" style=""><a href="?page=dm_form_settings&tab=my_address_books<?php if (isset($neworder)) echo $neworder; ?>"><span>Address Books</span><span class="sorting-indicator"></span></a></th>
                    <th scope="col" id="changelabel" class="manage-column column-changelabel" style="">Change label</th>
                    <th scope="col" id="visible" class="manage-column column-visible" style="text-align: center;">Visible?</th>		
                </tr>		
            </tfoot>
            <tbody id="the-list" class="sort_books">
                <?php
                $selected_books = get_option('dm_API_address_books');

                $indexes_to_replace = array();
                $elements_to_swap = array();
                //re-sort
                if (!empty($selected_books)) {
                    $swapped_array = array();
                    foreach ($dm_account_books as $account_book) {

                        if (in_array($account_book->Name, array_keys($selected_books))) {
                            $indexes_to_replace[] = array_search($account_book, $dm_account_books);
                            $elements_to_swap[] = $account_book;
                        }
                    }

                    foreach ($selected_books as $book_name => $book_details) {
                        foreach ($elements_to_swap as $index => $element) {

                            if ($book_name == $element->Name) {
                                $swapped_array[] = $element;
                            }
                        }
                    }

                    if (!empty($indexes_to_replace)) {
                        $new_order = array_combine($indexes_to_replace, $swapped_array);
//             echo "<pre>";
//         print_r($new_order);
//         echo "</pre>";
                        foreach ($new_order as $new_key => $element) {
                            $old_index = array_search($element, $dm_account_books);
                            $temp = $dm_account_books[$new_key];
                            $dm_account_books[$new_key] = $element;
                            $dm_account_books[$old_index] = $temp;
                        }
                    }
                }



                foreach ($dm_account_books as $account_book) {
                    $selected = "";
                    $label = "";
                    $visible = "";

                    if ($account_book->Name == "Test") {
                        continue;
                    }
                    if (!empty($selected_books)) {


                        if (in_array($account_book->Name, array_keys($selected_books))) {
                            $selected = " checked='checked'";




                            $book_values = $selected_books[$account_book->Name];

                            $label = $book_values['label'];
                            if ($book_values['isVisible'] == 'true') {

                                $visible = " checked='checked'";
                            }
                        }
                    }
                    ?>

                    <tr  id="<?php echo $account_book->ID ?>" class="dragger">
                        <th scope="row" id="cb" ><span class="handle" ><img src="<?php echo plugins_url('images/large.png', __FILE__) ?>" class="drag_image" /></span><input class="bookselector" type="checkbox" value="<?php echo $account_book->ID ?>" name="dm_API_address_books[<?php echo $account_book->Name ?>][id]" <?php echo $selected; ?>/></th>
                        <td class="addressbook column-addressbook"><strong><?php echo $account_book->Name ?></strong></td>
                        <td><input type="text" disabled="disabled" name="dm_API_address_books[<?php echo $account_book->Name ?>][label]" value ="<?php
            if (!empty($label)) {
                echo $label;
            } else {
                echo $account_book->Name;
            }
                    ?>"/></td>
                        <td class=""><input disabled="disabled" value="false" type="hidden" name="dm_API_address_books[<?php echo $account_book->Name ?>][isVisible]" />
                            <input value="true" type="checkbox" name="dm_API_address_books[<?php echo $account_book->Name ?>][isVisible]" disabled="disabled" <?php echo $visible; ?>/></td>


                    </tr>

        <?php
    }
    ?>
            </tbody>
        </table>


    <?php
}

function dm_API_data_fields_input() {


    if (isset($_SESSION['connection']) && $_SESSION['connection'] !== FALSE) {


        $dm_API_data_fields = unserialize($_SESSION['dm_data_fields']);
    }
    if (isset($_GET['order'])) {
        if ($_GET['order'] == 'asc') {
            uasort($dm_API_data_fields, 'bookSortAsc');
            $neworder = "&order=desc";
        } elseif ($_GET['order'] == 'desc') {
            uasort($dm_API_data_fields, 'bookSortDesc');
            $neworder = "&order=asc";
        }
    } else {

        $neworder = "&order=desc";
    }
    ?>
        <table class="wp-list-table widefat fixed " cellspacing="0">
            <thead>
            <th scope="col" id="cb" class="manage-column column-cb check-column " style=""><input class="multiselector" type="checkbox"/></th>
            <th scope="col" id="addressbook" class="manage-column column-addressbook sortable desc" style=""><a href="?page=dm_form_settings&tab=my_data_fields<?php if (isset($neworder)) echo $neworder; ?>"><span>Contact data fields</span><span class="sorting-indicator"></span></a></th>
            <th scope="col" id="changelabel" class="manage-column column-changelabel" style="">Change label</th>
            <th scope="col" id="visible" class="manage-column column-visible" style="text-align: center;">Required?</th>			

            </thead>
            <tfoot>
                <tr>
                    <th scope="col" id="cb" class="manage-column column-cb check-column " style=""><input class="multiselector" type="checkbox"/></th>
                    <th scope="col" id="addressbook" class="manage-column column-addressbook sortable desc" style=""><a href="?page=dm_form_settings&tab=my_data_fields<?php if (isset($neworder)) echo $neworder; ?>"><span>Contact data fields</span><span class="sorting-indicator"></span></a></th>
                    <th scope="col" id="changelabel" class="manage-column column-changelabel" style="">Change label</th>
                    <th scope="col" id="visible" class="manage-column column-visible" style="text-align: center;">Required?</th>			
                </tr>		
            </tfoot>
            <tbody id="the-list" class="sort_fields">


    <?php
    $selected_fields = get_option('dm_API_data_fields');

    //sorting data fields

    $indexes_to_replace = array();
    $elements_to_swap = array();
    //re-sort
    if (!empty($selected_fields)) {
        $swapped_array = array();
        foreach ($dm_API_data_fields as $data_field) {

            if (in_array($data_field->Name, array_keys($selected_fields))) {
                $indexes_to_replace[] = array_search($data_field, $dm_API_data_fields);
                $elements_to_swap[] = $data_field;
            }
        }

        foreach ($selected_fields as $field_name => $field_details) {
            foreach ($elements_to_swap as $index => $element) {

                if ($field_name == $element->Name) {
                    $swapped_array[] = $element;
                }
            }
        }

        if (!empty($indexes_to_replace)) {
            $new_order = array_combine($indexes_to_replace, $swapped_array);

            foreach ($new_order as $new_key => $element) {
                $old_index = array_search($element, $dm_API_data_fields);
                $temp = $dm_API_data_fields[$new_key];
                $dm_API_data_fields[$new_key] = $element;
                $dm_API_data_fields[$old_index] = $temp;
            }
        }
    }



    //end of sorting
    foreach ($dm_API_data_fields as $dm_API_data_field) {

        $selected = "";
        $label = "";
        $required = "";

        if (!empty($selected_fields)) {

            if (in_array($dm_API_data_field->Name, array_keys($selected_fields))) {
                $selected = " checked='checked'";

                $fields_values = $selected_fields[$dm_API_data_field->Name];

                $label = $fields_values['label'];
                if ($fields_values['isRequired'] == 'true') {

                    $required = " checked='checked'";
                }
            }
        }
        ?>
                    <tr id="<?php echo $dm_API_data_field->Name ?>" class="dragger">
                        <th  scope="row" ><span class="handle"><img src="<?php echo plugins_url('images/large.png', __FILE__) ?>" class="drag_image" /></span><input class="bookselector" type="checkbox" value="<?php echo $dm_API_data_field->Name ?>" name="dm_API_data_fields[<?php echo $dm_API_data_field->Name ?>][name]" <?php echo $selected; ?>/> </th>
                        <td><strong><?php echo $dm_API_data_field->Name ?></strong></td>
                        <td><input  size="50" type="text" disabled="disabled" name="dm_API_data_fields[<?php echo $dm_API_data_field->Name ?>][label]" value ="<?php
            if (!empty($label)) {
                echo $label;
            } else {
                echo ucwords(strtolower($dm_API_data_field->Name));
            }
        ?>" /></td>
                        <td class=""><input  disabled="disabled" value="false" type="hidden" name="dm_API_data_fields[<?php echo $dm_API_data_field->Name ?>][isRequired]"/>
                            <input value="true" type="checkbox" name="dm_API_data_fields[<?php echo $dm_API_data_field->Name ?>][isRequired]"  disabled="disabled" <?php echo $required; ?>/>
                            <input disabled="disabled" value="<?php echo $dm_API_data_field->Type ?>" type="hidden" name="dm_API_data_fields[<?php echo $dm_API_data_field->Name ?>][type]" /></td>


                    </tr>




                    <?php
                }
                ?>
            </tbody>
        </table>

    <?php
}

function dm_API_credentials_validate($input) {
    require_once ( plugin_dir_path(__FILE__) . 'DotMailerConnect.php');
    $options = get_option('dm_API_credentials');


    $submitted_API_username = trim($input['dm_API_username']);
    $submitted_API_password = trim($input['dm_API_password']);
    $connection = new DotMailerConnect($submitted_API_username, $submitted_API_password);
    $dm_account_books = $connection->listAddressBooks();

    if ($submitted_API_username == "" || $submitted_API_password == "") {
        add_settings_error('dm_API_credentials', 'dm_API_credentials_error', "Your API credentials cannot be empty");

        return $options;
    } elseif ($dm_account_books === FALSE) {
        add_settings_error('dm_API_credentials', 'dm_API_credentials_error', "Your API credentials seem to be invalid");
        return $options;
    } else {
        $options['dm_API_username'] = trim($input['dm_API_username']);
        $options['dm_API_password'] = trim($input['dm_API_password']);
    }





    return $options;
}

function dm_API_books_validate($input) {
    if (empty($input)) {
        return array();
    } else {
        return $input;
    }
}

function dm_API_fields_validate($input) {

    if (empty($input)) {
        return array();
    } else {
        return $input;
    }
}

function dm_API_messages_validate($input) {
    $options = get_option('dm_API_messages');
    foreach ($input as $input_field) {
        if (empty($input_field)) {
            add_settings_error('dm_API_messages', 'dm_API_messages_error', "Please fill all the fields");
            return $options;
        }
    }
    if (empty($input)) {
        return array();
    } else {
        return $input;
    }
}

//***************************************
//This will render the settings page    *
//***************************************
function dm_settings_menu_display() {

    $options = get_option('dm_API_credentials');
    if (isset($options)) {
        ob_start();
        require_once ( plugin_dir_path(__FILE__) . 'initialize.php');
        ob_end_clean();
        $connection = new DotMailerConnect($options['dm_API_username'], $options['dm_API_password']);
        $dm_account_books = $connection->listAddressBooks();
        $dm_data_fields = $connection->listDataFields();
        $account_info = $connection->getAccountDetails();
        $_SESSION['connection'] = serialize($connection);
        $_SESSION['dm_account_books'] = serialize($dm_account_books);
        $_SESSION['dm_data_fields'] = serialize($dm_data_fields);
    }
    ?>
        <style> 
            #namediv input.bookselector[type="checkbox"] {width: auto !important;} 
        </style> 

        <div class="wrap">  

            <img src="<?php echo plugins_url("/images/dmtarget.png", ( __FILE__)) ?>" alt="dotMailer" style="float:left; margin: 0 10px 0 10px; padding:9px 0px 4px 0;"  />
            <h2 style="padding:9px 15px 4px 0;">dotMailer Sign-up Form</h2>  
        <?php settings_errors(); ?>
        <?php
        $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'about_dm';
        ?>

            <h2 class='nav-tab-wrapper'>
                <a href='?page=dm_form_settings&tab=about_dm' class="nav-tab <?php echo $active_tab == 'about_dm' ? 'nav-tab-active' : ''; ?>">About</a>
                <a href='?page=dm_form_settings&tab=api_credentials' class="nav-tab <?php echo $active_tab == 'api_credentials' ? 'nav-tab-active' : ''; ?>">API credentials</a>
                <a href='?page=dm_form_settings&tab=my_address_books' class="nav-tab <?php echo $active_tab == 'my_address_books' ? 'nav-tab-active' : ''; ?>">My address books</a>
                <a href='?page=dm_form_settings&tab=my_data_fields' class="nav-tab <?php echo $active_tab == 'my_data_fields' ? 'nav-tab-active' : ''; ?>">My contact data fields</a>
                <a href='?page=dm_form_settings&tab=my_form_msg' class="nav-tab <?php echo $active_tab == 'my_form_msg' ? 'nav-tab-active' : ''; ?>">Messages</a>


            </h2>
        <?php
        if ($active_tab == 'my_address_books') {


            if ($dm_account_books) {
                ?> 

                    <div class="metabox-holder columns-2 newdmstyle" id="post-body">
                        <div id="post-body-content">
                            <div id="namediv" class="stuffbox">
                                <form action="options.php" method="post">
                    <?php settings_fields('dm_API_address_books'); ?>
                    <?php do_settings_sections('address_books_section'); ?>
                                    <p><a href="https://dotmailer.zendesk.com/entries/23228992-Using-dotMailer-WordPress-sign-up-form-plugin-v2#myaddbooks" target="_blank">Find out more...</a></p>



                            </div> 
                        </div>
                    </div>
                </div>
                <input name="Submit" type="submit" value="Save Changes" class="button-primary action">
                </form>  



                    <?php
                } else {
                    ?>
                <div class="metabox-holder columns-2 newdmstyle" id="post-body">
                    <table width="100%" cellspacing="0" cellpadding="0">
                        <tbody>
                            <tr valign="top">
                                <td>
                                    <div id="post-body-content">
                                        <div id="namediv" class="stuffbox">
                                            <h3>You're not up and running yet...</h3>
                                            <div class="inside">


                                                <p>Before you can use this tab, you need to enter your API credentials. See our <a href="https://dotmailer.zendesk.com/entries/23228992-Using-dotMailer-WordPress-sign-up-form-plugin-v2" target="_blank">user guide</a> on how to get started</p>
                                            </div>
                                        </div>						
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <?php
            }
        }

        if ($active_tab == 'my_data_fields') {




            if ($dm_data_fields) {
                ?> 
                <div class="metabox-holder columns-2 newdmstyle" id="post-body">
                    <div id="post-body-content">
                        <div id="namediv" class="stuffbox">
                            <form action="options.php" method="post">
            <?php settings_fields('dm_API_data_fields'); ?>
            <?php do_settings_sections('data_fields_section'); ?>
                                <p><a href="https://dotmailer.zendesk.com/entries/23228992-Using-dotMailer-WordPress-sign-up-form-plugin-v2#mycdf" target="_blank">Find out more...</a></p>
                        </div> 
                    </div>
                </div>
            </div>
            <input name="Submit" type="submit" value="Save Changes" class="button-primary action">
            </form>  
                <?php
            } else {
                ?>
            <div class="metabox-holder columns-2 newdmstyle" id="post-body">
                <table width="100%" cellspacing="0" cellpadding="0">
                    <tbody>
                        <tr valign="top">
                            <td>
                                <div id="post-body-content">
                                    <div id="namediv" class="stuffbox">
                                        <h3>You're not up and running yet...</h3>
                                        <div class="inside">


                                            <p>Before you can use this tab, you need to enter your API credentials. See our <a href="https://dotmailer.zendesk.com/entries/23228992-Using-dotMailer-WordPress-sign-up-form-plugin-v2" target="_blank">user guide</a> on how to get started</p>
                                        </div>
                                    </div>						
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <?php
        }
    }

    if ($active_tab == 'about_dm') {
        ?>

        <div class="metabox-holder columns-2 newdmstyle" id="post-body">
            <table width="100%" cellspacing="0" cellpadding="0">
                <tbody>
                    <tr valign="top">
                        <td>
                            <div id="post-body-content">
                                <div id="namediv" class="stuffbox">
                                    <h3>What it does</h3>
                                    <div class="inside">
                                        <p>Capture the email addresses of visitors and put them in your dotMailer address books. You can also collect contact data
                                            field information, too.</p>



                                        <b>What’s new in version 2.0:</b>

                                        <ul style="list-style-type: circle; list-style-position: inside;">
                                            <li>Put
                                                addresses into multiple address books</li>
                                            <li>Capture
                                                additional information to store in your contact data fields</li>
                                            <li>Reorder
                                                address books and contact data fields</li>
                                            <li>New look:
                                                native WordPress design with tabbed navigation</li>
                                            <li>New
                                                setup advice</li>
                                            <li>The plugin
                                                is now shown as ‘dotMailer’ in the WordPress left-hand menu</li>
                                        </ul>
                                    </div>
                                </div>						
                            </div>

                            <div id="post-body-content">
                                <div id="namediv" class="stuffbox">
                                    <h3>Setup advice</h3>
                                    <div class="inside">
                                        <p>To get you up and running, we have full setup
                                            instructions on the <a href="https://dotmailer.zendesk.com/entries/23228992-Using-dotMailer-WordPress-sign-up-form-plugin-v2" target="_blank">dotMailer knowledge base</a>.</p>
                                    </div>
                                </div>						
                            </div>


                        </td>
                        <td width="10"></td>
                        <td width="350">
                            <div class="postbox" id="linksubmitdiv">
                                <h3 style="cursor: default;">dotMailer</h3>
                                <div class="inside">												
                                    <img src="<?php echo plugins_url("/images/dmlogo.png", ( __FILE__)) ?>" style="display:block; margin-bottom:10px; " alt="dotMailer" style="float:left;" /> <p>Powerful email marketing made easy -
                                        with the most intuitive, easy to use email marketing platform you will find.
                                        Grab yourself a free 30-day trial now from our website.&nbsp; Visit <a href="http://dotmailer.co.uk" target="_blank" >dotMailer.co.uk &gt;&gt;</a></p>
                                </div>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <?php
    }

    if ($active_tab == 'api_credentials') {
        ?>

        <div class="metabox-holder columns-2 newdmstyle" id="post-body">
            <div id="post-body-content">
                <div id="namediv" class="stuffbox">


                    <form action="options.php" method="post">

        <?php settings_fields('dm_API_credentials'); ?>

        <?php do_settings_sections('credentials_section'); ?>
                        <p><a href="https://dotmailer.zendesk.com/entries/23228992-Using-dotMailer-WordPress-sign-up-form-plugin-v2#api" target="_blank">Find out more...</a></p>
                </div> 
            </div>

        </div>

        </div>
        <input name="Submit" type="submit" value="Save Changes" class="button-secondary action">
        </form>

        <?php
        if ($account_info) {
            ?>

            <div class="metabox-holder columns-2 newdmstyle" id="post-body">
                <table width="100%" cellspacing="0" cellpadding="0">
                    <tbody>
                        <tr valign="top">
                            <td>
                                <div id="post-body-content">
                                    <div id="namediv" class="stuffbox">                    
                                        <h3>Account details</h3>
                                        <div class="inside">												
                            <?php
                            $acc_dets = array();
                            foreach ($account_info as $info) {
                                switch ($info->Name) {
                                    case "Name":
                                        $acc_dets['Name'] = $info->Value;
                                        break;
                                    case "MainEmail":
                                        $acc_dets['MainEmail'] = $info->Value;
                                        break;
                                    case "APICallsInLast24Hours":
                                        $acc_dets['APICallsInLast24Hours'] = $info->Value;
                                        break;
                                    case "APICallsRemaining":
                                        $acc_dets['APICallsRemaining'] = $info->Value;
                                        break;
                                }
                            }


                            echo "<p style='font-weight:bold;'>Account holder name:</p> {$acc_dets['Name']}";
                            echo "<p style='font-weight:bold;'>Main account email address:</p> {$acc_dets['MainEmail']}";
                            echo "<p style='font-weight:bold;'>API calls in last 24hrs:</p> {$acc_dets['APICallsInLast24Hours']}";
                            echo "<p style='font-weight:bold;'>API calls remaining:</p> {$acc_dets['APICallsRemaining']}";
                            ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
                                            <?php
                                        }
                                    }


                                    if ($active_tab == 'my_form_msg') {
                                        ?>    


        <div class="metabox-holder columns-2 newdmstyle" id="post-body">
            <div id="post-body-content">
                <div id="namediv" class="stuffbox" style="padding-bottom:10px;">
                    <form action="options.php" method="post">
                                        <?php settings_fields('dm_API_messages'); ?>
                                        <?php do_settings_sections('messages_section'); ?>
                        <p><a href="https://dotmailer.zendesk.com/entries/23228992-Using-dotMailer-WordPress-sign-up-form-plugin-v2#messages" target="_blank">Find out more...</a></p>
                </div> 
            </div>
        </div>
        </div>
        <input name="Submit" type="submit" value="Save Changes" class="button-primary action">
        </form>




        <?php
    }
    ?>



    </div>


    <?php
}
?>