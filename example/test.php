<?php

require_once(dirname(__FILE__).'/../vingd/vingd.php');

$v = new Vingd(
    "test@knopso.com", "123",
    Vingd::URL_ENDPOINT_SANDBOX, Vingd::URL_FRONTEND_SANDBOX
);

$balance = $v->getAccountBalance();
echo "My balance is VINGD $balance.\n";

$vouchers = $v->getVouchers();
echo "I have ", count($vouchers), " active vouchers.\n";

?>