<?php
/**
 * Vingd API interface for PHP.
 *
 * @version 1.6
 * @date 2013-02-13
 * @author Radomir Stevanovic <radomir@vingd.com>
 * @copyright Copyright 2012 Vingd, Inc.
 * @package vingd-api-php
 * @link https://github.com/vingd/vingd-api-php
 * @license http://creativecommons.org/licenses/MIT/ MIT
 * 
 */


/** \mainpage Vingd API PHP

\section basics The basics

    You communicate with Vingd system thru Vingd Broker, using the Vingd object in PHP (see
    kapi.php), or the <a href="http://docs.vingd.com/apis/python/">Python module</a>. Direct
    communication with Vingd Broker over HTTP is also simple (see \ref technical).
    
    \subsection step0 An overview
        
        Vingd enables you to monetize your digital goods by "selling" the access rights to your
        content/service. You host the content and you control the access, and we handle the access
        rights. As the first approximation, Vingd can be seen as a payment gateway.
        
        The content or service of yours has an abstract representation on Vingd, called \term{Vingd
        Object}. Object enrollment (aka registration) is a one-time process, after which an object
        can be sold to Vingd users. Terms of purchase (namely, the price) and object access
        parameters are defined by you, the seller, in what is called \term{Object Order}. Orders can
        be viewed as bills you give to users. User pays that bill on <a
        href="http://www.vingd.com/">Vingd [user frontend]</a> and it gets object access rights
        assigned for the object you referenced in the order. Each time a user tries to access your
        object (the location you specified upon object enrollment and which you control), you should
        demand an access token from your user, issued by Vingd Broker. With the access token we
        shall vouch that the specific user has paid the access, and you should allow him to view
        the object (content/service).
        
        \deprecated Access can be limited in \b time and/or by \b count (i.e. access flag is
        a \term{counting semaphore} with a time limit).
        
        Thru Vingd, you can also reward users with VINGDs via Vingd Vouchers (see \ref vouchers).
    
    \subsection step1 Registering objects
    
        In order to be able to sell anything thru Vingd, you must \term{enroll} (i.e.
        \term{register}) an abstract representation of that item, as an object in our Registry (thru
        Vingd Broker). Objects are enrolled with Vingd::register() function. During object
        enrollment, a unique \term{Object ID} (\term{OID}) is assigned to that object. Store that
        \term{OID}, since you'll be using it as the object handle.
        
        Object description is a dictionary (associative array) which has only two mandatory keys:
        \c name and \c url, but can store up to 4 KiB of data. Make sure the \c url points to a
        valid location, because users will be redirected there upon object purchase (consider this
        \c url to represent the object location, according to the REST paradigm). More precisely,
        the complete URL where user will be redirected is object's \c url with \term{Token ID} and
        \term{Object ID} glued as GET parameters.
    
    \subsection step2 Selling an object
    
        To sell any of your enrolled objects, you must create an \term{Object Order} which shall
        encapsulate the terms under which the object is being sold to the customer (e.g. price).
        Vingd::order() function facilitates order creation. Object and price are defined with
        \c oid and integer \c price in VINGDs (rounded to cents). Expiry date of the very
        order is defined with \c order_expires.
        
        It is important to note that Orders are \b not bound to a specific user. That means you
        could (and preferably would) generate one order with expiry time longer than the default
        15mins and offer that same order to more than one user (e.g. you could have special order
        for each "class" of your users). The advantage of having pre-generated orders is you are
        cutting down the overhead of object purchase on your site - you don't have to generate a new
        order in background for every user's "buy-click", but simply direct the user to
        order-purchase-link on Vingd (as returned upon the initial Order making).
        
        On successful Order creation, you shall be given an URL pointing to your Order on Vingd
        frontend (see \tt{['urls']['redirect']} element of a value returned from Vingd::order()).
        To enable an user to pay the access to your object, you should direct her exactly to that
        very URL (if you're using <a href="http://docs.vingd.com/libs/popup/">Vingd Popup
        Library</a> you should use \tt{order['urls']['popup']}).
    
    \subsection step3 Verifying user access rights
    
        Vingd Broker provides implementation (thru Vingd Registry) of object access control list
        for all objects and all users in system. Access rights are defined (or redefined) upon
        purchase (according to parameters in Order), and updated each time a content provider
        (seller) verifies a \tt{(Token ID, Object ID)} pair; \tt{(tid, oid)} for short.
        
        Tokens are issued by Broker and presented to user via Vingd frontend, at least on purchase,
        but also later on request. Token is short-lived (typically 15 minutes) and during its
        lifespan it represents a voucher that can be checked by Broker to see if the carrier of the
        \tt{(tid, oid)} pair has rights for accessing object \c oid.
        
        If user access is granted, token verification interface returns \term{Token Description}
        (see Vingd::verify()).
        
        You will want to verify a token when user returns from Vingd frontend (or a popup closes, in
        the <a href="http://docs.vingd.com/libs/popup/0.8/vingd.html#popupParams.onSuccess">
        \tt{onSuccess} callback</a>) and comes back carrying a token as a short-term guarantee she
        just paid for your object, according to your Order. If verification succeeds, you should
        serve her the content (object).
    
    \subsection step4 Wrapping up the purchase
    
        Once you have successfully served the content user has paid for, you should notify the
        Vingd Broker, referencing the \term{Order ID}, \term{Purchase ID} and \term{Transfer ID}
        (the last two are returned in \term{Token Description} upon token verification). This
        completes the purchase.
        
        If you don't do Vingd::commit(), Vingd Broker assumes a seller (you) failed to deliver the
        content or service to the user and does an automatic refund.
    
    \subsection vouchers Creating Vingd vouchers
        
        Vingd::createVoucher() allows you to allocate a certain amount of VINGDs from your account
        and offer it to a Vingd user. Voucher code looks like \tt{LXKG-TBR-HQV} (only letters A-Z
        and digits 0-9 are significant; all other characters are ignored). Upon voucher creation,
        Vingd::createVoucher() returns a structure with an URL pointing to Vingd frontend which
        user can follow to use the voucher. Vingd popup can also be used, see <a
        href="http://docs.vingd.com/libs/popup/0.8/overview.html#dynamic-voucher-order-fetch">here</a>.


\section technical A few technical details

    <a href="https://api.vingd.com/broker/v1/">Vingd Broker</a> has a very simple REST interface to
    a complete Vingd backend. For example, to retrieve a list of object owned, execute a \tt{GET}
    request on <a href="https://api.vingd.com/broker/v1/registry/objects/">https://api.vingd.com/broker/v1/registry/objects/</a>
    resource. (Previously https://broker.knopso.com:8004/registry/objects/) Creating (enrolling, or registering) a new object is slightly more complex, but
    nevertheless still trivial: \tt{POST} a JSON-encoded description of the object to that same URL
    (which, btw, represents a collection of your objects).
    
    Much more details on Vingd Broker interface, from a Python perspective can
    be found at <a href="http://docs.vingd.com/apis/python/">Python module docs</a>. Since sellers
    (and other regular Vingd users) are so called \term{non-certified users} (as opposed to Vingd
    frontend, for example), not all Broker methods/resources are accessible to them. Filtered
    documentation of Broker interface for non-certified users is also available.

*/


require_once("http.php");

/**
 * Standard HTTP response codes used inside Vingd ecosystem.
 */
class ResponseCodes {
    // success
    const OK = 200;
    const CREATED = 201;
    const NO_CONTENT = 204;
    const PARTIAL_CONTENT = 206;
    // error
    const MULTIPLE_CHOICES = 300;
    const MOVED_PERMANENTLY = 301;
    const BAD_REQUEST = 400;
    const PAYMENT_REQUIRED = 402;
    const FORBIDDEN = 403;
    const NOT_FOUND = 404;
    const CONFLICT = 409;
    const GONE = 410;
    const INTERNAL_SERVER_ERROR = 500;
    const NOT_IMPLEMENTED = 501;
}

class VingdException extends Exception {
    private $context = null;
    private $subcode = 0;
    
    // Redefine the exception so message isn't optional
    public function __construct(
        $message, $context = 'General error',
        $code = ResponseCodes::CONFLICT, $subcode = 0
    ) {
        parent::__construct($message, $code);
        $this->context = $context;
        $this->subcode = $subcode;
    }
    
    // custom string representation of object
    public function __toString() {
        return __CLASS__ . ": " .
            "[{$this->context}]: {$this->message} " .
            "({$this->code}: {$this->subcode})\n";
    }
}

class Vingd {
    // default vingd (server) backend and (user) frontend base urls
    const URL_BACKEND = 'https://api.vingd.com/broker/v1';
    const URL_FRONTEND = 'https://www.vingd.com';
    
    // default expiry date for object order: 15 minutes from now
    const EXP_ORDER = '+15 minutes';
    
    // default expiry date for user-created vouchers: 1 month from now
    const EXP_VOUCHER = '+1 month';
    
    // date format used for display
    const DATE_HUMAN = 'F j, Y \a\t H:i:s O';
    
    // date format used by vingd broker (ISO 8601)
    const DATE_ISO = 'c';
    
    // unix epoch timestamp
    const DATE_UNIX = 'U';
    
    // [deprecated, ignored]
    const CLSID_GENERIC = 0;
    const CLSID_POST = 1;
    const CLSID_SUBSCRIPTION = 2;
    const EXP_ENTITLEMENT = null;
    const CNT_ENTITLEMENT = 1;
    
    public $TRANSFER_CLASSNAMES = array(
        1 => 'Purchase',
        2 => 'Ad reward',
        3 => 'Seller payout',
        4 => 'Ad deposit',
        5 => 'Currency exchange',
        6 => 'Direct transfer',
        7 => 'Refund',
        8 => 'Unverified purchase refund',
        9 => 'Voucher allocated',
        10 => 'Voucher deposited'
    );
    
    // connection parameters
    private $username = null;
    private $pwhash = null;
    private $backend = Vingd::URL_BACKEND;
    private $frontend = Vingd::URL_FRONTEND;
    
    /**
     * Vingd API initialization.
     *
     * @param string $username Vingd username.
     * @param string $pwhash SHA1(plain_text_vingd_password).
     * @param string $backend URL of Vingd Broker (backend service).
     * @param string $frontend URL of Vingd user frontend.
     */
    function __construct(
        $username = null, $pwhash = null,
        $backend = null, $frontend = null
    ) {
        $this->init($username, $pwhash, $backend, $frontend);
    }
    
    /**
     * Vingd API (re-)initialization.
     * @see __construct()
     */
    public function init(
        $username, $pwhash, 
        $backend = null, $frontend = null
    ) {
        $this->username = $username;
        $this->pwhash = $pwhash;
        if ($backend) $this->backend = $backend;
        if ($frontend) $this->frontend = $frontend;
    }
    
    // issues a https request to vingd broker ($this->backend)
    private function request($verb, $resource, $data = '') {
        try {
            // the WP way of doing http request (WP_Http is introduced in wp 2.7)
            $request = new Http();
            $result = $request->request(
                $this->backend . $resource,
                array(
                    'method' => $verb,
                    'body' => $data,
                    'auth' => 'basic',
                    'username' => $this->username,
                    'password' => $this->pwhash
                )
            );
            
            if (!isset($result['body']) ||
                !isset($result['response']) || !is_array($result['response']) ||
                !isset($result['response']['code'])
            ) {
                throw new Exception('Invalid HTTP response');
            }
            
            $body = json_decode($result['body'], true);
            $code = $result['response']['code'];
            
        } catch (Exception $e) {
            throw new VingdException(
                "Failed to establish connection with " .
                "Vingd Broker: " . $e->getMessage() . '.'
            );
        }
        
        if ($code >= 200 && $code < 300) {
            return $body['data'];
        }
        
        if (is_array($body) && array_key_exists('message', $body)) {
            throw new VingdException(
                $body['message'], $body['context'], $code
            );
        } else {
            throw new VingdException(
                "Failed to establish connection with Vingd Broker ".
                "(HTTP error $code: {$result['response']['message']})."
            );
        }
        
    }
    
    // converts various date/time and period formats into custom date format
    private function formatDate($format, $timestamp, $default = null) {
        if (!$timestamp) $timestamp = $default;
        if ($timestamp) {
            if ($timestamp[0] && strtoupper($timestamp[0]) == 'P') {
                $timestamp[0] = '+';
            } else {
                $timestamp = date($format, strtotime($timestamp));
            }
        }
        return $timestamp;
    }
    
    // converts various date/time and period formats into ISO date
    // (default: null == infinity)
    // returns: iso date (string)
    private function toIsoDate($timestamp, $default = null) {
        return $this->formatDate(self::DATE_ISO, $timestamp, $default);
    }
    
    // converts various date/time and period formats into
    // predefined human readable date (default: null == infinity)
    // returns: human-readable date (string)
    private function toHumanDate($timestamp, $default = null) {
        if (!$timestamp && !$default) return "never";
        return $this->formatDate(self::DATE_HUMAN, $timestamp, $default);
    }
    
    // converts various date/time and period formats into a custom date format
    // (default: null == infinity)
    // returns: custom formatted date (string)
    private function toCustomDate($fmt, $timestamp, $default = null) {
        return $this->formatDate($fmt, $timestamp, $default);
    }
    
    // Utility function for concatenation of URL and GET parameters.
    // returns string: base + {? | &} + params
    private function concatURL($base, $params) {
        $glue = (strpos($base, "?") !== false) ? "&" : "?";
        $url = $base . $glue . $params;
        return $url;
    }
    
    /**
     * Utility function for building URL given a `base` and GET param list in a
     * dictionary.
     *
     * @param string $base URL
     * @param array $params Array of GET params to be concatenated to the URL.
     */
    private function buildURL($base, $params) {
        return $this->concatURL($base, http_build_query($params));
    }
    
    // unholy, forward-compatible, mess for extraction of id/oid from a
    // soon-to-be (deprecated) batch response
    private function unpackBatchResponse($r, $name='id') {
        $names = $name.'s';
        if (array_key_exists($names, $r)) {
            // soon-to-be deprecated reponse
            if (isset($r['errors']) && isset($r['errors'][0])) {
                $err = $r['errors'][0];
                throw new VingdException($err['desc'], $err['code']);
            }
            $id = $r[$names][0];
        } else {
            // new-style simplified api response
            $id = $r[$name];
        }
        return intval($id);
    }
    
    /**
     * Registers (enrolls) an object into the Vingd Registry.
     *
     * @param array $description
     *      Object description. It MUST contain at least two keys: 'name' and
     *      'url'. It CAN, however, contain arbitrary number of user-defined,
     *      custom entries (but up to 4 KiB of data overall, when JSON-encoded).
     * @param enumeration $class
     *      [deprecated, ignored]
     * 
     * @return integer Object ID assigned in Registry.
     * @throws VingdException, Exception
     */
    public function register($description, /*ignored*/$class = null) {
        $data = array(
            "description" => $description
        );
        $ret = $this->request('POST', "/registry/objects/", json_encode($data));
        return $this->unpackBatchResponse($ret, 'oid');
    }
    
    /**
     * Updates an object enrolled in the Vingd Registry.
     *
     * @param integer $oid
     *      Object ID, as returned by `register()`.
     * @param array $description
     *      Object description. It MUST contain at least two keys: 'name' and
     *      'url'. It CAN, however, contain arbitrary number of user-defined,
     *      custom entries (but up to 4 KiB of data overall, when JSON-encoded).
     * @param enumeration $class
     *      [deprecated, ignored]
     * 
     * @return integer Object ID assigned in Registry.
     * @throws VingdException, Exception
     */
    public function update($oid, $description, /*ignored*/$class = null) {
        $data = array(
            "description" => $description
        );
        $ret = $this->request('PUT', "/registry/objects/$oid/", json_encode($data));
        return $this->unpackBatchResponse($ret, 'oid');
    }

    /**
     * Contacts the Vingd Broker and generates a new order for selling the object
     * $oid under the defined terms ($price, expiry date, etc.).
     * 
     * @param integer $oid
     *      Identifier of the object to be sold, as in Vingd Registry.
     * @param float $price
     *      Object's price in VINGDs. Only first two decimal digits are stored.
     * @param string $context
     *      Arbitrary (user-defined) context handle of this purchase. The
     *      $context shall be referenced on the access verification handler URL.
     *      (Usage discouraged for sensitive data.)
     * @param string $entitlement_expires
     *      [deprecated, ignored]
     * @param string $entitlement_count
     *      [deprecated, ignored]
     * @param string $order_expires
     *      Expiry timestamp / validity period of the order being generated
     *      (accepts any PHP parsable date/time string).
     *      Default: '+15 minutes' (== order expires in 15 minutes)
     * @param string $date_format
     *      Date format string used for formatting of return dates in order.
     * @param array $cosellers
     *      [deprecated, ignored]
     * @param array $shares
     *      [deprecated, ignored]
     * 
     * @return array Order data
     * @throws VingdException, Exception
     */
    public function order(
        $oid, $price, $context = null,
        /*ignored*/$entitlement_expires = null,
        /*ignored*/$entitlement_count = null,
        $order_expires = Vingd::EXP_ORDER,
        $date_format = Vingd::DATE_ISO,
        /*ignored*/$cosellers = null,
        /*ignored*/$shares = null
    ) {
        $data = array(
            "price" => intval($price * 100),
            "order_expires" => $this->toIsoDate($order_expires)
        );
        $ret = $this->request('POST', "/objects/$oid/orders", json_encode($data));
        $id = $this->unpackBatchResponse($ret);
        $cx = is_null($context) ? array() : array('context' => $context);
        $data_human = array(
            "id" => $id,
            "expires" => $this->toCustomDate($date_format, $order_expires),
            "object" => array(
                "id" => $oid,
                "price" => $data['price']
            ),
            "urls" => array(
                "redirect" => $this->buildURL("{$this->frontend}/orders/$id/add/", $cx),
                "popup" => $this->buildURL("{$this->frontend}/popup/orders/$id/add/", $cx)
            )
        );
        return $data_human;
    }
    
    /**
     * Verifies the token of purchase thru Vingd Broker.
     *
     * If token was invalid (purchase can not be verified), a VingdException is
     * thrown.
     *
     * @param array $token
     * @param string $token
     *      Access token user brings in, as returned from Vingd user frontent via
     *      callback link.
     *      \n
     *      verify() accepts $token as either \b string (as read from \tt{$_GET['token']}
     *      on callback processor), \i or as <b>PHP Array</b> (json-decoded from the
     *      URL).
     *      \n
     *      You should always verify the token from Vingd user frontent that
     *      user brings you to ensure the user has access rights to your object
     *      and/or service. Successful token verification guarantees Vingd
     *      Broker has reserved user funds for the seller. Those funds will be
     *      transfered after seller commits purchase completion notification.
     * 
     * @return array
     *      Purchase details:
     *        - 'object' key: Object name.
     *        - 'huid' key:
     *              Buyer's seller-bound user id (user id unique for the
     *              authenticated owner/seller of $oid).
     *        - 'purchaseid':
     *              Purchase ID
     *        - 'transferid':
     *              Money transfer (from user to Broker) ID
     * 
     * @throws VingdException, Exception
     */
    public function verify($token) {
        if (is_string($token)) {
            $token = json_decode(stripslashes($token), true);
        }
        if (!is_array($token)) {
            throw new VingdException('Invalid token format.');
        }
        if (!isset($token['oid']) || !($oid = $token['oid'])) {
            throw new VingdException('Invalid object identifier.');
        }
        if (!isset($token['tid']) || !($tid = $token['tid'])) {
            throw new VingdException('Invalid token.');
        }
        return $this->request('GET', "/objects/$oid/tokens/$tid");
    }
    
    /**
     * Commits user's reserved funds to seller account. Call commit() upon
     * successful delivery of paid content to the user.
     *
     * If you do not call commit() user shall be automatically refunded.
     * 
     * @param array $purchase
     *      User purchase description, as returned by Vingd Broker upon
     *      successful token verification.
     * 
     * @return array
     *      - On success: {'ok' => true}
     *      - On failure: VingdException is thrown
     * 
     * @throws VingdException, Exception
     */
    public function commit($purchase) {
        $purchaseid = $purchase['purchaseid'];
        $transferid = $purchase['transferid'];
        return $this->request(
            'PUT',
            "/purchases/$purchaseid",
            json_encode(array("transferid" => $transferid))
        );
    }
    
    /**
     * Fetches profile of the authenticated user.
     * 
     * @return array User profile
     * @throws VingdException, Exception
     */
    public function profile() {
        return $this->request('GET', '/id/users/username='.$this->username);
    }
    
    /**
     * Fetches account info for the authenticated user.
     *
     * @return array
     *      Account profile:
     *        - 'uid' key: User ID (integer).
     *        - 'balance' key: Account balance (floating point number with two significant digits).
     *
     * @throws VingdException, Exception
     */
    public function account() {
        $account = $this->request('GET', '/fort/accounts/');
        $account['balance'] /= 100.0;
        return $account;
    }
    
    /**
     * Shorthand to fetch current user's balance (as float, see account()).
     */
    public function balance() {
        $account = $this->account();
        return $account['balance'];
    }
    
    /**
     * Shorthand to fetch current user's id (integer, see account()).
     */
    public function uid() {
        $account = $this->account();
        return $account['uid'];
    }
    
    /**
     * Fetches a filtered list of money transfer descriptions.
     * 
     * Filtering is possible with UID, date and number (count).
     * 
     * Usage example:
     * \code
     *      $v = new Vingd();
     *      $transfers = $v->transfers(
     *          array('to'=>$v->uid()),
     *          array('since'=>'20101005T154000+02')
     *      );
     *      print_r($transfers);
     * \endcode
     * 
     * @param array $uid
     *      UID filter.
     *        - 'from' => <uid>:
     *              Only transfers from this UID shall be returned.
     *        - 'to' => <uid>:
     *              Only transfers to this UID shall be returned.
     *        .
     *        Note: at least one of these keys MUST be specified, and
     *        (for non-certified users) it MUST be equal to the authenticated
     *        user UID.
     * 
     * @param array $limit
     *      Date/number filters.
     *        - 'first' => <integer>:
     *              Number of transfers to return (counting from first,
     *              ordered by date of creation)
     *        - 'last' => <integer>:
     *              Number of transfers to return (counting from last,
     *              ordered by date of creation in reverse)
     *        - 'since' => <timestamp_iso8601_basic>:
     *              Transfers newer than this point in time shall be returned.
     *        - 'until' => <timestamp_iso8601_basic>:
     *              Transfers older than this point in time shall be returned.
     * 
     * @return array
     *      List of transfers. Each list item is an associative array with the
     *      following keys:
     *        - 'id':
     *              Transfer ID, as integer.
     *        - 'amount':
     *              Amount transfered in VINGDs (floating point with two
     *              significant decimals)
     *        - 'uid_from':
     *              Source account UID.
     *        - 'uid_to':
     *              Destination account UID.
     *        - 'uid_proxy':
     *              Proxy (broker) account UID.
     *        - 'timestamp':
     *              Date and time of the transfer, as ISO 8601 extended string,
     *              with timezone and with space ' ' instead of 'T' separator.
     *        - 'description':
     *              Additional description dictionary (assoc array). Contains
     *              a 'class' key at minimum.
     *                - 'class':
     *                      One of the ten basic transfer classes (integer):
     *                        - 1: 'Purchase',
     *                        - 2: 'Ad reward',
     *                        - 3: 'Seller payout',
     *                        - 4: 'Ad deposit',
     *                        - 5: 'Currency exchange',
     *                        - 6: 'Direct transfer',
     *                        - 7: 'Refund',
     *                        - 8: 'Unverified purchase refund',
     *                        - 9: 'Voucher allocated',
     *                        - 10: 'Voucher deposited'
     *                - 'classname':
     *                      Name of the transfer class, as string.
     *                .
     *                Can also contain: 'oid' (Object ID), 'object' (Object
     *                name), names of the from/to accounts, 'count' (number of
     *                purchases paidout), 'fee' (Vingd fee upon purchases
     *                payout), and other.
     *
     * @throws VingdException, Exception
     * 
     */
    public function transfers(
        $uid = array('from' => null, 'to' => null),
        $limit = array('first' => null, 'last' => null, 'since' => null, 'until' => null)
    ) {
        $resource = "/fort/transfers";
        
        $allowed = array('from', 'to');
        if (!is_null($uid))
            foreach ($allowed as $name)
                if (array_key_exists($name, $uid) && !is_null($uid[$name]))
                    $resource .= "/$name={$uid[$name]}";
        
        $allowed = array('first', 'last', 'since', 'until');
        if (!is_null($limit))
            foreach ($allowed as $name)
                if (array_key_exists($name, $limit) && !is_null($limit[$name]))
                    $resource .= "/$name={$limit[$name]}";
        
        $transfers = $this->request('GET', $resource);
        
        foreach ($transfers as &$transfer) {
            $transfer['amount'] /= 100.0;
            $transfer['description'] = json_decode($transfer['description'], true);
            $transfer['description']['classname'] = $this->TRANSFER_CLASSNAMES[$transfer['description']['class']];
        }
        
        return $transfers;
    }
    
    /**
     * Makes a new voucher.
     *
     * @param float $amount Voucher amount in VINGDs.
     * @param date $until `strtotime`-acceptable timestamp of voucher expiry.
     *      (like '+1 day', '+1 month', etc.)
     * @param string $message A message user shall be presented with after
     *      submitting the voucher (on Vingd frontend).
     * @param string $gid Voucher Group ID (alphanumeric string:
     *      ``[-_a-zA-Z0-9]{1,32}``). User can use only one voucher per group.
     * @param string $description Voucher internal description. Optional, but
     *      can be helpful for tracking.
     *      
     * Important: user doing the request has to have 'voucher.add' permission
     * (if you don't have it, contact us).
     * 
     */
    public function createVoucher(
        $amount, $until = Vingd::EXP_VOUCHER, $message = '',
        $gid = null, $description = null
    ) {
        $params = array(
            "amount" => intval($amount * 100),
            "until" => $this->toIsoDate($until),
            "message" => $message,
            "description" => $description,
            "gid" => $gid
        );
        $res = $this->request('POST', "/vouchers/", json_encode($params));
        return $this->normalizeVoucher($res);
    }
    
    private function normalizeVoucher($raw) {
        $voucher = array(
            "amount" => $raw["amount_vouched"] / 100.0,
            "transferid" => $raw["id_fort_transfer"],
            "description" => $raw["description"],
            "message" => $raw["message"],
            "until" => $raw["ts_valid_until"],
            "created" => $raw["ts_created"],
            "code" => $raw["vid_encoded"],
            "gid" => $raw["gid"],
            "urls" => array(
                "redirect" => "{$this->frontend}/vouchers/{$raw['vid_encoded']}",
                "popup" => "{$this->frontend}/popup/vouchers/{$raw['vid_encoded']}"
            )
        );
        // voucher log entry?
        if (array_key_exists("action", $raw)) {
            $voucher["action"] = $raw["action"];
        }
        if (array_key_exists("timestamp", $raw)) {
            $voucher["timestamp"] = $raw["timestamp"];
        }
        return $voucher;
    }
    
    private function normalizeVoucherList($vouchers) {
        $filtered = array();
        foreach ($vouchers as &$raw) {
            $voucher = $this->normalizeVoucher($raw);
            array_push($filtered, $voucher);
        }
        return $filtered;
    }
    
    /**
     * Fetches a list of active vouchers for the authenticated user.
     */
    public function getActiveVouchers() {
        $vouchers = $this->request('GET', "/vouchers/");
        return $this->normalizeVoucherList($vouchers);
    }
    
    /**
     * Fetches a complete vouchers history (for the authenticated user).
     */
    public function getVouchers() {
        $vouchers = $this->request('GET', "/vouchers/history/");
        return $this->normalizeVoucherList($vouchers);
    }
}

?>
