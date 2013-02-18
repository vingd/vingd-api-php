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
-----------------

Vingd API enables you to register Vingd objects you're selling, create Vingd
purchase orders, verify and commit Vingd purchases. You can also reward users,
either directly (in backend), or indirectly via Vingd vouchers. Detailed `docs`_
and `demos`_ are available.


Installation
------------

The last stable release of PHP Vingd API is available on `GitHub`_::

   $ git clone https://github.com/vingd/vingd-api-php
   $ cd vingd-api-php
   $ php example/test.php


Example Usage
-------------

.. code-block:: php

   require_once('/path/to/vingd-api-php/vingd/vingd.php');
   
   $v = new Vingd("<vingd-login-username>", "<vingd-login-password>");
   
   $balance = $v->getUserBalance();
   echo "My balance is VINGD $balance.\n";
   
   $vouchers = $v->getActiveVouchers();
   echo "I have ", count($vouchers), " active vouchers.\n";

For more examples, see `example/test.py`_ in source.


Documentation
-------------

Automatically generated documentation for latest stable version is available on:
https://vingd-api-for-php.readthedocs.org/en/latest/.


Copyright and License
---------------------

Vingd API is Copyright (c) 2013 Vingd, Inc and licensed under the MIT license.
See the LICENSE file for full details.


.. _`Vingd`: http://www.vingd.com/
.. _`docs`: https://vingd-api-for-php.readthedocs.org/en/latest/
.. _`GitHub`: https://github.com/vingd/vingd-api-php/
.. _`demos`: http://docs.vingd.com/
.. _`example/test.py`: https://github.com/vingd/vingd-api-php/blob/master/example/test.php