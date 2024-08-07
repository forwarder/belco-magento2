<?php
namespace Belco\Widget\Model;


/**
 * A small wrapper to communicate with the Belco API.
 * Class Belco_Widget_Model_Api
 */
class Api
{

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @var \Belco\Widget\Model\BelcoCustomer
     */
    private $belcoCustomer;

    /**
     * @var \Belco\Widget\Model\BelcoOrder
     */
    private $belcoOrder;

    /**
     * @var \Belco\Widget\Model\BelcoCustomerFactory
     */
    protected $widgetBelcoCustomerFactory;

    /**
     * @var \Belco\Widget\Model\BelcoOrderFactory
     */
    protected $widgetBelcoOrderFactory;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;
    
    /**
     * @var \Magento\Store\Model\StoreManagerInterface 
     */ 
    protected $storeManager;
    
    /**
   * Gets and sets the dependency's
   */
  public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Belco\Widget\Model\BelcoCustomerFactory $widgetBelcoCustomerFactory,
        \Belco\Widget\Model\BelcoOrderFactory $widgetBelcoOrderFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->logger = $logger;
        $this->widgetBelcoCustomerFactory = $widgetBelcoCustomerFactory;
        $this->widgetBelcoOrderFactory = $widgetBelcoOrderFactory;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;

        $this->belcoCustomer = $this->widgetBelcoCustomerFactory->create();
        $this->belcoOrder = $this->widgetBelcoOrderFactory->create();
  }

  public function connect() {
    $config = $this->scopeConfig->getValue('belco_settings/general', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    $data = array(
      'id' => $config['shop_id'],
      'type' => 'magento2',
      'url' => $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA)
    );
    return $this->post('shops/connect', $data);
  }

  /**
   * Synchonizes customer data to Belco
   *
   * @param $customer
   * @return mixed
   */
  public function syncCustomer($customer)
  {
    return $this->post('sync/customer', $this->toBelcoCustomer($customer));
  }

  /**
   * Delete a customer in Belco
   *
   * @param $customer
   * @return mixed
   */
  public function deleteCustomer($id)
  {
    return $this->post('sync/customer/delete', array('id' => $id));
  }

  /**
   * Sync order data to Belco
   *
   * @param $order
   * @return mixed
   */
  public function trackOrder($order) {
    $data = $this->toBelcoOrder($order);
    $identity = $data['customer'];
    unset($data['customer']);

    $event = 'Order Completed';
    if ($data['status'] === 'cancelled') {
        $event = 'Order Cancelled';
    }

    return $this->trackEvent(array(
        'type' => 'track',
        'event' => $event,
        'identity' => $identity,
        'sentAt' => $data['date'],
        'properties' => $data
    ));
  }

  /**
   * Track an event in Belco
   *
   * @param $event
   * @return mixed
   */
  public function trackEvent($event)
  {
    return $this->post('v1/t', $event);
  }

  /**
   * Converts a Mage_Customer_Model_Customer into a simple array
   * with key/value pairs that are required by the Belco API.
   *
   * @param \Magento\Customer\Model\Customer $customer
   * @return array
   */
  public function toBelcoCustomer(\Magento\Customer\Model\Customer $customer)
  {
    return $this->belcoCustomer->factory($customer);
  }


  /**
   * Converts a Mage_Sales_Model_Order into a array with required key/value pairs and a
   * example details_view.
   *
   * @param \Magento\Sales\Model\Order $order
   * @return array
   */
  public function toBelcoOrder(\Magento\Sales\Model\Order $order)
  {
    return $this->belcoOrder->factory($order);
  }

  public function log($message){
    return $this->logger->log(\Psr\Log\LogLevel::INFO, "Belco: " . $message);
  }

  public function debug($message){
      return $this->logger->log(\Psr\Log\LogLevel::DEBUG, "Belco: " . $message);
  }

  public function logError($message){
      return $this->logger->log(\Psr\Log\LogLevel::ERROR, "Belco: " . $message);
  }

  /**
   * Posts the values as a json string to the Belco API endpoint given in $request.
   *
   * @param $path
   * @param $data
   * @throws \Exception
   * @return mixed
   */
  private function post($path, $data)
  {
    $config = $this->scopeConfig->getValue('belco_settings/general', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    $errorCodes = array(500, 400, 401);
    $data = json_encode($data);

    if (empty($config['api_secret'])) {
      $this->log('Missing API configuration, go to System -> Configuration -> Belco.io -> Settings and fill in your API credentials');
      return false;
    }

    $url = $config['api_url'] . $path;

    $signature = hash_hmac('sha256', $data, $config['api_secret']);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      'Content-Type: application/json',
      'Content-Length: ' . strlen($data),
      'X-Signature: ' . $signature,
      'X-Shop-Id: ' . $config['shop_id']
    ));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    curl_exec($ch);

    if (curl_errno($ch)) {
      $this->logError("Curl error: " . curl_error($ch));
      curl_close($ch);
      return false;
    }

    $responseInfo = curl_getinfo($ch);

    if (in_array($responseInfo['http_code'], $errorCodes)) {
      $this->logError("Request failed with code " . $responseInfo['http_code'] . "");
      curl_close($ch);
      return false;
    }

    curl_close($ch);

    return true;
  }
}
