.. _overview:


Vingd interaction overview
==========================

You communicate with Vingd system thru Vingd Broker, using the
:php:class:`Vingd` PHP class. Direct communication with Vingd Broker over HTTP
is also simple (see `A few technical details`_), but discouraged.

Vingd enables you to monetize your digital goods by "selling" the access rights
to your content/service. You host the content and you control the access, and we
handle the access rights. As the first approximation, Vingd can be seen as a
payment gateway.

Thru Vingd, you can also reward users with vingds via *Vingd Vouchers*, or
directly transfering vingds to user account in backend.


Purchase
--------

The content or service of yours has an abstract representation on Vingd, called
*Vingd Object*. Object enrollment (aka registration) is a one-time process,
after which an object can be "sold" to Vingd users. Terms of purchase (namely,
the price) are defined by you, the seller, in what is called *Purchase Order*.
Orders can be viewed as bills you give to users. User pays that bill on `Vingd
user frontend`_ and it gets object access rights assigned for the object you
referenced in the order. Each time a user tries to access your object (the
location you specified upon object enrollment and which you control), you should
demand an access token from your user, issued by Vingd Broker. With the access
token we shall vouch that the specific user has paid for the access, and you
should allow him to view the object (content/service).


Registering objects
~~~~~~~~~~~~~~~~~~~
    
In order to sell anything thru Vingd, you must *enroll* (i.e. register) an
abstract representation of that item, as an object in our *Object Registry*
(thru Vingd Broker). Objects are enrolled with :php:meth:`Vingd::createObject`
function. During object enrollment, a unique *Object ID* (``oid``) is assigned
to that object. Store that ``oid``, since you'll be using it as the object
handle.

Object description is a dictionary (associative array) with only two mandatory
keys: ``name`` and ``url``. Make sure the ``url`` points to a valid location,
because users will be redirected there upon object purchase (consider this URL
to represent the object location, according to the REST paradigm). More
precisely, the complete URL where user will be redirected is object's ``url``
with *Token ID* and *Object ID* glued as GET parameters (``tid`` and ``oid``).


Selling an object
~~~~~~~~~~~~~~~~~

To sell any of your enrolled objects, you must create an *Object Order* which
shall encapsulate the terms under which the object is being sold to the customer
(e.g. price). :php:meth:`Vingd::createOrder` method facilitates order creation. Object
and price are defined with `$oid` and `$price` in vingds (rounded to
cents). Expiry date of the very order is defined with `$expires`.

It is important to note that orders are not bound to a specific user. That means
you could (and preferably would) generate one order with expiry time longer than
the default 15mins and offer that same order to more than one user (e.g. you
could have special order for each "class" of your users). The advantage of
having pre-generated orders is you are cutting down the overhead of object
purchase on your site - you don't have to generate a new order in background for
every user's "buy-click", but simply direct the user to order-purchase-link on
Vingd (as returned upon the initial order making).

On a successful order creation, you shall be given an URL pointing to your order
on Vingd frontend (see ``['urls']['redirect']`` element of a value returned from
:php:meth:`Vingd::createOrder`). To enable an user to pay the access to your
object, you should direct her exactly to that very URL (if you're using `Vingd
Popup Library`_ you should use the ``order['urls']['popup']`` URL).


Verifying user access rights
~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Upon successful confirmation of an order (specified amount of vingds is reserved
from user's account), a *Vingd Token* is issued by Vingd Broker. User is
redirected to the (callback) *object url* with the *Token* (``tid``) and *Object
ID* (``oid``) appended. This token is short-lived (typically 15 minutes) and
during its lifespan it represents a voucher that can be checked to see if the
carrier of the ``(tid, oid)`` pair has rights for accessing object ``oid``.

To verify the token, use :php:meth:`Vingd::verifyPurchase`. If user access is
granted, token verification interface returns *Token Description*.

In a popup mode, you will also want to verify the token when user returns from
Vingd frontend (see `Vingd Popup onSuccess() callback`_).


Wrapping up the purchase
~~~~~~~~~~~~~~~~~~~~~~~~

Once you have successfully served the content user has paid for, you should
notify the Vingd Broker, referencing the *Order ID*, *Purchase ID* and
*Transfer ID* (the last two are returned in *Token Description* upon token
verification). This completes the purchase.

If you don't do :php:meth:`Vingd::commitPurchase`, Vingd Broker assumes a seller
(you) failed to deliver the content or service to the user and does an automatic
refund.


Rewarding
---------

:php:meth:`Vingd::createVoucher` allows you to allocate a certain amount of
vingds from your account and offer it to a Vingd user. Voucher code looks like
``LXKG-TBR-HQV`` (only letters A-Z and digits 0-9 are significant; all other
characters are ignored). Upon voucher creation, :php:meth:`Vingd::createVoucher`
returns a structure with an URL pointing to Vingd frontend which user can follow
to use the voucher. Vingd popup can also be used, see `Vingd Popup Voucher
example`_.


A few technical details
-----------------------

`Vingd Broker`_ (https://api.vingd.com/broker/v1/) has a very simple REST
interface to a complete Vingd backend. For example, to retrieve a list of
objects you registered, execute a ``GET`` request on the Vingd Objects resource
(https://api.vingd.com/broker/v1/registry/objects/) authenticating using HTTP
Basic Auth (with Vingd username and password SHA1 hash). The response should be
a JSON list of object descriptions.

Creating (enrolling, or registering) a new object is slightly more complex, but
nevertheless still trivial: ``POST`` a JSON-encoded description of the object to
that same URL (which, btw, represents a collection of your objects).

However, if you are using Python/PHP/.NET/Java there should never be a need for
you to manually implement a client for our REST backend, since we already
support libraries for those environments.


.. _`Vingd user frontend`: http://www.vingd.com/
.. _`Vingd Popup Library`: http://docs.vingd.com/libs/popup/
.. _`Vingd Popup onSuccess() callback`: http://docs.vingd.com/libs/popup/0.8/vingd.html#popupParams.onSuccess
.. _`Vingd Popup Voucher example`: http://docs.vingd.com/libs/popup/0.8/overview.html#dynamic-voucher-order-fetch
.. _`Vingd Broker`: https://api.vingd.com/broker/v1/