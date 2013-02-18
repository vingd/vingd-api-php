<?php

require_once(dirname(__FILE__).'/../vingd/vingd.php');

// sandbox backend:
$v = new Vingd("test@knopso.com", "123", Vingd::URL_ENDPOINT_SANDBOX, Vingd::URL_FRONTEND_SANDBOX);

// in production use:
//$v = new Vingd("<vingd-login-username>", "<vingd-login-password>");

//
// profile/account
//

$profile = $v->getUserProfile();
print "I ({$profile['name']}) registered on {$profile['timestamp_created']}.\n";

$balance = $v->getUserBalance();
echo "My balance is VINGD $balance.\n";


//
// voucher rewarding
//

$voucher = $v->createVoucher(1.00, '+7 days');
echo "I'm rewarding you with this 1 vingd voucher ({$voucher['code']}): {$voucher['urls']['redirect']}.\n";

$vouchers = $v->getActiveVouchers();
echo 'Now I have ', count($vouchers), " active vouchers.\n";

//
// selling
//

$oid = $v->createObject("My test object", "http://localhost:666/");
echo "I've just created an object, just for you. OID is $oid.\n";

$oid2 = $v->updateObject($oid, "New object name", "http://localhost:777/");
echo "Object updated.\n";

$object = $v->getObject($oid);
echo "Object last modified at {$object['timestamp_modified']}, new url is {$object['description']['url']}.\n";

$objects = $v->getObjects();
echo 'I have ', count($objects), " registered objects I can sell.\n";

$order = $v->createOrder($oid, 2.00);
echo "I've also created an order (id={$order['id']}) for the object (oid={$order['object']['id']}): {$order['urls']['redirect']}.\n";

echo "After you buy it, enter the Token ID here ('tid' param on callback url): ";
$tid = fgets(STDIN);
$purchase = $v->verifyPurchase(array('oid' => $oid, 'tid' => $tid));
$huid_buyer = $purchase['huid'];
echo "Purchase verified (buyer's HUID = $huid_buyer).\n";

$commit = $v->commitPurchase($purchase);
echo "Content served, and purchase committed.\n";

//
// direct rewarding
//

$reward = $v->rewardUser($huid_buyer, 0.75, 'Testing direct rewarding');
echo "User rewarded (transfer id = {$reward['transfer_id']}).\n";

?>