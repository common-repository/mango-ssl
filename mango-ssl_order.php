<?php
/*********************************************************************************************************
Description:

	- Request for a hook to receive orders events
	- Creation of the mapping between woocommerce order structure and mango order structure
	- Request to save the new invoice to Mango
	
*********************************************************************************************************/

// Add a hook to trigger the mango_ssl_update function every time an order is created or updated

	add_action('woocommerce_order_status_changed', 'mango_ssl_update', 10, 1);
	
	add_action( 'woocommerce_payment_complete', 'mango_ssl_payment_complete' );
	
	add_action( 'woocommerce_order_status_cancelled', 'mango_ssl_cancel', 10, 1 );
	
// mango_ssl_update creates a mango order class from a woocommerce order change and sends it to mango

	function mango_ssl_update($order_id) 
	{
		error_log("Order has been updated $order_id");
		
		global $woocommerce;
        $order = wc_get_order( $order_id );
		$obj = new mango_ssl_order($order,'update');
		
		$mango = new mango_ssl_call();
		$mango->send('save','COMMAND',$obj);
	}
	
	function mango_ssl_cancel( $order_id )
	{
		error_log("Order has been canceled $order_id");
		
		global $woocommerce;
        $order = wc_get_order( $order_id );
		$obj = new mango_ssl_order($order,'cancel');
		
		$mango = new mango_ssl_call();
		$mango->send('save','COMMAND',$obj);
	}
	
	function mango_ssl_payment_complete( $order_id )
	{
		error_log("Order has been payed $order_id");
		
		global $woocommerce;
        $order = wc_get_order( $order_id );
		$obj = new mango_ssl_order($order,'paid');
		
		$mango = new mango_ssl_call();
		$mango->send('save','COMMAND',$obj);
	}

// Address structure mapping

	class mango_ssl_address {
		
		var $address1;
		var $address2;
		var $city;
		var $state;
		var $postcode;
		var $country;
		
		public function __construct($woo_address)
		{
			$this->address1 = $woo_address['address_1'];
			$this->address2 = $woo_address['address_2'];
			$this->city = $woo_address['city'];
			$this->state = $woo_address['state'];
			$this->postcode = $woo_address['postcode'];
			$this->country = $woo_address['country'];
		}
	}

// Client structure mapping
	
	class mango_ssl_counterparty {
		
		var $id;
		var $name;
		var $surname;
		var $company;
		var $billing;
		var $shipping;
		
		public function __construct($woo_client)
		{
			$this->id = $woo_client['customer_id']; 
			$this->name = $woo_client['billing']['first_name'];
			$this->surname = $woo_client['billing']['last_name'];
			$this->company = $woo_client['billing']['company'];
			
			$this->billing = new mango_ssl_address($woo_client['billing']);
			$this->shipping = new mango_ssl_address($woo_client['shipping']);
		}
	}
	
// Chart of account setting

	class mango_ssl_chart {
		
		var $type;
		var $category;
		
		public function __construct()
		{
			$this->type = 'income';
		}
	}
	
// Product structure mapping
	
	class mango_ssl_product {
		
		var $id;
		var $name;
		
		public function __construct($woo_product)
		{
			$this->name = $woo_product['name'];
			$this->id = $woo_product['product_id'];
		}
	}

// Order item structure mapping
	
	class mango_ssl_order_detail {
		
		var $chart;
		var $product;
		var $quantity;
		var $tax;
		var $total;
		var $fx;
		var $unit_price;
		var $currency;
		
		public function __construct($woo_item)
		{
			$this->chart = new mango_ssl_chart();
			$this->product = new mango_ssl_product($woo_item);
			
			$this->fx = 1;
			$this->quantity = $woo_item['quantity'];
			$this->tax_id = $woo_item['tax_class'];
			$this->tax = $woo_item['total_tax'];
			$this->total = $woo_item['total'];
			$this->currency = $woo_item['currency'];
			$this->unit_price = $woo_item['subtotal']/$this->quantity;
		}
	}

// Order structure mapping
	
	class mango_ssl_order {
		
		var $id;
		var $po;
		var $date;
		var $type;
		var $status; 
		var $message;
		var $system_status;
		var $currency;
		var $payment_method;
		var $discount_total;
		var $discount_tax;
		var $total;
		var $tax_total;
		var $counterparty;
		var $detail = array();
		
		public function __construct($woo_order,$message)
		{
			$order_data = $woo_order->get_data();
			
			$this->type = 'INVOICE';
			$this->version = $order_data['version'];
			$this->id = 'WC-'.$order_data['id'];
			$this->po = $order_data['parent_id'];
			
			$this->message = $message;
			$this->system_status = $order_data['status'];
			
			if ($message=='update')
			{
				if ($order_data['status']=='on-hold')
					$this->status = 'HOLD';
				else if ($order_data['status']=='processing')
					$this->status = 'COMMIT';
				else if ($order_data['status']=='completed')
					$this->status = 'COMMIT';
				else if ($order_data['status']=='cancelled')
					$this->status = 'CANCELLED';
				else
					$this->status = 'HOLD';
			}
			else if ($message=='paid')
			{
				$this->status = 'PAID';
			}
			else if ($message=='cancel')
			{
				$this->status = 'CANCELLED';
			}

			$this->currency = $order_data['currency'];
			
			if ($order_data['payment_method']=='cod')
				$this->payment_method = 'Cash';
			else if ($order_data['payment_method']=='bacs')
				$this->payment_method = 'Bank Transfer';
			else if ($order_data['payment_method']=='cheque')
				$this->payment_method = 'Check';
			else
				$this->payment_method = 'Other';
				
			$this->date = $order_data['date_created']->date('Y-m-d');

			$this->discount = $order_data['discount_total'];
			$this->discount_tax = $order_data['discount_tax'];
			$this->tax_total = $order_data['total_tax'];
		
			$this->counterparty = new mango_ssl_counterparty($order_data);
			
			// Add expense items to the order

			foreach ($woo_order->get_items() as $item)
			{
				$item_data = $item->get_data();
				$item_data['currency'] = $order_data['currency'];
				$this->detail[] = new mango_ssl_order_detail($item_data);
			}
			
			// Add the shipping expense as any other expense
			
			$item_data = null;
			$item_data['currency'] = $order_data['currency'];
			$item_data['quantity'] = 1;
			$item_data['tax_class'] = 0;
			$item_data['product_id'] = 0;
			$item_data['total_tax'] = $order_data['shipping_tax'];
			$item_data['total'] = $order_data['shipping_total'];
			$item_data['subtotal'] = $order_data['shipping_total'];
			$item_data['name'] = 'Shipping';
			
			$this->detail[] = new mango_ssl_order_detail($item_data);
			
			// compute the total 
			
			$this->total = 0;
			
			foreach ($this->detail as $detail) 
				$this->total += $detail->total;
		}
	}

?>
