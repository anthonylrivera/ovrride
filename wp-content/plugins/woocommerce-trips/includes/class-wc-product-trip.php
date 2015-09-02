<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Product_Trip extends WC_Product {
    
    public function __construct( $product ) {
        $this->product_type = 'trip';
        $this->manage_stock = 'yes';
        parent::__construct( $product );
    }
    /*
    public function check_package_stock( $type, $description ) {
        $packages = $this->{"wc_trip_" . $type . "_package"};
        if ( "" == $this->{"wc_trip_" . $type . "_stock"} ) {
            return true;
        } else {
            $index = array_search($description, $packages);
            if ( $index ) {
                error_log(print_r($packages, true));
            }
        }
        return $this->wc_trip_tertiary_package_label;
    }*/
    public function is_purchasable() {
        return true;
    }
    
    public function is_sold_individually() {
        return true;
    }
    
    public function output_packages( $type ) {
        $packages = get_post_meta( $this->id, "_wc_trip_" . $type . "_packages", true );
        $stock = get_post_meta( $this->id, "_wc_trip_" . $type . "_package_stock", true );
        $stock = ( $stock == "yes" ? true : false);
        $label = get_post_meta( $this->id, "_wc_trip_" . $type . "_package_label", true );
        if ( "" === strval($label) ) {
            $label = $type;
        }
        $htmlOutput = "";
        foreach( $packages as $key => $values ) {
            $disabled = $outOfStock = $costLabel = $dataCost = "";
            
            if ( $stock && "" !== strval($values['stock']) && 0 === intval($values['stock'])) {
                $disabled = "disabled";
                $outOfStock = "(Out of Stock) ";
            }
            
            if ( "" !== $values['cost'] && $values['cost'] != "0") {
                $dataCost = "data-cost='" . $values['cost'] . "'" ;
                $costLabel = " +" . $values['cost'];
            }
            
            $htmlOutput .= '<option value="' . $values['description'] . '" ' . $dataCost . ' ' . $disabled . '>' .$outOfStock . $values['description'] . $costLabel . '</option>';

            
        }
        if ( count($packages) > 0 ) {
            return array("label" => $label, "html" => $htmlOutput);
        } else {
            return false;
        }
    }

}