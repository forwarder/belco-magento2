<?php
namespace Belco\Widget\Observer;
 
use Magento\Framework\Event\ObserverInterface;
 
class CustomerHook implements ObserverInterface
{
    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @var \Belco\Widget\Model\Api
     */
    private $api;

    /**
     * @var \Belco\Widget\Helper\Data
     */
    private $helper;
 
    /**
     * @param \Belco\Widget\Helper\Data $widgetHelper
     */
    public function __construct(
        \Belco\Widget\Helper\Data $widgetHelper
    ) {
        $this->helper = $widgetHelper;
        $this->api = $this->helper->getApi();
    }
 
    /**
     * customer register event handler
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $customer = $observer->getEvent()->getCustomer();

        if ($customer) {
            try {
                $this->api->syncCustomer($customer);
            } catch (Exception $e) {
                $this->helper->log("Exception: " . $e->getMessage());
            }
        }
    }
}