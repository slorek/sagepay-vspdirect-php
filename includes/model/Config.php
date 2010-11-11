<?php

# SagePay configuration.
class Config {
	
	const DATABASE  = 'mysql://<username>:<password>@<host>/<database>';
	
	const SAGEPAY_VENDOR   		    = 'test';
	const SAGEPAY_PROTOCOL 		    = '2.22';
	const SAGEPAY_URL		 		= 'https://test.sagepay.com/gateway/service/vspdirect-register.vsp';
	const SAGEPAY_AUTHORISE_URL 	= 'https://test.sagepay.com/gateway/service/authorise.vsp';
}