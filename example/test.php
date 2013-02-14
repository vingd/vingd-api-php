<?php

require_once(dirname(__FILE__).'/../vingd/vingd.php');

$v = new Vingd(
    "test@knopso.com", "123",
    "https://api.vingd.com/sandbox/broker/v1/", "http://www.sandbox.vingd.com/"
);

$balance = $v->getAccountBalance();
echo "My balance is VINGD $balance.\n";

$vouchers = $v->getVouchers();
echo "I have ", count($vouchers), " active vouchers.\n";

?>