<?php
namespace Belco\Widget\Model;

/**
 * Class Belco_Widget_Model_BelcoOrder
 */
class BelcoOrder
{

    /**
     * @var \Magento\Sales\Model\Order
     */
    private $order;

    /**
     * @var \Magento\Backend\Helper\Data
     */
    protected $backendHelper;

    /**
     * @var \Magento\Sales\Model\CustomerFactory
     */
    protected $customerCustomerFactory;

    /**
     * @var \Belco\Widget\Model\BelcoCustomer
     */
    protected $belcoCustomer;

    public function __construct(
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Customer\Model\CustomerFactory $customerCustomerFactory,
        \Belco\Widget\Model\BelcoCustomerFactory $widgetBelcoCustomerFactory
    ) {
        $this->backendHelper = $backendHelper;
        $this->customerCustomerFactory = $customerCustomerFactory;
        $this->belcoCustomer = $widgetBelcoCustomerFactory->create();
    }
    /**
   * Factory method for creating an array with data that is
   * required by the Belco API.
   *
   * @param \Magento\Sales\Model\Order $order
   * @return array
   */
  public function factory(\Magento\Sales\Model\Order $order)
  {
    $this->order = $order;
    return $this->make();
  }

  /**
   * Starts the construction of the belcoOrder array by generating
   * the base and getting the details view.
   *
   * @return array
   */
  private function make()
  {
    $order = $this->getOrder();
    $order['customer'] = $this->getCustomer();

    return $order;
  }

  /**
   * Gets the base info for a order, contains the required parts
   * for the Belco API.
   * @return array
   */
  private function getOrder()
  {
    $date = strtotime($this->order->getCreatedAt());
    $currency = $this->order->getBaseCurrency();
    return array(
      'orderId' => $this->order->getIncrementId(),
      'url' => $this->getOrderAdminUrl(),
      'date' => $date,
      'status' => $this->order->getStatus(),
      'total' => $this->order->getBaseGrandTotal(),
      'currency' => $currency->getCode()
    );
  }

  /**
   * Gets the customer info for a order, contains the required parts
   * for the Belco API.
   * @return array
   */
  private function getCustomer()
  {
    $customerId = $this->order->getCustomerId();

    if ($customerId === NULL) {
      $address = $this->order->getBillingAddress();
      $customer = array(
        'name' => $address->getName(),
        'firstName' => $address->getFirstname(),
        'lastName' => $address->getLastname(),
        'email' => $this->order->getCustomerEmail(),
        'phoneNumber' => $address->getTelephone(),
        'city' => $address->getCity(),
        'country' => $address->getCountryId(),
        'lastOrder' => strtotime($this->order->getCreatedAt()),
        'lastVisit' => time(),
        'ipAddress' => $this->order->getRemoteIp()
      );
    } else {
      $customer = $this->belcoCustomer->factory(
        $this->customerCustomerFactory->create()->load($customerId)
      );
    }

    return $customer;
  }

  /**
   * @return mixed
   */
  private function getOrderAdminUrl()
  {
    return $this->backendHelper->getUrl(
      'adminhtml/sales_order/view',
      array('order_id' => $this->order->getId())
    );
  }
}
