<?php

require_once(dirname(__FILE__).'/../vingd/vingd.php');

// sandbox backend:
$v = new Vingd("test@vingd.com", "123", Vingd::URL_ENDPOINT_SANDBOX, Vingd::URL_FRONTEND_SANDBOX);

// in production use:
//$v = new Vingd("<vingd-login-username>", "<vingd-login-password>");

// create object to sell (should be persistent)
$oid = $v->createObject("My test object", "http://localhost:666/");
echo "object created, oid = $oid\n";

// create zombie account
$user = $v->authorizedCreateUser(null, null, array("get.account.balance", "purchase.object"));
$huid = $user['huid'];
echo "user created, huid = '$huid'\n";

// get balance (requires delegate-user permission: 'get.account.balance')
$balance = $v->authorizedGetAccountBalance($huid);
echo "initial balance: $balance\n";

// reward the new user
$reward = $v->rewardUser($huid, 5, 'Testing direct rewarding');
echo "user rewarded with 5.00 vingds (transfer id = {$reward['transfer_id']})\n";

$balance = $v->authorizedGetAccountBalance($huid);
echo "balance after rewarding: $balance\n";

// charge the user for $oid
// requires ACL flag: 'purchase.object.authorize' (seller account)
// and delegate-user permission: 'purchase.object' (user account)
$ret = $v->authorizedPurchaseObject($oid, 2, $huid);
print "user charged 2 vingds\n";

$balance = $v->authorizedGetAccountBalance($huid);
echo "balance after charging: $balance\n";

?>