<?php
@ignore_user_abort (true) . @ini_set ("zlib.output_compression", 0);
header ("Content-Type: text/plain; charset=utf-8") . eval ('while (@ob_end_clean ());');
/**/
if (empty ($_POST)) /* No $_POST vars? */
	header ("HTTP/1.0 500 Error") . exit ();
/*
---- Central IPN Processing: ------------------------------------------------------------------------------------------------

With PayPal® Pro integration you absolutely MUST set an IPN URL inside your PayPal® account.
PayPal® Pro integration does NOT allow the IPN location to be overridden on a per-transaction basis.

So, if you're using a single PayPal® Pro account for multiple cross-domain installations,
and you need to receive IPN notifications for each of your domains; you'll want to create
a central IPN processing script that scans variables in each IPN response,
forking itself out to each of your individual domains.

In rare cases when this is necessary, you'll find two variables in all IPN responses for s2Member.
The originating domain name will always be included somewhere within, either:
`custom` and/or `rp_invoice_id`; depending on the type of transaction.

These variables can be used to test incoming IPNs, and fork to the proper installation.

---- Instructions: ----------------------------------------------------------------------------------------------------------

1. Save this PHP file to your website.

2. Set the IPN URL ( in your PayPal® account ) to the location of this script on your server.
	This central processor forks IPNs out to the proper installation domain.

3. Configuration ( below ).

---- Configuration: --------------------------------------------------------------------------------------------------------*/
$CONFIG = array ( /* One line for each domain ( examples below ). */
#
"[YOUR DOMAIN]" => "[FULL URL TO AN IPN HANDLER FOR YOUR DOMAIN]", #
"www.site1.com" => "http://www.site1.com/?s2member_paypal_notify=1", #
"www.site2.com" => "http://www.site2.com/?s2member_paypal_notify=1", #
#
);
/*
---- Do NOT edit anything below, unless you know what you're doing. --------------------------------------------------------*/
foreach ($CONFIG as $key => $value)
	$CONFIG[strtolower ($key)] = $value;
unset ($key, $value);
/*
Fork IPN transactions out to particular domains.
*/
preg_match ("/^(.+?)(?:\||$)/i", (string)@$_POST["custom"], $custom);
preg_match ("/~(.+?)~/i", (string)@$_POST["rp_invoice_id"], $rp_invoice_id);
/**/
if ((!empty ($custom[1]) || !empty ($rp_invoice_id[1])) && strtolower ($domain = (!empty ($custom[1])) ? $custom[1] : $rp_invoice_id[1]) && !empty ($CONFIG[$domain]))
	{
		header ("HTTP/1.0 200 OK") . exit (trim (curlpsr ($CONFIG[$domain], http_build_query ($_POST))));
	}
else /* Unexpected condition. Unable to process. */
	{
		header ("HTTP/1.0 500 Error") . exit ();
	}
/*
cURL operation for posting data and reading response.
*/
function curlpsr ($url = FALSE, $postvars = array (), $max_con_secs = 20, $max_stream_secs = 20, $headers = array ())
	{
		if (($url = trim ($url)) && ($c = curl_init ()))
			{
				if (is_array ($postvars)) /* Because cURL can't deal with complex arrays. */
					/* Since cURL can't deal with complex arrays, we force this to a query string. */
					$postvars = http_build_query ($postvars);
				/**/
				curl_setopt_array ($c, /* Configure options. */
				array (CURLOPT_URL => $url, CURLOPT_POST => true,/**/
				CURLOPT_CONNECTTIMEOUT => $max_con_secs, CURLOPT_TIMEOUT => $max_stream_secs, /* Initial connection & stream seconds. */
				CURLOPT_HEADER => false, CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => $headers, CURLOPT_POSTFIELDS => $postvars,/**/
				CURLOPT_FOLLOWLOCATION => ($follow = (!ini_get ("safe_mode") && !ini_get ("open_basedir"))), CURLOPT_MAXREDIRS => (($follow) ? 5 : 0),/**/
				CURLOPT_ENCODING => "", CURLOPT_VERBOSE => false, CURLOPT_FAILONERROR => true, CURLOPT_FORBID_REUSE => true, CURLOPT_SSL_VERIFYPEER => false));
				/**/
				$o = trim (curl_exec ($c));
				/**/
				curl_close ($c);
			}
		/**/
		return (!empty ($o)) ? $o : false;
	}
?>