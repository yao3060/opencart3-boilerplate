<?php
namespace Opencart\Admin\Model\Extension\PayPal\Module;
class PayPalSmartButton extends \Opencart\System\Engine\Model {
		
	public function install(): void {
		$query = $this->db->query("SELECT DISTINCT layout_id FROM " . DB_PREFIX . "layout_route WHERE route = 'product/product' OR route LIKE 'checkout/%'");
		
		$layouts = $query->rows;
		
		foreach ($layouts as $layout) {
			$this->db->query("INSERT INTO " . DB_PREFIX . "layout_module SET layout_id = '" . (int)$layout['layout_id'] . "', code = 'paypal.paypal_smart_button', position = 'content_top', sort_order = '0'");
		}
	}
}