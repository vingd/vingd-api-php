.. _api:


Vingd library reference 
=======================

.. toctree::
   :maxdepth: 3


Vingd class
-----------

.. php:class:: Vingd

   Vingd API interface class.
   
Constants
~~~~~~~~~
   
   .. php:const:: URL_ENDPOINT
      
      https://api.vingd.com/broker/v1
   
   .. php:const:: URL_FRONTEND
      
      https://www.vingd.com
   
   .. php:const:: URL_ENDPOINT_SANDBOX
      
      https://api.vingd.com/sandbox/broker/v1
   
   .. php:const:: URL_FRONTEND_SANDBOX
      
      http://www.sandbox.vingd.com
   
   .. php:const:: EXP_ORDER
   
      Default order expiry, '+15 minutes'. The order expires in 15 minutes,
      relative to the time of creation.

   .. php:const:: EXP_VOUCHER
   
      Default voucher expiry, '+1 month'. The voucher expires in one month,
      relative to the time of creation. When it expires, allocated funds are
      refunded to the issuer's Vingd account.

Purchase
~~~~~~~~
   
   .. php:method:: createObject($name, $url)
   
      Creates (registers) an object in the Vingd Object Registry.
      
      :param string $name: Object's name.
      :param string $url: Object's callback URL.
      
      :returns: (integer) Object ID assigned in Vingd.
      :throws: VingdException, Exception
   
   .. php:method:: updateObject($oid, $name, $url)
      
      Updates an object enrolled in the Vingd Object Registry.
      
      :param integer $oid: Object ID, as returned by :php:meth:`Vingd::createObject`.
      :param string $name: Object's new name.
      :param string $url: Object's new callback URL.
      
      :returns: (integer) Object ID assigned in Vingd.
      :throws: VingdException, Exception
   
   .. php:method:: getObject($oid)
      
      Fetches object with id `$oid`.
      
      :param integer $oid: Object ID, as returned by :php:meth:`Vingd::createObject`.
      
      :returns: (array) Vingd object description.
      :throws: VingdException, Exception
   
   .. php:method:: getObjects()
      
      Fetches all objects for the authenticated user.
      
      :returns: (array) A list of Vingd objects.
      :throws: VingdException, Exception
   
   .. php:method:: createOrder($oid, $price, $context = null, $expires = Vingd::EXP_ORDER)
      
      Creates an order for object `$oid`, with price set to `$price` and
      validity until `$expires`.
      
      :param integer $oid: Identifier of the object to be sold, as returned by :php:meth:`Vingd::createObject`.
      :param float $price: Object's price in VINGDs. Rounded to two decimal digits.
      :param string $context:
         Arbitrary (user-defined) context handle of this purchase. The $context
         shall be referenced on the access verification handler URL. (Usage
         discouraged for sensitive data.)
         Default: no context associated with order.
      :param string $expires:
         Expiry timestamp / validity period of the order being generated
         (accepts any PHP/strtotime-parsable date/time string, including ISO
         8601, RFC 822, and most English date formats, as well as relative
         dates).
         Default: :php:const:`Vingd::EXP_ORDER` ('+15 minutes', i.e. order
         expires in 15 minutes).
      
      :returns: (array) Vingd order description.
      :throws: VingdException, Exception
   
   .. php:method:: verifyPurchase($token)
      
      Verifies purchase token `$tid` and returns token data associated with it
      (and bound to object `$oid`).
      
      :note: If token was invalid (purchase can not be verified), a
         VingdException is thrown.
      
      :param array/string $token:
         Access token user brings in, as returned from Vingd user frontent on
         object's callback URL.
         
         :php:meth:`verifyPurchase` accepts `$token` as either *string* (as read from
         ``$_GET['token']`` on callback processor), or as *array* (json-decoded
         from the URL).
         
         You should always verify the token the user brings in from Vingd
         frontend to ensure the user has access rights for your object and/or
         service. Successful token verification guarantees Vingd Broker has
         reserved user vingds for the seller. Those vingds will be transfered
         after seller commits the purchase (see :php:meth:`commitPurchase`).
      
      :returns:
         (array) Purchase details:
            - 'object' key: Object name.
            - 'huid' key: Buyer's seller-bound user id (user id unique for the
              authenticated owner/seller of `$oid`).
            - 'purchaseid': Vingd Purchase ID.
            - 'transferid': Vingd transfer ID.
      
      :throws: VingdException, Exception
   
   .. php:method:: commitPurchase($purchase)
      
      Commits the purchase (defined with `$purchaseid` and `$transferid`) as
      finished.
      
      Call :php:meth:`commitPurchase` upon successful delivery of paid content
      to the user. If you do not call :php:meth:`commitPurchase`, the user shall
      be refunded automatically.
      
      :param array $purchase:
         User purchase description, as returned by :php:meth:`verifyPurchase`
         upon successful token verification.
      
      :returns: (array) ('ok => true), or fails with VingdException.
      :throws: VingdException, Exception

Rewarding
~~~~~~~~~
   
   .. php:method:: createVoucher($amount, $until = Vingd::EXP_VOUCHER, $message = '', $gid = null, $description = null)
   
      Makes a new voucher.
      
      :param float $amount: Voucher amount in VINGDs.
      :param date $until: Timestamp of voucher expiry (accepts any
         PHP/strtotime-parsable date/time string, including ISO 8601, RFC 822,
         and most English date formats, as well as relative dates; e.g. '+1
         day', '+1 month', etc.).
         Default: :php:const:`Vingd::EXP_VOUCHER` ('+1 month', i.e. voucher
         expires in one month). When it expires, allocated funds are refunded to
         the issuer's Vingd account.
      :param string $message: A message user shall be presented with after
         submitting the voucher (on Vingd frontend).
      :param string $gid: Voucher Group ID (alphanumeric string:
         ``[-_a-zA-Z0-9]{1,32}``). User can use only one voucher per group.
      :param string $description: Voucher internal description. Optional, but
         can be helpful for tracking.
      
      :returns: (array) Voucher description. The most interesting keys being:
         ``code`` and ``urls`` (which branches to ``redirect`` URL and ``popup``
         URL).
      :throws: VingdException, Exception
   
   .. php:method:: getActiveVouchers()
      
      Fetches a list of all active (non-expired) vouchers for the authenticated
      user.
      
      :returns: (array) A list of voucher descriptions.
      :throws: VingdException, Exception
      
   .. php:method:: getVouchers()
      
      Fetches a complete vouchers history (for the authenticated user). The list
      includes **active**, **expired**, **used** and **revoked** vouchers
      (discriminated via ``action`` key in voucher description).
      
      :returns: (array) A list of voucher descriptions.
      :throws: VingdException, Exception
   
   .. php:method:: rewardUser($huid, $amount, $description = null)
   
      Rewards user defined with `$huid` with `$amount` vingds, transfered from
      the account of the authenticated user.

      :param float $amount: Voucher amount in VINGDs.
      :param date $until: Timestamp of voucher expiry (accepts any
         PHP/strtotime-parsable date/time string, including ISO 8601, RFC 822,
         and most English date formats, as well as relative dates; e.g. '+1
         day', '+1 month', etc.).
         Default: :php:const:`Vingd::EXP_VOUCHER` ('+1 month', i.e. voucher
         expires in one month). When it expires, allocated funds are refunded to
         the issuer's Vingd account.
      :param string $message: A message user shall be presented with after
         submitting the voucher (on Vingd frontend).
      :param string $gid: Voucher Group ID (alphanumeric string:
         ``[-_a-zA-Z0-9]{1,32}``). User can use only one voucher per group.
      :param string $description: Voucher internal description. Optional, but
         can be helpful for tracking.
      
      :returns: (array) Voucher description. The most interesting keys being:
         ``code`` and ``urls`` (which branches to ``redirect`` URL and ``popup``
         URL).
      :throws: VingdException, Exception

Account-related
~~~~~~~~~~~~~~~

   .. php:method:: getUserProfile()
      
      Fetches profile of the authenticated user.
      
      :returns: (array) User profile.
      :throws: VingdException, Exception
   
   .. php:method:: getUserId()
      
      Shorthand to fetch authenticated user's id.
      
      :returns: (integer) User ID.
      :throws: VingdException, Exception
   
   .. php:method:: getAccount()
      
      Fetches account info for the authenticated user.
      
      :returns: (array) Account profile ('uid' => <user_id>, 'balance' =>
         <vingd_balance>).
      :throws: VingdException, Exception
   
   .. php:method:: getAccountBalance()
      
      Shorthand to fetch authenticated user's balance.
      
      :returns: (float) Account balance.
      :throws: VingdException, Exception
