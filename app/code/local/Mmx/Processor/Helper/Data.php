<?php

class Mmx_Processor_Helper_Data extends Mage_Core_Helper_Abstract {
    
    /**
     * Copies serialised items (e.g. INCIENABOM/INBTRESERVATION) into a new order
     * 
     * @param Mage_Sales_Model_Order $sourceOrder
     * @return Mage_Sales_Model_Order
     */
    public function splitOrder($sourceOrder) {

        $orderId = $sourceOrder->getId();

        /* @var $order Mage_Sales_Model_Order */
        $order = Mage::getModel('sales/order')->load($orderId);

        /* @var $customer Mage_Customer_Model_Customer */
        $customer = Mage::getModel('customer/customer')->load($order->getCustomerId());

        /* @var $quote Mage_Sales_Model_Quote */
        $quote = Mage::getModel('sales/quote')
                ->setStoreId($sourceOrder->getStore()->getId())
                ->assignCustomer($customer);

        /* @var $orderItems Mage_Sales_Model_Resource_Order_Item_Collection */
        $orderItems = $order->getAllItems();

        /* @var $orderItem Mage_Sales_Model_Order_Item */
        foreach ($orderItems as $orderItem) {
            
            // Only add serialised items to this order e.g. INCIENABOM/INBTRESERVATION
            if ($this->isSerialisedItem($orderItem)) {

                /* @var $product Mage_Catalog_Model_Product */
                $product = Mage::getModel('catalog/product')->load($orderItem->getProductId());
                
                try {
                    /* @var $quoteItem Mage_Sales_Model_Quote_Item */
                    $productOptions = $orderItem->getProductOptions(); // copy settings of previous cart item to product
                    $quoteItem = $quote->addProduct($product, new Varien_Object($productOptions['info_buyRequest']));
                }
                catch (Exception $e) {
                   Mage::logException($e);
                }

            }
        }

        /*
        // Set Sales Order Billing Address
        $billingAddress = $quote->getBillingAddress()->addData(array(
            'customer_address_id' => $order->getBillingAddressId()
        ));
        */

        // http://stackoverflow.com/questions/33823683/magento-order-split-through-observer/33824070#33824070
        // Set Sales Order Billing Address
        $billingAddress = $quote->getBillingAddress()->addData(array(
            'customer_address_id' => '',
            'prefix' => $order->getBillingAddress()->getPrefix(),
            'firstname' => $order->getBillingAddress()->getFirstname(),
            'middlename' => $order->getBillingAddress()->getMiddlename(),
            'lastname' => $order->getBillingAddress()->getLastname(),
            'suffix' => $order->getBillingAddress()->getSuffix(),
            'company' => $order->getBillingAddress()->getCompany(),
            'street' => $order->getBillingAddress()->getStreetFull(),
            'city' => $order->getBillingAddress()->getCity(),
            'region' => $order->getBillingAddress()->getRegion(),
            'postcode' => $order->getBillingAddress()->getPostcode(),
            'country_id' => $order->getBillingAddress()->getCountryId(),
            'email' => $order->getBillingAddress()->getEmail(),
            'telephone' => $order->getBillingAddress()->getTelephone(),
            'fax' => $order->getBillingAddress()->getFax(),
            'save_in_address_book' => 0
        ));
        
        /*
        // Set Sales Order Shipping Address
        $shippingAddress = $quote->getShippingAddress()->addData(array(
            'customer_address_id' => $order->getShippingAddressId()
        ));
        */
        
        // http://stackoverflow.com/questions/33823683/magento-order-split-through-observer/33824070#33824070
        // Set Sales Order Shipping Address
        $shippingAddress = $quote->getShippingAddress()->addData(array(
            'customer_address_id' => '',
            'prefix' => $order->getShippingAddress()->getPrefix(),
            'firstname' => $order->getShippingAddress()->getFirstname(),
            'middlename' => $order->getShippingAddress()->getMiddlename(),
            'lastname' => $order->getShippingAddress()->getLastname(),
            'suffix' => $order->getShippingAddress()->getSuffix(),
            'company' => $order->getShippingAddress()->getCompany(),
            'street' => $order->getShippingAddress()->getStreetFull(),
            'city' => $order->getShippingAddress()->getCity(),
            'region' => $order->getShippingAddress()->getRegion(),
            'postcode' => $order->getShippingAddress()->getPostcode(),
            'country_id' => $order->getShippingAddress()->getCountryId(),
            'email' => $order->getShippingAddress()->getEmail(),
            'telephone' => $order->getShippingAddress()->getTelephone(),
            'fax' => $order->getShippingAddress()->getFax(),
            'save_in_address_book' => 0
        ));

        // Collect Rates and Set Shipping & Payment Method
        $shippingAddress->setCollectShippingRates(true)
                ->collectShippingRates()
                ->setShippingMethod($order->getShippingMethod())
                ->setPaymentMethod($order->getPayment()->getMethod());

        // Set Sales Order Payment
        $quote->getPayment()->importData(array(
            'method' => $order->getPayment()->getMethod()
        ));

        // This is the original source Indigo order, it's used to determine if an order has been split
        // DO NOT USE THIS FUNCTION, IT SEEMS TO SET A NOTE GLOBALLY THAT APPEARS ON ALL OF THIS CUSTOMER'S ORDERS
        // $quote->setCustomerNote($order->getIncrementId());

        // Collect Totals & Save Quote
        $quote->collectTotals()->save();

        // Create Order From Quote
        // http://magento.stackexchange.com/questions/41882/how-can-i-quick-update-of-qty-and-stock-and-all-inventory-fields-in-magento
        try {
            /* @var $service Mage_Sales_Model_Service_Quote */
            $service = Mage::getModel('sales/service_quote', $quote);

            /* @var $quoteItems Mage_Sales_Model_Quote_Item */
            $quoteItems = $service->getQuote()->getAllItems();
            foreach ($quoteItems as $quoteItem) {
                
                /* @var $stockItem Mage_CatalogInventory_Model_Stock_Item */
                /*
                $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($quoteItem->getProductId());          
                $stockItem->setUseConfigManageStock(0)
                        ->setManageStock(0)
                        ->setUseConfigNotifyStockQty(0);
                $stockItem->save();
                */
                
                $productObj = Mage::getModel('catalog/product')->load($quoteItem->getProductId());
                $productObj->setStockData(array( 
                            'use_config_manage_stock' => 0,
                            'manage_stock' => 0,
                            'use_config_notify_stock_qty' => 0
                        ));
                $productObj->save();     
            }
            
            $service->submitAll();

            foreach ($quoteItems as $quoteItem) {
                /*
                $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($quoteItem->getProductId());          
                $stockItem->setUseConfigManageStock(1)
                        ->setManageStock(1)
                        ->setUseConfigNotifyStockQty(1);
                $stockItem->save();
                */
                
                $productObj = Mage::getModel('catalog/product')->load($quoteItem->getProductId());
                $productObj->setStockData(array( 
                            'use_config_manage_stock' => 1,
                            'manage_stock' => 1,
                            'use_config_notify_stock_qty' => 1
                        ));
                $productObj->save();
            }

            // Setup order object and gather newly entered order
            /* @var $newOrder Mage_Sales_Model_Service_Order */
            $newOrder = $service->getOrder();

            // Now set newly entered order's status to same as source order
            // TODO: Check this status, not working
            // http://magento.stackexchange.com/questions/67919/change-order-status-programmatically-not-working
            // Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID); // do this to allow us to write
            // $newOrder->setStatus($order->getStatus());
            
            // This is the original source Indigo order, it's used to determine if an order has been split
            /* @var $comment Mage_Sales_Model_Order_Status_History */
            // $newOrder->addStatusHistoryComment($order->getIncrementId())
            //            ->setIsVisibleOnFront(false);
            
            // Finally we save our order after setting it's status
            $newOrder->save();  // <<< this triggers a new dispatch cycle

            // Email new order details
            if ($newOrder->getCanSendNewEmailFlag()) {
                try {
                    $newOrder->sendNewOrderEmail();
                } catch (Exception $e){
                    Mage::log($e->getMessage());
                }
            }

            // Done
            return $newOrder;

        } catch (Exception $ex) {
            Mage::log($ex->getMessage());
        } catch (Mage_Core_Exception $e) {
            Mage::log($e->getMessage());
        }
        
    }
    
    public function getBtIncrementIdBySchemeref($schemeref) {

        // Find last BT store order with matching Scheme Reference
        $read = Mage::getSingleton('core/resource')->getConnection('core_read');
        $sql = "    SELECT sales_flat_order.increment_id
                    FROM sales_flat_order
                    INNER JOIN amasty_amorderattr_order_attribute ON amasty_amorderattr_order_attribute.order_id = sales_flat_order.entity_id
                    WHERE amasty_amorderattr_order_attribute.schemeref = :schemeref
                    AND sales_flat_order.store_id = :store_id
                    ORDER BY sales_flat_order.entity_id DESC
                    LIMIT 1";

        $bindings = array(
                'schemeref' => $schemeref,
                'store_id' => 2 // BT store id
        );

        $result = $read->query($sql, $bindings);
        $increment_id = $result->fetchColumn();
        
        Mage::log('Got BT increment_id' . $increment_id);
        return $increment_id;

    } 

    /**
     * Determines if a product contains serial numbers without relying on hard-coded SKUs
     * 
     * @param Mage_Sales_Model_Order_Item $orderItem
     * @return boolean
     */
    public function isSerialisedItem($orderItem)
    {
        $is_serialised_product = false;

        $productOptions = $orderItem->getProductOptions();
        foreach ($productOptions as $productOption) {
            foreach ($productOption as $option) {
                if (isset($option['label'])) {
                    if ($option['label'] == 'Serial Code') {
                        $is_serialised_product = true;
                    }
                }
            }
        }
        
        return $is_serialised_product;
    }
    
    /*
    public function isSerialisedItem($product) {
        if (strtoupper($product->getSku()) == 'INCIENABOM' || strtoupper($product->getSku()) == 'INBTRESERVATION') {    // these are to be displayed in IndigoCienaSalesOrder
            return true;
        }
        else {
            return false;
        }
    }    
    */
}
