<?php
namespace Belco\Widget\CustomerData;

use Magento\Customer\CustomerData\SectionSourceInterface;

class BelcoConfig implements SectionSourceInterface
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Belco\Widget\Model\BelcoCustomerFactory
     */
    protected $widgetBelcoCustomerFactory;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $customerCustomerFactory;

    /**
     * @var \Magento\Checkout\Helper\Cart
     */
    protected $checkoutCartHelper;

    /**
     * @var \Belco\Widget\Model\BelcoCustomer
     */
    protected $belcoCustomer;


    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Customer\Model\CustomerFactory $customerCustomerFactory,
        \Magento\Checkout\Helper\Cart $checkoutCartHelper,
        \Belco\Widget\Model\BelcoCustomerFactory $widgetBelcoCustomerFactory
    ) {
      $this->scopeConfig = $scopeConfig;
      $this->customerSession = $customerSession;
      $this->customerCustomerFactory = $customerCustomerFactory;
      $this->checkoutCartHelper = $checkoutCartHelper;
      $this->belcoCustomer = $widgetBelcoCustomerFactory->create();
    }

    public function getSectionData() {
      return $this->getConfig();
    }

    protected function getConfig() {
        $settings = $this->scopeConfig->getValue('belco_settings/general', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        if (empty($settings['shop_id'])) {
          return array();
        }

        $secret = $settings['api_secret'];

        $config = array(
          'shopId' => $settings['shop_id']
        );

        if ($this->customerSession->isLoggedIn()) {
          $customer = $this->customerCustomerFactory->create()->load($this->customerSession->getCustomer()->getId());

          if ($secret) {
            $config['hash'] = hash_hmac("sha256", $customer->getId(), $secret);
          }
          $config = array_merge($config, $this->belcoCustomer->factory($customer));
        }

        if ($cart = $this->getCart()) {
          $config['cart'] = $cart;
        }

        return $config;
    }

    protected function getCart() {
        $cart = $this->checkoutCartHelper->getCart();

        $quote = $cart->getQuote();
        $items = $quote->getAllVisibleItems();

        $config = array(
          'items' => array(),
          'total' => $quote->getGrandTotal()
        );

        foreach ($items as $item) {
          $product = $item->getProduct();

          $config['items'][] = array(
            'id' => $product->getId(),
            'quantity' => $item->getQty(),
            'name' => $item->getName(),
            'price' => $item->getPrice(),
            'url' => $product->getProductUrl()
          );
        }

        if (count($config['items'])) {
          return $config;
        }
    }

}