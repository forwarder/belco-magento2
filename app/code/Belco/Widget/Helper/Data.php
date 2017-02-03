<?php
namespace Belco\Widget\Helper;


/**
 * Class Belco_Widget_Helper_Data
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{

    /**
     * @var \Belco\Widget\Model\Api
     */
    private $api;

    /**
     * @var \Belco\Widget\Model\ApiFactory
     */
    protected $widgetApiFactory;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    protected $salesResourceModelOrderCollectionFactory;

    /**
     * @var \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory
     */
    protected $customerResourceModelCustomerCollectionFactory;

    /**
     * @var \Magento\Backend\Model\Session
     */
    protected $backendSession;

    /**
     * Gets and sets the dependency's
     */
    public function __construct(
          \Magento\Framework\App\Helper\Context $context,
          \Belco\Widget\Model\ApiFactory $widgetApiFactory,
          \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $salesResourceModelOrderCollectionFactory,
          \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerResourceModelCustomerCollectionFactory,
          \Magento\Backend\Model\Session $backendSession
      ){
        $this->widgetApiFactory = $widgetApiFactory;
        $this->salesResourceModelOrderCollectionFactory = $salesResourceModelOrderCollectionFactory;
        $this->customerResourceModelCustomerCollectionFactory = $customerResourceModelCustomerCollectionFactory;
        $this->backendSession = $backendSession;
        parent::__construct(
            $context
        );

        $this->api = $this->widgetApiFactory->create();
    }

    /**
     * Sends all orders to the Belco API.
     */
    public function sendOrders(){
        $collection = $this->salesResourceModelOrderCollectionFactory->create();
        $this->api->syncOrders($collection);
    }

    /**
     * Sends all customers to the Belco API.
     */
    public function sendCustomers(){
        $collection = $this->customerResourceModelCustomerCollectionFactory->create()
          ->addNameToSelect()
          ->addAttributeToSelect('email')
          ->addAttributeToSelect('created_at');

        $this->api->syncCustomers($collection);
    }
      
    public function connectShop()
    {
        $result = $this->api->connect();
    }

    /**
     * @param $message
     */
    public function log($message){
        $this->logger->log(null, $message);
    }

    /**
     * @return \Belco\Widget\Model\Api
     */
    public function getApi(){
        return $this->api;
    }

    /**
     * @return \Psr\Log\LoggerInterface
     */
    public function getLogger(){
        return $this->logger;
    }

    public static function warnAdmin($warning){
        $this->backendSession->addWarning("Belco: " . $warning);
    }

    public static function noticeAdmin($notice){
        $this->backendSession->addSuccess("Belco: " . $notice);
    }

    public static function formatPrice($price){
        return Mage::helper('core')->currency($price, true, false);
    }
}
   