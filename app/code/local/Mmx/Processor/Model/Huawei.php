<?php

class Mmx_Processor_Model_Huawei {

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

        if ($this->order->getStoreId() == 5) {

            $exporter = new Mmx_Fsascii_Model_Exporter();
            $exporter->setOrder($this->order)
                ->addWriter($this->getWriter())
                ->addOutput(new Mmx_Fsascii_Model_File_HuaweiSalesOrder())
                ->export();
        }

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

}
