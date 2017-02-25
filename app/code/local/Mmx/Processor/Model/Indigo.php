<?php

class Mmx_Processor_Model_Indigo {

    /**
     *
     * @var Mage_Sales_Model_Order
     */
    protected $order;

    public function getOrder() {
        return $this->order;
    }

    public function setOrder($order) {
        $this->order = $order;
        return $this;
    }
    
    public function processOrder() {
        
        if ($this->order->getStoreId() == 3) {
            
            Mage::log('Indigo order passing through, order_id:' . $this->order->getId());
            
            Mage::log('orderContainsIndigoProductsOnly=' . $this->orderContainsIndigoProductsOnly());
            Mage::log('orderContainsIndigoAndSerialisedProducts=' . $this->orderContainsIndigoAndSerialisedProducts());
            Mage::log('orderContainsSerialisedProductsOnly=' . $this->orderContainsSerialisedProductsOnly());
            Mage::log('orderIsCiena=' . $this->orderIsCiena());
            
            // 1. If contains Indigo products only, write ascii
            if ($this->orderContainsIndigoProductsOnly()) {

                Mage::log('orderContainsIndigoProductsOnly is true ' . $this->order->getId());
                
                // Tag with BT increment id
                $amorderattr = Mage::getModel('amorderattr/attribute')->load($this->order->getId(), 'order_id');
                $helper = new Mmx_Processor_Helper_Data();
                $bt_increment_id = $helper->getBtIncrementIdBySchemeref($amorderattr->getSchemeref());
                $amorderattr->setBtIncrementId($bt_increment_id);
                $amorderattr->save();

                $exporter = new Mmx_Fsascii_Model_Exporter();
                $exporter->setOrder($this->order)
                        ->addWriter($this->getWriter())
                        ->addOutput(new Mmx_Fsascii_Model_File_IndigoSalesOrder())
                        ->export();

            }

            // 2. If contains Indigo AND serialised, split to new Ciena order, then write ascii
            // Note: splitOrder() will create new dispatch cycle containing only serialised products which
            // will run through this observer code again and have to be ignored
            if ($this->orderContainsIndigoAndSerialisedProducts()) {

                Mage::log('orderContainsIndigoAndSerialisedProducts is true ' . $this->order->getId());
                
                // Tag with BT increment id
                $amorderattr = Mage::getModel('amorderattr/attribute')->load($this->order->getId(), 'order_id');
                $helper = new Mmx_Processor_Helper_Data();
                $bt_increment_id = $helper->getBtIncrementIdBySchemeref($amorderattr->getSchemeref());
                $amorderattr->setBtIncrementId($bt_increment_id);
                $amorderattr->save();

                // Copy serialised products to new into a seperate Ciena order
                $helper = new Mmx_Processor_Helper_Data();
                $newOrder = $helper->splitOrder($this->order);

                // INDIGO SALES ORDER
                // Manually update custom order attributes of the source order to show new Ciena order containing INCIENABOM/INBTRESERVATION items
                $amorderattr = Mage::getModel('amorderattr/attribute')->load($this->order->getId(), 'order_id');
                $amorderattr->setCienaIncrementId($newOrder->getIncrementId());
                $amorderattr->save();

                $exporter = new Mmx_Fsascii_Model_Exporter();
                $exporter->setOrder($this->order)
                        ->addWriter($this->getWriter())
                        ->addOutput(new Mmx_Fsascii_Model_File_IndigoSalesOrder())
                        ->export();


                // CIENA SALES ORDER
                // Manually copy original order attributes from source Indigo order to new Ciena order
                $newAmorderattr = Mage::getModel('amorderattr/attribute')->load($newOrder->getId(), 'order_id');
                $newAmorderattr->setSchemeref($amorderattr->getSchemeref());
                $newAmorderattr->setSchemesite($amorderattr->getSchemesite());
                $newAmorderattr->setRouteid($amorderattr->getRouteid());
                $newAmorderattr->setSchemedriver($amorderattr->getSchemedriver());
                $newAmorderattr->setOrderstaging(sprintf("%s", $amorderattr->getOrderstaging())); 
                $newAmorderattr->setIndigoship(sprintf("%s", $amorderattr->getIndigoship())); // 0 needs to be cast as string to be saved

                $newAmorderattr->setIndigoIncrementId($this->order->getIncrementId()); // the original order this one was split from
                $newAmorderattr->save();

                $exporter = new Mmx_Fsascii_Model_Exporter();
                $exporter->setOrder($newOrder)
                        ->addWriter($this->getWriter())
                        ->addOutput(new Mmx_Fsascii_Model_File_IndigoCienaSalesOrder())
                        ->export();
            }


            // 3. If Indigo order just contains serialised products and is not the split Ciena order 
            // created programatically by splitOrder() dispatch cycle above in 2., write ascii
            if ($this->orderContainsSerialisedProductsOnly() && !$this->orderIsCiena()) {

                Mage::log('orderContainsSerialisedProductsOnly is true ' . $this->order->getId());
                
                // Tag with BT increment id
                $amorderattr = Mage::getModel('amorderattr/attribute')->load($this->order->getId(), 'order_id');
                $helper = new Mmx_Processor_Helper_Data();
                $bt_increment_id = $helper->getBtIncrementIdBySchemeref($amorderattr->getSchemeref());
                $amorderattr->setBtIncrementId($bt_increment_id);
                $amorderattr->save();

                $exporter = new Mmx_Fsascii_Model_Exporter();
                $exporter->setOrder($this->order)
                        ->addWriter($this->getWriter())
                        ->addOutput(new Mmx_Fsascii_Model_File_IndigoCienaSalesOrder())
                        ->export();
            }
            
            Mage::log('Indigo order finished passing through:' . $this->order->getId());
            
        } // store
        
    }
    

    /**
     * 
     * @return \Mmx_Fsascii_Helper_FileWriter
     */
    public function getWriter() {

        $writer = new Mmx_Fsascii_Helper_FileWriter();
        $writer->setOutputDir(Mage::getStoreConfig('mmx_fsascii/general/output_dir', Mage::app()->getStore()));

        return $writer;
    }

    /**
     * 
     * @return boolean
     */
    public function orderContainsIndigoProductsOnly() {

        $helper = new Mmx_Processor_Helper_Data();
        
        $contains_indigo_products_only = true;

        /* @var $orderItems Mage_Sales_Model_Resource_Order_Item_Collection */
        $orderItems = $this->order->getAllItems();

        /* @var $orderItem Mage_Sales_Model_Order_Item */
        foreach ($orderItems as $orderItem) {
            if ($helper->isSerialisedItem($orderItem)) {
                $contains_indigo_products_only = false;
                break;
            }
        }

        return $contains_indigo_products_only;
    }

    
    /**
     * 
     * @return boolean
     */
    public function orderContainsSerialisedProductsOnly() {
        
        $helper = new Mmx_Processor_Helper_Data();

        $contains_serialised_only = true;

        /* @var $orderItems Mage_Sales_Model_Resource_Order_Item_Collection */
        $orderItems = $this->order->getAllItems();

        /* @var $orderItem Mage_Sales_Model_Order_Item */
        foreach ($orderItems as $orderItem) {
            if (!$helper->isSerialisedItem($orderItem)) {
                $contains_serialised_only = false;
            }
        }
        
        return $contains_serialised_only;
    }
    
    
    /**
     * 
     * @return boolean
     */
    public function orderContainsIndigoAndSerialisedProducts() {
        
        $helper = new Mmx_Processor_Helper_Data();

        $contains_indigo_products = false;
        $contains_serialised_products = false;

        /* @var $orderItems Mage_Sales_Model_Resource_Order_Item_Collection */
        $orderItems = $this->order->getAllItems();

        /* @var $orderItem Mage_Sales_Model_Order_Item */
        foreach ($orderItems as $orderItem) {
            if (!$helper->isSerialisedItem($orderItem)) {
                $contains_indigo_products = true;
            }
            if ($helper->isSerialisedItem($orderItem)) {
                $contains_serialised_products = true;
            }
        }

        if (($contains_indigo_products && $contains_serialised_products)) {
            return true;
        }
        else {
            return false;
        }
        
    }
    
    /**
     * Ciena orders created programatically have NULL remote_ip field
     * http://stackoverflow.com/questions/33129397/magento-differentiate-normal-orders-and-orders-created-programatically
     * 
     * @return boolean
     */
    public function orderIsCiena() {

        if (empty($this->order->getRemoteIp())) {
            return true;
        }
        else {
            return false;
        }
    }
    
}
