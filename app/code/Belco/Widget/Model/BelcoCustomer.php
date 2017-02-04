<?php
namespace Belco\Widget\Model;

class BelcoCustomer {

  /**
   * @var \Magento\Customer\Model\Customer
   */
  private $customer;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    protected $salesResourceModelOrderCollectionFactory;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Sale\CollectionFactory
     */
    protected $salesResourceModelSaleCollectionFactory;

    public function __construct(
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $salesResourceModelOrderCollectionFactory,
        \Magento\Sales\Model\ResourceModel\Sale\CollectionFactory $salesResourceModelSaleCollectionFactory
    ) {
        $this->salesResourceModelOrderCollectionFactory = $salesResourceModelOrderCollectionFactory;
        $this->salesResourceModelSaleCollectionFactory = $salesResourceModelSaleCollectionFactory;
    }
    /**
   * Factory method for creating an array with key/value pairs
   * the Belco API expects.
   *
   * @param \Magento\Customer\Model\Customer $customer
   * @return array
   */
  public function factory(\Magento\Customer\Model\Customer $customer){
    $this->customer = $customer;
    return $this->make();
  }

  /**
   * Makes the array the Belco API expects.
   *
   * @return array
   */
  private function make(){
    $lifetime = $this->getLifeTimeSalesCustomer();

    $belcoCustomer = array(
      'id' => $this->customer->getId(),
      'email' => $this->customer->getEmail(),
      'name' => $this->customer->getName(),
      'firstName' => $this->customer->getFirstname(),
      'lastName' => $this->customer->getLastname(),
      'signedUp' => strtotime($this->customer->getCreatedAt()),
      'orders' => $lifetime->getNumOrders(),
      'totalSpent' => $lifetime->getLifetime()
    );

    if ($lastOrder = $this->getLastOrder()) {
      if ($lastOrderDate = strtotime($lastOrder->getCreatedAt())) {
        $belcoCustomer['lastOrder'] = $lastOrderDate;
      }
    }

    $address = $this->customer->getDefaultBillingAddress();

    if (!empty($address)) {
      $belcoCustomer = array_merge($belcoCustomer, array(
        'phoneNumber' => $address->getTelephone(),
        'country' => $address->getCountry(),
        'city' => $address->getCity()
      ));
    }

    return $belcoCustomer;
  }

  function getLastOrder() {
    return $this->salesResourceModelOrderCollectionFactory->create()
      ->addFieldToSelect('created_at')
      ->addFieldToSelect('customer_id')
      ->addFieldToFilter('customer_id', $this->customer->getId())
      ->addAttributeToSort('created_at', 'DESC')
      ->getFirstItem();
  }

  /**
   * Gets customer statics like total order count and total spend.
   *
   * @return array
   */
  function getLifeTimeSalesCustomer() {
    return $this->salesResourceModelSaleCollectionFactory->create()
      // ->setOrderStateFilter(null)
      ->addFieldToFilter('customer_id', $this->customer->getId())
      ->load()
      ->getTotals();
  }
}
