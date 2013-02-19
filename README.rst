Vingd
=====

`Vingd`_ enables users to pay with money or with time. Money goes directly to
publishers and time is monetized indirectly through interaction with brands,
content creation, loyalty, bringing new users, etc. As a result Vingd
dramatically increases monetization while keeping reach. Vingd's secret sauce
are mathematical models that are adapting to each user in order to extract as
much value as possible from their time.

We use vingds (think of it as "digital currency", points, or credits) to express
the value ("price") of intangible goods (such as TV streams or newspaper
articles), to reward users for their activity (time), or to authorize ("charge")
them access to digital goods.


Vingd API for PHP
=================

Vingd API enables you to register Vingd objects you're selling, create Vingd
purchase orders, verify and commit Vingd purchases. You can also reward users,
either directly (in backend), or indirectly via Vingd vouchers. Detailed `docs`_
and `demos`_ are available.


Installation
============

The last stable release of PHP Vingd API is available on `GitHub`_::

   $ git clone https://github.com/vingd/vingd-api-php
   $ cd vingd-api-php
   $ php example/test.php


Examples
========

Client initialization and account balance fetching:

.. code-block:: php

    require_once('/path/to/vingd-api-php/vingd/vingd.php');

    $VINGD_USERNAME = 'test@vingd.com';
    $VINGD_PASSWORD = '123';

    // Initialize vingd client.
    $v = new Vingd($VINGD_USERNAME, $VINGD_PASSWORD, Vingd::URL_ENDPOINT_SANDBOX, Vingd::URL_FRONTEND_SANDBOX);
    
    // Fetch user balance.
    $balance = $v->getUserBalance();

Sell content
------------

Wrap up vingd order and redirect user to confirm his purchase at vingd frontend:

.. code-block:: php

    // Selling details.
    $OBJECT_NAME = "My test object";
    $OBJECT_URL = "http://localhost:666/";
    $ORDER_PRICE = 2.00;
    
    // Register vingd object (once per selling item).
    $oid = $v->createObject($OBJECT_NAME, $OBJECT_URL);
    
    // Prepare vingd order.
    $order = $v->createOrder($oid, $ORDER_PRICE);

    // Order ready, redirect user to confirm his purchase at vingd frontend.
    $redirect_url = $order['urls']['redirect'];

As user confirms his purchase on vingd fronted he is redirected back to object URL
expanded with purchase verification parameters.

.. code-block:: php

    // User confirmed purchase on vingd frontend and came back to http://localhost:666/?oid=<oid>&tid=<tid>

    // Verify purchase with received parameters.
    $purchase = $v->verifyPurchase(array('oid' => $oid, 'tid' => $tid));

    // Purchase successfully verified, serve purchased content to user.
    // ... content serving ...
    
    // Content is successfully served, commit vingd transaction.
    $commit = $v->commitPurchase($purchase);

Reward user directly
--------------------

Transfer vingd directly on users account:

.. code-block:: php

    // Vingd hashed user id, as obtained in purchase procedure (previous example).
    $REWARD_HUID = $purchase['huid'];
    $REWARD_AMOUNT = 0.75;
    $REWARD_DESCRIPTION = "Testing direct rewarding";
    
    // Reward user.
    $reward = $v->rewardUser($REWARD_HUID, $REWARD_AMOUNT, $REWARD_DESCRIPTION);

Reward user with voucher
------------------------

.. code-block:: php

    $VOUCHER_AMOUNT = 1.00;
    $VOUCHER_VALID_PERIOD = '+7 days';

    // Create vingd voucher.
    $voucher = $v->createVoucher($VOUCHER_AMOUNT, $VOUCHER_VALID_PERIOD);
    
    // Redirect user to use voucher on vingd frontend:
    $redirect_url = $voucher['urls']['redirect'];

For more examples, see `example/test.php`_ in source.

Documentation
=============

Automatically generated documentation for latest stable version is available on:
https://vingd-api-for-php.readthedocs.org/en/latest/.


Copyright and License
=====================

Vingd API is Copyright (c) 2013 Vingd, Inc and licensed under the MIT license.
See the LICENSE file for full details.


.. _`Vingd`: http://www.vingd.com/
.. _`docs`: https://vingd-api-for-php.readthedocs.org/en/latest/
.. _`GitHub`: https://github.com/vingd/vingd-api-php/
.. _`demos`: http://docs.vingd.com/
.. _`example/test.php`: https://github.com/vingd/vingd-api-php/blob/master/example/test.php