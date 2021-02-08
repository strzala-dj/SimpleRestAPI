<?php


use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

$access_data = include('params.php'); // in root folder for this project only

/** @ODM\Document */
class Product {

    /** @ODM\Id */
    private $id;

    /** @ODM\Field(type="string") */
    private $product_id = '';

    /** @ODM\Field(type="string") */
    private $name = '';

    /** @ODM\Field(type="collection") */
    private $price = [];

    /** @ODM\Field(type="string") */
    private $description = '';

    /** @ODM\Field(type="collection") */
    private $colors = [];

    /** @ODM\Field(type="collection") */
    private $sizes = [];

    //public function __construct() {}



    public function getProductID() {
        return $this->product_id;
    }

    public function getArray() {
        $array = array();
        foreach ($this as $key => $value) {
            $array[$key] = $value;
        }

        return $array;
    }


    public function getRequiredArray() {
        return [ 'product_id', 'name', 'price' ];
    }






    public function setDataArray(array $to_parse) {
        foreach ($to_parse as $keyP => $valueP) {
            if (isset($this->$keyP)) {
                $this->$keyP = $valueP;
            } else {
                return false;
            }
        }
        return true;
    }




    public function updateFromAPI(array $access_data) {
        //echo "updating\n";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://localhost:9999/product/' . $this->product_id);
        curl_setopt($ch, CURLOPT_HEADER, TRUE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('X-token: ' . $access_data['X-token']));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);

        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $body = substr( $result, $header_size );

        if(!curl_errno($ch)){
            $json_body = json_decode($body, true);
            foreach ($json_body as $key => $value) {
                //echo "updating " . $key . "\n";
                $this->$key = $value;
                //var_dump($this->$key);
            }
            curl_close($ch); 
            return true;
        } else { 
            echo 'error ' . curl_error($result);
            return false;
        }

    }


    public function getJsonString() {
        return json_encode($this);
    }


    
}