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
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * Gets and sets the dependency's
     */
    public function __construct(
          \Magento\Framework\App\Helper\Context $context,
          \Belco\Widget\Model\ApiFactory $widgetApiFactory,
          \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $salesResourceModelOrderCollectionFactory,
          \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerResourceModelCustomerCollectionFactory,
          \Magento\Framework\Message\ManagerInterface $messageManager
      ){
        $this->widgetApiFactory = $widgetApiFactory;
        $this->salesResourceModelOrderCollectionFactory = $salesResourceModelOrderCollectionFactory;
        $this->customerResourceModelCustomerCollectionFactory = $customerResourceModelCustomerCollectionFactory;
        $this->messageManager = $messageManager;
        parent::__construct(
            $context
        );

        $this->api = $this->widgetApiFactory->create();
    }
      
    public function connectShop()
    {
        return $this->api->connect();
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

    public function log($message){
        return $this->logger->log(\Psr\Log\LogLevel::INFO, "Belco: " . $message);
    }

    public function debug($message){
        return $this->logger->log(\Psr\Log\LogLevel::DEBUG, "Belco: " . $message);
    }

    public function logError($message){
        return $this->logger->log(\Psr\Log\LogLevel::ERROR, "Belco: " . $message);
    }

    public function warnAdmin($warning){
        $this->messageManager->addWarning("Belco: " . $warning);
    }

    public function noticeAdmin($notice){
        $this->messageManager->addSuccess("Belco: " . $notice);
    }
}
