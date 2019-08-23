<?php

namespace Cecabank\TPV\Model\Config\Source;

class Currencyoptions implements \Magento\Framework\Option\ArrayInterface
{
   
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
    	$arr = $this->toArray();
    	$ret = [];
    	foreach ($arr as $key => $value) {
    		$ret[] = [
    				'value' => $key,
    				'label' => $value
    		];
    	}
    	return $ret;
    }
    
    public function toArray()
    {
    	$array = [
			978 => __('EUR'),
			840 => __('USD'),
			826 => __('GBP'),
			392 => __('JPY'),
			32 => __('ARS'),
			124 => __('CAD'),
			152 => __('CLP'),
			170 => __('COP'),
			356 => __('INR'),
			484 => __('MXN'),
			604 => __('PEN'),
			756 => __('CHF'),
			986 => __('BRL'),
			937 => __('VEF'),
			949 => __('TRY')
    	];
    	return $array;
    }

}
?>