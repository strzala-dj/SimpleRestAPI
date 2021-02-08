# SimpleRestAPI

# Prerequisites:
You need:<br>
Apache2<br>
PHP 7.3<br>
PHP-cURL<br>
Composer >2.0<br>
Docker<br>

# Installation:
docker run -dp 9999:9999 --name PromoProductAPI peuek/promo:latest<br>
docker run -dp 27017:27017 --name SimpleRestAPI mongo:latest<br>
php composer.phar install<br>

# Input data
Body of the test input for Rest client:<br>
[<br>
  { <br>
    "product_id" : "zzz", <br>
    "name" : "Product zzz", <br>
    "price" : [{ "currency" : "usd", "value" : 10 }], <br>
    "description" : "Full description of the product zzz" <br>
  },<br>
  { <br>
    "product_id" : "xxx", <br>
    "name" : "Product xxx", <br>
    "price" : [{ "currency" : "usd", "value" : 20 }], <br>
    "description" : "Full description of the product Inputxxx" <br>
  }<br>
]<br>
<br>
Or Single shot works as well:<br>
{ <br>
    "product_id" : "xxx", <br>
    "name" : "Product xxx", <br>
    "price" : [{ "currency" : "usd", "value" : 20 }], <br>
    "description" : "Full description of the product Inputxxx" <br>
}<br>

