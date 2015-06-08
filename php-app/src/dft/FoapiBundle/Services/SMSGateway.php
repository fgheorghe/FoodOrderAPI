<?php
// TODO: Document.
namespace dft\FoapiBundle\Services;

use dft\FoapiBundle\Traits\ContainerAware;
use dft\FoapiBundle\Traits\Database;
use dft\FoapiBundle\Traits\Logger;
use Doctrine\DBAL\Connection;

require("/var/dft_lib/sms/SendScripts/IntelliSMS.php");


/**
 * Class SMSGateway
 * @package dft\FoapiBundle\Services
 */
class SMSGateway
{
    use ContainerAware;
    use Database;
    use Logger;

	private $SMSGatewayUsername;
	private $SMSGatewayPassword;

	public function setSMSUsername($username) {
		$this->SMSGatewayUsername = $username;
		return $this;
	}

	public function getSMSUsername() {
		return $this->SMSGatewayUsername;
	}

	public function setSMSGatewayPassword($password) {
		$this->SMSGatewayPassword = $password;
		return $this;
	}

	public function getSMSGatewayPassword() {
		return $this->SMSGatewayPassword;
	}

	private function constructOrderNotificationContent($order) {
		return "New online order:\n" .
			 "\nReference: " . $order["reference"] .
			 "\nName: " . $order["customer_name"] .
			 "\nPhone number: " . $order["customer_phone_number"] .
			 "\nPost code: " . $order["post_code"] .
			 "\nTotal: " . $order["final_price"] . "GBP" . 
			 "\n";
	}

	public function sendOrderNotificationSms($to, $order, $from = "DFT") {
		// As per: https://www.intellisms.co.uk/sms-gateway/php-sdk/sendmessage/
		$objIntelliSMS = new \IntelliSMS();
		$objIntelliSMS->Username = $this->getSMSUsername();
		$objIntelliSMS->Password = $this->getSMSGatewayPassword();

		$objIntelliSMS->SendMessage(
			$to,
			$this->constructOrderNotificationContent($order),
			$from
		);
	}
}
