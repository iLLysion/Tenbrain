<?php

/** DoDirectPayment NVP
 *
 *  Process a credit card payment. 
*/

class Paypal_DoDirectPayment
{
	private $minMoneyAmount = 10;
	
	public function doDirectPayment(array $cc_info)
	{
		$cc_amount = $this->CheckMoney($cc_info['cc_amount']);
		$cc_number = $cc_info['number'];
		$cc_type = $cc_info['type'];
		$cc_month = $cc_info['month'];
		$cc_year = $cc_info['year'];
		$cc_cvv2 = $cc_info['cvv2'];
		
		$payment_type = $cc_info['payment_type'];
		
		// Set request-specific fields.
		$paymentType = urlencode('Sale');				// 'Authorization' or 'Sale'
		$creditCardType = urlencode(strtoupper($cc_type));
		$creditCardNumber = urlencode($cc_number);
		$cvv2Number = urlencode($cc_cvv2);
		$amount = urlencode($cc_amount);
		$currencyID = urlencode('USD');
		$expDateYear = urlencode($cc_year);
		$expDateMonth = $cc_month;

		// Month must be padded with leading zero
		$padDateMonth = urlencode(str_pad($expDateMonth, 2, '0', STR_PAD_LEFT));
		
		// Add request-specific fields to the request string.
		$nvpStr =	"&PAYMENTACTION=$paymentType&AMT=$amount&CREDITCARDTYPE=$creditCardType&ACCT=$creditCardNumber".
					"&EXPDATE=$padDateMonth$expDateYear&CVV2=$cvv2Number&CURRENCYCODE=$currencyID&FEESPAYER=SENDER";
		
		// Execute the API operation; see the PPHttpPost function above.
		$httpPost = new Paypal_HttpPost();
		$this->details = $httpPost->httpPost('DoDirectPayment', $nvpStr);
		
		return $this->SaveToDB($payment_type);
	}
	
	
	private function SaveToDB($payment_type)
	{
		$table = new Application_Model_Paypal();
		return $table->db_save($this->details, $payment_type);
	}
	
	private function CheckMoney($amount)
	{
		$amount = floatval($amount);
		if(is_float($amount))
		{
			/*if($amount < $this->minMoneyAmount)
			{
				return $this->minMoneyAmount;
			}*/
			return $amount;
		}
		
		return $minMoneyAmount;
	}
	

}

