<?php
class TestController extends AppController {

	var $name = 'Test';

	/*
	 * Upgrades the plan
	 */
	private function _upgrade() {
		App::import('Vendor', 'paypal', array('file' => 'paypal'.DS.'Paypal.php'));
		$myPaypal = new Paypal();

		// Enable test mode if needed
		//$myPaypal->enableTestMode();

		// Specify your paypal email
		$paypal_email = 'my_random_email@yahoo.com';
		$myPaypal->addField('business', $paypal_email);

		// Specify the currency
		$myPaypal->addField('currency_code', 'USD');

		/*
		 * Can also use 'http://'.$_SERVER['SERVER_NAME'] or
		 * 'http://'.$_SERVER['HTTP_HOST'] in place of FULL_BASE_URL
		 */
		$my_base_url = FULL_BASE_URL.$this->base;

		// Specify the url where paypal will send the user on success/failure
		$myPaypal->addField('return', $my_base_url.'/upgradesuccess');
		$myPaypal->addField('cancel_return', $my_base_url.'/upgradecancel');

		// Specify the url where paypal will send the IPN
		$myPaypal->addField('notify_url', $my_base_url.'/invoices/process');

		//$myPaypal->addField('hosted_button_id', '5637548'); //Required for buttons that have been saved in PayPal accounts otherwise, not allowed.

		// Specify the product information
		$plan_name = 'Some Random Plan Name';
		$myPaypal->addField('item_name', htmlspecialchars($plan_name));
		//$myPaypal->addField('item_name', $plan_name);
		$myPaypal->addField('item_number', '001');
		$myPaypal->addField('currency_code', 'USD');  // USD also is the default

		// for 'Buy it now' transaction
		//$myPaypal->addField('amount', '29.99'); // 'Buy it Now' price of $29.99
		//$myPaypal->addField('cmd','_xclick'); // Button clicked was a Buy now button

		// for 'Subscription' transaction
		$myPaypal->addField('a3', '29.99'); // Regular subscription price of $29.99
		$myPaypal->addField('p3','1'); // Subscription duration. Specify an integer value in the allowable range for the units of duration that you specify with t3.
		$myPaypal->addField('t3','M'); // Regular subscription units of duration. M is for month

		// Recurring payments. subscription payments recur, default is 0 Subscription payments recur unless subscribers cancel their subscriptions before the end of the current billing cycle or you limit the number of times that payments recur with the value that you specify for srt.
		$myPaypal->addField('src','1');

		// Reattempt on failure. If a recurring payment fails, PayPal attempts to collect the payment two more times before canceling the subscription
		$myPaypal->addField('sra','1');

		// Recurring times. Number of times that subscription payments recur. Specify an integer above 1. Valid only if you specify src="1".(Dont think you should set this field)
		//$myPaypal->addField('srt','1');

		// Do not prompt payers to include a note. For Subscribe buttons, always include no_note and set it to 1.
		$myPaypal->addField('no_note','1');

		// Modification behavior. Allowable values: 0 – allows subscribers to only create new subscriptions, 1 – allows subscribers to modify their current subscriptions or sign up for new ones, 2 – allows subscribers to only modify their current subscriptions
		$myPaypal->addField('modify','0');

		$myPaypal->addField('cmd','_xclick-subscriptions'); // Button clicked was a Subscribe button

		// User-defined field which will be passed through the system and returned in your merchant payment notification email. This field will not be shown to your subscribers.
		//$myPaypal->addField('custom', 'gobbledegook');

		// submit the fields to paypal
		$myPaypal->submitPayment();
	}


	public function process_ipn() {
		App::import('Vendor', 'paypal', array('file' => 'paypal'.DS.'Paypal.php'));
		$myPaypal = new Paypal();
		$admin_email = 'put_admin_email_here@yahoo.com';

		// Log the IPN results
		$myPaypal->logIpn = TRUE;

		// Enable test mode if needed
		//$myPaypal->enableTestMode();

		// Specify your paypal email
		$paypal_email = 'my_random_email@yahoo.com';

		// Check validity
		if (! $myPaypal->validateIpn()) {
			// paypal transaction did not validate.
			$subject = 'Instant Payment did not validate';
			$body = "A Paypal instant payment failed to validate.\n";
			// send admin an email
		} else {
			// Assume everything is ok to begin with.
			$validity_check = 1;
			$subject = '';
			$body = '';

			// Paypal thinks the transaction is valid. Now we run our checks.
			if ($myPaypal->ipnData['test_ipn'] == 1) {
				if ($myPaypal->testMode) {
					$body .= "We are in TEST mode.\n";
					$subject .= 'TEST : ';
				} else {
					$body .= "WARNING. Payment was forged using an IPN simulator.\n";
					$subject .= 'FORGERY : ';
					$validity_check = 0;
				}
			}
			$subject .= 'Paypal Instant Payment Notification - ';
			$body .= "A Paypal instant payment notification was recieved.\n";
			$body .= "from user: ".$myPaypal->ipnData['payer_email']." on ".date('m/d/Y');
			$body .= " at ".date('g:i A')."\n\nDetails:\n";
			$body .= "Payment status=".$myPaypal->ipnData['payment_status']."\n";

			// case-insensitive string comparisons
			if (strcasecmp($myPaypal->ipnData['txn_type'],'recurring_payment')==0) {
				$paypal_payment_type = "Recurring payment received.\n";
			} else if (strcasecmp($myPaypal->ipnData['txn_type'],'recurring_payment_profile_created')==0) {
				$paypal_payment_type = "Recurring payment profile created.\n";
			} else if (strcasecmp($myPaypal->ipnData['txn_type'],'subscr_payment')==0) {
				$paypal_payment_type = "Subscription payment received.\n";
			} else if (strcasecmp($myPaypal->ipnData['txn_type'],'subscr_signup')==0) {
				$paypal_payment_type = "Subscription started.\n";
			} else if (strcasecmp($myPaypal->ipnData['txn_type'],'send_money')==0) {
				$paypal_payment_type = "Warning! Payment received; source is the Send Money tab on the PayPal website.\n";
			} else if (strcasecmp($myPaypal->ipnData['txn_type'],'subscr_cancel')==0) {
				$paypal_payment_type = "Subscription canceled.\n";
			} else if (strcasecmp($myPaypal->ipnData['txn_type'],'subscr_eot')==0) {
				$paypal_payment_type = "Subscription expired.\n";
			} else if (strcasecmp($myPaypal->ipnData['txn_type'],'subscr_failed')==0) {
				$paypal_payment_type = "Subscription signup failed.\n";
			} else if (strcasecmp($myPaypal->ipnData['txn_type'],'subscr_modify')==0) {
				$paypal_payment_type = "Subscription modified.\n";
			} else if (strcasecmp($myPaypal->ipnData['txn_type'],'virtual_terminal')==0) {
				$paypal_payment_type = "Payment received; source is Virtual Terminal.\n";
			} else if (strcasecmp($myPaypal->ipnData['txn_type'],'web_accept')==0) {
				$paypal_payment_type = "Payment received; source is a Buy Now, Donation, or Auction Smart Logos button.\n";
			} else {
				$paypal_payment_type = "Error. Invalid Transaction type.\n";
				$validity_check = 0;
			}
			$subject .= $paypal_payment_type;
			$body .= $paypal_payment_type;

			if (strcasecmp($myPaypal->ipnData['payment_status'],'completed')==0) {
				$paypal_payment_status = "Payment is complete.\n";
			} else if (strcasecmp($myPaypal->ipnData['payment_status'],'pending')==0) {
				$paypal_payment_status = "Payment is pending.\n";
			} else if (strcasecmp($myPaypal->ipnData['payment_status'],'denied')==0) {
				$paypal_payment_status = "Payment was denied.\n";
			} else {
				$paypal_payment_status = "Unknown payment status?!\n";
				$validity_check = 0;
			}
			$subject .= $paypal_payment_status;
			$body .= $paypal_payment_status;

			$body .= "Transaction ID: ".$myPaypal->ipnData['txn_id']."\n";
			$body .= "TODO: Make sure this txn_id is unique\n";

			$body .= "Recipient email=".$myPaypal->ipnData['receiver_email']."\n";
			if (strcasecmp($myPaypal->ipnData['receiver_email'], $paypal_email) == 0) {
				$body .= "Recipient email is valid.\n";
			} else {
				$body .= "Recipient email is INVALID.\n";
				$validity_check = 0;
			}

			$body .= "Item name=".$myPaypal->ipnData['item_name']."\n";
			if ($myPaypal->ipnData['item_name'] == $this->paid_lvl_1_plan_friendly_name) {
				$body .= "Item is correct\n";
			} else {
				$body .= "INCORRECT item.\n";
				$validity_check = 0;
			}
			$body .= "Payment amount =".$myPaypal->ipnData['mc_gross']."\n";
			if ($myPaypal->ipnData['mc_gross'] == $this->paid_lvl_1_plan_cost) {
				$body .= "Payment amount is correct\n";
			} else {
				$body .= "INCORRECT Payment amount.\n";
				$validity_check = 0;
			}
			$body .= "Custom fields=".$myPaypal->ipnData['custom']."\n";

			if ($validity_check == 1) {
				$body .= "Congratulations. A valid payment transaction was processed.\n";
				// send user an email saying his plan has been upgraded
			} else {
				$body .= "Sorry, there was something wrong with this transaction.\n";
			}
			foreach ($myPaypal->ipnData as $key => $value) {
				$body .= "\n$key: $value";
			}

			// send admin a dump of all the data
			mail($admin_email, $subject, $body);

			/*
			 * Save to the Invoices table
			 * Available Data is in the following format
			 *
			 * $myPaypal->ipnData['txn_type']
			 * $myPaypal->ipnData['mc_gross']
			 * $myPaypal->ipnData['mc_amount3']
			 * $myPaypal->ipnData['payment_status']
			 * $myPaypal->ipnData['payment_date']
			 * $myPaypal->ipnData['subscr_date']
			 * $myPaypal->ipnData['txn_id'];
			 * $myPaypal->ipnData['subscr_id'];
			 * $myPaypal->ipnData['payer_id'];
			 * $myPaypal->ipnData['payer_email'];
			 * $myPaypal->ipnData['first_name'];
			 * $myPaypal->ipnData['last_name'];
			 * $myPaypal->ipnData['address_name']
			 * $myPaypal->ipnData['address_street']
			 * $myPaypal->ipnData['address_city']
			 * $myPaypal->ipnData['address_state']
			 * $myPaypal->ipnData['address_zip']
			 * $myPaypal->ipnData['address_country']
			 * $myPaypal->ipnData['address_country_code']
			 * $myPaypal->ipnData['test_ipn']
			 *
			 * // Save to Invoices
			 */
		}
	}

}

?>