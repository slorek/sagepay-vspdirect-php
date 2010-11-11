<?php

class SagePay {
	
	public $url;
	
	# Function to register a transaction.
	public function authenticate($transactionId, $amount, $description, $card, $billingaddr = null, $shippingaddr = null, $customer = null)
	{		
		if (is_array($card)) {
			
			# Format the data.
			$data['VPSProtocol']	= Config::SAGEPAY_PROTOCOL;
			$data['TxType']			= 'AUTHENTICATE';
			$data['Vendor']			= Config::SAGEPAY_VENDOR;
			$data['VendorTxCode']	= $transactionId;
			$data['Amount']			= number_format($amount, 2);
			$data['Currency']		= 'GBP';
			$data['Description']	= substr($description, 0, 100);
			$data['CardHolder']		= substr($card['holder'], 0, 50);
			$data['CardNumber']		= substr($card['number'], 0, 20);
			
			if (isset($card['validfrom'])) {
				$data['StartDate'] = $card['validfrom'];
			}
			
			$data['ExpiryDate'] = $card['expiry'];
			
			if (isset($card['issuenumber'])) {
				$data['IssueNumber'] = $card['issuenumber'];
			}
			
			$data['CV2'] = $card['cv2'];
			$data['CardType'] = $card['type'];
			
			if (is_array($billingaddr)) {
				$data['BillingAddress'] = $billingaddr['address'];
				$data['BillingPostCode'] = $billingaddr['postalcode'];
			}
			
			if (is_array($shippingaddr)) {
				$data['DeliveryAddress'] = $shippingaddr['address'];
				$data['DeliveryPostCode'] = $shippingaddr['postalcode'];
			}
			
			if (is_array($customer)) {
				$data['CustomerName'] = $customer['firstname']." ".$customer['lastname'];
				$data['ContactNumber'] = $customer['daytimephone'];
				$data['ContactFax'] = $customer['faxnumber'];
				$data['CustomerEMail'] = $customer['email'];
			}
			
			$data['ClientIPAddress'] = $_SERVER['REMOTE_ADDR'];
			
			$data['purchaseurl'] = $this->url;
			
			return $this->_execute($this->_formatData($data));
		}
	}
	
	
	# Function to repeat a payment.
	public function repeat($transactionId, $amount, $description, $oldTransactionId, $processorId, $securityKey, $authNo)
	{
		# Formulate the XML.
		$data['VPSProtocol']			= Config::SAGEPAY_PROTOCOL;
		$data['TxType']					= 'REPEAT';
		$data['Vendor']					= Config::SAGEPAY_VENDOR;
		$data['VendorTxCode']			= $transactionId;
		$data['Amount']					= number_format($amount, 2);
		$data['Description']			= substr($description, 0, 100);
		$data['RelatedVPSTxId'] 		= $processorId;
		$data['RelatedVendorTxCode']	= $oldTransactionId;
		$data['RelatedSecurityKey']		= $securityKey;
		$data['RelatedTxAuthNo']		= $authNo;
		
		return $this->_execute($this->_formatData($data));
	}
	
	# Function to repeat a payment.
	public function authorise($transactionId, $amount, $description, $oldTransactionId, $processorId, $securityKey)
	{
		# Formulate the XML.
		$data['VPSProtocol']			= Config::SAGEPAY_PROTOCOL;
		$data['TxType']					= 'AUTHORISE';
		$data['Vendor']					= Config::SAGEPAY_VENDOR;
		$data['VendorTxCode']			= $transactionId;
		$data['Amount']					= number_format($amount, 2);
		$data['Description']			= substr($description, 0, 100);
		$data['RelatedVPSTxId'] 		= $processorId;
		$data['RelatedVendorTxCode']	= $oldTransactionId;
		$data['RelatedSecurityKey']		= $securityKey;
		
		return $this->_execute($this->_formatData($data));
	}
	
	
	# Internal function to send the XML request to eBay and retrieve the response.
	private function _execute($data)
	{		
		# Check that cURL is installed on this server.
		if (function_exists('curl_init')) {
			
			# Initialise output variable
			$result = array();
			
			# Initialise cURL.
			$curl = curl_init();
			
			# Set the URL to POST to.
			curl_setopt($curl, CURLOPT_URL, $this->url);
			
			# Specify to not verify the SSL certificate.
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
			
			# Set no headers.
			curl_setopt ($curl, CURLOPT_HEADER, 0);
			
			# Specify use of the POST Method.
			curl_setopt ($curl, CURLOPT_POST, 1);
			
			# Include the XML data in the body of the request.
			curl_setopt ($curl, CURLOPT_POSTFIELDS, $data);
			
			# Specify to return the output as a variable instead of to the browser.
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); 
			
			// This connection will timeout in 30 seconds
			curl_setopt($curl, CURLOPT_TIMEOUT, 30); 
			
			# Send the request.
			$response = split(chr(10), curl_exec($curl));
			
			# What to do if no connection was made.
			if (curl_error($curl)){
				
				$result['Status'] = "FAIL";
				$result['StatusDetail'] = "cURL reports ".curl_error($curl).". See http://curl.haxx.se/libcurl/c/libcurl-errors.html for further information.";
			}
			else {
			
				while (list($key, $value) = each($response)) {
					
					if ($value = trim($value)) {
						$splitAt = strpos($value, "=");
						$result[trim(substr($value, 0, $splitAt))] = trim(substr($value, ($splitAt+1)));
					}
				}
			}
			
			# Close the connection.
			curl_close($curl);
			
			if (('OK' == $result['Status']) || ('REGISTERED' == $result['Status']) || ('AUTHENTICATED' == $result['Status'])) {
				return $result;
			} else {
				throw new Exception($result['StatusDetail']);
			}
		}
		
		# If cURL is not installed, throw an error.
		else {
			trigger_error("cURL is not installed on this server. You must install it to communicate with SagePay. See http://www.php.net/curl for further information.", E_USER_ERROR);
		}
	}
	
	# Format data for sending in POST request.
	private function _formatData($data)
	{	
		# Initialise result variable.
		$result = '';
	
		# Step through the fields.
		foreach ($data as $key => $value) {
			$result .= "&" . urlencode($key) . "=" . urlencode($value);
		}
		
		# Take out the initial '&'.
		$result = substr($result, 1);
	
		# Return the output.
		return $result;
	}
}
