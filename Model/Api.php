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
   * Synchronizes order data to Belco
   *
   * @param $order
   * @return mixed
   */
  public function syncOrder($order)
  {
    $order = $this->toBelcoOrder($order);
    return $this->post('sync/customer', $order['customer']);
  }

  /**
   * Converts a Mage_Customer_Model_Customer into a simple array
   * with key/value pairs that are required by the Belco API.
   *
   * @param \Magento\Customer\Model\Customer $customer
   * @return array
   */
  private function toBelcoCustomer(\Magento\Customer\Model\Customer $customer)
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
  private function toBelcoOrder(\Magento\Sales\Model\Order $order)
  {
    return $this->belcoOrder->factory($order);
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
      throw new \Exception(
        'Missing API configuration, go to System -> Configuration -> Belco.io -> Settings and fill in your API credentials'
      );
    }

    $url = $config['api_url'] . $path;

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      'Content-Type: application/json',
      'Content-Length: ' . strlen($data),
      'X-Api-Key: ' . $config['api_secret']
    ));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CAINFO, dirname(dirname(__FILE__)) . '/etc/cabundle.crt');

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
      $this->logger->log(\Psr\Log\LogLevel::ERROR, "Curl error: " . curl_error($ch));
    }

    if ($response === false) {
      curl_close($ch);
      throw new \Exception("Error: 'Request to Belco failed'");
    }

    $response = json_decode($response);

    $responseInfo = curl_getinfo($ch);

    curl_close($ch);

    if (in_array($responseInfo['http_code'], $errorCodes)) {
      throw new \Exception("Error: '" . $response->message . "'");
    }

    return $response;
  }

}
