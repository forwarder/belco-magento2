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
     * @var \Magento\Sales\Model\Order\ShipmentFactory
     */
    protected $salesOrderShipmentFactory;

    public function __construct(
        \Magento\Backend\Helper\Data $backendHelper,
        // \Magento\Sales\Model\Order\ShipmentFactory $salesOrderShipmentFactory
    ) {
        $this->backendHelper = $backendHelper;
        // $this->salesOrderShipmentFactory = $salesOrderShipmentFactory;
    }
    /**
   * Factory method for creating an array with data that is
   * required by the Belco API.
   *
   * @param \Magento\Sales\Model\Order $order
   * @return array
   */
  public function factory(\Magento\Sales\Model\Order $order)
  
    $this->order = $order;

    // $this->shipments = $this->order->getShipmentsCollection();
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
    // $order['products'] = $this->getProducts();
    // $order['payment'] = $this->getPayment();
    // $order['shipping'] = $this->getShipping();
    // $order['shipments'] = $this->getShipments();
    // $order['invoices'] = $this->getInvoices();
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
      'id' => $this->order->getIncrementId(),
      'number' => $this->order->getIncrementId(),
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
  private function getCustomer() {
    $address = $this->order->getBillingAddress();
    $customer = array(
      'id' => $this->order->getCustomerId(),
      'name' => $this->order->getCustomerName(),
      'email' => $this->order->getCustomerEmail(),
      'phoneNumber' => $address->getTelephone(),
      'city' => $address->getCity(),
      'country' => $address->getCountry(),
      'lastOrder' => strtotime($this->order->getCreatedAt()),
      'lastVisit' => time(),
      'ipAddress' => $this->order->getRemoteIp()
    );
    return $customer;
  }

  /**
   * Gets the Details view as first item of the 'details_view' key
   * @return array
   */
  private function getPayment()
  {
    $payment = $this->order->getPayment()->getMethodInstance();
    $status = $payment->getStatus();
    return array(
      'method' => $payment->getTitle(),
      'status' => ($status === null) ? 'open' : $status
    );
  }

  private function getShipping()
  {
    return array(
      'method' => $this->order->getShippingDescription(),
      'total' => $this->order->getBaseShippingAmount()
    );
  }

  /**
   * When there are shipments, it lists them and their status.
   * Otherwise the 'data' key stays empty.
   * @return array
   */
  private function getShipments()
  {
    $shipments = array();
    $orderStatus = $this->order->getState();
    if ($this->shipments !== false) {
      foreach ($this->shipments as $shipment) {
        $shipments[] = array(
          'id' => $shipment->getIncrementId(),
          'name' => $shipment->getTitle(),
          'url' => $this->getShipmentUrl($shipment),
          'status' => ($orderStatus === \Magento\Sales\Model\Order::STATE_COMPLETE) ? 'shipped' : 'processing'
        );
      }
    }

    return $shipments;
  }

  /**
   * When there are invoices it lists them with a link, status and the price.
   *
   * @return array
   */
  private function getInvoices()
  {
    $invoices = array();
    if ($this->order->hasInvoices()) {
      foreach ($this->order->getInvoiceCollection() as $invoice) {
        $invoices[] = array(
          'id' => $invoice->getIncrementId(),
          'url' => $this->getInvoiceAdminUrl($invoice),
          'status' => $this->getInvoiceStatus($invoice),
          'total' => $invoice->getBaseGrandTotal()
        );
      }
    }

    return $invoices;
  }

  /**
   * @return array
   */
  private function getProducts()
  {
    $products = array();
    $items = $this->order->getAllItems();
    foreach ($items as $item) {
      $products[] = array(
        'name' => $item->getName(),
        'quantity' => (int)$item->getQtyOrdered(),
        'price' => $item->getPrice()
      );
    }
    return $products;
  }

  /**
   * @return mixed
   */
  private function getOrderAdminUrl()
  {
    return $this->backendHelper->getUrl(
      'adminhtml/sales_order/view',
      array('order_id' => $this->order->getId(), '_type' => \Magento\Store\Model\Store::URL_TYPE_WEB)
    );
  }

  /**
   * @param $shipment
   * @return mixed
   */
  private function getShipmentUrl($shipment)
  {
    $shipmentId = $this->salesOrderShipmentFactory->create()
      ->loadByIncrementId($shipment->getIncrementId())
      ->getId();

    return $this->backendHelper
      ->getUrl(
        'adminhtml/sales_shipment/view',
        array('shipment_id' => $shipmentId, '_type' => \Magento\Store\Model\Store::URL_TYPE_WEB)
    );
  }

  /**
   * @param $invoice
   * @return string
   */
  private function getInvoiceStatus($invoice)
  {
    $stateCode = $invoice->getState();
    switch ($stateCode) {
      case \Magento\Sales\Model\Order\Invoice::STATE_CANCELED:
        $state = 'canceled';
        break;
      case \Magento\Sales\Model\Order\Invoice::STATE_PAID:
        $state = 'paid';
        break;
      default:
        $state = 'open';
        break;
    }
    return $state;
  }


  /**
   * @param $invoice
   * @return string
   */
  private function getInvoiceAdminUrl($invoice)
  {
    return $this->backendHelper
      ->getUrl(
        'adminhtml/sales_invoice/view',
        array('invoice_id' => $invoice->getId(), '_type' => \Magento\Store\Model\Store::URL_TYPE_WEB)
      );
  }
}
