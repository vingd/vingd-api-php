<?php

/**
 * Vingd API interface for PHP.
 *
 * @version 1.7
 * @date 2014-01-14
 * @author Radomir Stevanovic <radomir@vingd.com>
 * @copyright Copyright 2012 Vingd, Inc.
 * @package vingd-api-php
 * @link https://github.com/vingd/vingd-api-php
 * @license http://creativecommons.org/licenses/MIT/ MIT
 * 
 */

require_once("http.php");
require_once("util.php");


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


/**
 * Vingd (service-side) errors are thrown as VingdException.
 */
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


/**
 * Vingd API interface class.
 */
class Vingd {
    const USER_AGENT = 'vingd-api-php/1.7';

    // production urls
    const URL_ENDPOINT = 'https://api.vingd.com/broker/v1';
    const URL_FRONTEND = 'https://www.vingd.com';
    
    // sandbox urls
    const URL_ENDPOINT_SANDBOX = 'https://api.vingd.com/sandbox/broker/v1';
    const URL_FRONTEND_SANDBOX = 'http://www.sandbox.vingd.com';
    
    // default expiry date for object order: 15 minutes from now
    const EXP_ORDER = '+15 minutes';
    
    // default expiry date for user-created vouchers: 1 month from now
    const EXP_VOUCHER = '+1 month';
    
    // date format used for display
    const DATE_HUMAN = 'F j, Y \a\t H:i:s O';
    
    // date format used by vingd broker (ISO 8601)
    const DATE_ISO = 'c';
    const DATE_ISO_BASIC = 'Ymd\THisO';
    
    // unix epoch timestamp
    const DATE_UNIX = 'U';
    
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
    private $apikey = null;
    private $apisecret = null;
    private $endpoint = Vingd::URL_ENDPOINT;
    private $frontend = Vingd::URL_FRONTEND;
    
    /**
     * Vingd API initialization.
     *
     * @param string $username Vingd username.
     * @param string $password Vingd password.
     * @param string $endpoint URL of Vingd Broker (backend).
     * @param string $frontend URL of Vingd user frontend.
     */
    function __construct(
        $username = null, $password = null,
        $endpoint = null, $frontend = null
    ) {
        $this->init($username, $password, $endpoint, $frontend);
    }
    
    /**
     * Vingd API (re-)initialization.
     * @see __construct()
     */
    public function init(
        $username, $password,
        $endpoint = null, $frontend = null
    ) {
        $this->apikey = $username;
        $this->apisecret = sha1($password);
        if ($endpoint) $this->endpoint = $endpoint;
        if ($frontend) $this->frontend = $frontend;
    }
    
    // issues a https request to vingd broker ($this->endpoint)
    private function request($verb, $resource, $data = '') {
        try {
            // the WP way of doing http request (WP_Http is introduced in wp 2.7)
            $request = new Http();
            $result = $request->request(
                $this->endpoint . $resource,
                array(
                    'method' => $verb,
                    'body' => $data,
                    'auth' => 'basic',
                    'username' => $this->apikey,
                    'password' => $this->apisecret,
                    'sslverify' => true,
                    'headers' => array('user-agent' => self::USER_AGENT)
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
    
    private function toIsoBasicDate($timestamp, $default = null) {
        return $this->formatDate(self::DATE_ISO_BASIC, $timestamp, $default);
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
    private function concatURL($base, $params = '') {
        if (!strlen($params)) return $base;
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
    private function buildURL($base, $params = array()) {
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
     * Creates (registers) an object in the Vingd Object Registry.
     *
     * @param string $name Object's name.
     * @param string $url Object's callback URL.
     * 
     * @return integer Object ID assigned in Vingd.
     * @throws VingdException, Exception
     */
    public function createObject($name, $url) {
        $data = array(
            "description" => array(
                "name" => $name,
                "url" => $url
            )
        );
        $ret = $this->request('POST', "/registry/objects/", json_encode($data));
        return $this->unpackBatchResponse($ret, 'oid');
    }
    
    /**
     * Updates an object enrolled in the Vingd Object Registry.
     *
     * @param integer $oid Object ID, as returned by `createObject()`.
     * @param string $name Object's new name.
     * @param string $url Object's new callback URL.
     * 
     * @return integer Object ID assigned in Vingd.
     * @throws VingdException, Exception
     */
    public function updateObject($oid, $name, $url) {
        $data = array(
            "description" => array(
                "name" => $name,
                "url" => $url
            )
        );
        $ret = $this->request(
            'PUT',
            safeformat("/registry/objects/{:int}/", $oid),
            json_encode($data)
        );
        return $this->unpackBatchResponse($ret, 'oid');
    }
    
    /**
     * Fetches object with id `$oid`.
     *
     * @return Array Vingd object description.
     * @throws VingdException, Exception
     */
    public function getObject($oid) {
        return $this->request(
            'GET',
            safeformat("/registry/objects/{:int}/", $oid)
        );
    }
    
    /**
     * Fetches all objects for the authenticated user.
     *
     * @return List of Arrays A list of Vingd objects.
     * @throws VingdException, Exception
     */
    public function getObjects() {
        return $this->request('GET', "/registry/objects/");
    }

    /**
     * Creates an order for object `$oid`, with price set to `$price` and
     * validity until `$expires`.
     * 
     * @param integer $oid
     *      Identifier of the object to be sold (see `createObject()`).
     * @param float $price
     *      Object's price in VINGDs. Rounded to two decimal digits.
     * @param string $context
     *      Arbitrary (user-defined) context handle of this purchase. The
     *      $context shall be retrieved upon purchase/token verification.
     *      (Usage discouraged for sensitive data.)
     * @param string $expires
     *      Expiry timestamp / validity period of the order being generated
     *      (accepts any PHP parsable date/time string, i.e. any
     *      `strtotime`-compatible timestamp, incl. ISO 8601 basic/extended).
     *      Default: '+15 minutes' (== order expires in 15 minutes)
     * 
     * @return array Vingd order description.
     * @throws VingdException, Exception
     */
    public function createOrder(
        $oid, $price, $context = null,
        $expires = Vingd::EXP_ORDER
    ) {
        $data = array(
            "price" => intval($price * 100),
            "order_expires" => $this->toIsoBasicDate($expires),
            "context" => $context
        );
        $ret = $this->request(
            'POST',
            safeformat("/objects/{:int}/orders", $oid),
            json_encode($data)
        );
        $id = $this->unpackBatchResponse($ret);
        $order = array(
            "id" => $id,
            "expires" => $this->toIsoDate($expires),
            "context" => $context,
            "object" => array(
                "id" => $oid,
                "price" => $data['price']
            ),
            "urls" => array(
                "redirect" => $this->buildURL("{$this->frontend}/orders/$id/add/"),
                "popup" => $this->buildURL("{$this->frontend}/popup/orders/$id/add/")
            )
        );
        return $order;
    }
    
    /**
     * Verifies purchase token `$tid` and returns token data associated with it
     * and bound to object `$oid`.
     *
     * If token was invalid (purchase can not be verified), a VingdException is
     * thrown.
     *
     * @param array $token
     * @param string $token
     *      Access token user brings in, as returned from Vingd user frontent via
     *      callback link.
     *      \n
     *      verifyPurchase() accepts $token as either \b string (as read from
     *      \tt{$_GET['token']} on callback processor), \i or as
     *      <b>PHP Array</b> (json-decoded from the URL).
     *      \n
     *      You should always verify the token the user brings in from Vingd
     *      frontend to ensure the user has access rights for your object and/or
     *      service. Successful token verification guarantees Vingd Broker has
     *      reserved user vingds for the seller. Those vingds will be transfered
     *      after seller commits the purchase (see `commitPurchase()`)
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
     *              Vingd transfer ID
     *        - 'context':
     *              Order context
     * 
     * @throws VingdException, Exception
     */
    public function verifyPurchase($token) {
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
        return $this->request(
            'GET',
            safeformat("/objects/{:int}/tokens/{:hex}", $oid, $tid)
        );
    }
    
    /**
     * Commits the purchase (defined with `$purchaseid` and `$transferid`) as
     * finished.
     *
     * Call commitPurchase() upon successful delivery of paid content
     * to the user. If you do not call commitPurchase() user shall be
     * automatically refunded.
     * 
     * @param array $purchase
     *      User purchase description, as returned by `verifyPurchase()` upon
     *      successful token verification.
     * 
     * @return array
     *      - On success: {'ok' => true}
     *      - On failure: VingdException is thrown
     * 
     * @throws VingdException, Exception
     */
    public function commitPurchase($purchase) {
        $purchaseid = $purchase['purchaseid'];
        $transferid = $purchase['transferid'];
        return $this->request(
            'PUT',
            safeformat("/purchases/{:int}", $purchaseid),
            json_encode(array("transferid" => $transferid))
        );
    }
    
    /**
     * Fetches profile of the authenticated user.
     * 
     * @return array User profile
     * @throws VingdException, Exception
     */
    public function getUserProfile() {
        return $this->request('GET', '/id/users/username='.$this->apikey);
    }
    
    /**
     * Shorthand to fetch authenticated user's id (integer).
     *
     * @return int User ID.
     * @throws VingdException, Exception
     */
    public function getUserId() {
        $profile = $this->getUserProfile();
        return $profile['uid'];
    }
    
    /**
     * Shorthand to fetch authenticated user's balance.
     * 
     * @return float Account balance.
     * @throws VingdException, Exception
     */
    public function getAccountBalance() {
        $account = $this->request('GET', '/fort/accounts/');
        return $account['balance'] / 100.0;
    }
    
    /**
     * Shorthand to fetch account balance for the user defined with `huid`.
     *
     * @return float Account balance.
     * @throws VingdException, Exception
     */
    public function authorizedGetAccountBalance($huid) {
        $account = $this->request(
            'GET',
            safeformat('/fort/accounts/{:hex}', $huid)
        );
        return $account['balance'] / 100.0;
    }

    /**
     * Does delegated (pre-authorized) purchase of `oid` in the name of `huid`,
     * at price `price` (vingd transferred from `huid` to consumer's acc).
     *
     * @throws VingdException, Exception
     */
    public function authorizedPurchaseObject($oid, $price, $huid) {
        return $this->request(
            'POST',
            safeformat('/objects/{:int}/purchases', $oid),
            json_encode(array(
                'price' => $price,
                'huid' => $huid,
                'autocommit' => true,
            ))
        );
    }

    /**
     * Creates Vingd user (profile & account), links it with the provided
     * identities (to be verified later), and sets the delegate-user
     * permissions (creator being the delegate). Returns Vingd user's `huid`
     * (hashed user id).
     *
     * @return string huid.
     * @throws VingdException, Exception
     */
    public function authorizedCreateUser(
        $identities = null, $primary = null, $permissions = null
    ) {
        return $this->request(
            'POST',
            '/id/users/',
            json_encode(array(
                'identities' => $identities,
                'primary_identity' => $primary,
                'delegate_permissions' => $permissions
            ))
        );
    }

    /**
     * Fetches a filtered list of vingd transfers related to the authenticated
     * user.
     * 
     * Filtering is possible with UID, date and count.
     * 
     * Usage example:
     * \code
     *      $v = new Vingd(...);
     *      $transfers = $v->getTransfers(
     *          array('to'=>$v->getUserId()),
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
     *        Note: at least one of these keys MUST be specified, and it MUST be
     *        equal to the authenticated user UID.
     * 
     * @param array $limit
     *      Date/count filters.
     *        - 'first' => <integer>:
     *              Number of transfers to return (counting from first,
     *              ordered by date of creation)
     *        - 'last' => <integer>:
     *              Number of transfers to return (counting from last,
     *              ordered by date of creation in reverse)
     *        - 'since' => <`strtotime`-compatible timestamp, incl. ISO8601>:
     *              Transfers newer than this point in time shall be returned.
     *        - 'until' => <`strtotime`-compatible timestamp, incl. ISO8601>:
     *              Transfers older than this point in time shall be returned.
     * 
     * @return array
     *      A list of transfers. Each list item is an associative array with the
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
    public function getTransfers(
        $uid = array('from' => null, 'to' => null),
        $limit = array('first' => null, 'last' => null, 'since' => null, 'until' => null)
    ) {
        $resource = "/fort/transfers";
        
        $allowed = array('from', 'to');
        if (!is_null($uid))
            foreach ($allowed as $name)
                if (array_key_exists($name, $uid) && !is_null($uid[$name]))
                    $resource .= safeformat("/$name={:int}", $uid[$name]);
        
        $allowed = array('first', 'last');
        if (!is_null($limit))
            foreach ($allowed as $name)
                if (array_key_exists($name, $limit) && !is_null($limit[$name]))
                    $resource .= safeformat("/$name={:int}", $limit[$name]);
        
        $allowed = array('since', 'until');
        if (!is_null($limit))
            foreach ($allowed as $name)
                if (array_key_exists($name, $limit) && !is_null($limit[$name]))
                    $resource .= "/$name=".$this->toIsoBasicDate($limit[$name]);
        
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
     * @param date $until `strtotime`-compatible timestamp of voucher expiry,
     *      incl. ISO 8601 basic/extended (like '+1 day', '+1 month', etc.).
     * @param string $message A message user shall be presented with after
     *      submitting the voucher (on Vingd frontend).
     * @param string $gid Voucher Group ID (alphanumeric string:
     *      ``[-_a-zA-Z0-9]{1,32}``). User can use only one voucher per group.
     * @param string $description Voucher internal description. Optional, but
     *      can be helpful for tracking.
     *
     * @return array Voucher description.
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
            "until" => $this->toIsoBasicDate($until),
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
            "until" => $this->toIsoDate($raw["ts_valid_until"]),
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
        if (array_key_exists("ts_created", $raw)) {
            $voucher["created"] = $raw["ts_created"];
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
    
    /**
     * Rewards user defined with `$huid` with `$amount` vingds, transfered from
     * the account of the authenticated user.
     *
     * @param string(40) $huid Hashed User ID, bound to account of the
     *      authenticated user (doing the request).
     * @param float $amount Amount in vingds.
     * @param string $description Transaction description (optional).
     *
     * @note The consumer has to have ``transfer.outbound`` ACL flag set.
     *
     * @returns array ('transfer_id' => <transfer_id>)
     * @throws VingdException, Exception
     * 
     */
    public function rewardUser($huid, $amount, $description = null) {
        return $this->request('POST', '/rewards/', json_encode(array(
            'huid_to' => $huid,
            'amount' => intval($amount * 100),
            'description' => $description
        )));
    }
    
}

?>
