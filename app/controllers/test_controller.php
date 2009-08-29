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
}
?>