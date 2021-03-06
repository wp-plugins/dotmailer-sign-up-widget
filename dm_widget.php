<?php

class DM_Widget extends WP_Widget {

    function DM_Widget() {
        $widget_ops = array('classname' => 'dm_widget', 'description' => __('Put a signup form on your WordPress Website', 'dm_widget'));
        $control_ops = array('id_base' => 'dm_widget');
        $this->WP_Widget('dm_widget', __('dotmailer Sign-up Form', 'dm_widget'), $widget_ops, $control_ops);
    }

    function widget($args, $instance) {
        //***********

        $showtitle = 1;
        $showdesc = 1;

		extract($args);

        $creds = get_option('dm_API_credentials');
        $msgs = get_option('dm_API_messages');
        if (empty($creds)) {
            ?>
            <div>
                <?php echo "<p class='error_message'>The dotmailer sign-up plugin cannot be activated. Please use dotmailer settings from your admin area to customise your form.</p>"; ?>
            </div>
            <?php
            return;
        }
        if (empty($msgs)) {
            ?>
            <div>
                <?php echo "<p class='error_message'>The dotmailer sign-up plugin cannot be activated. No messages have been set up.Please use the messages tab to set them up.</p>"; ?>
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
				
				$option = get_option( 'dm_redirections' );
				$redirect = NULL;
				if ( array_key_exists('page', $option) ) $redirect = get_permalink( $option["page"] );
				if ( array_key_exists('url', $option) ) $redirect = $option["url"];
				
				if ( $redirection != NULL ) $redirect = $redirection;
			
                if (!in_array(FALSE, $result)) {
                    $failure_message = "<p class='success'>{$form_success_message}</p>";
                    if ( $redirect != NULL ) $failure_message .= '<input type="hidden" name="dotMailer_redir" id="dotMailer_redir" value="'. $redirect .'" />
					';
                } else {
                    $success_message = "<p class='error_message'>{$form_failure_message}.</p>";
                }
            }
        }
        ?> 
        <?php /*?>PG FIX - Unclosed div tag<?php */?>
        <?php /*?><div><?php */?>
            <?php
            $messages_options = get_option('dm_API_messages');

            if (isset($messages_options)) {
                $form_header = $messages_options['dm_API_form_title'];
            }

            echo $before_widget;

			// Display the widget title 
            if ($form_header && $showtitle)
                echo $before_title . $form_header . $after_title;
            ?> 

            <form class="dotMailer_news_letter" style="margin:5px 0 10px 0;" method="post" action ="<?php the_permalink(); ?>" >
                <?php if ($showdesc) echo '<p>Please complete the fields below:</p>'; ?>
                <label for="dotMailer_email">Your email address*:</label><br>
                <input class="email" type="text" id="dotMailer_email" name="dotMailer_email" /><br>
                <?php
                if (isset($formErrors['email_invalid'])) {
                    echo "<p class='error_message'>" . $formErrors['email_invalid'] . "</p>";
                }
                ?>
                <?php
                if (get_option('dm_API_data_fields') != "") {
                    $dmdatafields = get_option('dm_API_data_fields');

					foreach ($dmdatafields as $key => $value) {
						writeFormLine($value['type'], $value['name'], $value['label'], $value['isRequired']);
						if (isset($formErrors[$value['name']])) {
							echo "<p class='error_message'>" . $formErrors[$value['name']] . "</p>";
						}
					}
				}

                $dmaddressbooks = get_option('dm_API_address_books');

                if (empty($dmaddressbooks)) {
                    writeFormBooks(-1, "All contacts", "");
                } elseif (count($dmaddressbooks) == 1 || checkBooksVisibility($dmaddressbooks)) {

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
                <?php ?>
            </div>






            <?php
            echo $after_widget;



            //***********
        }

    } 