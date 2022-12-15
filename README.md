# OpenCart 3 README

```

```

User and Site configuration

```
APACHE_HTTP_PORT_NUMBER: Port to bind by Apache for HTTP. Default: 8080
APACHE_HTTPS_PORT_NUMBER: Port to bind by Apache for HTTPS. Default: 8443
OPENCART_USERNAME: OpenCart application username. Default: user
OPENCART_PASSWORD: OpenCart application password. Default: bitnami
OPENCART_EMAIL: OpenCart application email. Default: user@example.com
OPENCART_HOST: OpenCart server hostname/address.
OPENCART_ENABLE_HTTPS: Whether to use HTTPS by default. Default: no.
OPENCART_EXTERNAL_HTTP_PORT_NUMBER: Port to used by OpenCart to generate URLs and links when accessing using HTTP. Default 80.
OPENCART_EXTERNAL_HTTPS_PORT_NUMBER: Port to used by OpenCart to generate URLs and links when accessing using HTTPS. Default 443.
OPENCART_SKIP_BOOTSTRAP: Whether to skip performing the initial bootstrapping for the application. Default: no
```

## OpenCart 3 支付组件

[document](https://code.tutsplus.com/tutorials/create-a-custom-payment-method-in-opencart-part-3--cms-22464)

### Create Payment Controller

新建文件 `/opencart/admin/controller/extension/payment/payermax.php`

这个文件负责显示后台配置表单，保存表单数据。

```
root@81413de15f58:/opencart/admin/controller/extension/payment$ ls -la
-rw-rw-r-- 1 daemon root  5859 Oct  8  2020 alipay.php
-rw-rw-r-- 1 daemon root  6563 Oct  8  2020 alipay_cross.php
-rw-rw-r-- 1 daemon root 29916 Oct  8  2020 amazon_login_pay.php
-rw-rw-r-- 1 daemon root  6362 Oct  8  2020 authorizenet_aim.php
...
-rw-rw-r-- 1 daemon root  6362 Oct  8  2020 payermax.php
...
```

Sample Code

```php
<?php
class ControllerPaymentPayerMax extends Controller {
  private $error = [];

  public function index() {
	// load the language file
	$this->language->load('payment/payermax');

	// set page title
	$this->document->setTitle('PayerMax Payment Method Configuration');

	// load the model file "setting.php" which will provide us the methods to save `post` values to the database. We also check if `post` values are available we'll save it to the database.
	$this->load->model('setting/setting');
	if (($this->request->server['REQUEST_METHOD'] == 'POST')) {
		// save configuration
	}

	// next couple of lines of code is just used to set up the static labels which will be used in the template file.
	$this->data['text_enabled'] = $this->language->get('text_enabled');
	$this->data['text_disabled'] = $this->language->get('text_disabled');

	// set up the "action" variable to make sure form is submitted to our "index" method when submitted. And in the same way, user is taken back to list of payment methods if she clicks on "Cancel" button.
    	$this->data['action'] = $this->url->link('payment/payermax', 'token=' . $this->session->data['token'], 'SSL');
    	$this->data['cancel'] = $this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL');


	// code to populate the default values of the configuration form fields either in add or edit mode.
	if (isset($this->request->post['text_config_one'])) {
      		$this->data['text_config_one'] = $this->request->post['text_config_one'];
    	} else {
      		$this->data['text_config_one'] = $this->config->get('text_config_one');
    	}

	// load the different order status values that area available, which will be used for the drop-down in the configuration form for the Order Status field.
	$this->load->model('localisation/order_status');
	$this->data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

	// Finally, we assign our template file and render the view.
	$this->template = 'payment/payermax.twig';

	$this->children = [
		'common/header',
		'common/footer'
	]

    	$this->response->setOutput($this->render());
  }

	public function validate() {}

	// Now if you clicked a green button it will install module and also call install method of our controller class. we can initializing some primary data here.
    	public function install() {}

	// Before leaving we’ll cleanup with uninstall. It's also called automatically by system.
    	public function uninstall() {}

}
```

That's the set up for the controller file.

### Language and Template Files

create language file `/opencart/admin/language/en-gb/extension/payment/payermax.php`

for example:

```php
<?php
$_['heading_title'] = 'PayerMax Payment Method';

$_['text_enabled'] = 'Enabled';
$_['text_disabled'] = 'Disabled';

```

create the view file `/opencart/admin/view/template/extension/payment/payermax.twig`

for example:

```php
<?php echo $header; ?>
<div id="content">
	<div class="box">
		<div class="heading"> ... </div>
		<div class="content">
			  <form></form>
		</div>
	</div>
</div>
<?php echo $footer; ?>
```

### Payment Controller

create payment method controller `/opencart/catalog/controller/extension/payment/payermax.php` and template file
`/opencart/catalog/view/theme/default/template/extension/payment/payermax.twig`

```php
<?php
class ControllerPaymentPayerMax extends Controller {

	// The `index` method will be responsible for setting up the data when the form is submitted to payermax payment gateway
	protected function index() {

		// First, loaded the language file and set the value of the `Confirm` button.
		// set up the `action` attribute which will be used by the payment submission form.
		// You should change this as per your payment gateway.
		$this->language->load('payment/custom');
		$this->data['button_confirm'] = $this->language->get('button_confirm');
		$this->data['action'] = 'https://your.payment-gateway.url';

		// Next, loaded the order information from the user's active session.
		$this->load->model('checkout/order');
		$order = $this->model_checkout_order->getOrder($this->session->data['order_id']);

		// If order information is available,
		// go ahead and set up the data for the hidden variables which will be used to submit the form to the payment gateway URL.
		$this->data['text_config_one'] = trim($this->config->get('text_config_one'));
		$this->data['text_config_two'] = trim($this->config->get('text_config_two'));
		$this->data['callbackurl'] = $this->url->link('payment/custom/callback');


		// Finally, we assign our custom template file and render the view.
		$this->template = 'default/template/extension/payment/payermax.twig';

		$this->render();
	}

	// the `callback` method is used to handle the response data from the payment gateway.
	public function callback() {

		// First, we check if the orderid variable is available or not before proceeding further.
		// If it's not available, we simply stop further processing.
		if (isset($this->request->post['orderid'])) {
      			$order_id = trim(substr(($this->request->post['orderid']), 6));
    		} else {
      			die('Illegal Access');
   		}

		// Next, we load the order information from the database.
		// And finally, we'll check if we've got the success indicator from the payment gateway response.
		// If it's so, we'll go ahead and update the order status information accordingly.
		$this->load->model('checkout/order');
		$order_info = $this->model_checkout_order->getOrder($order_id);

		if ($order_info) {
			$data = array_merge($this->request->post,$this->request->get);

			//payment was made successfully
			if ($data['status'] == 'Y' || $data['status'] == 'y') {
				// update the order status accordingly
			}
		}

	}
}
```

### 模型

OpenCart 有自己的一套 约定 和 标准 来处理商店的内部工作。支付方法 检测的模型设置就是这种情况。
只需按照约定设置它，它就会自动被拾取。

创建支付数据模型在 `/opencart/catalog/model/extension/payment/payermax.php`

如：

```php
<?php
class ModelPaymentPayerMax extends Model {
  public function getMethod($address, $total) {
    $this->load->language('payment/payermax');

    $method_data = array(
      'code' => 'payermax',
      'title' => $this->language->get('text_title'),
      'sort_order' => $this->config->get('custom_sort_order')
    );

    return $method_data;
  }
}
```

在 OpenCart 结算流程里列出支付方式会使用该类。
在支付过程中，OpenCart 从后端获取激活的支付方式。
然后依次检查模型是否可用。

这个步骤的 `code` 变量很重要。当用户选择支付方式并按下 `Continue`，会调用 `payment/payermax`，最终为支付网关设置了表单。

简而言之，它是前端支付方式检测和正常工作的一个必须文件。

### Language and Template Files

创建前段语言文件：

`/opencart/catalog/language/en-gb/extension/payment/payermax.php`

example:

```php
<?php
$_['text_title'] = 'PayerMax Payment Method';
$_['button_confirm'] = 'Confirm Order';
```

前端模版文件：

`/opencart/catalog/view/theme/default/template/extension/payment/payermax.twig`

如：

```php
<form action="<?php echo $action; ?>" method="post">
  <input type="hidden" name="text_config_one" value="<?php echo $text_config_one; ?>" />
  <input type="hidden" name="text_config_two" value="<?php echo $text_config_two; ?>" />
  <input type="hidden" name="orderid" value="<?php echo $orderid; ?>" />
  <input type="hidden" name="callbackurl" value="<?php echo $callbackurl; ?>" />

  <div class="buttons">
    <div class="right">
      <input type="submit" value="<?php echo $button_confirm; ?>" class="button" />
    </div>
  </div>
</form>
```

这是当用户点击 “Confirm Order” 按钮时将提交的表单。
我们刚刚建立了隐藏变量和它们的值，这些值之前在控制器的索引方法中定义过。

![create-custom-payment-method-opencart-part-three-front-end-checkout-view](https://cms-assets.tutsplus.com/cdn-cgi/image/width=1200/uploads/users/413/posts/22464/image/create-custom-payment-method-opencart-part-three-front-end-checkout-view.png)

## 总结

1、必须为支付方法设置模型文件，以便它可以列在 `支付方法` 选项卡中。
2、当用户在 `支付方法` 选项卡中选择 `PayerMax Payment Method` 并单击 `Continue` 按钮时，`OpenCart` 在内部调用 `payment/payermax` ，后者最终调用 `index` 方法并 渲染前端模版。
3、最后，当用户单击 `Confirm Order` 按钮时，表单将被提交，用户将被带到支付网关站点，在那里开始支付过程。一旦支付过程完成，用户将被重定向到我们的网站，通过 `callbackurl` 中的变量，找到并更新订单状态。

## 模块结构

```php
├── opencart
│   ├── admin
│   │   ├── controller
│   │   │   └── extension
│   │   │       └── payment
│   │   │           └── payermax.php
│   │   ├── language
│   │   │   └── en-gb
│   │   │       └── extension
│   │   │           └── payment
│   │   │               └── payermax.php
│   │   └── view
│   │       └── template
│   │           └── extension
│   │               └── payment
│   │                   └── payermax.twig
│   └── catalog
│       ├── controller
│       │   └── extension
│       │       └── payment
│       │           └── payermax.php
│       ├── language
│       │   └── en-gb
│       │       └── extension
│       │           └── payment
│       │               └── payermax.php
│       ├── model
│       │   └── extension
│       │       └── payment
│       │           └── payermax.php
│       └── view
│           └── theme
│               └── default
│                   └── template
│                       └── extension
│                           └── payment
│                               └── payermax.twig
└── transfer
```
