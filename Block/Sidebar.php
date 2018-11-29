<?php
namespace Godogi\LoginSidebar\Block;

class Sidebar extends \Magento\Framework\View\Element\Template
{
	protected $_customerSession;

	public function __construct(
			\Magento\Framework\View\Element\Template\Context $context,
			\Magento\Customer\Model\Session $customerSession)
	{
		$this->_customerSession = $customerSession;
		parent::__construct($context);
	}
	public function isLoggedIn()
	{
	    return $this->_customerSession->isLoggedIn();
	}
}